<x-app-layout>
<div class="row">

    <div class="col-12">
        <h1 class="text-center col-12">Profile</h1>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h2 class="mb-3 card-title h1 text-capitalize">
                    <i class="fa fa-user"></i>
                    {{ $user->name }}
                </h2>
                <h3 class="mb-3 h6 text-muted">
                    <i class="fa fa-envelope"></i>
                    {{ $user->email }}
                </h3>
            </div>
        </div>
    </div>

</div>
</x-app-layout>
