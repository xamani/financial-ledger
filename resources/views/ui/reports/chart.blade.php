@extends('ui.layout', ['title' => 'گزارش‌ها — نمودار', 'subtitle' => 'دادهٔ سری زمانی (روز/ماه) مثل Nova metrics'])

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
                <div class="field">
                    <label for="granularity">دانه‌بندی (granularity)</label>
                    <select id="granularity" name="granularity">
                        <option value="day" selected>روزانه (day)</option>
                        <option value="month">ماهانه (month)</option>
                    </select>
                </div>
                <div class="btn-row">
                    <button class="btn btn-primary" type="submit">بارگذاری</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3>راهنما</h3>
            <p>خروجی این endpoint برای رسم نمودار در فرانت آماده است. اینجا جدول می‌بینید (برای سادگی).</p>
        </div>
    </div>

    <div style="margin-top: 14px" class="card">
        <h3>سری زمانی</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="mono">period</th>
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
        const rowsEl = document.getElementById('rows');

        function setDefaultDates() {
            const end = new Date();
            const start = new Date();
            start.setDate(end.getDate() - 30);
            document.getElementById('start_date').value = start.toISOString().slice(0, 10);
            document.getElementById('end_date').value = end.toISOString().slice(0, 10);
        }

        function renderSeries(series) {
            if (!series?.length) {
                rowsEl.innerHTML = `<tr><td colspan="5" class="mono">موردی یافت نشد</td></tr>`;
                return;
            }
            rowsEl.innerHTML = series.map(r => `
                <tr>
                    <td class="mono">${r.period}</td>
                    <td class="mono">${formatThousands(r.volume)}</td>
                    <td class="mono">${formatThousands(r.inflow)}</td>
                    <td class="mono">${formatThousands(r.outflow)}</td>
                    <td class="mono">${formatThousands(r.net)}</td>
                </tr>
            `).join('');
        }

        async function load() {
            const start_date = document.getElementById('start_date').value;
            const end_date = document.getElementById('end_date').value;
            const granularity = document.getElementById('granularity').value;
            const body = await apiJson(`/api/financial-reports/chart?start_date=${encodeURIComponent(start_date)}&end_date=${encodeURIComponent(end_date)}&granularity=${encodeURIComponent(granularity)}`);
            renderSeries(body?.data?.series || []);
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            try { await load(); showToast('بارگذاری شد', 'سری زمانی آماده است.'); }
            catch (err) { showToast('خطا', String(err?.message || err)); }
        });

        setDefaultDates();
        load().catch(err => showToast('خطا', String(err?.message || err)));
    </script>
@endpush
