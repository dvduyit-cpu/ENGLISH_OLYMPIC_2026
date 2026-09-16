<?php

use App\Http\Middleware\AdminRole;
use App\Http\Middleware\CandidateSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('admin.login'));

        $middleware->alias([
            'candidate.session' => CandidateSession::class,
            'admin.role' => AdminRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419) return null;
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.'], 419);
            }
            $portal = $request->is('admin/*') ? 'admin' : 'candidate';
            return redirect()->route('login', $portal === 'admin' ? ['portal' => 'admin'] : [])
                ->withErrors(['login' => 'Phiên đăng nhập đã hết hạn. Vui lòng nhập lại thông tin.']);
        });
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.'], 419);
            }
            $portal = $request->is('admin/*') ? 'admin' : 'candidate';
            return redirect()->route('login', $portal === 'admin' ? ['portal' => 'admin'] : [])
                ->withErrors(['login' => 'Phiên đăng nhập đã hết hạn. Vui lòng nhập lại thông tin.']);
        });
    })->create();

