@extends('layouts.app')
@section('title','Đăng nhập thí sinh')
@push('head')
<style>
.login-shell{max-width:980px;margin:42px auto;display:grid;grid-template-columns:1.08fr .92fr;overflow:hidden;padding:0}.login-brand{padding:42px;background:linear-gradient(145deg,#073b8c,#0b67dc);color:#fff;position:relative}.login-brand:after{content:"";position:absolute;width:220px;height:220px;border-radius:50%;background:#8b1538;right:-100px;bottom:-110px;opacity:.9}.logo-mark{width:62px;height:62px;border-radius:18px;background:#fff;color:#0b5ed7;display:grid;place-items:center;font-size:30px;font-weight:900;margin-bottom:28px}.level-line{display:flex;gap:10px;margin-top:28px}.level-chip{padding:8px 13px;border-radius:999px;font-weight:900;background:rgba(255,255,255,.16)}.level-chip.pet{background:#8b1538}.login-form{padding:42px;background:#fff}.login-form .btn{width:100%;padding:14px;margin-top:5px}.field{margin-bottom:17px}.security-note{font-size:13px;color:#64748b;margin-top:20px;padding-top:16px;border-top:1px solid #e2e8f0}@media(max-width:760px){.login-shell{grid-template-columns:1fr;margin:12px auto}.login-brand,.login-form{padding:27px}.login-brand:after{display:none}}
</style>
@endpush
@section('content')
<div class="card login-shell">
    <section class="login-brand">
        <div class="logo-mark">E</div>
        <div style="font-size:13px;font-weight:800;letter-spacing:.16em;opacity:.85">ENGLISH OLYMPIC 2026</div>
        <h1 style="font-size:38px;line-height:1.12;margin:12px 0">Sẵn sàng<br>chinh phục tiếng Anh?</h1>
        <p style="opacity:.88;line-height:1.7">Hệ thống thi trực tuyến dành cho thí sinh KET và PET. Đăng nhập bằng số báo danh và mã PIN do Ban tổ chức cấp.</p>
        <div class="level-line"><span class="level-chip">KET · BLUE</span><span class="level-chip pet">PET · BURGUNDY</span></div>
    </section>
    <section class="login-form">
        <span class="badge">CANDIDATE PORTAL</span>
        <h2 style="font-size:28px;margin:15px 0 8px">Đăng nhập dự thi</h2>
        <p class="muted" style="margin-bottom:25px">Nhập chính xác thông tin trên thẻ dự thi của bạn.</p>
        <form method="post" action="{{ route('candidate.login.submit') }}">@csrf
            <div class="field"><label for="candidate_code">Số báo danh</label><input id="candidate_code" name="candidate_code" value="{{ old('candidate_code') }}" placeholder="Ví dụ: KET001 hoặc PET001" required autofocus autocomplete="username"></div>
            <div class="field"><label for="pin">Mã PIN</label><input id="pin" type="password" name="pin" placeholder="Nhập mã PIN" required autocomplete="current-password"></div>
            <button class="btn" type="submit">Đăng nhập và kiểm tra thông tin →</button>
        </form>
        <div class="security-note">🔒 Không chia sẻ mã PIN. Nếu thông tin sai, hãy báo ngay cho giám thị.</div>
    </section>
</div>
@endsection