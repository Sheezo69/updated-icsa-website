<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ContactMessage;
use App\Support\AdminActivityLogger;
use App\Support\InquiryMailer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InquiryController extends Controller
{
    public function index(Request $request): View
    {
        $currentAdmin = $request->attributes->get('currentAdmin');
        $query = ContactMessage::query()->with(['emailAttempts', 'assignedTo']);

        $this->applyAssignmentFilter($query, $request, $currentAdmin);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = trim($request->string('search')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('message', 'like', '%'.$search.'%');
            });
        }

        if ($course = $request->string('course')->toString()) {
            $query->where('course_interest', $course);
        }

        if ($dateFrom = $request->string('date_from')->toString()) {
            $query->where('created_at', '>=', $dateFrom.' 00:00:00');
        }

        if ($dateTo = $request->string('date_to')->toString()) {
            $query->where('created_at', '<=', $dateTo.' 23:59:59');
        }

        $inquiries = $query
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => ContactMessage::query()->count(),
            ContactMessage::STATUS_NEW => ContactMessage::query()->where('status', ContactMessage::STATUS_NEW)->count(),
            ContactMessage::STATUS_IN_PROGRESS => ContactMessage::query()->where('status', ContactMessage::STATUS_IN_PROGRESS)->count(),
            ContactMessage::STATUS_RESOLVED => ContactMessage::query()->where('status', ContactMessage::STATUS_RESOLVED)->count(),
            'assigned_to_me' => ContactMessage::query()->where('assigned_to', $currentAdmin->id)->count(),
            'unassigned' => ContactMessage::query()->whereNull('assigned_to')->count(),
        ];

        return view('admin.inquiries.index', [
            'inquiries' => $inquiries,
            'stats' => $stats,
            'filters' => $request->only(['status', 'course', 'search', 'date_from', 'date_to', 'assignment']),
            'courses' => ContactMessage::query()
                ->whereNotNull('course_interest')
                ->where('course_interest', '!=', '')
                ->distinct()
                ->orderBy('course_interest')
                ->pluck('course_interest'),
            'admins' => Admin::query()->where('role', Admin::ROLE_STAFF)->orderBy('username')->get(['id', 'username']),
        ]);
    }

    public function update(Request $request, ContactMessage $inquiry, AdminActivityLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,in_progress,resolved,archived'],
        ]);

        $before = ['status' => $inquiry->status];
        $inquiry->update([
            'status' => $data['status'],
            'updated_by' => (int) $request->session()->get('admin_id'),
        ]);
        $audit->record($request, 'inquiry.status_changed', 'inquiries', 'Changed the inquiry status.', ContactMessage::class, $inquiry->id, '#'.$inquiry->id.' '.$inquiry->name, $before, ['status' => $inquiry->status]);

        return back()->with('success', 'Inquiry updated.');
    }

    public function destroy(Request $request, ContactMessage $inquiry, AdminActivityLogger $audit): RedirectResponse
    {
        $before = $this->inquirySnapshot($inquiry);
        $label = '#'.$inquiry->id.' '.$inquiry->name;
        $id = $inquiry->id;
        $inquiry->delete();
        $audit->record($request, 'inquiry.deleted', 'inquiries', 'Deleted an inquiry.', ContactMessage::class, $id, $label, $before);

        return back()->with('success', 'Inquiry deleted.');
    }

    public function resend(Request $request, ContactMessage $inquiry, InquiryMailer $mailer, AdminActivityLogger $audit): RedirectResponse
    {
        $data = $request->validate(['kind' => ['required', 'in:visitor,admin']]);
        try {
            $attempt = $mailer->send($inquiry, $data['kind']);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Unable to record the email attempt. Check the server log before retrying.');
        }
        if (! $attempt) {
            return back()->with('error', 'This email was just attempted or is being sent. Wait one minute before retrying.');
        }
        $audit->record($request, 'inquiry.email_resent', 'inquiries', 'Retried the '.$data['kind'].' inquiry email.', ContactMessage::class, $inquiry->id, '#'.$inquiry->id.' '.$inquiry->name, null, [
            'email_kind' => $data['kind'],
            'recipient' => $attempt->recipient,
            'delivery_status' => $attempt->status,
        ]);

        return $attempt->status === 'sent'
            ? back()->with('success', 'Email accepted by the configured mail transport for '.$attempt->recipient.'.')
            : back()->with('error', 'Email failed. Expand Email tracking for details.');
    }

    public function bulk(Request $request, AdminActivityLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:bulk_delete,bulk_status,bulk_assign'],
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
            'bulk_status' => ['nullable', 'required_if:action,bulk_status', 'in:new,in_progress,resolved,archived'],
            'assigned_admin' => [
                'nullable',
                'required_if:action,bulk_assign',
                'integer',
                Rule::exists('admins', 'id')->where('role', Admin::ROLE_STAFF),
            ],
        ]);

        $query = ContactMessage::query()->whereIn('id', $data['ids']);
        $items = (clone $query)->get()->map(fn (ContactMessage $inquiry): array => $this->inquirySnapshot($inquiry))->values()->all();
        $ids = array_column($items, 'id');

        if ($data['action'] === 'bulk_delete') {
            $query->delete();
            $audit->record($request, 'inquiry.deleted', 'inquiries', 'Deleted '.count($items).' selected inquiries.', ContactMessage::class, implode(',', $ids), count($items).' inquiries', ['items' => $items]);

            return back()->with('success', 'Selected inquiries deleted.');
        }

        if ($data['action'] === 'bulk_assign') {
            $query->update([
                'assigned_to' => $data['assigned_admin'],
                'updated_by' => (int) $request->session()->get('admin_id'),
            ]);
            $after = (clone $query)->get()->map(fn (ContactMessage $inquiry): array => $this->inquirySnapshot($inquiry))->values()->all();
            $audit->record($request, 'inquiry.assigned', 'inquiries', 'Assigned '.count($items).' selected inquiries.', ContactMessage::class, implode(',', $ids), count($items).' inquiries', ['items' => $items], ['items' => $after]);

            return back()->with('success', 'Selected inquiries assigned.');
        }

        $query->update([
            'status' => $data['bulk_status'] ?? ContactMessage::STATUS_NEW,
            'updated_by' => (int) $request->session()->get('admin_id'),
        ]);
        $after = (clone $query)->get()->map(fn (ContactMessage $inquiry): array => $this->inquirySnapshot($inquiry))->values()->all();
        $audit->record($request, 'inquiry.status_changed', 'inquiries', 'Changed the status of '.count($items).' selected inquiries.', ContactMessage::class, implode(',', $ids), count($items).' inquiries', ['items' => $items], ['items' => $after]);

        return back()->with('success', 'Selected inquiries updated.');
    }

    public function export(Request $request): StreamedResponse
    {
        $currentAdmin = $request->attributes->get('currentAdmin');
        $query = ContactMessage::query()->with('assignedTo')->latest('created_at');

        $this->applyAssignmentFilter($query, $request, $currentAdmin);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = trim($request->string('search')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('message', 'like', '%'.$search.'%');
            });
        }

        if ($course = $request->string('course')->toString()) {
            $query->where('course_interest', $course);
        }

        if ($ids = array_filter(array_map('intval', (array) $request->input('ids', [])))) {
            $query->whereIn('id', $ids);
        }

        if ($dateFrom = $request->string('date_from')->toString()) {
            $query->where('created_at', '>=', $dateFrom.' 00:00:00');
        }

        if ($dateTo = $request->string('date_to')->toString()) {
            $query->where('created_at', '<=', $dateTo.' 23:59:59');
        }

        $fileName = 'inquiries_'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'Email', 'Phone', 'Course', 'Subject', 'Message', 'Status', 'Assigned To', 'Date']);

            $query->chunk(500, function ($messages) use ($handle): void {
                foreach ($messages as $message) {
                    fputcsv($handle, [
                        $message->id,
                        $message->name,
                        $message->email,
                        $message->phone,
                        $message->course_interest,
                        $message->subject,
                        $message->message,
                        $message->status,
                        $message->assignedTo?->username,
                        optional($message->created_at)->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function applyAssignmentFilter($query, Request $request, Admin $currentAdmin): void
    {
        $assignment = $request->string('assignment')->toString();

        if ($assignment === 'mine') {
            $query->where('assigned_to', $currentAdmin->id);
        } elseif ($assignment === 'unassigned') {
            $query->whereNull('assigned_to');
        } elseif ($currentAdmin->isOwner() && ctype_digit($assignment)) {
            $query->where('assigned_to', (int) $assignment);
        }
    }

    private function inquirySnapshot(ContactMessage $inquiry): array
    {
        return [
            'id' => $inquiry->id,
            'name' => $inquiry->name,
            'email' => $inquiry->email,
            'phone' => $inquiry->phone,
            'course' => $inquiry->course_interest,
            'status' => $inquiry->status,
            'assigned_to' => $inquiry->assigned_to,
        ];
    }
}
