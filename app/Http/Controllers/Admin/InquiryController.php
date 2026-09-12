<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ContactMessage;
use App\Support\InquiryMailer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InquiryController extends Controller
{
    public function index(Request $request): View
    {
        $query = ContactMessage::query()->with(['emailAttempts', 'updatedBy']);

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
        ];

        return view('admin.inquiries.index', [
            'inquiries' => $inquiries,
            'stats' => $stats,
            'filters' => $request->only(['status', 'course', 'search', 'date_from', 'date_to']),
            'courses' => ContactMessage::query()
                ->whereNotNull('course_interest')
                ->where('course_interest', '!=', '')
                ->distinct()
                ->orderBy('course_interest')
                ->pluck('course_interest'),
            'admins' => Admin::query()->orderBy('username')->get(['id', 'username']),
        ]);
    }

    public function update(Request $request, ContactMessage $inquiry): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,in_progress,resolved,archived'],
        ]);

        $inquiry->update([
            'status' => $data['status'],
            'updated_by' => (int) $request->session()->get('admin_id'),
        ]);

        return back()->with('success', 'Inquiry updated.');
    }

    public function destroy(ContactMessage $inquiry): RedirectResponse
    {
        $inquiry->delete();

        return back()->with('success', 'Inquiry deleted.');
    }

    public function resend(Request $request, ContactMessage $inquiry, InquiryMailer $mailer): RedirectResponse
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
        return $attempt->status === 'sent'
            ? back()->with('success', 'Email accepted by the configured mail transport for '.$attempt->recipient.'.')
            : back()->with('error', 'Email failed. Expand Email tracking for details.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:bulk_delete,bulk_status,bulk_assign'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'bulk_status' => ['nullable', 'required_if:action,bulk_status', 'in:new,in_progress,resolved,archived'],
            'assigned_admin' => ['nullable', 'required_if:action,bulk_assign', 'integer', 'exists:admins,id'],
        ]);

        $query = ContactMessage::query()->whereIn('id', $data['ids']);

        if ($data['action'] === 'bulk_delete') {
            $query->delete();

            return back()->with('success', 'Selected inquiries deleted.');
        }

        if ($data['action'] === 'bulk_assign') {
            $query->update(['updated_by' => $data['assigned_admin']]);

            return back()->with('success', 'Selected inquiries assigned.');
        }

        $query->update([
            'status' => $data['bulk_status'] ?? ContactMessage::STATUS_NEW,
            'updated_by' => (int) $request->session()->get('admin_id'),
        ]);

        return back()->with('success', 'Selected inquiries updated.');
    }

    public function export(Request $request): StreamedResponse
    {
        $query = ContactMessage::query()->latest('created_at');

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
            fputcsv($handle, ['ID', 'Name', 'Email', 'Phone', 'Course', 'Subject', 'Message', 'Status', 'Date']);

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
                        optional($message->created_at)->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
