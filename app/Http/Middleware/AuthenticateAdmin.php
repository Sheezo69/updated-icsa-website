<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\AdminMessage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
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

        $timezone = array_key_exists((string) $admin->timezone, Admin::TIMEZONES) ? $admin->timezone : 'Asia/Kuwait';
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
        app()->setLocale(array_key_exists((string) $admin->language, Admin::LANGUAGES) ? $admin->language : 'en');

        View::share('currentAdmin', $admin);
        View::share('adminUnreadMessages', ($admin->notify_messages ?? true) && Schema::hasTable('admin_messages') && Schema::hasTable('admin_conversations')
            ? AdminMessage::query()
                ->whereNull('read_at')
                ->where('sender_id', '!=', $admin->id)
                ->whereHas('conversation', fn ($query) => $query->forAdmin($admin->id))
                ->count()
            : 0);

        return $next($request);
    }
}
