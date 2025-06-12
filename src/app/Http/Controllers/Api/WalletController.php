<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\WalletService;

class WalletController extends Controller
{
    const DEPOSIT_SUCCESS_MSG = 'Deposit successful';
    const DEPOSIT_FAILED_MSG = 'Deposit failed';

    const TRANSFER_SUCCESS_MSG = 'Transfer successful';
    const TRANSFER_FAILED_MSG = 'Transfer failed';

    const REVERSE_SUCCESS_MSG = 'Reverse successful';
    const REVERSE_FAILED_MSG = 'Reverse failed';

    const BALANCE_INSUFFICIENT_MSG = 'Insufficient balance';

    public function __construct (protected readonly WalletService $walletService) {}

    public function deposit(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $deposit_amount = 100 * $request->input('amount');

        try {
            $this->walletService->deposit(auth()->user()->account, $deposit_amount);
            return response()->json(['message' => self::DEPOSIT_SUCCESS_MSG]);
        } catch (Exception $e) {
            return redirect()->json(['message' => self::DEPOSIT_FAILED_MSG]);
        }
    }

    public function transfer(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'receiver_account_id' => ['required', 'exists:accounts,id', 'different:' . auth()->user()->account->id],
        ]);

        $transfer_amount = 100 * $request->input('amount');
        $receiver_account_id = $request->input('receiver_account_id');

        try {
            $receiver_account = Account::where('id', $receiver_account_id)->firstOrFail();
            $sender_account = auth()->user()->account;

            if ($sender_account->balance < $transfer_amount) {
                return redirect()->json(['message' => self::BALANCE_INSUFFICIENT_MSG]);
            }

            $this->walletService->transfer($sender_account, $receiver_account, $transfer_amount);

            return redirect()->json(['message' => self::TRANSFER_SUCCESS_MSG]);
        } catch (Exception $e) {
            return redirect()->json(['message' => self::TRANSFER_FAILED_MSG]);
        }
    }

    public function reverse(Transaction $transaction): RedirectResponse
    {
        try {
            $this->walletService->reversal($transaction);
            return redirect()->json(['message' => self::REVERSE_SUCCESS_MSG]);
        } catch (Exception $exception) {
            return redirect()->json(['message' => self::REVERSE_FAILED_MSG]);
        }
    }
}
