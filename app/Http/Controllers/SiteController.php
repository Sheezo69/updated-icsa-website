<?php

namespace App\Http\Controllers;

use App\Support\CourseFileRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SiteController extends Controller
{
    public function home(CourseFileRepository $courses): View
    {
        $courseCards = $this->preparedCourseCards($courses);
        $categoryCounts = [
            'it' => count(array_filter($courseCards, static fn (array $course): bool => $course['listing_category'] === 'it')),
            'diploma' => count(array_filter($courseCards, static fn (array $course): bool => $course['listing_category'] === 'diploma')),
            'language' => count(array_filter($courseCards, static fn (array $course): bool => $course['listing_category'] === 'language')),
        ];

        return view('site.home', [
            'courses' => $courseCards,
            'categoryCounts' => $categoryCounts,
            'courseTotal' => count($courseCards),
        ]);
    }

    public function about(): RedirectResponse
    {
        return new RedirectResponse('/#about');
    }

    public function courses(Request $request): RedirectResponse
    {
        $query = $request->query('category') ? '?category='.urlencode((string) $request->query('category')) : '';

        return new RedirectResponse('/'.$query.'#courses');
    }

    public function contact(Request $request): RedirectResponse
    {
        $query = $request->query() ? '?'.http_build_query($request->query()) : '';

        return new RedirectResponse('/'.$query.'#contact');
    }

    public function course(string $slug, CourseFileRepository $courses): View
    {
        $course = $courses->find($slug);
        abort_if($course === null, 404);

        $course['detail_image_url'] = $this->assetUrlFromLegacyPath($course['image']);
        $course['video_url'] = $this->assetUrlFromLegacyPath($course['video_path'] ?? '');
        $course['video_thumbnail_url'] = $this->assetUrlFromLegacyPath($course['video_thumbnail'] ?? '') ?? $course['detail_image_url'];
        $course['highlight_items'] = $this->splitLines($course['highlights']);
        $course['learning_outcome_items'] = $this->splitLines($course['learning_outcomes']);
        $course['target_audience_items'] = $this->splitLines($course['target_audience']);
        $course['career_items'] = $this->splitLines($course['careers']);

        return view('site.course', [
            'course' => $course,
        ]);
    }

    private function splitLines(?string $value): array
    {
        $lines = preg_split('/\R+/', trim((string) $value)) ?: [];

        return array_values(array_filter(array_map('trim', $lines)));
    }

    private function assetUrlFromLegacyPath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $normalized = preg_replace('#^(\.\./)+#', '', $path) ?: $path;

        return asset(ltrim($normalized, '/'));
    }

    private function preparedCourseCards(CourseFileRepository $courses): array
    {
        return array_map(function (array $course): array {
            $course['listing_image_url'] = $this->assetUrlFromLegacyPath($course['image'])
                ?? $this->assetUrlFromLegacyPath($course['video_thumbnail'] ?? '');
            $course['listing_category'] = $this->courseCategory($course);
            $course['listing_category_label'] = match ($course['listing_category']) {
                'diploma' => 'UK Diploma',
                'language' => 'Language & Professional',
                default => 'IT & Technical',
            };

            return $course;
        }, $courses->all());
    }

    private function courseCategory(array $course): string
    {
        $text = Str::lower(implode(' ', [
            $course['title'] ?? '',
            $course['badge'] ?? '',
            $course['diploma_type'] ?? '',
        ]));

        if (str_contains($text, 'diploma') || str_contains($text, 'uk')) {
            return 'diploma';
        }

        if (
            str_contains($text, 'english')
            || str_contains($text, 'ielts')
            || str_contains($text, 'arabic')
            || str_contains($text, 'airline')
            || str_contains($text, 'travel')
            || str_contains($text, 'language')
            || str_contains($text, 'professional')
        ) {
            return 'language';
        }

        return 'it';
    }
}
