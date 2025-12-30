<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ ($title ?? 'UI').' — '.config('app.name', 'Laravel') }}</title>

        <style>
            :root {
                --bg: #f6f7fb;
                --surface: #ffffff;
                --border: rgba(15, 23, 42, 0.08);
                --text: #0f172a;
                --muted: rgba(15, 23, 42, 0.65);
                --primary: #5a67d8;
                --primary-600: #4c51bf;
                --danger: #e11d48;
                --success: #16a34a;
                --warning: #f59e0b;
                --shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
                --radius: 14px;
                --mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
                background: var(--bg);
                color: var(--text);
            }
            a { color: inherit; text-decoration: none; }
            .app { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
            .sidebar {
                position: sticky; top: 0;
                height: 100vh;
                background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
                color: rgba(255,255,255,0.9);
                padding: 18px 16px;
                border-left: 1px solid rgba(255,255,255,0.06);
            }
            .brand { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 10px 16px; }
            .brand__title { font-weight: 700; letter-spacing: -0.02em; }
            .brand__badge { font-size: 12px; color: rgba(255,255,255,0.75); background: rgba(255,255,255,0.08); padding: 4px 10px; border-radius: 999px; }
            .nav { display: grid; gap: 6px; padding: 6px; }
            .nav a {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px 12px;
                border-radius: 12px;
                color: rgba(255,255,255,0.85);
                background: transparent;
                border: 1px solid rgba(255,255,255,0.06);
            }
            .nav a:hover { background: rgba(255,255,255,0.06); }
            .nav a[aria-current="page"] {
                background: rgba(90, 103, 216, 0.25);
                border-color: rgba(90, 103, 216, 0.5);
                color: #fff;
            }
            .nav small { color: rgba(255,255,255,0.6); font-size: 12px; }

            .content { padding: 26px 26px 40px; }
            .topbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
            .title { font-size: 22px; font-weight: 800; letter-spacing: -0.02em; }
            .subtitle { margin-top: 6px; color: var(--muted); font-size: 13px; }

            .grid { display: grid; gap: 14px; }
            .grid--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .grid--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            @media (max-width: 1024px) { .app { grid-template-columns: 1fr; } .sidebar { position: relative; height: auto; } .grid--2, .grid--3 { grid-template-columns: 1fr; } }

            .card {
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: var(--radius);
                box-shadow: var(--shadow);
                padding: 16px;
            }
            .card h3 { margin: 0 0 8px; font-size: 14px; color: rgba(15, 23, 42, 0.78); }
            .card p { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.8; }

            .btn {
                appearance: none;
                border: 1px solid var(--border);
                background: #fff;
                color: var(--text);
                padding: 10px 12px;
                border-radius: 12px;
                font-weight: 600;
                cursor: pointer;
            }
            .btn:hover { border-color: rgba(15, 23, 42, 0.18); }
            .btn-primary { background: var(--primary); color: #fff; border-color: rgba(90, 103, 216, 0.6); }
            .btn-primary:hover { background: var(--primary-600); }
            .btn-danger { background: var(--danger); color: #fff; border-color: rgba(225, 29, 72, 0.6); }
            .btn-ghost { background: transparent; }
            .btn-row { display: flex; gap: 10px; flex-wrap: wrap; }

            .form { display: grid; gap: 12px; }
            .field { display: grid; gap: 6px; }
            label { font-size: 12px; color: rgba(15, 23, 42, 0.75); }
            input, select, textarea {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid rgba(15, 23, 42, 0.12);
                border-radius: 12px;
                background: #fff;
                font: inherit;
                outline: none;
            }
            input:focus, select:focus, textarea:focus { border-color: rgba(90, 103, 216, 0.6); box-shadow: 0 0 0 4px rgba(90, 103, 216, 0.14); }
            .hint { font-size: 12px; color: rgba(15, 23, 42, 0.55); }

            .table-wrap { overflow: auto; border-radius: 12px; border: 1px solid var(--border); }
            table { width: 100%; border-collapse: collapse; background: #fff; }
            th, td { padding: 10px 12px; border-bottom: 1px solid rgba(15, 23, 42, 0.06); text-align: right; vertical-align: top; }
            th { font-size: 12px; color: rgba(15, 23, 42, 0.65); background: rgba(15, 23, 42, 0.02); }
            td { font-size: 13px; }
            .mono { font-family: var(--mono); font-size: 12px; }
            .pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; border: 1px solid rgba(15, 23, 42, 0.10); background: rgba(15, 23, 42, 0.02); font-size: 12px; }
            .pill--success { border-color: rgba(22, 163, 74, 0.25); background: rgba(22, 163, 74, 0.08); color: var(--success); }
            .pill--danger { border-color: rgba(225, 29, 72, 0.25); background: rgba(225, 29, 72, 0.08); color: var(--danger); }
            .pill--warning { border-color: rgba(245, 158, 11, 0.25); background: rgba(245, 158, 11, 0.10); color: #b45309; }
            .kv { display: grid; gap: 10px; }
            .kv .row { display: grid; grid-template-columns: 160px 1fr; gap: 10px; padding: 10px 12px; border: 1px solid rgba(15, 23, 42, 0.08); border-radius: 12px; background: rgba(255,255,255,0.65); }
            .kv .k { color: rgba(15, 23, 42, 0.65); font-size: 12px; }
            .kv .v { font-weight: 650; }

            .toast {
                position: fixed;
                inset-inline-start: 18px;
                bottom: 18px;
                min-width: 280px;
                max-width: 420px;
                background: #0f172a;
                color: #fff;
                border: 1px solid rgba(255,255,255,0.10);
                padding: 12px 14px;
                border-radius: 14px;
                box-shadow: 0 20px 50px rgba(15, 23, 42, 0.35);
                display: none;
            }
            .toast strong { display: block; font-size: 13px; margin-bottom: 4px; }
            .toast p { margin: 0; color: rgba(255,255,255,0.80); font-size: 12px; line-height: 1.7; }
            .toast.show { display: block; }
        </style>
    </head>
    <body>
        <div class="app">
            <aside class="sidebar">
                <div class="brand">
                    <div class="brand__title">پنل تست</div>
                    <div class="brand__badge">UI</div>
                </div>
                <nav class="nav">
                    <a href="{{ route('ui.dashboard') }}" aria-current="{{ request()->routeIs('ui.dashboard') ? 'page' : 'false' }}">
                        <span>داشبورد</span>
                        <small>خانه</small>
                    </a>
                    <a href="{{ route('ui.orders.create') }}" aria-current="{{ request()->routeIs('ui.orders.*') ? 'page' : 'false' }}">
                        <span>سفارش‌ها</span>
                        <small>ایجاد / نمایش</small>
                    </a>
                    <a href="{{ route('ui.transactions.index') }}" aria-current="{{ request()->routeIs('ui.transactions.*') ? 'page' : 'false' }}">
                        <span>تراکنش‌ها</span>
                        <small>لیست / نمایش</small>
                    </a>
                    <a href="{{ route('ui.reports.summary') }}" aria-current="{{ request()->routeIs('ui.reports.*') ? 'page' : 'false' }}">
                        <span>گزارش‌ها</span>
                        <small>لیست</small>
                    </a>
                    <a href="{{ route('ui.wallets.withdraw') }}" aria-current="{{ request()->routeIs('ui.wallets.*') ? 'page' : 'false' }}">
                        <span>کیف‌پول‌ها</span>
                        <small>ایجاد</small>
                    </a>
                </nav>
            </aside>

            <main class="content">
                <div class="topbar">
                    <div>
                        <div class="title">{{ $title ?? 'UI' }}</div>
                        @isset($subtitle)
                            <div class="subtitle">{{ $subtitle }}</div>
                        @else
                            <div class="subtitle">صفحات تست API با دیزاین مشابه Laravel Nova (create / list / show)</div>
                        @endisset
                    </div>
                    <div class="btn-row">
                        <a class="btn btn-ghost" href="/api/documentation" target="_blank" rel="noreferrer">Swagger (مستندات)</a>
                        <a class="btn" href="/" target="_blank" rel="noreferrer">صفحهٔ اصلی</a>
                    </div>
                </div>

                @yield('content')
            </main>
        </div>

        <div id="toast" class="toast" role="status" aria-live="polite"></div>

        <script>
            function showToast(title, message) {
                const el = document.getElementById('toast');
                el.innerHTML = `<strong>${title}</strong><p>${message}</p>`;
                el.classList.add('show');
                window.clearTimeout(window.__toastTimer);
                window.__toastTimer = window.setTimeout(() => el.classList.remove('show'), 4500);
            }

            function formatThousands(value) {
                if (value === null || value === undefined) return '—';
                const raw = String(value).trim();
                if (!raw) return '—';
                const negative = raw.startsWith('-');
                const digits = raw.replace(/[^0-9]/g, '');
                if (!digits) return raw;
                const withCommas = digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                return negative ? `-${withCommas}` : withCommas;
            }

            async function apiJson(url, options = {}) {
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        ...(options.headers || {}),
                    },
                    ...options,
                });

                let body = null;
                try { body = await res.json(); } catch (e) { body = null; }
                if (!res.ok) {
                    const message = body?.message || `HTTP ${res.status}`;
                    const errors = body?.errors ? JSON.stringify(body.errors) : null;
                    throw new Error(errors ? `${message} — ${errors}` : message);
                }
                return body;
            }
        </script>

        @stack('scripts')
    </body>
</html>
