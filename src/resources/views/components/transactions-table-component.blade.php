<table class="table">
    <thead>
        <tr>
            <th scope="col" class="text-center" >#</th>
            <th scope="col">Date</th>
            <th scope="col" class="text-right" >Amount</th>
            <th scope="col">Type</th>
            <th scope="col" class="text-center" >Related Transaction</th>
            <th scope="col" class="text-center" >Related Account</th>
            <th scope="col">Status</th>
            <th scope="col"></th>
        </tr>
    </thead>
    <tbody>
        @foreach ($transactions as $transaction)
        <tr class="@if($transaction['status'] === 'canceled') table-danger @endif">
            <td scope="row" class="text-center">{{ $transaction['id'] }}</td>
            <td>{{ $transaction['created_at'] }}</td>
            <td class="text-right text-{{ $transaction['amount_color'] }}" >@money($transaction['amount'])</td>
            <td class="text-capitalize" >{{ $transaction['type'] }}</td>
            <td class="text-center" >{{ $transaction['related_transaction_id'] ?? '-' }}</td>
            <td class="text-center text-capitalize" >{{ $transaction['related_account_user_name'] ?? '-' }}</td>
            <td>
                <span class="badge bg-{{ $transaction['status_color'] }}" >
                    {{ $transaction['status'] }}
                </span>
            </td>
            <td>
                <form method="POST" action="{{ route('wallet.reverse', [ 'transaction' => $transaction['id'] ]) }}" >
                    @csrf
                    <button class="btn btn-primary @if(!$transaction['reversible']) disabled @endif" >
                        <i class="fa fa-undo"></i>
                        Reverse
                    </button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>