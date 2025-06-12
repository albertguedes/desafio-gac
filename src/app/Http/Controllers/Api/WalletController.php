<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\WalletService;

class WalletController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->middleware('auth');
        $this->walletService = $walletService;
    }

    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $account = Auth::user()->account;
        $transaction = $this->walletService->deposit($account, $request->amount);

        return response()->json(['transaction' => $transaction], 201);
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'receiver_id' => 'required|exists:users,id|different:' . Auth::id(),
        ]);

        $senderAccount = Auth::user()->account;
        $receiverAccount = Account::where('user_id', $request->receiver_id)->first();

        try {
            $transactions = $this->walletService->transfer($senderAccount, $receiverAccount, $request->amount);
            return response()->json(['transactions' => $transactions], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function reverse(Request $request, Transaction $transaction)
    {
        // Só permite reverter transações do usuário autenticado
        if ($transaction->account->user_id !== Auth::id()) {
            return response()->json(['error' => 'Não autorizado'], 403);
        }

        try {
            $reversedTransaction = $this->walletService->reverse($transaction);
            return response()->json(['transaction' => $reversedTransaction]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
