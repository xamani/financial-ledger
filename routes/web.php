<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UiController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('ui')->name('ui.')->group(function (): void {
    Route::get('/', [UiController::class, 'dashboard'])->name('dashboard');

    Route::get('/orders/create', [UiController::class, 'ordersCreate'])->name('orders.create');
    Route::get('/orders/{order}', [UiController::class, 'ordersShow'])->name('orders.show');

    Route::get('/transactions', [UiController::class, 'transactionsIndex'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [UiController::class, 'transactionsShow'])->name('transactions.show');

    Route::get('/reports/summary', [UiController::class, 'reportsSummary'])->name('reports.summary');
    Route::get('/reports/chart', [UiController::class, 'reportsChart'])->name('reports.chart');

    Route::get('/wallets/withdraw', [UiController::class, 'walletsWithdraw'])->name('wallets.withdraw');
});
