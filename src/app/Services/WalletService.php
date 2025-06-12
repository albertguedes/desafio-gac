<?php

namespace App\Services;

use DB;
use Exception;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;

use App\Models\Account;
use App\Models\Transaction;

class WalletService
{
    /**
     * Deposits money into the given account.
     *
     * This method will create a transaction of type "deposit" and update the balance of the given account.
     *
     * @param Account $account the account that receives the money
     * @param int $amount the amount of money to be deposited
     *
     * @throws Exception if the given amount is negative
     */
    public function deposit(Account $account, int $amount): void
    {
        $this->assertPositiveAmount($amount);

        DB::transaction(function () use ($account, $amount)
        {
            $account->lockForUpdate();

            $transaction = $this->createReceiveTransaction(null, $account, $amount);
            $transaction->type = TransactionType::Deposit->value;
            $transaction->save();

            $account->balance += $amount;
            $account->save();
        });
    }

    /**
     * Transfers money from one account to another.
     *
     * This method will create two transactions: one for the sender, and one for the receiver.
     * The sender's transaction will have the type "transfer_sent", and the receiver's transaction
     * will have the type "transfer_received".
     *
     * The method will also update the balance of both accounts.
     *
     * @param Account $sender_account the account that sends the money
     * @param Account $receiver_account the account that receives the money
     * @param int $amount the amount of money to be transferred
     */
    public function transfer(Account $sender_account, Account $receiver_account, int $amount): void
    {
        $this->assertPositiveAmount($amount);

        $this->checkBalance($sender_account, $amount);

        DB::transaction(function () use ($sender_account, $receiver_account, $amount)
        {
            $sender_account->lockForUpdate();
            $receiver_account->lockForUpdate();

            $sender_transaction = $this->createSendTransaction($sender_account, $receiver_account, $amount);
            $receiver_transaction = $this->createReceiveTransaction($sender_account, $receiver_account, $amount);

            $sender_transaction->relatedTransaction()->associate($receiver_transaction);
            $receiver_transaction->relatedTransaction()->associate($sender_transaction);
            $receiver_transaction->save();
            $sender_transaction->save();

            $sender_account->balance -= $amount;
            $sender_account->save();

            $receiver_account->balance += $amount;
            $receiver_account->save();
        });
    }

    /**
     * Reverses a transaction.
     *
     * This method is used to reverse a transaction that has been completed.
     * It will create a new transaction with the same amount but opposite sign,
     * and update the balance of the accounts involved.
     *
     * @param Transaction $transaction the transaction to be reversed
     *
     * @throws Exception if the transaction is not completed
     */
    public function reversal(Transaction $transaction): void
    {
        $this->assertReversalTransaction($transaction);

        DB::transaction(function () use ($transaction)
        {
            $type = $transaction->type;

            if($type == TransactionType::Deposit->value){
                $this->reverseDeposit($transaction);
            }

            if($type == TransactionType::TransferSent->value)
            {
                $this->reverseTransferSent($transaction);
            }

            if($type == TransactionType::TransferReceived->value){
                $this->reverseTransferReceived($transaction);
            }
        });
    }

    private function reverseDeposit(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction)
        {
            $amount = abs($transaction->amount);
            $transaction->account->lockForUpdate();
            $transaction->relatedAccount?->lockForUpdate();

            $reverse_transaction = $this->createSendTransaction($transaction->account, null, $amount);
            $reverse_transaction->type = TransactionType::Reversal->value;
            $reverse_transaction->save();

            $transaction->account->balance -= $amount;
            $transaction->account->save();

            $transaction->status = TransactionStatus::Reversed->value;
            $transaction->save();
        });
    }

    private function reverseTransferSent(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction)
        {
            $transaction->account->lockForUpdate();
            $transaction->relatedAccount?->lockForUpdate();

            $reverse_transaction = $this->createReceiveTransaction($transaction->relatedAccount, $transaction->account, -$transaction->amount);
            $reverse_transaction->type = TransactionType::Reversal->value;
            $reverse_transaction->save();

            $transaction->account->balance += -$transaction->amount;
            $transaction->account->save();
            $transaction->status = TransactionStatus::Reversed->value;
            $transaction->save();

            $reverse_related_transaction = $this->createSendTransaction($transaction->relatedAccount, $transaction->account, -$transaction->amount);
            $reverse_related_transaction->type = TransactionType::Reversal->value;
            $reverse_related_transaction->save();

            $transaction->relatedTransaction->account->balance -= -$transaction->amount;
            $transaction->relatedTransaction->account->save();

            $transaction->relatedTransaction->status = TransactionStatus::Reversed->value;
            $transaction->relatedTransaction->save();
        });
    }

    private function reverseTransferReceived(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction)
        {
            $transaction->account->lockForUpdate();
            $transaction->relatedAccount?->lockForUpdate();

            $reverse_transaction = $this->createSendTransaction($transaction->account, $transaction->relatedAccount, $transaction->amount);
            $reverse_transaction->type = TransactionType::Reversal->value;
            $reverse_transaction->save();

            $transaction->account->balance -= $transaction->amount;
            $transaction->account->save();

            $transaction->status = TransactionStatus::Reversed->value;
            $transaction->save();

            $reverse_related_transaction = $this->createReceiveTransaction($transaction->account, $transaction->relatedAccount, $transaction->amount);
            $reverse_related_transaction->type = TransactionType::Reversal->value;
            $reverse_related_transaction->save();

            $transaction->relatedTransaction->account->balance += $transaction->amount;
            $transaction->relatedTransaction->account->save();

            $transaction->relatedTransaction->status = TransactionStatus::Reversed->value;
            $transaction->relatedTransaction->save();
        });
    }

    /**
     * Creates a new transaction that records a money send operation.
     *
     * @param ?Account $sender the account that sends the money
     * @param ?Account $receiver the account that receives the money
     * @param int $amount the amount of money to be sent, in cents
     *
     * @return Transaction the newly created transaction
     */
    protected function createSendTransaction(?Account $sender, ?Account $receiver, int $amount): Transaction
    {
        $this->assertPositiveAmount($amount);
        $this->checkBalance($sender, $amount);

        return Transaction::create([
            'account_id' => $sender->id,
            'related_account_id' => $receiver->id ?? null,
            'amount' => -$amount,
            'type' => TransactionType::TransferSent->value,
            'status' => TransactionStatus::Completed->value
        ]);
    }

    /**
     * Creates a new transaction that records a money receive operation.
     *
     * @param ?Account $sender the account that sent the money, or null if it's a deposit
     * @param ?Account $receiver the account that received the money
     * @param int $amount the amount of money transferred, in cents
     *
     * @return Transaction the newly created transaction
     */
    protected function createReceiveTransaction(?Account $sender, ?Account $receiver, int $amount): Transaction
    {
        $this->assertPositiveAmount($amount);

        return Transaction::create([
            'account_id' => $receiver->id,
            'related_account_id' => $sender->id ?? null,
            'amount' => $amount,
            'type' => TransactionType::TransferReceived->value,
            'status' => TransactionStatus::Completed->value
        ]);
    }

    /**
     * Checks if the given account has a balance sufficient to cover the given amount.
     *
     * @param Account $account
     * @param int $amount
     * @throws Exception if the account's balance is insufficient
     */
    private function checkBalance(Account $account, int $amount): void
    {
        if ($account->balance < $amount) {
            throw new \RuntimeException('Insufficient balance');
        }
    }

    /**
     * Asserts that the given amount is positive.
     *
     * @param int $amount The amount to check.
     * @throws Exception if the amount is negative.
     */
    private function assertPositiveAmount(int $amount): void
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
    }

    /**
     * Asserts that the given transaction has not already been reversed.
     *
     * @param Transaction $transaction The transaction to check.
     * @throws Exception if the transaction is already reversed.
     */
    private function assertReversalTransaction(Transaction $transaction): void
    {
        if ($transaction->status === TransactionStatus::Reversed->value) {
            throw new Exception('Transaction is already reversed');
        }
    }
}