<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('account_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->foreignId('related_account_id')
                  ->nullable()
                  ->constrained('accounts')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('related_transaction_id')
                  ->nullable();
            $table->foreign('related_transaction_id')
                  ->references('id')
                  ->on('transactions')
                  ->onDelete('cascade');

            $table->enum('type', ['deposit', 'transfer_sent', 'transfer_received', 'reversal']);

            $table->bigInteger('amount');

            $table->enum('status', ['completed', 'canceled', 'reversed'])
                  ->default('completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
