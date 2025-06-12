<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WalletController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/wallet/deposit', [WalletController::class, 'deposit'])
    ->name('wallet.deposit');

    Route::post('/wallet/transfer', [WalletController::class, 'transfer'])
    ->name('wallet.transfer');

    Route::post('/wallet/reverse/{transaction}', [WalletController::class, 'reverse'])
    ->name('wallet.reverse');
});
