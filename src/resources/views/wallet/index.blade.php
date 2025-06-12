<x-app-layout>
<div class="row">

    <div class="mb-3 col-12">
        <h1 class="text-center col-12">Wallet</h1>
    </div>

    @foreach (['success', 'danger', 'info', 'warning' ] as $msg)
        @if(session($msg))
        <div class="mb-3 col-12">
            <div class="alert alert-{{ $msg }}">
                {{ session($msg) }}
            </div>
        </div>
        @endif
    @endforeach

    <div class="mb-3 col-12">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title h3">Balance</h2>
                <p class="card-text h2 fw-bold" >@money($balance)</p>
            </div>
        </div>
    </div>

    <div class="mb-3 col-12">
        <div class="row" >

            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="card-title">Deposit</h3>

                        <form method="POST" action="{{ route('wallet.deposit') }}" >
                            @csrf
                            <div class="mb-3">
                                <div class="input-group">
                                    <input class="form-control @error('amount') is-invalid @enderror" type="number" name="amount" placeholder="Amount" required step="0.01" min="0.01">
                                    <button class="btn btn-primary" type="submit"><i class="fa fa-sign-in"></i> Deposit</button>
                                </div>

                                @error('amount')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="card-title">Transfer</h3>
                        <x-receiver-form-component />
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="mb-3 col-12">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">Transactions</h3>
                <x-transactions-table-component :transactions="$transactions" />
            </div>
        </div>
    </div>

</div>
</x-app-layout>
