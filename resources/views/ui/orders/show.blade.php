@extends('ui.layout', ['title' => 'سفارش‌ها — نمایش', 'subtitle' => 'معادل Nova Detail: نمایش تک سفارش + اکشن‌ها'])

@section('content')
    <div class="grid grid--2">
        <div class="card">
            <h3>سفارش #{{ $orderId }}</h3>
            <div class="btn-row" style="margin: 10px 0 14px">
                <button class="btn" type="button" id="refreshBtn">به‌روزرسانی</button>
                <button class="btn" type="button" id="payBtn">پرداخت (Mock)</button>
            </div>
            <div id="statusPills" style="margin-bottom: 12px"></div>
            <div id="kv" class="kv"></div>
        </div>

        <div class="card">
            <h3>Callback (بانک Mock)</h3>
            <p>با این اکشن وضعیت پرداخت را مثل webhook بانک شبیه‌سازی می‌کنیم.</p>
            <div style="margin-top: 12px" class="btn-row">
                <button class="btn btn-primary" type="button" data-status="success">Callback: موفق</button>
                <button class="btn btn-danger" type="button" data-status="failed">Callback: ناموفق</button>
            </div>

            <h3 style="margin-top: 16px">JSON خام</h3>
            <pre id="raw" class="mono" style="white-space: pre-wrap;">—</pre>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const orderId = {{ (int) $orderId }};
        const rawEl = document.getElementById('raw');
        const kvEl = document.getElementById('kv');
        const statusPillsEl = document.getElementById('statusPills');

        function renderKv(data) {
            const entries = [
                ['شناسه (id)', data?.id],
                ['شناسه کاربر (user_id)', data?.user_id],
                ['مبلغ کل (total_amount)', formatThousands(data?.total_amount)],
                ['وضعیت (status)', data?.status],
                ['زمان تکمیل (completed_at)', data?.completed_at],
            ];

            kvEl.innerHTML = entries.map(([k, v]) => `
                <div class="row">
                    <div class="k mono">${k}</div>
                    <div class="v">${v ?? '—'}</div>
                </div>
            `).join('');

            const status = String(data?.status || '');
            let pill = 'pill';
            if (status === 'completed') pill += ' pill--success';
            else if (status === 'cancelled') pill += ' pill--danger';
            else pill += ' pill--warning';

            statusPillsEl.innerHTML = `<span class="${pill}">وضعیت: <span class="mono">${status || '—'}</span></span>`;
        }

        async function loadOrder() {
            const body = await apiJson(`/api/orders/${orderId}`);
            rawEl.textContent = JSON.stringify(body, null, 2);
            renderKv(body?.data);
        }

        document.getElementById('refreshBtn').addEventListener('click', async () => {
            try { await loadOrder(); showToast('به‌روزرسانی شد', `سفارش #${orderId}`); }
            catch (err) { showToast('خطا', String(err?.message || err)); }
        });

        document.getElementById('payBtn').addEventListener('click', async () => {
            try {
                const body = await apiJson(`/api/orders/${orderId}/pay`, { method: 'POST' });
                rawEl.textContent = JSON.stringify(body, null, 2);
                showToast('لینک پرداخت', body?.data?.payment_url || 'OK');
            } catch (err) {
                showToast('خطا', String(err?.message || err));
            }
        });

        document.querySelectorAll('button[data-status]').forEach(btn => {
            btn.addEventListener('click', async () => {
                try {
                    const status = btn.getAttribute('data-status');
                    const body = await apiJson('/api/orders/callback', {
                        method: 'POST',
                        body: JSON.stringify({ order_id: orderId, status })
                    });
                    rawEl.textContent = JSON.stringify(body, null, 2);
                    await loadOrder();
                    showToast('Callback ارسال شد', `status=${status}`);
                } catch (err) {
                    showToast('خطا', String(err?.message || err));
                }
            });
        });

        loadOrder().catch(err => showToast('خطا', String(err?.message || err)));
    </script>
@endpush
