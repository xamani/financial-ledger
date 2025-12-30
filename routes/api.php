<?php

use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::post('/orders/{order}/pay', [OrderController::class, 'pay'])->name('orders.pay');
Route::post('/orders/callback', [OrderController::class, 'callback'])->name('orders.callback');

Route::get('/financial-reports', [FinancialReportController::class, 'index'])->name('financial-reports.index');
