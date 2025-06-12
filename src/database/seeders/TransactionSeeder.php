<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Account;
use App\Models\Transaction;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Account::each(function ($account) {
            Transaction::factory(50)->create([
                'account_id' => $account->id
            ]);
        });
    }
}
