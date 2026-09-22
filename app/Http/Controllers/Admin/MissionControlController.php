<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ContactMessage;
use App\Support\AdminActivityLogger;
use App\Support\LeadIntelligence;
use App\Support\MissionControlIntelligence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MissionControlController extends Controller
{
    public function index(Request $request, MissionControlIntelligence $intelligence): View
    {
        $data = $intelligence->dashboard();

        return view('admin.mission-control.index', [
            ...$data,
            'command' => trim($request->string('command')->toString()),
            'commandResult' => $intelligence->command($request->string('command')->toString(), $data),
        ]);
    }

    public function snapshot(MissionControlIntelligence $intelligence): JsonResponse
    {
        $data = $intelligence->dashboard();

        return response()->json([
            'stats' => $data['stats'],
            'alerts' => $data['alerts']->take(4)->values(),
            'feed' => $data['feed']->take(10)->map(fn (array $item): array => [
                ...$item,
                'time' => $item['time']?->toIso8601String(),
                'ago' => $item['time']?->diffForHumans(),
            ])->values(),
            'journeys' => $data['journeys']->take(6)->map(fn (array $journey): array => [
                ...$journey,
                'last_seen' => $journey['last_seen']?->toIso8601String(),
                'ago' => $journey['last_seen']?->diffForHumans(),
            ])->values(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function automate(
        Request $request,
        LeadIntelligence $leadIntelligence,
        AdminActivityLogger $audit,
    ): RedirectResponse {
        $data = $request->validate(['action' => ['required', 'in:balance_workload,recalculate_scores']]);

        if ($data['action'] === 'recalculate_scores') {
            $updated = 0;
            ContactMessage::query()->whereIn('status', [ContactMessage::STATUS_NEW, ContactMessage::STATUS_IN_PROGRESS])
                ->orderBy('id')->chunkById(100, function ($inquiries) use ($leadIntelligence, &$updated): void {
                    foreach ($inquiries as $inquiry) {
                        $leadIntelligence->refresh($inquiry);
                        $updated++;
                    }
                });
            $audit->record($request, 'mission.leads_rescored', 'mission_control', 'Recalculated active lead intelligence scores.', ContactMessage::class, null, $updated.' active leads', null, ['updated' => $updated]);

            return back()->with('success', 'Intelligence scores refreshed for '.$updated.' active leads.');
        }

        $staff = Admin::query()->where('role', Admin::ROLE_STAFF)->orderBy('id')->get();
        if ($staff->isEmpty()) {
            return back()->with('error', 'Create at least one staff account before balancing workload.');
        }

        $loads = $staff->mapWithKeys(fn (Admin $member): array => [
            $member->id => ContactMessage::query()->where('assigned_to', $member->id)->whereIn('status', [ContactMessage::STATUS_NEW, ContactMessage::STATUS_IN_PROGRESS])->count(),
        ])->all();
        $assigned = 0;
        DB::transaction(function () use (&$loads, &$assigned, $request): void {
            ContactMessage::query()->whereNull('assigned_to')->whereIn('status', [ContactMessage::STATUS_NEW, ContactMessage::STATUS_IN_PROGRESS])
                ->oldest('created_at')->lockForUpdate()->get()->each(function (ContactMessage $inquiry) use (&$loads, &$assigned, $request): void {
                    asort($loads);
                    $staffId = (int) array_key_first($loads);
                    $inquiry->update(['assigned_to' => $staffId, 'updated_by' => (int) $request->session()->get('admin_id')]);
                    $loads[$staffId]++;
                    $assigned++;
                });
        });
        $audit->record($request, 'mission.workload_balanced', 'mission_control', 'Distributed unassigned active inquiries across staff.', ContactMessage::class, null, $assigned.' inquiries', null, ['assigned' => $assigned, 'final_loads' => $loads]);

        return back()->with('success', $assigned > 0 ? 'Balanced '.$assigned.' inquiries across the staff team.' : 'Workload is already balanced—nothing was unassigned.');
    }
}
