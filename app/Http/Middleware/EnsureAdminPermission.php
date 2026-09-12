<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $admin = $request->attributes->get('currentAdmin');

        if (! $admin instanceof Admin || ! $admin->canAccess($permission)) {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'You do not have permission to access that section.');
        }

        return $next($request);
    }
}
