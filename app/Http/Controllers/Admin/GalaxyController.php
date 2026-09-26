<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ContactMessage;
use App\Support\AdminActivityLogger;
use App\Support\CourseFileRepository;
use App\Support\GalaxyIntelligence;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class GalaxyController extends Controller
{
    public function index(GalaxyIntelligence $galaxy): View
    {
        $snapshot = $galaxy->snapshot();

        return view('admin.galaxy.index', [
            'galaxy' => $snapshot,
            'stats' => ['total' => ContactMessage::query()->count()],
        ]);
    }

    public function snapshot(GalaxyIntelligence $galaxy): JsonResponse
    {
        return response()->json($galaxy->snapshot());
    }

    public function act(
        Request $request,
        ContactMessage $inquiry,
        CourseFileRepository $courses,
        GalaxyIntelligence $galaxy,
        AdminActivityLogger $audit,
    ): JsonResponse {
        $data = $request->validate([
            'action' => ['required', Rule::in(['assign_staff', 'qualify_course'])],
            'staff_id' => [
                'nullable', 'required_if:action,assign_staff', 'integer',
                Rule::exists('admins', 'id')->where('role', Admin::ROLE_STAFF),
            ],
            'course_slug' => ['nullable', 'required_if:action,qualify_course', 'string', 'max:160'],
        ]);

        $before = [
            'assigned_to' => $inquiry->assigned_to,
            'course' => $inquiry->course_interest,
            'pipeline_stage' => $inquiry->pipeline_stage,
            'status' => $inquiry->status,
        ];
        $updates = ['updated_by' => (int) $request->session()->get('admin_id')];
        $description = '';

        if ($data['action'] === 'assign_staff') {
            $member = Admin::query()->where('role', Admin::ROLE_STAFF)->findOrFail((int) $data['staff_id']);
            $updates['assigned_to'] = $member->id;
            $description = 'Assigned an inquiry to '.$member->username.' from Galaxy Mode.';
        } else {
            $course = $courses->find((string) $data['course_slug']);
            abort_if($course === null, 422, 'That course no longer exists.');
            $updates['course_interest'] = $course['title'];
            $updates['status'] = ContactMessage::STATUS_IN_PROGRESS;
            if (Schema::hasColumn('contact_messages', 'pipeline_stage')) {
                $updates['pipeline_stage'] = 'qualified';
            }
            if (Schema::hasColumn('contact_messages', 'pipeline_moved_at')) {
                $updates['pipeline_moved_at'] = now();
            }
            $description = 'Qualified an inquiry for '.$course['title'].' from Galaxy Mode.';
        }

        DB::transaction(fn () => $inquiry->update($updates));
        $after = [
            'assigned_to' => $inquiry->assigned_to,
            'course' => $inquiry->course_interest,
            'pipeline_stage' => $inquiry->pipeline_stage,
            'status' => $inquiry->status,
        ];
        $audit->record($request, 'galaxy.'.$data['action'], 'galaxy', $description, ContactMessage::class, $inquiry->id, '#'.$inquiry->id.' '.$inquiry->name, $before, $after);

        return response()->json([
            'ok' => true,
            'message' => $description,
            'snapshot' => $galaxy->snapshot(),
        ]);
    }
}
