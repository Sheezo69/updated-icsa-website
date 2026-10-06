<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->filteredQuery($request);

        return view('admin.activity.index', [
            'logs' => $query->latest('created_at')->paginate(25)->withQueryString(),
            'filters' => $request->only(['search', 'actor', 'action', 'section', 'date_from', 'date_to']),
            'actors' => AdminActivityLog::query()->select('admin_id', 'actor_name')->distinct()->orderBy('actor_name')->get(),
            'actions' => AdminActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
            'sections' => AdminActivityLog::query()->distinct()->orderBy('section')->pluck('section'),
            'stats' => [
                'total' => AdminActivityLog::query()->count(),
                'today' => AdminActivityLog::query()->whereDate('created_at', today())->count(),
                'staff' => AdminActivityLog::query()->where('actor_role', 'staff')->count(),
                'deletions' => AdminActivityLog::query()->where('action', 'like', '%.deleted')->count(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filteredQuery($request)->oldest('created_at');
        $timezone = $request->attributes->get('currentAdmin')?->timezone ?: 'Asia/Kuwait';

        return response()->streamDownload(function () use ($query, $timezone): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Actor', 'Role', 'Action', 'Section', 'Target', 'Description', 'IP address', 'Browser', 'Before', 'After']);
            $query->chunk(500, function ($logs) use ($handle, $timezone): void {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->created_at?->timezone($timezone)->format('Y-m-d H:i:s'),
                        $log->actor_name, $log->actor_role, $log->action, $log->section,
                        $log->subject_label ?: $log->subject_id, $log->description,
                        $log->ip_address, $log->deviceLabel(),
                        $log->before_values ? json_encode($log->before_values, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '',
                        $log->after_values ? json_encode($log->after_values, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '',
                    ]);
                }
            });
            fclose($handle);
        }, 'admin_activity_'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = AdminActivityLog::query();

        if ($search = trim($request->string('search')->toString())) {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('actor_name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('subject_label', 'like', '%'.$search.'%')
                    ->orWhere('ip_address', 'like', '%'.$search.'%');
            });
        }
        if ($actor = $request->integer('actor')) {
            $query->where('admin_id', $actor);
        }
        if ($action = $request->string('action')->toString()) {
            $query->where('action', $action);
        }
        if ($section = $request->string('section')->toString()) {
            $query->where('section', $section);
        }
        if ($from = $request->string('date_from')->toString()) {
            $query->where('created_at', '>=', $from.' 00:00:00');
        }
        if ($to = $request->string('date_to')->toString()) {
            $query->where('created_at', '<=', $to.' 23:59:59');
        }

        return $query;
    }
}
