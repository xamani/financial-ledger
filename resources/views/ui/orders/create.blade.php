@extends('ui.layout', ['title' => 'سفارش‌ها — ایجاد', 'subtitle' => 'معادل Nova Create: ساخت سفارش pending'])

@section('content')
    <div class="grid grid--2">
        <div class="card">
            <h3>ساخت سفارش</h3>
            <form id="createOrderForm" class="form">
                <div class="field">
                    <label for="user_id">شناسه کاربر (user_id)</label>
                    <input id="user_id" name="user_id" inputmode="numeric" placeholder="مثلاً 1" required>
                    <div class="hint">باید user وجود داشته باشد (factory/seed/test).</div>
                </div>

                <div class="field">
                    <label for="total_amount">مبلغ کل (total_amount)</label>
                    <input id="total_amount" name="total_amount" inputmode="numeric" placeholder="مثلاً 100000" required>
                    <div class="hint">برای خوانایی از جداکننده هزارگان استفاده کنید (مثلاً 100000).</div>
                </div>

                <div class="btn-row">
                    <button class="btn btn-primary" type="submit">ایجاد</button>
                    <button class="btn" type="button" id="resetBtn">ریست</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3>خروجی</h3>
            <div class="hint">بعد از ساخت، لینک نمایش سفارش و اکشن پرداخت فعال می‌شود.</div>
            <pre id="result" class="mono" style="margin-top:12px; white-space: pre-wrap;">—</pre>
            <div style="margin-top: 12px" class="btn-row">
                <a id="showOrderLink" class="btn" href="#" style="display:none">نمایش سفارش</a>
                <button id="payBtn" class="btn" type="button" style="display:none">گرفتن لینک پرداخت</button>
            </div>
            <pre id="payResult" class="mono" style="margin-top:12px; white-space: pre-wrap; display:none">—</pre>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const resultEl = document.getElementById('result');
        const payResultEl = document.getElementById('payResult');
        const showOrderLink = document.getElementById('showOrderLink');
        const payBtn = document.getElementById('payBtn');

        let createdOrderId = null;

        document.getElementById('resetBtn').addEventListener('click', () => {
            document.getElementById('createOrderForm').reset();
            createdOrderId = null;
            resultEl.textContent = '—';
            payResultEl.style.display = 'none';
            showOrderLink.style.display = 'none';
            payBtn.style.display = 'none';
        });

        document.getElementById('createOrderForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const user_id = Number(document.getElementById('user_id').value);
            const total_amount = Number(document.getElementById('total_amount').value);

            try {
                const body = await apiJson('/api/orders', {
                    method: 'POST',
                    body: JSON.stringify({ user_id, total_amount })
                });

                resultEl.textContent = JSON.stringify(body, null, 2);
                createdOrderId = body?.data?.id ?? null;

                if (createdOrderId) {
                    showOrderLink.href = `/ui/orders/${createdOrderId}`;
                    showOrderLink.style.display = 'inline-flex';
                    payBtn.style.display = 'inline-flex';
                }

                showToast('سفارش ایجاد شد', `سفارش #${createdOrderId} ساخته شد.`);
            } catch (err) {
                resultEl.textContent = String(err?.message || err);
                showToast('خطا', String(err?.message || err));
            }
        });

        payBtn.addEventListener('click', async () => {
            if (!createdOrderId) return;
            try {
                const body = await apiJson(`/api/orders/${createdOrderId}/pay`, { method: 'POST' });
                payResultEl.style.display = 'block';
                payResultEl.textContent = JSON.stringify(body, null, 2);
                showToast('لینک پرداخت', 'لینک پرداخت ساخته شد.');
            } catch (err) {
                payResultEl.style.display = 'block';
                payResultEl.textContent = String(err?.message || err);
                showToast('خطا', String(err?.message || err));
            }
        });
    </script>
@endpush
