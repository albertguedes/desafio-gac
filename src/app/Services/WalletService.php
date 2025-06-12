<?php

namespace App\Services;

use DB;
use Exception;

use App\Models\Account;
use App\Models\Transaction;

class WalletService
{
    /**
     * Deposit money into the account.
     *
     * @param Account $account The account to deposit money into.
     * @param int $depositAmount The amount of money to deposit.
     *
     * @return Transaction The created deposit transaction.
     *
     * @throws \Exception If the deposit amount is not greater than 0.
     */
    public function deposit(Account $account, int $depositAmount): Transaction
    {
        if ($depositAmount <= 0) {
            throw new \Exception('Deposit amount must be greater than 0');
        }

        return $this->createDepositTransaction($account, $depositAmount);
    }

    /**
     * Transfer money from the sender's account to the receiver's account.
     *
     * @param Account $senderAccount The account from which the money is to be deducted.
     * @param Account $receiverAccount The account to which the money is to be credited.
     * @param int $amount The amount of money to transfer.
     *
     * @return array An array containing the sender and receiver transactions.
     *
     * @throws Exception If the amount is less than or equal to zero or if the sender has insufficient balance.
     */
    public function transfer(Account $sender, Account $receiver, int $amount): array
    {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than 0');
        }

        if ($sender->balance < $amount) {
            throw new Exception('Insufficient balance');
        }

        return DB::transaction(function () use ($sender, $receiver, $amount) {
            $senderTransaction = $this->createSenderTransaction($sender, $receiver, $amount);
            $receiverTransaction = $this->createReceiverTransaction($receiver, $sender, $amount);

            $senderTransaction->relatedTransaction()->associate($receiverTransaction);
            $senderTransaction->save();

            $receiverTransaction->relatedTransaction()->associate($senderTransaction);
            $receiverTransaction->save();

            return [
                'sender_transaction' => $senderTransaction,
                'receiver_transaction' => $receiverTransaction,
            ];
        });
    }

    /**
     * Reverse a transaction.
     *
     * This will reverse the transaction by creating a reversal transaction
     * and updating the status of the original transaction to 'reversed'.
     *
     * @param Transaction $transaction The transaction to reverse.
     *
     * @return array An array containing the sender and receiver transactions.
     *
     * @throws \Exception If the transaction type is not supported or if the transaction is already reversed.
     */
    public function reversal(Transaction $originalTransaction): array
    {
        if ($originalTransaction->status === 'reversed') {
            throw new \Exception('Transaction already reversed');
        }

        $baseTransaction = $originalTransaction;
        $mirrorTransaction = $originalTransaction->relatedTransaction ?? null;

        if ($originalTransaction->type === 'transfer_sent') {
            $senderAccount = $originalTransaction->account;
            $receiverAccount = $originalTransaction->relatedAccount;
        } elseif ($originalTransaction->type === 'transfer_received') {
            $senderAccount = $originalTransaction->relatedAccount;
            $receiverAccount = $originalTransaction->account;
        } elseif ($originalTransaction->type === 'deposit') {
            $senderAccount = null;
            $receiverAccount = $originalTransaction->account;
        } else {
            throw new \Exception('Transaction type not supported');
        }

        return $this->createReverseTransaction(
            $senderAccount,
            $receiverAccount,
            $baseTransaction,
            $mirrorTransaction
        );
    }

    /**
     * Reverses a transaction.
     *
     * The reversal of a transaction will have the same amount as the original transaction,
     * but the direction of the transfer will be reversed.
     * The type of the reversal transaction will be 'reversal'.
     *
     * @param Account|null $senderAccount The account that will receive the amount.
     * @param Account $receiverAccount The account that will return the amount.
     * @param Transaction $originalTransaction The transaction to reverse.
     * @param Transaction|null $relatedTransaction The transaction related to the base transaction.
     *
     * @return array An array containing the sender and receiver transactions.
     */
    protected function createReverseTransaction(
        ?Account $senderAccount,
        Account $receiverAccount,
        Transaction $originalTransaction,
        ?Transaction $relatedTransaction
    ): array {
        return DB::transaction(function () use ($senderAccount, $receiverAccount, $originalTransaction, $relatedTransaction) {
            $amount = $originalTransaction->amount;

            // The receiver returns the amount to the sender.
            $receiverTransaction = $this->createSenderTransaction($receiverAccount, $senderAccount, $amount);
            $receiverTransaction->type = 'reversal';
            $receiverTransaction->relatedTransaction()->associate($relatedTransaction);
            $receiverTransaction->save();

            // The sender receives the amount returned. If reversed transaction is
            // a deposit, the sender transaction is null.
            $senderTransaction = $senderAccount ? $this->createReceiverTransaction($senderAccount, $receiverAccount, $amount) : null;
            if ($senderTransaction !== null) {
                $senderTransaction->type = 'reversal';
                $senderTransaction->relatedTransaction()->associate($originalTransaction);
                $senderTransaction->save();
            }

            if ($relatedTransaction) {
                $originalTransaction->status = 'reversed';
                $originalTransaction->save();
                $relatedTransaction->status = 'reversed';
                $relatedTransaction->save();
            }

            return compact('senderTransaction', 'receiverTransaction');
        });
    }

    /**
     * Creates a deposit transaction for the given account.
     *
     * @param Account $account The account where the deposit will be made.
     * @param int $depositAmount The amount to be deposited.
     *
     * @return Transaction The created deposit transaction.
     *
     * @throws \Exception If the deposit amount is not greater than 0.
     */
    protected function createDepositTransaction(Account $account, int $depositAmount): Transaction
    {
        if ($depositAmount <= 0) {
            throw new \Exception('Deposit amount must be greater than 0');
        }

        return DB::transaction(function () use ($account, $depositAmount) {
            $depositTransaction = Transaction::create([
                'account_id' => $account->id,
                'type' => 'deposit',
                'amount' => $depositAmount,
                'status' => 'completed',
            ]);

            $account->balance += $depositAmount;
            $account->save();

            return $depositTransaction;
        });
    }

    /**
     * Creates a sender transaction for transferring money to another account.
     *
     * This function deducts the specified amount from the sender's account
     * and creates a transaction record of type 'transfer_sent'.
     *
     * @param Account $sender The account from which the amount will be deducted.
     * @param Account $receiver The destination account for the transfer.
     * @param int $transferAmount The amount to be transferred.
     *
     * @return Transaction The created sender transaction.
     *
     * @throws \Exception If the amount is not greater than 0 or if the sender's balance is insufficient.
     */
    protected function createSenderTransaction(Account $sender, ?Account $receiver, int $transferAmount): Transaction
    {
        if ($transferAmount <= 0) {
            throw new \Exception('Amount must be greater than 0');
        }

        if ($sender->balance < $transferAmount) {
            throw new \Exception('Insufficient balance');
        }

        return DB::transaction(function () use ($sender, $receiver, $transferAmount) {
            $transaction = Transaction::create([
                'account_id' => $sender->id,
                'related_account_id' => $receiver->id ?? null,
                'type' => 'transfer_sent',
                'amount' => $transferAmount,
                'status' => 'completed'
            ]);

            $sender->balance -= $transferAmount;
            $sender->save();

            return $transaction;
        });
    }

    /**
     * Creates a receiver transaction for transferring money from another account.
     *
     * This function adds the specified amount to the receiver's account
     * and creates a transaction record of type 'transfer_received'.
     *
     * @param Account $receiver The account to which the amount will be credited.
     * @param Account $sender The source account for the transfer.
     * @param int $transferAmount The amount to be transferred.
     *
     * @return Transaction The created receiver transaction.
     *
     * @throws \Exception If the amount is not greater than 0.
     */
    protected function createReceiverTransaction(Account $receiver, Account $sender, int $transferAmount): Transaction
    {
        if ($transferAmount <= 0) {
            throw new \Exception('Transfer amount must be greater than 0');
        }

        return DB::transaction(function () use ($receiver, $sender, $transferAmount) {
            $transaction = Transaction::create([
                'account_id' => $receiver->id,
                'related_account_id' => $sender->id,
                'type' => 'transfer_received',
                'amount' => $transferAmount,
                'status' => 'completed'
            ]);

            $receiver->balance += $transferAmount;
            $receiver->save();

            return $transaction;
        });
    }
}