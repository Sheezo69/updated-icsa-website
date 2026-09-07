<?php

namespace App\Http\Controllers;

use App\Support\CourseFileRepository;
use App\Support\YoutubeVideo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SiteController extends Controller
{
    public function home(CourseFileRepository $courses): View
    {
        $courseCards = $this->preparedCourseCards($courses);
        $categoryCounts = [
            'it' => count(array_filter($courseCards, static fn (array $course): bool => in_array('it', $course['listing_categories'], true))),
            'diploma' => count(array_filter($courseCards, static fn (array $course): bool => in_array('diploma', $course['listing_categories'], true))),
            'language' => count(array_filter($courseCards, static fn (array $course): bool => in_array('language', $course['listing_categories'], true))),
            'nursing' => count(array_filter($courseCards, static fn (array $course): bool => in_array('nursing', $course['listing_categories'], true))),
            'design' => count(array_filter($courseCards, static fn (array $course): bool => in_array('design', $course['listing_categories'], true))),
            'short-skills' => count(array_filter($courseCards, static fn (array $course): bool => in_array('short-skills', $course['listing_categories'], true))),
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

    public function contact(): View
    {
        return view('site.contact');
    }

    public function course(string $slug, CourseFileRepository $courses): View
    {
        $course = $courses->find($slug);
        abort_if($course === null, 404);

        $course['detail_image_url'] = $this->assetUrlFromLegacyPath($course['image']);
        $course['poster_image_url'] = $this->assetUrlFromLegacyPath($course['poster_image'] ?? '');
        $course['background_image_url'] = $this->assetUrlFromLegacyPath($course['background_image'] ?? '');
        $course['youtube_video_id'] = YoutubeVideo::extractVideoId($course['youtube_url'] ?? '');
        $course['video_poster_url'] = $this->resolveVideoPosterUrl($course);
        $course['highlight_items'] = $this->splitLines($course['highlights']);
        $course['learning_outcome_items'] = $this->splitLines($course['learning_outcomes']);
        $course['target_audience_items'] = $this->splitLines($course['target_audience']);
        $course['career_items'] = $this->splitLines($course['careers']);

        return view('site.course', [
            'course' => $course,
        ]);
    }

    private function resolveVideoPosterUrl(array $course): ?string
    {
        $customPoster = $this->assetUrlFromLegacyPath($course['video_thumbnail'] ?? '');

        if ($customPoster) {
            return $customPoster;
        }

        if (! empty($course['detail_image_url'])) {
            return $course['detail_image_url'];
        }

        if (! empty($course['youtube_video_id'])) {
            return YoutubeVideo::thumbnailUrl($course['youtube_video_id']);
        }

        return null;
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
            $course['listing_image_url'] = $this->courseCoverImageUrl($course);
            $course['listing_categories'] = $this->courseCategories($course);
            $course['listing_category'] = $course['listing_categories'][0];
            $course['listing_category_label'] = match ($course['listing_category']) {
                'diploma' => 'UK Diploma',
                'language' => 'Language & Professional',
                'nursing' => 'Nursing & Healthcare',
                'design' => 'Design & Multimedia',
                'short-skills' => 'Short Skill Courses',
                default => 'IT & Technical',
            };

            return $course;
        }, $courses->all());
    }

    private function courseCoverImageUrl(array $course): string
    {
        $uploadedCover = trim((string) ($course['image'] ?? ''));

        if (Str::startsWith($uploadedCover, ['/storage/course-covers/', 'storage/course-covers/'])) {
            return $this->assetUrlFromLegacyPath($uploadedCover) ?? asset('images/ICSA-LOGO.png');
        }

        $coverFiles = [
            '3d-studio-max' => 'course-3d-studio-max.jpg',
            'advanced-excel' => 'course-advanced-excel.png',
            'airline-ticketing-travel-agent' => 'course-airline-ticketing.jpg',
            'airport-training-course-amadeus-altea-dcs' => 'course-airport-training-course-amadeus-altea-dcs.png',
            'arabic-learning' => 'course-arabic-learning.png',
            'autocad-2d-3d' => 'course-autocad-2d-3d.jpg',
            'caregiver-practical-training-course' => 'course-caregiver-practical-training-course.png',
            'cctv-camera-networking-training-course' => 'course-cctv-camera-networking-training-course.png',
            'computer-secretarial' => 'course-computer-secretarial.jpg',
            'english-enhancement' => 'course-english-enhancement.jpg',
            'full-stack-web-development-with-laravel-framework' => 'course-full-stack-web-development-with-laravel-framework.png',
            'graphics-designing' => 'course-graphics-designing.jpg',
            'ielts-preparation' => 'course-ielts-preparation.jpg',
            'multimedia-motion-graphics' => 'course-multimedia-motion-graphics.jpg',
            'office-management' => 'course-office-management.jpg',
            'pc-laptop-maintenance-and-cctv-installation-with-networking' => 'course-pc-laptop-maintenance-and-cctv-installation-with-networking.png',
            'pc-laptop-maintenance-training' => 'course-pc-laptop-maintenance-training.png',
            'pc-networking' => 'course-pc-networking.jpg',
            'photoshop-training' => 'course-photoshop-training.png',
            'programming-web-development' => 'course-programming-web-development.jpg',
            'python-programming' => 'course-python-programming.png',
            'revit' => 'course-revit.jpg',
            'shopify-ecommerce-dropshipping' => 'course-shopify-ecommerce-dropshipping.png',
            'sketchup' => 'course-sketchup.jpg',
            'social-media-advertisement' => 'course-social-media-advertisement.png',
            'social-media-marketing' => 'course-social-media-marketing.png',
            'uiux-designing-figma-course' => 'course-uiux-designing-figma-course.png',
            'uk-diploma-accounting-finance' => 'course-uk-diploma-accounting-finance.jpg',
            'uk-diploma-business-management' => 'course-uk-diploma-business-management.jpg',
            'uk-diploma-entrepreneurship' => 'course-uk-diploma-entrepreneurship.jpg',
            'uk-diploma-health-social-care' => 'course-uk-diploma-health-social-care.jpg',
            'uk-diploma-hospitality-tourism' => 'course-uk-diploma-hospitality-tourism.jpg',
            'uk-diploma-in-health-social-care' => 'course-uk-diploma-in-health-social-care.png',
            'uk-diploma-in-artificial-intelligence' => 'course-uk-diploma-in-artificial-intelligence.png',
            'uk-diploma-in-cyber-security' => 'course-uk-diploma-in-cyber-security.png',
            'uk-diploma-in-data-science' => 'course-uk-diploma-in-data-science.png',
            'uk-diploma-in-education-training' => 'course-uk-diploma-in-education-training.png',
            'uk-diploma-information-technology' => 'course-uk-diploma-information-technology.jpg',
            'uk-diploma-strategic-management' => 'course-uk-diploma-strategic-management.png',
            'video-editing-training' => 'course-video-editing-training.png',
            'virtual-assistant' => 'course-virtual-assistant.png',
            'web-designing' => 'course-web-designing.jpg',
        ];

        $filename = $coverFiles[$course['slug'] ?? ''] ?? null;

        if ($filename && File::exists(public_path('images/'.$filename))) {
            return asset('images/'.$filename);
        }

        return asset('images/ICSA-LOGO.png');
    }

    private function courseCategories(array $course): array
    {
        $categories = array_values(array_intersect($course['categories'] ?? [], ['it', 'diploma', 'language', 'nursing', 'design', 'short-skills']));
        if ($categories !== []) {
            return $categories;
        }

        $titleAndBadge = Str::lower(implode(' ', [
            $course['title'] ?? '',
            $course['badge'] ?? '',
        ]));

        if (str_contains($titleAndBadge, 'diploma') || str_contains($titleAndBadge, 'uk')) {
            return ['diploma'];
        }

        if (
            str_contains($titleAndBadge, 'english')
            || str_contains($titleAndBadge, 'ielts')
            || str_contains($titleAndBadge, 'arabic')
            || str_contains($titleAndBadge, 'airline')
            || str_contains($titleAndBadge, 'travel')
            || str_contains($titleAndBadge, 'language')
            || str_contains($titleAndBadge, 'professional')
        ) {
            return ['language'];
        }

        return ['it'];
    }
}
