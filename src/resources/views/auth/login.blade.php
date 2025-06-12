<x-guest-layout>
<div class="row justify-content-center align-items-center vh-100">
    <form class="col-4" method="POST" action="{{ route('login') }}" >
        @csrf
        <div class="row" >
            <div class="col-12">
                <h1 class="text-center col-12">Login</h1>
            </div>

            <div class="mb-3 col-12">
                <div class="input-group">
                    <label class="input-group-text"><i class="fa fa-user"></i></label>
                    <input class="form-control" type="email" name="email" placeholder="Email" required>
                </div>
                @error('email')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror
            </div>

            <div class="mb-3 col-12">
                <div class="input-group">
                    <label class="input-group-text"><i class="fa fa-lock"></i></label>
                    <input class="form-control" type="password" name="password" placeholder="Password" required>
                </div>
                @error('password')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror
            </div>

            <div class="mb-3 text-center col-12">
                <button class="btn btn-primary" type="submit"><i class="fa fa-sign-in"></i> Sign In</button>
            </div>

            <div class="pt-3 text-center col-12 border-top">
                @if (Route::has('register'))
                <a class="btn btn-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                @endif
            </div>
        </div>
    </form>
</div>
</x-guest-layout>

