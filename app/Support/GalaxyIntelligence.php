<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\ContactMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GalaxyIntelligence
{
    private const CATEGORIES = [
        'it' => ['label' => 'Technology', 'color' => '#38bdf8', 'glyph' => 'T'],
        'diploma' => ['label' => 'UK Diplomas', 'color' => '#a78bfa', 'glyph' => 'D'],
        'language' => ['label' => 'Languages', 'color' => '#f472b6', 'glyph' => 'L'],
        'nursing' => ['label' => 'Healthcare', 'color' => '#34d399', 'glyph' => 'H'],
        'design' => ['label' => 'Design', 'color' => '#fb923c', 'glyph' => 'V'],
        'short-skills' => ['label' => 'Short Skills', 'color' => '#facc15', 'glyph' => 'S'],
    ];

    public function __construct(private readonly CourseFileRepository $courses) {}

    public function snapshot(): array
    {
        $courseRows = collect($this->courses->all());
        $active = ContactMessage::query()
            ->with('assignedTo:id,username')
            ->whereIn('status', [ContactMessage::STATUS_NEW, ContactMessage::STATUS_IN_PROGRESS])
            ->latest('created_at')->limit(36)->get();
        $enrolled = ContactMessage::query()
            ->where('status', ContactMessage::STATUS_RESOLVED)
            ->latest('created_at')->limit(30)->get();
        $staff = Admin::query()->where('role', Admin::ROLE_STAFF)->orderBy('username')->get(['id', 'username']);
        $demand = $this->demandIndex($active->concat($enrolled));

        $categories = collect(self::CATEGORIES)->map(function (array $meta, string $key) use ($courseRows, $demand): array {
            $courses = $courseRows
                ->filter(fn (array $course): bool => $this->categoryFor($course) === $key)
                ->map(fn (array $course): array => [
                    'id' => 'course:'.$course['slug'],
                    'slug' => $course['slug'],
                    'title' => $course['title'],
                    'duration' => $course['duration'] ?: 'Flexible schedule',
                    'demand' => $demand[$this->normalize($course['title'])] ?? $demand[$this->normalize($course['slug'])] ?? 0,
                    'url' => route('admin.courses.edit', $course['slug']),
                ])->values();

            return ['id' => $key, ...$meta, 'courses' => $courses->all(), 'demand' => $courses->sum('demand')];
        })->values();

        $inquiryRows = $active->map(fn (ContactMessage $inquiry): array => [
            'id' => 'inquiry:'.$inquiry->id,
            'record_id' => $inquiry->id,
            'name' => $inquiry->name,
            'course' => $inquiry->course_interest ?: 'General inquiry',
            'score' => max(8, (int) ($inquiry->lead_score ?: 24)),
            'stage' => $inquiry->pipeline_stage ?: ($inquiry->status === ContactMessage::STATUS_IN_PROGRESS ? 'contacted' : 'new_lead'),
            'staff_id' => $inquiry->assigned_to,
            'assignee' => $inquiry->assignedTo?->username,
            'timestamp' => optional($inquiry->created_at)->timestamp,
            'neglected' => optional($inquiry->created_at)?->lt(now()->subHours(48)) ?? false,
            'url' => route('admin.inquiries.index', ['open' => $inquiry->id]),
        ])->values();

        $staffRows = $staff->map(fn (Admin $member): array => [
            'id' => 'staff:'.$member->id,
            'record_id' => $member->id,
            'name' => $member->username,
            'active' => $active->where('assigned_to', $member->id)->count(),
            'qualified' => $active->where('assigned_to', $member->id)->where('pipeline_stage', 'qualified')->count(),
            'url' => route('admin.inquiries.index', ['assignment' => $member->id]),
        ])->values();

        $starRows = $enrolled->map(fn (ContactMessage $inquiry): array => [
            'id' => 'star:'.$inquiry->id,
            'record_id' => $inquiry->id,
            'name' => $inquiry->name,
            'course' => $inquiry->course_interest ?: 'General enrollment',
            'timestamp' => optional($inquiry->created_at)->timestamp,
        ])->values();

        $versionSeed = $active->concat($enrolled)->map(fn (ContactMessage $inquiry): string => implode(':', [
            $inquiry->id, $inquiry->status, $inquiry->assigned_to, $inquiry->pipeline_stage, optional($inquiry->pipeline_moved_at)->timestamp,
        ]))->implode('|').'|'.$courseRows->pluck('modified')->implode('|').'|'.$staff->pluck('id')->implode(',');

        return [
            'version' => sha1($versionSeed),
            'generated_at' => now()->timestamp,
            'categories' => $categories->all(),
            'inquiries' => $inquiryRows->all(),
            'staff' => $staffRows->all(),
            'stars' => $starRows->all(),
            'stats' => [
                'courses' => $courseRows->count(),
                'active' => $active->count(),
                'unassigned' => $active->whereNull('assigned_to')->count(),
                'enrolled' => $enrolled->count(),
            ],
        ];
    }

    private function demandIndex(Collection $inquiries): array
    {
        return $inquiries->filter(fn (ContactMessage $inquiry): bool => filled($inquiry->course_interest))
            ->countBy(fn (ContactMessage $inquiry): string => $this->normalize((string) $inquiry->course_interest))->all();
    }

    private function categoryFor(array $course): string
    {
        $categories = $course['categories'] ?? [];
        if ($categories !== []) {
            return $categories[0];
        }

        $haystack = $this->normalize(($course['badge'] ?? '').' '.($course['title'] ?? ''));

        return match (true) {
            str_contains($haystack, 'diploma') => 'diploma',
            str_contains($haystack, 'language'), str_contains($haystack, 'english'), str_contains($haystack, 'arabic') => 'language',
            str_contains($haystack, 'nursing'), str_contains($haystack, 'health'), str_contains($haystack, 'caregiver') => 'nursing',
            str_contains($haystack, 'design'), str_contains($haystack, 'multimedia'), str_contains($haystack, 'studio') => 'design',
            str_contains($haystack, 'short') => 'short-skills',
            default => 'it',
        };
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->lower()->replace('-', ' ')->squish()->toString();
    }
}
