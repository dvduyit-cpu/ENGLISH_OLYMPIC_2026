@extends('layouts.app')
@section('title','Đăng nhập Admin')
@section('content')
<div class="card" style="max-width:480px;margin:50px auto">
    <h1>Đăng nhập Ban tổ chức</h1>
    <form method="post" action="{{ route('admin.login.submit') }}">@csrf
        <div style="margin-bottom:14px"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required autofocus></div>
        <div style="margin-bottom:14px"><label>Mật khẩu</label><input type="password" name="password" required></div>
        <label style="font-weight:400"><input type="checkbox" name="remember" value="1" style="width:auto"> Ghi nhớ đăng nhập</label>
        <div style="margin-top:16px"><button class="btn" type="submit">Đăng nhập</button></div>
    </form>
</div>
@endsection
