<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'English Olympic')</title>
    <style>
        :root{--primary:#0b5ed7;--primary-dark:#073b8c;--primary-soft:#eaf3ff;--accent:#f4b400;--ink:#14213d;--muted:#64748b;--line:#dbe4f0;--bg:#f3f6fb;--success:#14804a;--danger:#a61b35;font-family:Inter,"Segoe UI",Arial,sans-serif;color:var(--ink);background:var(--bg)}
        *{box-sizing:border-box} body{margin:0;background:linear-gradient(180deg,#eef4fc 0,#f8fafc 380px);min-height:100vh} body.theme-pet{--primary:#8b1538;--primary-dark:#5d0d25;--primary-soft:#fbeaf0;--accent:#d9a323} a{color:var(--primary);text-decoration:none} h1,h2,h3,p{margin-top:0} button,input,select,textarea{font:inherit}
        .topbar{background:linear-gradient(135deg,var(--primary-dark),var(--primary));color:#fff;padding:15px 28px;display:flex;gap:18px;align-items:center;justify-content:space-between;box-shadow:0 5px 18px rgba(15,23,42,.14)} .topbar a{color:#fff}.brand{font-weight:900;letter-spacing:.07em}.nav{display:flex;gap:14px;align-items:center;flex-wrap:wrap}
        .container{max-width:1180px;margin:26px auto;padding:0 20px}.card{background:#fff;border:1px solid var(--line);border-radius:18px;padding:22px;margin-bottom:18px;box-shadow:0 8px 30px rgba(15,23,42,.06)}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px}.row{display:flex;gap:12px;align-items:center;flex-wrap:wrap}.between{justify-content:space-between}
        input,select,textarea{width:100%;padding:12px 14px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;outline:none;transition:.2s}input:focus,select:focus,textarea:focus{border-color:var(--primary);box-shadow:0 0 0 4px color-mix(in srgb,var(--primary) 14%,transparent)}label{font-weight:700;font-size:14px;display:block;margin-bottom:7px}textarea{min-height:110px}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:11px;padding:11px 17px;font-weight:800;cursor:pointer;background:var(--primary);color:#fff;transition:.18s;box-shadow:0 4px 12px color-mix(in srgb,var(--primary) 22%,transparent)}.btn:hover{transform:translateY(-1px);filter:brightness(1.04)}.btn.secondary{background:#475569}.btn.danger{background:#b42318}.btn.success{background:var(--primary)}.btn.warning{background:#a85d00}.btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
        .alert{padding:13px 16px;border-radius:11px;margin-bottom:16px}.alert.ok{background:#e8f7ee;color:#146c3d}.alert.error{background:#fff0f0;color:#9b1c1c}.badge{display:inline-flex;align-items:center;padding:6px 11px;border-radius:999px;background:var(--primary-soft);color:var(--primary);font-size:12px;font-weight:900;letter-spacing:.04em}.muted{color:var(--muted)}
        table{width:100%;border-collapse:collapse;background:#fff}th,td{border-bottom:1px solid #e8edf3;padding:11px;text-align:left;vertical-align:top}th{background:#f8fafc}.metric{font-size:30px;font-weight:900}.table-wrap{overflow:auto}.pagination{display:flex;justify-content:center;align-items:center;gap:7px;margin:24px 0;flex-wrap:wrap}.page-link{width:40px;height:40px;border:1px solid #dbe4f0;background:#fff;border-radius:9px;display:grid;place-items:center;font-weight:800;color:var(--ink);box-shadow:0 2px 8px rgba(15,23,42,.04)}.page-link:hover{border-color:var(--primary);color:var(--primary)}.page-link.active{background:var(--primary);border-color:var(--primary);color:#fff}.page-link.disabled{opacity:.4;cursor:not-allowed}.admin-nav{position:sticky;top:0;z-index:100;padding:11px 28px}.admin-nav .nav a{padding:9px 12px;border-radius:9px;font-weight:700}.admin-nav .nav a.active{background:rgba(255,255,255,.18)}
        @media(max-width:700px){.topbar{padding:12px 15px}.container{margin:15px auto;padding:0 12px}.card{padding:17px;border-radius:14px}.hide-mobile{display:none}}
    </style>
    @stack('head')
</head>
<body class="@yield('body_class')">
    @hasSection('topbar') @yield('topbar') @else <div class="topbar"><div class="brand">ENGLISH OLYMPIC 2026</div></div> @endif
    <main class="container">
        @if(session('message')) <div class="alert ok">{{ session('message') }}</div> @endif
        @if($errors->any()) <div class="alert error"><strong>Vui lòng kiểm tra:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>