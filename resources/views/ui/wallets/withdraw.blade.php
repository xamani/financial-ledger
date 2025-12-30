@extends('ui.layout', ['title' => 'کیف‌پول‌ها — برداشت', 'subtitle' => 'معادل Nova Create: برداشت از کیف‌پول'])

@section('content')
    <div class="grid grid--2">
        <div class="card">
            <h3>برداشت</h3>
            <form id="form" class="form">
                <div class="field">
                    <label for="wallet_id">شناسه کیف‌پول (wallet_id)</label>
                    <input id="wallet_id" name="wallet_id" inputmode="numeric" placeholder="مثلاً 1" required>
                </div>
                <div class="field">
                    <label for="amount">مبلغ (amount)</label>
                    <input id="amount" name="amount" inputmode="numeric" placeholder="مثلاً 50000" required>
                </div>
                <div class="field">
                    <label for="description">توضیحات (اختیاری)</label>
                    <input id="description" name="description" placeholder="مثلاً برداشت کاربر">
                </div>
                <div class="btn-row">
                    <button class="btn btn-primary" type="submit">ثبت برداشت</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3>خروجی</h3>
            <div id="pretty" class="kv" style="margin: 12px 0"></div>
            <pre id="raw" class="mono" style="white-space: pre-wrap;">—</pre>
            <div class="btn-row" style="margin-top: 12px">
                <a class="btn" href="{{ route('ui.transactions.index') }}">رفتن به لیست تراکنش‌ها</a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const form = document.getElementById('form');
        const rawEl = document.getElementById('raw');
        const prettyEl = document.getElementById('pretty');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const wallet_id = Number(document.getElementById('wallet_id').value);
            const amount = Number(document.getElementById('amount').value);
            const description = document.getElementById('description').value || null;

            try {
                const body = await apiJson('/api/wallets/withdraw', {
                    method: 'POST',
                    body: JSON.stringify({ wallet_id, amount, description })
                });
                rawEl.textContent = JSON.stringify(body, null, 2);
                const balance = body?.data?.balance ?? null;
                const walletId = body?.data?.wallet_id ?? wallet_id;
                prettyEl.innerHTML = `
                    <div class="row">
                        <div class="k mono">wallet_id</div>
                        <div class="v">${walletId ?? '—'}</div>
                    </div>
                    <div class="row">
                        <div class="k mono">balance</div>
                        <div class="v mono">${formatThousands(balance)}</div>
                    </div>
                `;
                showToast('برداشت ثبت شد', body?.message || 'OK');
            } catch (err) {
                rawEl.textContent = String(err?.message || err);
                prettyEl.innerHTML = '';
                showToast('خطا', String(err?.message || err));
            }
        });
    </script>
@endpush
