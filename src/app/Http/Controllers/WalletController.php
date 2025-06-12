<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
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

    public function index(): View
    {
        $user = auth()->user();
        $account = $user->account;
        $balance = $account->balance;
        $transactions = $account->transactions()->orderByDesc('created_at')->get();

        return view('wallet.index', compact('balance', 'transactions'));
    }

    public function deposit(Request $request): RedirectResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $amount = 100 * $request->input('amount');
        $account = auth()->user()->account;

        try {
            $this->walletService->deposit($account, $amount);

            return redirect()->route('wallet')->with('success', self::DEPOSIT_SUCCESS_MSG);
        }
        catch (Exception $e) {
            return redirect()->route('wallet')->with('danger', self::DEPOSIT_FAILED_MSG);
        }
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'receiver_account_id' => ['required', 'exists:accounts,id', 'different:' . auth()->user()->account->id],
        ]);

        $amount = 100 * $request->input('amount');
        $receiver_account_id = $request->input('receiver_account_id');

        try {
            $receiver_account = Account::where('id', $receiver_account_id)->firstOrFail();
            $sender_account = auth()->user()->account;

            if ($sender_account->balance < $amount) {
                return redirect()->route('wallet')
                                 ->with('danger', self::BALANCE_INSUFFICIENT_MSG);
            }

            $this->walletService->transfer($sender_account, $receiver_account, $amount);

            return redirect()->route('wallet')
                             ->with('success', self::TRANSFER_SUCCESS_MSG);
        } catch (Exception $e) {
            return redirect()->route('wallet')
                             ->with('danger', self::TRANSFER_FAILED_MSG);
        }
    }

    public function reverse(Transaction $transaction): RedirectResponse
    {
        try {
            $this->walletService->reversal($transaction);
            return redirect()->route('wallet')->with('success', self::REVERSE_SUCCESS_MSG);
        } catch (Exception $exception) {
            return redirect()->route('wallet')->with('danger', self::REVERSE_FAILED_MSG);
        }
    }
}
