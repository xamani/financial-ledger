<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;

class UiController extends Controller
{
    public function dashboard(): View
    {
        return view('ui.dashboard');
    }

    public function ordersCreate(): View
    {
        return view('ui.orders.create');
    }

    public function ordersShow(Order $order): View
    {
        return view('ui.orders.show', [
            'orderId' => (int) $order->id,
        ]);
    }

    public function transactionsIndex(): View
    {
        return view('ui.transactions.index');
    }

    public function transactionsShow(Transaction $transaction): View
    {
        return view('ui.transactions.show', [
            'transactionId' => (int) $transaction->id,
        ]);
    }

    public function reportsSummary(): View
    {
        return view('ui.reports.summary');
    }

    public function reportsChart(): View
    {
        return view('ui.reports.chart');
    }

    public function walletsWithdraw(): View
    {
        return view('ui.wallets.withdraw');
    }
}

