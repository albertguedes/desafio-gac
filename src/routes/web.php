<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WalletController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::redirect('/', '/login');

require __DIR__.'/auth.php';

Route::get('/profile', [ProfileController::class, 'index'])
     ->middleware(['auth'])
     ->name('profile');

Route::get('/wallet', [WalletController::class, 'index'])
     ->middleware(['auth'])
     ->name('wallet');

Route::post('/wallet/deposit', [WalletController::class, 'deposit'])
     ->middleware(['auth'])
     ->name('wallet.deposit');

Route::post('/wallet/transfer', [WalletController::class, 'transfer'])
     ->middleware(['auth'])
     ->name('wallet.transfer');

Route::post('/wallet/reverse/{transaction}', [WalletController::class, 'reverse'])
     ->middleware(['auth'])
     ->name('wallet.reverse');
