<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Lease Redemption')</title>
    <style>
        :root{
            --bg:#0f172a; --card:#ffffff; --ink:#0f172a; --muted:#64748b;
            --brand:#2563eb; --brand-dark:#1d4ed8; --line:#e2e8f0;
            --ok-bg:#ecfdf5; --ok-ink:#047857; --ok-line:#a7f3d0;
            --err-bg:#fef2f2; --err-ink:#b91c1c; --err-line:#fecaca;
        }
        *{box-sizing:border-box}
        body{
            margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;
            color:var(--ink);
            background:linear-gradient(160deg,#1e293b 0%,#0f172a 60%);
            min-height:100vh;
        }
        .wrap{max-width:920px; margin:0 auto; padding:32px 20px 64px}
        .brand{display:flex; align-items:center; gap:12px; color:#fff; margin-bottom:24px}
        .brand .logo{
            width:40px; height:40px; border-radius:10px; background:var(--brand);
            display:grid; place-items:center; font-weight:700; color:#fff; font-size:18px
        }
        .brand h1{font-size:18px; margin:0; font-weight:600; letter-spacing:.2px}
        .brand small{display:block; color:#94a3b8; font-weight:400; font-size:12px}
        .card{
            background:var(--card); border-radius:16px; padding:28px;
            box-shadow:0 10px 40px rgba(2,6,23,.35); border:1px solid rgba(255,255,255,.06)
        }
        h2{margin:0 0 4px; font-size:22px}
        .sub{color:var(--muted); margin:0 0 22px; font-size:14px}
        label{display:block; font-weight:600; font-size:13px; margin-bottom:6px}
        .hint{color:var(--muted); font-weight:400}
        input[type=text]{
            width:100%; padding:12px 14px; border:1px solid var(--line); border-radius:10px;
            font-size:15px; outline:none; transition:border .15s, box-shadow .15s
        }
        input[type=text]:focus{border-color:var(--brand); box-shadow:0 0 0 3px rgba(37,99,235,.15)}
        .field{margin-bottom:18px}
        .row{display:flex; gap:16px; flex-wrap:wrap}
        .row .field{flex:1; min-width:200px}
        .btn{
            display:inline-flex; align-items:center; gap:8px; cursor:pointer;
            background:var(--brand); color:#fff; border:none; padding:12px 20px;
            border-radius:10px; font-size:15px; font-weight:600; transition:background .15s
        }
        .btn:hover{background:var(--brand-dark)}
        .btn.secondary{background:#fff; color:var(--ink); border:1px solid var(--line)}
        .btn.secondary:hover{background:#f8fafc}
        .btn[disabled]{opacity:.5; cursor:not-allowed}
        .actions{display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-top:8px}
        .alert{padding:12px 16px; border-radius:10px; font-size:14px; margin-bottom:18px; border:1px solid}
        .alert.ok{background:var(--ok-bg); color:var(--ok-ink); border-color:var(--ok-line)}
        .alert.err{background:var(--err-bg); color:var(--err-ink); border-color:var(--err-line)}
        .alert ul{margin:6px 0 0; padding-left:18px}
        table{width:100%; border-collapse:collapse; font-size:14px}
        thead th{
            text-align:left; color:var(--muted); font-size:12px; text-transform:uppercase;
            letter-spacing:.4px; padding:10px 12px; border-bottom:1px solid var(--line)
        }
        tbody td{padding:12px; border-bottom:1px solid var(--line); vertical-align:middle}
        tbody tr:hover{background:#f8fafc}
        .tbl-wrap{overflow-x:auto; border:1px solid var(--line); border-radius:12px}
        .pill{
            display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px;
            font-weight:600; background:var(--ok-bg); color:var(--ok-ink)
        }
        .meta{color:var(--muted); font-size:13px; margin:0 0 16px}
        .money{font-variant-numeric:tabular-nums; font-weight:600}
        input[type=checkbox]{width:18px; height:18px; accent-color:var(--brand); cursor:pointer}
        .selbar{
            display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;
            gap:12px; margin-top:18px
        }
        .count{color:var(--muted); font-size:14px}
        .count b{color:var(--ink)}
        .footer{color:#64748b; text-align:center; font-size:12px; margin-top:28px}
        a.back{color:var(--brand); text-decoration:none; font-size:14px}
        a.back:hover{text-decoration:underline}
        @media (max-width:560px){ .card{padding:20px} thead th:nth-child(4),tbody td:nth-child(4){display:none} }
        .demo-note{margin-top:22px; padding-top:16px; border-top:1px dashed var(--line); font-size:13px; color:var(--muted)}
        .demo-note code{background:#f1f5f9; padding:2px 7px; border-radius:6px; font-size:12px; color:#1e293b}
        .demo-note .codes{display:inline-block; margin:6px 0}
        .demo-note .reset-link{display:block; margin-top:8px; color:var(--brand); text-decoration:none; font-size:13px}
        .demo-note .reset-link:hover{text-decoration:underline}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="brand">
            <div class="logo">LR</div>
            <h1>Lease Redemption Portal<small>Dealer self-service</small></h1>
        </div>

        @if (session('success'))
            <div class="alert ok">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert err">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert err">
                Please fix the following:
                <ul>
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')

        <div class="footer">Lease Redemption MVP &middot; built with Laravel</div>
    </div>
    @yield('scripts')
</body>
</html>
