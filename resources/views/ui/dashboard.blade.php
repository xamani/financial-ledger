@extends('ui.layout', ['title' => 'داشبورد'])

@section('content')
    <div class="grid grid--3">
        <div class="card">
            <h3>ایجاد (Create)</h3>
            <p>ساخت سفارش `pending` و گرفتن لینک پرداخت و callback بانک.</p>
            <div style="margin-top: 12px" class="btn-row">
                <a class="btn btn-primary" href="{{ route('ui.orders.create') }}">ساخت سفارش</a>
            </div>
        </div>

        <div class="card">
            <h3>لیست (Index)</h3>
            <p>لیست تراکنش‌ها با فیلتر و صفحه‌بندی (مثل Nova index).</p>
            <div style="margin-top: 12px" class="btn-row">
                <a class="btn btn-primary" href="{{ route('ui.transactions.index') }}">لیست تراکنش‌ها</a>
            </div>
        </div>

        <div class="card">
            <h3>گزارش‌ها</h3>
            <p>گزارش تجمیعی و نموداری در بازهٔ زمانی (Summary/Chart).</p>
            <div style="margin-top: 12px" class="btn-row">
                <a class="btn" href="{{ route('ui.reports.summary') }}">خلاصه</a>
                <a class="btn" href="{{ route('ui.reports.chart') }}">نمودار</a>
            </div>
        </div>
    </div>

    <div style="margin-top: 14px" class="grid grid--2">
        <div class="card">
            <h3>کیف‌پول‌ها / برداشت (Create)</h3>
            <p>ثبت برداشت و ایجاد تراکنش `withdrawal` با `flow=out`.</p>
            <div style="margin-top: 12px" class="btn-row">
                <a class="btn btn-primary" href="{{ route('ui.wallets.withdraw') }}">برداشت از کیف‌پول</a>
            </div>
        </div>

        <div class="card">
            <h3>پایهٔ API</h3>
            <p>پایهٔ مسیرها: <span class="mono">/api</span></p>
            <p style="margin-top: 10px">اگر پاسخ‌ها خطا داد، معمولاً دیتابیس migrate نشده یا seed انجام نشده.</p>
        </div>
    </div>
@endsection
