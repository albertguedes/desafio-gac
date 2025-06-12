<?php

namespace Tests\Feature\App\Controllers;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;

class WalletControllerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_show_the_wallet_view()
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();

        $this->actingAs($user)
             ->get(route('wallet'))
             ->assertStatus(200)
             ->assertViewIs('wallet.index');
    }

    /**
     * @test
     */
    public function it_can_deposit_money_into_the_wallet()
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();
        $account = Account::factory()->make(['user_id' => $user->id]);
        $user->account()->save($account);

        $this->actingAs($user)
             ->post(route('wallet.deposit'), [
                 'value' => 10
             ])
             ->assertStatus(302)
             ->assertRedirect(route('wallet'));
    }

    /**
     * @test
     */
    public function it_can_transfer_money_from_the_wallet()
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();
        $account = Account::factory()->make(['user_id' => $user->id]);
        $user->account()->save($account);

        $receiver = User::factory()->create();
        $receiverAccount = Account::factory()->make(['user_id' => $receiver->id]);
        $receiver->account()->save($receiverAccount);

        $this->actingAs($user)
             ->post(route('wallet.transfer'), [
                'receiver_id' => $receiver->id,
                'value' => 10
             ])
             ->assertStatus(302)
             ->assertRedirect(route('wallet'));
    }

    /**
     * @test
     */
    public function it_can_reverse_a_transaction_from_the_wallet()
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();
        $account = Account::factory()->make(['user_id' => $user->id]);
        $user->account()->save($account);

        $receiver = User::factory()->create();
        $receiverAccount = Account::factory()->make(['user_id' => $receiver->id]);
        $receiver->account()->save($receiverAccount);

        $transaction = Transaction::factory()->make([
            'account_id' => $account->id,
            'receiver_id' => $receiver->id
        ]);
        $account->transactions()->save($transaction);

        $this->actingAs($user)
             ->post(route('wallet.reverse', $transaction->id))
             ->assertStatus(302)
             ->assertRedirect(route('wallet'));
    }
}
