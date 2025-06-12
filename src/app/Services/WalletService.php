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
    public function deposit(Account $account, int $amount): void
    {
        $this->assertPositiveAmount($amount);

        DB::transaction(function () use ($account, $amount)
        {
            $account->lockForUpdate();
            $account->balance += $amount;
            $account->save();

            $transaction = $this->createReceiveTransaction(null, $account, $amount);
            $transaction->type = TransactionType::Deposit->value;
            $transaction->save();
        });
    }

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

    public function reversal(Transaction $transaction)
    {
        $this->assertReversalTransaction($transaction);

        DB::transaction(function () use ($transaction)
        {
            $transaction->account->lockForUpdate();
            $transaction->relatedAccount?->lockForUpdate();

            $type = $transaction->type;
            $amount = abs($transaction->amount);

            if($type == TransactionType::Deposit->value){
                $reverse_transaction = $this->createSendTransaction($transaction->account, null, $amount);
                $reverse_transaction->type = TransactionType::Reversal->value;
                $reverse_transaction->save();

                $transaction->account->balance -= $amount;
                $transaction->account->save();

                $transaction->status = TransactionStatus::Reversed->value;
                $transaction->save();
            }

            if($type == TransactionType::TransferSent->value)
            {
                $reverse_transaction = $this->createReceiveTransaction($transaction->relatedAccount, $transaction->account, $amount);
                $reverse_transaction->type = TransactionType::Reversal->value;
                $reverse_transaction->save();

                $transaction->account->balance += $amount;
                $transaction->account->save();
                $transaction->status = TransactionStatus::Reversed->value;
                $transaction->save();

                $reverse_related_transaction = $this->createSendTransaction($transaction->relatedAccount, $transaction->account, $amount);
                $reverse_related_transaction->type = TransactionType::Reversal->value;
                $reverse_related_transaction->save();

                $transaction->relatedTransaction->account->balance -= $amount;
                $transaction->relatedTransaction->account->save();

                $transaction->relatedTransaction->status = TransactionStatus::Reversed->value;
                $transaction->relatedTransaction->save();
            }

            if($type == TransactionType::TransferReceived->value){
                $reverse_transaction = $this->createSendTransaction($transaction->account, $transaction->relatedAccount, $amount);
                $reverse_transaction->type = TransactionType::Reversal->value;
                $reverse_transaction->save();

                $transaction->account->balance -= $amount;
                $transaction->account->save();

                $transaction->status = TransactionStatus::Reversed->value;
                $transaction->save();

                $reverse_related_transaction = $this->createReceiveTransaction($transaction->account, $transaction->relatedAccount, $amount);
                $reverse_related_transaction->type = TransactionType::Reversal->value;
                $reverse_related_transaction->save();

                $transaction->relatedTransaction->account->balance += $amount;
                $transaction->relatedTransaction->account->save();

                $transaction->relatedTransaction->status = TransactionStatus::Reversed->value;
                $transaction->relatedTransaction->save();
            }
        });
    }

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

    private function checkBalance(Account $account, int $amount): void
    {
        if ($account->balance < $amount) {
            throw new Exception('Insufficient balance');
        }
    }

    private function assertPositiveAmount(int $amount): void
    {
        if ($amount < 0) {
            throw new Exception('Amount must be positive');
        }
    }

    private function assertReversalTransaction(Transaction $transaction): void
    {
        if ($transaction->status === TransactionStatus::Reversed->value) {
            throw new Exception('Transaction is already reversed');
        }
    }
}