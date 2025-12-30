@extends('ui.layout', ['title' => 'تراکنش‌ها — نمایش', 'subtitle' => 'معادل Nova Detail: نمایش تک تراکنش'])

@section('content')
    <div class="grid grid--2">
        <div class="card">
            <h3>تراکنش #{{ $transactionId }}</h3>
            <div class="btn-row" style="margin: 10px 0 14px">
                <a class="btn" href="{{ route('ui.transactions.index') }}">بازگشت به لیست</a>
                <button class="btn" type="button" id="refreshBtn">به‌روزرسانی</button>
            </div>
            <div id="kv" class="kv"></div>
        </div>

        <div class="card">
            <h3>JSON خام</h3>
            <pre id="raw" class="mono" style="white-space: pre-wrap;">—</pre>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const transactionId = {{ (int) $transactionId }};
        const rawEl = document.getElementById('raw');
        const kvEl = document.getElementById('kv');

        function renderKv(t) {
            const entries = [
                ['شناسه (id)', t?.id],
                ['شناسه کیف‌پول (wallet_id)', t?.wallet_id],
                ['شناسه سفارش (order_id)', t?.order_id],
                ['نوع (type)', t?.type],
                ['جهت (flow)', t?.flow],
                ['مبلغ (amount)', formatThousands(t?.amount)],
                ['توضیحات (description)', t?.description],
                ['نام کیف‌پول (wallet.name)', t?.wallet?.name],
                ['موجودی کیف‌پول (wallet.balance)', formatThousands(t?.wallet?.balance)],
            ];

            kvEl.innerHTML = entries.map(([k, v]) => `
                <div class="row">
                    <div class="k mono">${k}</div>
                    <div class="v">${v ?? '—'}</div>
                </div>
            `).join('');
        }

        async function loadTransaction() {
            const body = await apiJson(`/api/transactions/${transactionId}`);
            rawEl.textContent = JSON.stringify(body, null, 2);
            renderKv(body?.data);
        }

        document.getElementById('refreshBtn').addEventListener('click', async () => {
            try { await loadTransaction(); showToast('به‌روزرسانی شد', `تراکنش #${transactionId}`); }
            catch (err) { showToast('خطا', String(err?.message || err)); }
        });

        loadTransaction().catch(err => showToast('خطا', String(err?.message || err)));
    </script>
@endpush
