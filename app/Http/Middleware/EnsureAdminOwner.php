<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->attributes->get('currentAdmin');

        if (! $admin instanceof Admin || ! $admin->isOwner()) {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Administrator access is required for that page.');
        }

        return $next($request);
    }
}
