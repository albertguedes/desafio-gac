<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Account;
use App\Models\Transaction;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['deposit', 'transfer_sent', 'transfer_received', 'reversal'];
        $statuses = ['completed', 'canceled', 'reversed'];
        $account = Account::inRandomOrder()->first();

        $account_id = $account->id;
        $related_account_id = null;
        $related_transaction_id = null;

        do {
            $type = $types[array_rand($types)];
        } while($type === 'reversal' && !Transaction::exists());

        if($type === 'reversal') {
            $nonReversalTransactions = Transaction::where('type', '<>', 'reversal')->get();
            if($nonReversalTransactions->count() > 0) {
                $relatedTransaction = $nonReversalTransactions->random();
                $relatedAccount = $relatedTransaction->account;
            }
            else{
                $type = 'deposit';
            }
        }

        $status = $statuses[array_rand($statuses)];

        switch($type) {
            case 'deposit':
                $amount = random_int(1, 100000);
                $account->balance += $amount;
                $account->save();
                $status = 'completed';
                break;

            case 'transfer_sent':
                $related_account = Account::inRandomOrder()->first();
                $related_account_id = $related_account->id;

                $amount = -random_int(1, 100000);

                if($account->balance >= $amount){
                    $account->balance += $amount;
                    $account->save();

                    $related_account->balance -= $amount;
                    $related_account->save();

                    $status = 'completed';
                }
                else{
                    $status = 'canceled';
                }

                break;

            case 'transfer_received':
                $related_account = Account::inRandomOrder()->first();
                $related_account_id = $related_account->id;

                $amount = random_int(1, 100000);
                if($related_account->balance >= $amount){
                    $account->balance += $amount;
                    $account->save();

                    $related_account->balance -= $amount;
                    $related_account->save();

                    $status = 'completed';
                }
                else{
                    $status = 'canceled';
                }

                break;

            case 'reversal':

                $related_transaction_id = $relatedTransaction->id;
                $status = 'reversed';

                switch($relatedTransaction->type){
                    case 'deposit':
                        $amount = -$relatedTransaction->amount;
                        $account->balance += $amount;
                        $account->save();
                        break;

                    case 'transfer_sent':
                        $amount = $relatedTransaction->amount;

                        $account->balance += $amount;
                        $account->save();

                        $related_account = $relatedTransaction->relatedAccount;
                        $related_account_id = $related_account->id;
                        $related_account->balance -= $amount;
                        $related_account->save();

                        break;

                    case 'transfer_received':
                        $amount = -$relatedTransaction->amount;

                        $account->balance += $amount;
                        $account->save();

                        $related_account = $relatedTransaction->relatedAccount;
                        $related_account_id = $related_account->id;
                        $related_account->balance -= $amount;
                        $related_account->save();

                        break;
                }

                break;
        }

        return compact(
            'account_id',
            'related_account_id',
            'related_transaction_id',
            'type',
            'amount',
            'status'
        );
    }
}
