<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

class TransactionsTableComponent extends Component
{
    public array $transactions = [];

    public $status_colors = [
        'completed' => 'success',
        'reversed' => 'warning',
        'canceled' => 'danger'
    ];

    /**
     * Create a new component instance.
     */
    public function __construct(Collection $transactions)
    {
        foreach($transactions as $transaction)
        {
            $status_color = 'secondary';
            if (array_key_exists($transaction->status, $this->status_colors)) {
                $status_color = $this->status_colors[$transaction->status];
            }

            $reversible = false;
            if(
                ($transaction->status != 'canceled') &&
                ($transaction->status != 'reversed') &&
                ($transaction->type != 'reversal')
            ) {
                $reversible = true;
            }

            $type_color = 'success';

            if($transaction->type === 'transfer_sent') {
                $type_color = 'danger';
            }

            if (($transaction->type === 'reversal')){
                $related_type = $transaction->relatedTransaction->type;
                if ( $related_type == 'transfer_received' || $related_type == 'deposit') {
                    $type_color = 'danger';
                }
            }

            $this->transactions[] = [
                'id' => $transaction->id,
                'created_at' => $transaction->created_at->format('d/m/Y H:i:s'),
                'type' => str_replace('_', ' ', $transaction->type),
                'type_color' => $type_color,
                'amount' => $transaction->amount,
                'related_transaction_id' => $transaction->relatedTransaction->id ?? null,
                'related_account_user_name' => $transaction->relatedAccount->user->name ?? null,
                'status' => $transaction->status,
                'status_color' => $status_color,
                'reversible' => $reversible
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.transactions-table-component');
    }
}
