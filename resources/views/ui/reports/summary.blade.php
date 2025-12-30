@extends('ui.layout', ['title' => 'گزارش‌ها — خلاصه', 'subtitle' => 'معادل Nova Lens/Index: گزارش تجمیعی بازهٔ زمانی'])

@section('content')
    <div class="grid grid--2">
        <div class="card">
            <h3>بازهٔ زمانی</h3>
            <form id="form" class="form">
                <div class="grid grid--2">
                    <div class="field">
                        <label for="start_date">از تاریخ (start_date)</label>
                        <input id="start_date" name="start_date" type="date" required>
                    </div>
                    <div class="field">
                        <label for="end_date">تا تاریخ (end_date)</label>
                        <input id="end_date" name="end_date" type="date" required>
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn btn-primary" type="submit">بارگذاری</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3>جمع کل</h3>
            <div id="totals" class="kv"></div>
        </div>
    </div>

    <div style="margin-top: 14px" class="card">
        <h3>تفکیک بر اساس نوع</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="mono">type</th>
                        <th>حجم (volume)</th>
                        <th>ورودی (inflow)</th>
                        <th>خروجی (outflow)</th>
                        <th>خالص (net)</th>
                    </tr>
                </thead>
                <tbody id="rows">
                    <tr><td colspan="5" class="mono">—</td></tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const form = document.getElementById('form');
        const totalsEl = document.getElementById('totals');
        const rowsEl = document.getElementById('rows');

        function setDefaultDates() {
            const end = new Date();
            const start = new Date();
            start.setDate(end.getDate() - 30);
            document.getElementById('start_date').value = start.toISOString().slice(0, 10);
            document.getElementById('end_date').value = end.toISOString().slice(0, 10);
        }

        function renderTotals(totals) {
            const entries = [
                ['حجم (volume)', formatThousands(totals?.volume)],
                ['ورودی (inflow)', formatThousands(totals?.inflow)],
                ['خروجی (outflow)', formatThousands(totals?.outflow)],
            ];
            totalsEl.innerHTML = entries.map(([k, v]) => `
                <div class="row">
                    <div class="k mono">${k}</div>
                    <div class="v">${v ?? '0'}</div>
                </div>
            `).join('');
        }

        function renderByType(byType) {
            const entries = Object.entries(byType || {});
            if (!entries.length) {
                rowsEl.innerHTML = `<tr><td colspan="5" class="mono">موردی یافت نشد</td></tr>`;
                return;
            }
            rowsEl.innerHTML = entries.map(([type, v]) => `
                <tr>
                    <td class="mono">${type}</td>
                    <td class="mono">${formatThousands(v.volume)}</td>
                    <td class="mono">${formatThousands(v.inflow)}</td>
                    <td class="mono">${formatThousands(v.outflow)}</td>
                    <td class="mono">${formatThousands(v.net)}</td>
                </tr>
            `).join('');
        }

        async function load() {
            const start_date = document.getElementById('start_date').value;
            const end_date = document.getElementById('end_date').value;
            const body = await apiJson(`/api/financial-reports?start_date=${encodeURIComponent(start_date)}&end_date=${encodeURIComponent(end_date)}`);
            renderTotals(body?.data?.totals || {});
            renderByType(body?.data?.by_type || {});
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            try { await load(); showToast('بارگذاری شد', 'گزارش خلاصه آماده است.'); }
            catch (err) { showToast('خطا', String(err?.message || err)); }
        });

        setDefaultDates();
        load().catch(err => showToast('خطا', String(err?.message || err)));
    </script>
@endpush
