<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Store Billing')</title>
    <style>
        :root {
            --bg: #f5f7fa; --surface: #ffffff; --surface-alt: #f0f3f8; --ink: #1f2937; --muted: #6b7280;
            --line: #cbd5e1; --header: #1e293b; --primary: #2b6cb0; --success: #15803d;
            --danger: #b91c1c; --danger-bg: #fef2f2; --warn: #9a3412; --warn-bg: #fffbeb; --warn-line: #d97706;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font: 15px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: var(--bg); color: var(--ink); }
        header.top { background: var(--header); color: #fff; padding: 14px 24px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        header.top h1 { margin: 0; font-size: 20px; }
        header.top a { color: #cbd5e1; text-decoration: none; font-size: 14px; }
        main { max-width: 1100px; margin: 0 auto; padding: 24px 16px 48px; }
        h2 { font-size: 17px; margin: 0 0 10px; }
        .grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 24px; align-items: start; }
        @media (max-width: 820px) { .grid { grid-template-columns: 1fr; } }
        .card { background: var(--surface); border: 1px solid var(--line); border-radius: 8px; padding: 16px; margin-bottom: 20px; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 560px) { .row { grid-template-columns: 1fr; } }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; }
        input, select { width: 100%; padding: 8px 10px; border: 1px solid var(--line); border-radius: 6px; font: inherit; background: #fff; color: var(--ink); }
        input[readonly] { background: var(--surface-alt); }
        .hint { font-size: 12px; color: var(--muted); min-height: 18px; margin-top: 2px; }
        .error { font-size: 12px; color: var(--danger); min-height: 0; }
        .error:not(:empty) { margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid var(--line); vertical-align: top; }
        th { background: var(--surface-alt); font-size: 13px; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        .table-wrap { overflow-x: auto; }
        button, .btn { display: inline-block; border: 0; border-radius: 6px; padding: 9px 16px; font: inherit; font-weight: 600; cursor: pointer; text-decoration: none; color: #fff; background: var(--primary); }
        .btn-success { background: var(--success); }
        .btn-ghost { background: transparent; color: var(--muted); padding: 4px 8px; }
        .btn-outline { background: transparent; color: var(--primary); border: 1px solid var(--primary); }
        button:disabled { opacity: .6; cursor: wait; }
        .totals div { display: flex; justify-content: space-between; padding: 4px 0; }
        .totals .grand { font-weight: 700; font-size: 17px; border-top: 1px dashed var(--line); margin-top: 4px; padding-top: 8px; }
        .alert { border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; }
        .alert-danger { background: var(--danger-bg); color: var(--danger); border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: var(--success); border: 1px solid #bbf7d0; }
        .low-stock { background: var(--warn-bg); border-color: var(--warn-line); color: var(--warn); }
        .low-stock ul { margin: 0; padding-left: 18px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .muted { color: var(--muted); }
        @media print { header.top, .no-print { display: none !important; } body { background: #fff; } .card { border: 0; } }
    </style>
</head>
<body>
    <header class="top">
        <h1>Store Billing &mdash; @yield('heading', 'New Order')</h1>
        <a href="{{ route('counter') }}">+ New order</a>
    </header>
    <main>
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
