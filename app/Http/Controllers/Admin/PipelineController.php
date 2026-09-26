<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ContactMessage;
use App\Support\AdminActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PipelineController extends Controller
{
    private const STAGES = [
        'new_lead' => ['label' => 'New Leads', 'note' => 'Fresh inquiries to review', 'icon' => 'fa-sparkles', 'tone' => 'cyan'],
        'contacted' => ['label' => 'Contacted', 'note' => 'Conversation started', 'icon' => 'fa-phone-volume', 'tone' => 'blue'],
        'qualified' => ['label' => 'Qualified', 'note' => 'Ready for enrollment', 'icon' => 'fa-bullseye', 'tone' => 'violet'],
        'enrolled' => ['label' => 'Enrolled', 'note' => 'Successfully converted', 'icon' => 'fa-circle-check', 'tone' => 'green'],
    ];

    public function index(): View
    {
        $data = $this->pipelineData();

        return view('admin.inquiries.pipeline', $data + [
            'stats' => ['total' => ContactMessage::query()->count()],
        ]);
    }

    public function snapshot(): JsonResponse
    {
        $data = $this->pipelineData();

        return response()->json([
            'version' => $data['version'],
            'board_html' => view('admin.inquiries.partials.pipeline-board', $data)->render(),
            'workload_html' => view('admin.inquiries.partials.pipeline-workload', $data)->render(),
        ]);
    }

    public function move(Request $request, ContactMessage $inquiry, AdminActivityLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'stage' => ['required', 'string', 'in:'.implode(',', array_keys(self::STAGES))],
        ]);
        $stage = $data['stage'];
        $before = [
            'pipeline_stage' => $inquiry->pipeline_stage ?: 'new_lead',
            'status' => $inquiry->status,
        ];

        $inquiry->update([
            'pipeline_stage' => $stage,
            'pipeline_moved_at' => now(),
            'status' => $this->statusForStage($stage),
            'updated_by' => (int) $request->session()->get('admin_id'),
        ]);

        $audit->record(
            $request,
            'inquiry.pipeline_moved',
            'inquiries',
            'Moved an inquiry through the enrollment pipeline.',
            ContactMessage::class,
            $inquiry->id,
            '#'.$inquiry->id.' '.$inquiry->name,
            $before,
            ['pipeline_stage' => $stage, 'status' => $inquiry->status],
        );

        return response()->json([
            'ok' => true,
            'message' => $inquiry->name.' moved to '.self::STAGES[$stage]['label'].'.',
            'version' => $this->version(),
        ]);
    }

    private function pipelineData(): array
    {
        $inquiries = ContactMessage::query()
            ->with('assignedTo:id,username,email')
            ->where('status', '!=', ContactMessage::STATUS_ARCHIVED)
            ->latest('created_at')
            ->get();

        $pipeline = collect(self::STAGES)->mapWithKeys(function (array $stage, string $key) use ($inquiries): array {
            return [$key => $inquiries->filter(fn (ContactMessage $inquiry): bool => ($inquiry->pipeline_stage ?: 'new_lead') === $key)->values()];
        });

        $staff = Admin::query()
            ->where('role', Admin::ROLE_STAFF)
            ->orderBy('username')
            ->get(['id', 'username', 'email', 'avatar_path'])
            ->map(function (Admin $member) use ($inquiries): array {
                $assigned = $inquiries->where('assigned_to', $member->id);
                $active = $assigned->filter(fn (ContactMessage $inquiry): bool => in_array($inquiry->pipeline_stage ?: 'new_lead', ['new_lead', 'contacted', 'qualified'], true));

                return [
                    'id' => $member->id,
                    'name' => $member->username,
                    'email' => $member->email,
                    'avatar' => $member->avatar_path,
                    'active' => $active->count(),
                    'total' => $assigned->count(),
                    'qualified' => $assigned->where('pipeline_stage', 'qualified')->count(),
                    'enrolled' => $assigned->where('pipeline_stage', 'enrolled')->count(),
                ];
            });

        return [
            'stages' => self::STAGES,
            'pipeline' => $pipeline,
            'staffWorkload' => $staff,
            'unassignedCount' => $inquiries->whereNull('assigned_to')->filter(fn (ContactMessage $inquiry): bool => in_array($inquiry->pipeline_stage ?: 'new_lead', ['new_lead', 'contacted', 'qualified'], true))->count(),
            'version' => $this->version($inquiries),
        ];
    }

    private function version(?Collection $inquiries = null): string
    {
        $inquiries ??= ContactMessage::query()->where('status', '!=', ContactMessage::STATUS_ARCHIVED)->get(['id', 'pipeline_stage', 'pipeline_moved_at', 'assigned_to']);

        return sha1($inquiries->map(fn (ContactMessage $inquiry): string => implode(':', [
            $inquiry->id,
            $inquiry->pipeline_stage,
            optional($inquiry->pipeline_moved_at)->format('Y-m-d H:i:s.u'),
            $inquiry->assigned_to,
        ]))->implode('|'));
    }

    private function statusForStage(string $stage): string
    {
        return match ($stage) {
            'contacted', 'qualified' => ContactMessage::STATUS_IN_PROGRESS,
            'enrolled' => ContactMessage::STATUS_RESOLVED,
            default => ContactMessage::STATUS_NEW,
        };
    }
}
