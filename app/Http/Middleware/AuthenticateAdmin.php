<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $adminId = (int) $request->session()->get('admin_id', 0);
        $admin = $adminId > 0 ? Admin::query()->find($adminId) : null;

        if (! $admin) {
            $request->session()->forget(['admin_id', 'admin_role']);
            $request->session()->put('admin_intended', $request->fullUrl());

            return redirect()->route('admin.login');
        }

        $request->attributes->set('currentAdmin', $admin);
        $request->session()->put('admin_role', $admin->role);
        View::share('currentAdmin', $admin);

        return $next($request);
    }
}
