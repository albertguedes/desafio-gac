<?php

namespace Tests\Unit\App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WalletService $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        // Cria usuários necessários para os accounts
        User::factory()->count(5)->create();

        $this->wallet = new WalletService();
    }

    /**
     * @param string $method
     * @return ReflectionMethod
     *
     * This method is used to call protected methods of the WalletService class.
     * It creates a ReflectionMethod object for the given method and sets it to be accessible.
     * The method is then returned, allowing it to be called.
     */
    protected function getProtectedMethod(string $method): ReflectionMethod
    {
        $ref = new ReflectionMethod(WalletService::class, $method);
        $ref->setAccessible(true);
        return $ref;
    }

    /**
     * Verifies that the `createSendTransaction` method successfully creates a transaction
     * that records a money send operation.
     *
     * The test creates a sender and a receiver account, and then calls the
     * `createSendTransaction` method with the sender, receiver, and an amount.
     *
     * The test verifies that the returned transaction is an instance of the
     * Transaction class, and that the account_id, related_account_id, amount,
     * type, and status of the transaction are as expected. It also verifies
     * that the transaction is stored in the database.
     */
    public function test_creates_send_transaction_successfully()
    {
        $method = $this->getProtectedMethod('createSendTransaction');

        $sender = Account::factory()->create(['balance' => 10000]);
        $receiver = Account::factory()->create();

        $amount = 5000;

        /** @var Transaction $tx */
        $tx = $method->invoke($this->wallet, $sender, $receiver, $amount);

        $this->assertInstanceOf(Transaction::class, $tx);
        $this->assertEquals($sender->id, $tx->account_id);
        $this->assertEquals($receiver->id, $tx->related_account_id);
        $this->assertEquals(-$amount, $tx->amount);
        $this->assertEquals(TransactionType::TransferSent->value, $tx->type);
        $this->assertEquals(TransactionStatus::Completed->value, $tx->status);

        $this->assertDatabaseHas('transactions', ['id' => $tx->id]);
    }

    /**
     * Verifies that the `createSendTransaction` method throws an InvalidArgumentException
     * when provided with a negative amount.
     *
     * The test creates a sender and a receiver account, then attempts to call the
     * `createSendTransaction` method with a negative amount.
     *
     * The test expects an InvalidArgumentException to be thrown with the message
     * 'Amount must be positive', ensuring that the method correctly handles
     * invalid input.
     */
    public function test_create_send_transaction_throws_on_negative_amount()
    {
        $method = $this->getProtectedMethod('createSendTransaction');

        $sender = Account::factory()->create(['balance' => 10000]);
        $receiver = Account::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');

        $method->invoke($this->wallet, $sender, $receiver, -100);
    }

    /**
     * Verifies that the `createSendTransaction` method throws a RuntimeException
     * when the sender account has insufficient balance.
     *
     * The test creates a sender and a receiver account, then attempts to call the
     * `createSendTransaction` method with an amount greater than the sender's balance.
     *
     * The test expects a RuntimeException to be thrown with the message
     * 'Insufficient balance', ensuring that the method correctly handles
     * insufficient balance.
     */
    public function test_create_send_transaction_throws_on_insufficient_balance()
    {
        $method = $this->getProtectedMethod('createSendTransaction');

        $sender = Account::factory()->create(['balance' => 1000]);
        $receiver = Account::factory()->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient balance');

        $method->invoke($this->wallet, $sender, $receiver, 5000);
    }

    /**
     * Verifies that the `createReceiveTransaction` method successfully creates
     * a transaction that records a money receive operation with a specified
     * sender, when the operation type is a transfer.
     *
     * The test creates a sender and a receiver account, and then calls the
     * `createReceiveTransaction` method with the sender, receiver, and an amount.
     *
     * The test asserts that the returned transaction is an instance of the
     * Transaction class, and that the account_id, related_account_id, amount,
     * type, and status of the transaction are as expected. It also verifies
     * that the transaction is stored in the database.
     */
    public function test_creates_receive_transaction_with_sender()
    {
        $method = $this->getProtectedMethod('createReceiveTransaction');

        $sender = Account::factory()->create();
        $receiver = Account::factory()->create();
        $amount = 7500;

        $tx = $method->invoke($this->wallet, $sender, $receiver, $amount);

        $this->assertInstanceOf(Transaction::class, $tx);
        $this->assertEquals($receiver->id, $tx->account_id);
        $this->assertEquals($sender->id, $tx->related_account_id);
        $this->assertEquals($amount, $tx->amount);
        $this->assertEquals(TransactionType::TransferReceived->value, $tx->type);
        $this->assertEquals(TransactionStatus::Completed->value, $tx->status);

        $this->assertDatabaseHas('transactions', ['id' => $tx->id]);
    }

    /**
     * Verifies that the `createReceiveTransaction` method successfully creates
     * a transaction that records a money receive operation without a sender in
     * case of deposit type.
     *
     * The test creates a receiver account, and then calls the
     * `createReceiveTransaction` method with the receiver, an amount, and null
     * as the sender.
     *
     * The test asserts that the returned transaction is an instance of the
     * Transaction class, and that the account_id, amount, type, and status of
     * the transaction are as expected. It also verifies that the
     * related_account_id is null, and that the transaction is stored in the
     * database.
     */
    public function test_creates_receive_transaction_without_sender()
    {
        $method = $this->getProtectedMethod('createReceiveTransaction');

        $receiver = Account::factory()->create();
        $amount = 5000;

        /** @var Transaction $tx */
        $tx = $method->invoke($this->wallet, null, $receiver, $amount);

        $this->assertInstanceOf(Transaction::class, $tx);
        $this->assertEquals($receiver->id, $tx->account_id);
        $this->assertNull($tx->related_account_id);
        $this->assertEquals($amount, $tx->amount);
        $this->assertEquals(TransactionType::TransferReceived->value, $tx->type);
        $this->assertEquals(TransactionStatus::Completed->value, $tx->status);

        $this->assertDatabaseHas('transactions', ['id' => $tx->id]);
    }

    /**
     * Verifies that the `createReceiveTransaction` method throws an InvalidArgumentException
     * when given a negative amount.
     *
     * The test creates a receiver account, and then attempts to call the
     * `createReceiveTransaction` method with the receiver, an amount of -1, and null as the sender.
     *
     * The test expects an InvalidArgumentException to be thrown with the message
     * 'Amount must be positive', ensuring that the method correctly handles
     * invalid input.
     */
    public function test_receive_transaction_throws_on_negative_amount()
    {
        $method = $this->getProtectedMethod('createReceiveTransaction');

        $receiver = Account::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');

        $method->invoke($this->wallet, null, $receiver, -1);
    }

    /**
     * Até aqui ok
     */

     public function test_deposit_increases_account_balance_and_creates_transaction()
     {
         $account = Account::factory()->create(['balance' => 10000]);
         $amount = 2500;

         $this->wallet->deposit($account, $amount);

         $account->refresh();

         $this->assertEquals(12500, $account->balance);

         $this->assertDatabaseHas('transactions', [
             'account_id' => $account->id,
             'related_account_id' => null,
             'amount' => $amount,
             'type' => TransactionType::Deposit->value,
             'status' => TransactionStatus::Completed->value,
         ]);
     }

     public function test_deposit_throws_on_negative_amount()
     {
         $account = Account::factory()->create(['balance' => 10000]);

         $this->expectException(\InvalidArgumentException::class);
         $this->expectExceptionMessage('Amount must be positive');

         $this->wallet->deposit($account, -500);
     }

     /**
      * Até aqui ok
      */

      public function test_transfer_success()
      {
        $sender = Account::factory()->create(['balance' => 10000]);
        $receiver = Account::factory()->create(['balance' => 5000]);
        $amount = 3000;

        $this->wallet->transfer($sender, $receiver, $amount);

        $sender->refresh();
        $receiver->refresh();

        // Saldos atualizados
        $this->assertEquals(7000, $sender->balance);
        $this->assertEquals(8000, $receiver->balance);

        // Verifica transações na base
        $this->assertDatabaseHas('transactions', [
            'account_id' => $sender->id,
            'related_account_id' => $receiver->id,
            'amount' => -$amount,
            'type' => TransactionType::TransferSent->value,
            'status' => TransactionStatus::Completed->value,
        ]);
        $this->assertDatabaseHas('transactions', [
            'account_id' => $receiver->id,
            'related_account_id' => $sender->id,
            'amount' => $amount,
            'type' => TransactionType::TransferReceived->value,
            'status' => TransactionStatus::Completed->value,
        ]);
    }

    public function test_transfer_throws_on_negative_amount()
    {
        $sender = Account::factory()->create(['balance' => 10000]);
        $receiver = Account::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');

        $this->wallet->transfer($sender, $receiver, -100);
    }

    public function test_transfer_throws_on_insufficient_balance()
    {
        $sender = Account::factory()->create(['balance' => 1000]);
        $receiver = Account::factory()->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient balance');

        $this->wallet->transfer($sender, $receiver, 5000);
    }

    public function test_reverseDeposit_reverses_transaction_correctly()
    {
        $initial_receiver_balance = 10000;

        // Create an account
        $account = Account::factory()->create(['balance' => $initial_receiver_balance ]);

        // Deposited amount
        $amount = 5000;

        // Create deposit transaction.
        $transaction = Transaction::factory()->create([
            'account_id' => $account->id,
            'amount' => $amount,
            'type' => TransactionType::Deposit->value,
            'status' => TransactionStatus::Completed->value,
        ]);
        $account->balance += $amount;
        $account->save();

        // Turn method visible.
        $method = new \ReflectionMethod(WalletService::class, 'reverseDeposit');
        $method->setAccessible(true);

        // Exec method
        $method->invoke($this->wallet, $transaction);

        $account->refresh();
        $transaction->refresh();

        $this->assertEquals($initial_receiver_balance, $account->balance);

        $this->assertEquals(TransactionStatus::Reversed->value, $transaction->status);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'amount' => -$amount,
            'type' => TransactionType::Reversal->value,
        ]);
    }

    /**
     * Até aqui ok
     */
    public function test_reverseTransferSent_reverses_transaction_correctly()
    {
        $initial_sender_balance = 10000;
        $initial_receiver_balance = 5000;

        $sender = Account::factory()->create(['balance' => $initial_sender_balance]);
        $receiver = Account::factory()->create(['balance' => $initial_receiver_balance ]);

        $transferAmount = 3000;

        // Create a send transaction
        $sentTransaction = Transaction::factory()->create([
            'account_id' => $sender->id,
            'related_account_id' => $receiver->id,
            'amount' => -$transferAmount,
            'type' => TransactionType::TransferSent->value,
            'status' => TransactionStatus::Completed->value,
        ]);

        // Create a receive transaction
        $receivedTransaction = Transaction::factory()->create([
            'account_id' => $receiver->id,
            'related_account_id' => $sender->id,
            'amount' => $transferAmount,
            'type' => TransactionType::TransferReceived->value,
            'status' => TransactionStatus::Completed->value,
        ]);

        // Update the balances.
        $sender->balance -= $transferAmount;
        $sender->save();

        $receiver->balance += $transferAmount;
        $receiver->save();

        $sentTransaction->relatedTransaction()->associate($receivedTransaction);
        $sentTransaction->save();

        $receivedTransaction->relatedTransaction()->associate($sentTransaction);
        $receivedTransaction->save();

        // Exec reverse
        $method = new \ReflectionMethod(WalletService::class, 'reverseTransferSent');
        $method->setAccessible(true);
        $method->invoke($this->wallet, $sentTransaction);

        $sender->refresh();
        $receiver->refresh();
        $sentTransaction->refresh();
        $receivedTransaction->refresh();

        // Verify if the transfer was reversed and the balances are equal to initial values.
        $this->assertEquals( $initial_sender_balance , $sender->balance);    // saldo do sender incrementado
        $this->assertEquals($initial_receiver_balance, $receiver->balance);   // saldo do receiver decrementado

        // Verify transactions status
        $this->assertEquals(TransactionStatus::Reversed->value, $sentTransaction->status);
        $this->assertEquals(TransactionStatus::Reversed->value, $receivedTransaction->status);

        // Verify if reversal transactions exists
        $this->assertDatabaseHas('transactions', [
            'account_id' => $sender->id,
            'related_account_id' => $receiver->id,
            'amount' => $transferAmount,
            'type' => TransactionType::Reversal->value,
        ]);
        $this->assertDatabaseHas('transactions', [
            'account_id' => $receiver->id,
            'related_account_id' => $sender->id,
            'amount' => -$transferAmount,
            'type' => TransactionType::Reversal->value,
        ]);
    }

    public function test_reverseTransferReceived_reverses_transaction_correctly()
    {
        $initial_sender_balance = 10000;
        $initial_receiver_balance = 5000;

        $sender = Account::factory()->create(['balance' => $initial_sender_balance]);
        $receiver = Account::factory()->create(['balance' => $initial_receiver_balance ]);

        $transferAmount = 3000;

        // Create a send transaction
        $sentTransaction = Transaction::factory()->create([
            'account_id' => $sender->id,
            'related_account_id' => $receiver->id,
            'amount' => -$transferAmount,
            'type' => TransactionType::TransferSent->value,
            'status' => TransactionStatus::Completed->value,
        ]);

        // Create a receive transaction
        $receivedTransaction = Transaction::factory()->create([
            'account_id' => $receiver->id,
            'related_account_id' => $sender->id,
            'amount' => $transferAmount,
            'type' => TransactionType::TransferReceived->value,
            'status' => TransactionStatus::Completed->value,
        ]);

        // Update the balances.
        $sender->balance -= $transferAmount;
        $sender->save();

        $receiver->balance += $transferAmount;
        $receiver->save();

        $sentTransaction->relatedTransaction()->associate($receivedTransaction);
        $sentTransaction->save();

        $receivedTransaction->relatedTransaction()->associate($sentTransaction);
        $receivedTransaction->save();

        // Exec reverse
        $method = new \ReflectionMethod(WalletService::class, 'reverseTransferReceived');
        $method->setAccessible(true);
        $method->invoke($this->wallet, $receivedTransaction);

        $sender->refresh();
        $receiver->refresh();
        $sentTransaction->refresh();
        $receivedTransaction->refresh();

        // Verify if the transfer was reversed and the balances are equal to initial values.
        $this->assertEquals($initial_sender_balance ,  $sender->balance);    // saldo do sender incrementado
        $this->assertEquals($initial_receiver_balance, $receiver->balance);   // saldo do receiver decrementado

        // Verify transactions status
        $this->assertEquals(TransactionStatus::Reversed->value, $sentTransaction->status);
        $this->assertEquals(TransactionStatus::Reversed->value, $receivedTransaction->status);

        // Verify if reversal transactions exists
        $this->assertDatabaseHas('transactions', [
            'account_id' => $sender->id,
            'related_account_id' => $receiver->id,
            'amount' => $transferAmount,
            'type' => TransactionType::Reversal->value,
        ]);
        $this->assertDatabaseHas('transactions', [
            'account_id' => $receiver->id,
            'related_account_id' => $sender->id,
            'amount' => -$transferAmount,
            'type' => TransactionType::Reversal->value,
        ]);
    }

    public function test_reverse_transfer_received_throws_if_no_related_transaction(): void
    {
        $this->expectException(\RuntimeException::class);

        $receiver = Account::factory()->create(['balance' => 5000]);

        $receiver_transaction = Transaction::factory()->create([
            'account_id' => $receiver->id,
            'related_account_id' => 9999, // inexistente
            'amount' => 3000,
            'type' => TransactionType::TransferReceived->value,
            'status' => TransactionStatus::Completed->value,
            'related_transaction_id' => null,
        ]);

        $this->wallet->reverseTransferReceived($receiver_transaction);
    }
}
