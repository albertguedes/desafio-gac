<form method="POST" action="{{ route('wallet.transfer') }}" >

    @csrf

    <div class="mb-3">
        <select class="form-select" name="receiver_account_id" required>
            <option value="" selected="selected">Select the receiver</option>
            @foreach ($users as $user)
            <option value="{{ $user['account_id'] }}">{{ $user['name'] }}</option>
            @endforeach
        </select>
        @error('receiver_account_id')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
        @enderror
    </div>

    <div class="mb-3">
        <div class="input-group">
            <input class="form-control" type="number" name="amount" placeholder="Amount" required step="0.01" min="0.01">
            <button class="btn btn-primary" type="submit"><i class="fa fa-sign-in"></i> Transfer</button>
        </div>
    </div>

</form>