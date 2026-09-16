<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        return redirect()->route('login', ['portal' => 'admin']);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Thông tin đăng nhập không đúng.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        if (!in_array(Auth::user()->role, ['admin','judge'], true)) {
            Auth::logout();
            return back()->withErrors(['email' => 'Tài khoản không có quyền quản trị.']);
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
