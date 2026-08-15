<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Support\CourseFileRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FormController extends Controller
{
    public function csrfToken(Request $request): JsonResponse
    {
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'csrf_token' => csrf_token(),
        ]);
    }

    public function contact(Request $request, CourseFileRepository $courses): JsonResponse
    {
        if ($response = $this->enforceRateLimit($request, 'contact_form')) {
            return $response;
        }

        if ($request->filled('website')) {
            return response()->json(['success' => true, 'message' => 'Contact message saved']);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['required', 'regex:/^[0-9+()\-\s]{7,40}$/'],
            'course' => ['nullable', 'string', Rule::in($this->allowedCourseValues($courses))],
            'subject' => ['nullable', 'string', Rule::in(['general', 'enrollment', 'pricing', 'schedule', 'other'])],
            'message' => ['nullable', 'string', 'max:4000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            ContactMessage::query()->create([
                'name' => $this->trimmed($request->string('name')->toString(), 120),
                'email' => $this->trimmed($request->string('email')->toString(), 190),
                'phone' => $this->trimmed($request->string('phone')->toString(), 40),
                'course_interest' => $this->nullableTrimmed($request->input('course'), 120),
                'subject' => $this->nullableTrimmed($request->input('subject'), 60),
                'message' => $this->nullableTrimmed($request->input('message'), 4000),
                'status' => ContactMessage::STATUS_NEW,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Unable to save message right now',
            ], 500);
        }

        return response()->json(['success' => true, 'message' => 'Contact message saved']);
    }

    public function inquiry(Request $request, CourseFileRepository $courses): JsonResponse
    {
        if ($response = $this->enforceRateLimit($request, 'inquiry_form')) {
            return $response;
        }

        if ($request->filled('website')) {
            return response()->json(['success' => true, 'message' => 'Inquiry saved']);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['required', 'regex:/^[0-9+()\-\s]{7,40}$/'],
            'course' => ['required', 'string', Rule::in($this->allowedCourseValues($courses, false))],
            'message' => ['nullable', 'string', 'max:4000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            ContactMessage::query()->create([
                'name' => $this->trimmed($request->string('name')->toString(), 120),
                'email' => $this->trimmed($request->string('email')->toString(), 190),
                'phone' => $this->trimmed($request->string('phone')->toString(), 40),
                'course_interest' => $this->trimmed((string) $request->input('course'), 120),
                'subject' => null,
                'message' => $this->nullableTrimmed($request->input('message'), 4000),
                'status' => ContactMessage::STATUS_NEW,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Unable to save inquiry right now',
            ], 500);
        }

        return response()->json(['success' => true, 'message' => 'Inquiry saved']);
    }

    private function enforceRateLimit(Request $request, string $scope, int $maxAttempts = 6, int $windowSeconds = 300): ?JsonResponse
    {
        $key = $scope.'|'.sha1((string) $request->ip());

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again in a few minutes.',
            ], 429);
        }

        RateLimiter::hit($key, $windowSeconds);

        return null;
    }

    private function allowedCourseValues(CourseFileRepository $courses, bool $allowEmpty = true): array
    {
        $values = array_map(static fn (array $course): string => $course['slug'], $courses->all());

        return $allowEmpty ? array_merge([''], $values) : $values;
    }

    private function trimmed(string $value, int $maxLength): string
    {
        return mb_substr(trim($value), 0, $maxLength);
    }

    private function nullableTrimmed(mixed $value, int $maxLength): ?string
    {
        $trimmed = mb_substr(trim((string) $value), 0, $maxLength);

        return $trimmed !== '' ? $trimmed : null;
    }
}
