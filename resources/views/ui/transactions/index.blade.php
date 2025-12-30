@extends('ui.layout', ['title' => 'تراکنش‌ها — لیست', 'subtitle' => 'معادل Nova Index: لیست تراکنش‌ها + فیلتر + صفحه‌بندی'])

@section('content')
    <div class="grid grid--2">
        <div class="card">
            <h3>فیلترها</h3>
            <form id="filters" class="form">
                <div class="grid grid--2">
                    <div class="field">
                        <label for="wallet_id">شناسه کیف‌پول (wallet_id)</label>
                        <input id="wallet_id" name="wallet_id" inputmode="numeric" placeholder="اختیاری">
                    </div>
                    <div class="field">
                        <label for="order_id">شناسه سفارش (order_id)</label>
                        <input id="order_id" name="order_id" inputmode="numeric" placeholder="اختیاری">
                    </div>
                </div>

                <div class="grid grid--2">
                    <div class="field">
                        <label for="type">نوع (type)</label>
                        <input id="type" name="type" placeholder="مثلاً platform_commission">
                    </div>
                    <div class="field">
                        <label for="flow">جهت (flow)</label>
                        <select id="flow" name="flow">
                            <option value="">(همه)</option>
                            <option value="in">ورودی (in)</option>
                            <option value="out">خروجی (out)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid--2">
                    <div class="field">
                        <label for="start_date">از تاریخ (start_date)</label>
                        <input id="start_date" name="start_date" type="date">
                    </div>
                    <div class="field">
                        <label for="end_date">تا تاریخ (end_date)</label>
                        <input id="end_date" name="end_date" type="date">
                    </div>
                </div>

                <div class="grid grid--2">
                    <div class="field">
                        <label for="per_page">تعداد در صفحه (per_page)</label>
                        <select id="per_page" name="per_page">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="page">صفحه (page)</label>
                        <input id="page" name="page" inputmode="numeric" value="1">
                    </div>
                </div>

                <div class="btn-row">
                    <button class="btn btn-primary" type="submit">اعمال</button>
                    <button class="btn" type="button" id="reset">ریست</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3>مشخصات صفحه</h3>
            <div id="meta" class="kv"></div>
            <div style="margin-top: 12px" class="btn-row">
                <button class="btn" type="button" id="prev">قبلی</button>
                <button class="btn" type="button" id="next">بعدی</button>
            </div>
        </div>
    </div>

    <div style="margin-top: 14px" class="card">
        <h3>ردیف‌ها</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="mono">id</th>
                        <th class="mono">wallet_id</th>
                        <th class="mono">order_id</th>
                        <th class="mono">type</th>
                        <th class="mono">flow</th>
                        <th>مبلغ</th>
                        <th>توضیحات</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody id="rows">
                    <tr><td colspan="8" class="mono">—</td></tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const form = document.getElementById('filters');
        const rowsEl = document.getElementById('rows');
        const metaEl = document.getElementById('meta');

        function setMeta(meta) {
            const entries = [
                ['current_page', meta?.current_page],
                ['last_page', meta?.last_page],
                ['per_page', meta?.per_page],
                ['total', meta?.total],
            ];
            metaEl.innerHTML = entries.map(([k, v]) => `
                <div class="row">
                    <div class="k mono">${k}</div>
                    <div class="v">${v ?? '—'}</div>
                </div>
            `).join('');
        }

        function toQuery(params) {
            const sp = new URLSearchParams();
            Object.entries(params).forEach(([k, v]) => {
                if (v === null || v === undefined || v === '') return;
                sp.set(k, String(v));
            });
            return sp.toString();
        }

        function formValues() {
            const data = new FormData(form);
            const obj = Object.fromEntries(data.entries());
            return obj;
        }

        async function load() {
            const params = formValues();
            const qs = toQuery(params);
            const body = await apiJson(`/api/transactions?${qs}`);

            const list = body?.data || [];
            if (!list.length) {
                rowsEl.innerHTML = `<tr><td colspan="8" class="mono">موردی یافت نشد</td></tr>`;
            } else {
                rowsEl.innerHTML = list.map(t => `
                    <tr>
                        <td class="mono">${t.id}</td>
                        <td class="mono">${t.wallet_id}</td>
                        <td class="mono">${t.order_id ?? ''}</td>
                        <td class="mono">${t.type}</td>
                        <td class="mono">${t.flow}</td>
                        <td class="mono">${formatThousands(t.amount)}</td>
                        <td>${t.description ?? ''}</td>
                        <td><a class="btn" href="/ui/transactions/${t.id}">نمایش</a></td>
                    </tr>
                `).join('');
            }

            setMeta(body?.meta || {});
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            try { await load(); showToast('بارگذاری شد', 'لیست تراکنش‌ها به‌روزرسانی شد.'); }
            catch (err) { showToast('خطا', String(err?.message || err)); }
        });

        document.getElementById('reset').addEventListener('click', async () => {
            form.reset();
            document.getElementById('per_page').value = '25';
            document.getElementById('page').value = '1';
            try { await load(); showToast('ریست شد', 'فیلترها پاک شد.'); }
            catch (err) { showToast('خطا', String(err?.message || err)); }
        });

        document.getElementById('prev').addEventListener('click', async () => {
            const pageEl = document.getElementById('page');
            const page = Math.max(1, Number(pageEl.value || 1) - 1);
            pageEl.value = String(page);
            try { await load(); }
            catch (err) { showToast('خطا', String(err?.message || err)); }
        });

        document.getElementById('next').addEventListener('click', async () => {
            const pageEl = document.getElementById('page');
            const page = Math.max(1, Number(pageEl.value || 1) + 1);
            pageEl.value = String(page);
            try { await load(); }
            catch (err) { showToast('خطا', String(err?.message || err)); }
        });

        load().catch(err => showToast('خطا', String(err?.message || err)));
    </script>
@endpush
