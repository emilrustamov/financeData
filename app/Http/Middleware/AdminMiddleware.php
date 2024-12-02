<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Проверяем, является ли пользователь администратором
        if (Auth::check() && Auth::user()->is_admin) {
            return $next($request);
        }

        // Если пользователь не администратор, перенаправляем на главную
        return redirect('/')
            ->with('error', 'У вас нет доступа к этой странице');
    }
}
