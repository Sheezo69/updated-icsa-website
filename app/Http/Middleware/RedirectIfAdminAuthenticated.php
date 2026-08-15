<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $adminId = (int) $request->session()->get('admin_id', 0);

        if ($adminId > 0 && Admin::query()->whereKey($adminId)->exists()) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
