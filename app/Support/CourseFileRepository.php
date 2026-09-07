<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CourseFileRepository
{
    public function all(): array
    {
        $courses = [];

        foreach (File::glob($this->directory().'/*.html') as $file) {
            $slug = pathinfo($file, PATHINFO_FILENAME);
            $parsed = $this->find($slug);

            if ($parsed) {
                $courses[] = $parsed;
            }
        }

        usort($courses, static fn (array $left, array $right): int => strcmp($left['title'], $right['title']));

        return $courses;
    }

    public function count(): int
    {
        return count(File::glob($this->directory().'/*.html'));
    }

    public function raw(string $slug): ?string
    {
        $path = $this->path($slug);

        return File::exists($path) ? File::get($path) : null;
    }

    public function find(string $slug): ?array
    {
        $path = $this->path($slug);

        if (! File::exists($path)) {
            return null;
        }

        $content = File::get($path);
        preg_match('/<title>(.*?)\s*\|/is', $content, $titleMatch);
        preg_match('/<span class=["\']hero-label["\']>(.*?)<\/span>/is', $content, $badgeMatch);
        preg_match('/<!--\s*course-categories?:\s*(.*?)\s*-->/is', $content, $categoryMatch);
        preg_match('/<h1>(.*?)<\/h1>/is', $content, $headingMatch);
        preg_match_all('/<span class=["\']course-detail-meta-item["\']><i class=["\']fas fa-.*?["\']><\/i>\s*([^<]+)/is', $content, $metaMatches);
        preg_match('/<p class=["\']course-detail-description["\']>(.*?)<\/p>/is', $content, $descriptionMatch);
        preg_match('/<!--\s*course-image:\s*(.*?)\s*-->/is', $content, $imageCommentMatch);
        preg_match('/<!--\s*course-poster:\s*(.*?)\s*-->/is', $content, $posterCommentMatch);
        preg_match('/<!--\s*course-background:\s*(.*?)\s*-->/is', $content, $backgroundCommentMatch);
        preg_match('/<!--\s*course-background-darkness:\s*(.*?)\s*-->/is', $content, $backgroundDarknessMatch);
        preg_match('/<!--\s*course-background-blur:\s*(.*?)\s*-->/is', $content, $backgroundBlurMatch);
        preg_match('/<img src=["\']([^"\']+)["\'].*?class=["\']course-detail-image["\']/is', $content, $imageMatch);
        preg_match('/<!--\s*course-youtube:\s*(.*?)\s*-->/is', $content, $youtubeCommentMatch);
        preg_match('/<!--\s*course-video:\s*(.*?)\s*-->/is', $content, $videoCommentMatch);
        preg_match('/<!--\s*course-video-thumbnail:\s*(.*?)\s*-->/is', $content, $videoThumbnailCommentMatch);
        preg_match('/data-youtube-id=["\']([^"\']+)["\']/is', $content, $youtubeIdMatch);
        preg_match('/class=["\']course-youtube-poster["\'][^>]*src=["\']([^"\']+)["\']/is', $content, $youtubePosterMatch);
        preg_match('/<video[^>]*class=["\']course-detail-video["\'][^>]*>.*?<source src=["\']([^"\']+)["\']/is', $content, $videoMatch);
        preg_match('/<video[^>]*class=["\']course-detail-video["\'][^>]*poster=["\']([^"\']+)["\']/is', $content, $videoPosterMatch);
        preg_match('/<div class=["\']price["\']>(.*?)<\/div>/is', $content, $priceMatch);
        preg_match('/<div class=["\']price-note["\']>(.*?)<\/div>/is', $content, $priceNoteMatch);
        preg_match('/<div class=["\']course-detail-features["\']>.*?<ul>(.*?)<\/ul>/is', $content, $highlightsMatch);
        preg_match('/<h3>Program Overview<\/h3>\s*<p>(.*?)<\/p>/is', $content, $overviewMatch);
        preg_match('/<h3>What You Will Learn<\/h3>\s*<ul>(.*?)<\/ul>/is', $content, $learningMatch);
        preg_match('/<h3>Who Should Enroll<\/h3>\s*<ul>(.*?)<\/ul>/is', $content, $audienceMatch);
        preg_match('/<h3>Career Opportunities<\/h3>\s*<div class=["\']career-list["\']>(.*?)<\/div>/is', $content, $careersMatch);

        $meta = $metaMatches[1] ?? [];

        return [
            'slug' => $slug,
            'title' => $this->cleanText($headingMatch[1] ?? $titleMatch[1] ?? 'Untitled Course'),
            'badge' => $this->cleanText($badgeMatch[1] ?? ''),
            'categories' => $this->normalizeCategories($categoryMatch[1] ?? ''),
            'duration' => $this->cleanText($meta[0] ?? ''),
            'certification' => $this->cleanText($meta[1] ?? 'Certified'),
            'diploma_type' => $this->cleanText($meta[2] ?? ''),
            'description' => $this->cleanText($descriptionMatch[1] ?? ''),
            'image' => trim((string) ($imageCommentMatch[1] ?? $imageMatch[1] ?? '')),
            'poster_image' => trim((string) ($posterCommentMatch[1] ?? '')),
            'background_image' => trim((string) ($backgroundCommentMatch[1] ?? '')),
            'background_darkness' => max(0, min(100, (int) ($backgroundDarknessMatch[1] ?? 0))),
            'background_blur' => max(0, min(20, (int) ($backgroundBlurMatch[1] ?? 0))),
            'youtube_url' => $this->resolveYoutubeUrl(
                trim((string) ($youtubeCommentMatch[1] ?? '')),
                trim((string) ($videoCommentMatch[1] ?? $videoMatch[1] ?? '')),
                trim((string) ($youtubeIdMatch[1] ?? '')),
            ),
            'video_thumbnail' => trim((string) ($videoThumbnailCommentMatch[1] ?? $youtubePosterMatch[1] ?? $videoPosterMatch[1] ?? '')),
            'price' => $this->cleanText($priceMatch[1] ?? ''),
            'price_note' => $this->cleanText($priceNoteMatch[1] ?? 'Flexible payment options available'),
            'highlights' => $this->htmlToPlain($highlightsMatch[1] ?? ''),
            'overview' => $this->cleanText($overviewMatch[1] ?? ''),
            'learning_outcomes' => $this->htmlToPlain($learningMatch[1] ?? ''),
            'target_audience' => $this->htmlToPlain($audienceMatch[1] ?? ''),
            'careers' => $this->htmlToPlain($careersMatch[1] ?? ''),
            'modified' => File::lastModified($path),
            'file_name' => basename($path),
            'path' => $path,
        ];
    }

    public function save(array $input, ?string $originalSlug = null): string
    {
        $slug = Str::slug((string) ($input['title'] ?? ''));
        $slug = $slug !== '' ? $slug : 'untitled-course';

        $fragments = $this->templateFragments();
        $replacements = $this->templateData($input, $slug) + $fragments;
        $content = str_replace(
            array_map(static fn (string $key): string => '{{'.$key.'}}', array_keys($replacements)),
            array_values($replacements),
            $this->template()
        );

        File::ensureDirectoryExists($this->directory());
        File::put($this->path($slug), $content);

        if ($originalSlug && $originalSlug !== $slug) {
            $oldPath = $this->path($originalSlug);
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
        }

        return $slug;
    }

    public function delete(string $slug): bool
    {
        $path = $this->path($slug);

        if (! File::exists($path)) {
            return false;
        }

        return File::delete($path);
    }

    public function mediaUsage(string $mediaPath): array
    {
        $usage = [];

        foreach (File::glob($this->directory().'/*.html') as $file) {
            $content = File::get($file);
            if (! str_contains($content, $mediaPath)) {
                continue;
            }

            $course = $this->find(pathinfo($file, PATHINFO_FILENAME));
            if ($course) {
                $usage[] = [
                    'slug' => $course['slug'],
                    'title' => $course['title'],
                ];
            }
        }

        return $usage;
    }

    public function replaceMediaPath(string $oldPath, string $newPath): void
    {
        foreach (File::glob($this->directory().'/*.html') as $file) {
            $content = File::get($file);
            if (str_contains($content, $oldPath)) {
                File::put($file, str_replace($oldPath, $newPath, $content));
            }
        }
    }

    public function path(string $slug): string
    {
        return $this->directory().'/'.$this->normalizeSlug($slug).'.html';
    }

    private function directory(): string
    {
        return resource_path('content/courses');
    }

    private function normalizeSlug(string $slug): string
    {
        return preg_replace('/[^a-z0-9-]/', '', strtolower($slug)) ?: 'untitled-course';
    }

    private function cleanText(string $value): string
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function htmlToPlain(string $html): string
    {
        $text = preg_replace('/<li[^>]*>\s*<i[^>]*><\/i>\s*/i', '', $html);
        $text = preg_replace('/<li[^>]*>/i', '', (string) $text);
        $text = preg_replace('/<\/li>/i', "\n", (string) $text);
        $text = preg_replace('/<span[^>]*class=["\']career-tag["\'][^>]*>/i', '', (string) $text);
        $text = preg_replace('/<\/span>/i', "\n", (string) $text);

        return trim(html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function plainToList(string $text): string
    {
        $lines = array_filter(array_map('trim', preg_split('/\R/', $text) ?: []));
        $html = [];

        foreach ($lines as $line) {
            $html[] = "<li><i class='fas fa-check'></i> ".e($line).'</li>';
        }

        return implode("\n", $html);
    }

    private function plainToCareers(string $text): string
    {
        $lines = array_filter(array_map('trim', preg_split('/\R/', $text) ?: []));
        $html = [];

        foreach ($lines as $line) {
            $html[] = "<span class='career-tag'>".e($line).'</span>';
        }

        return implode("\n", $html);
    }

    private function normalizeCategories(mixed $categories): array
    {
        $values = is_array($categories) ? $categories : (preg_split('/\s*,\s*/', trim((string) $categories)) ?: []);

        return array_values(array_unique(array_filter($values, static fn (mixed $category): bool => in_array($category, ['it', 'diploma', 'language', 'nursing', 'design', 'short-skills'], true))));
    }

    private function templateData(array $input, string $slug): array
    {
        return [
            'TITLE' => e((string) ($input['title'] ?? '')),
            'BADGE' => e((string) ($input['badge'] ?? '')),
            'CATEGORIES' => e(implode(',', $this->normalizeCategories($input['categories'] ?? []))),
            'DURATION' => e((string) ($input['duration'] ?? '')),
            'CERTIFICATION' => e((string) ($input['certification'] ?? 'Certified')),
            'DIPLOMA_TYPE' => e((string) ($input['diploma_type'] ?? '')),
            'DESCRIPTION' => e((string) ($input['description'] ?? '')),
            'IMAGE' => e((string) ($input['image'] ?? '')),
            'POSTER_IMAGE' => e((string) ($input['poster_image'] ?? '')),
            'BACKGROUND_IMAGE' => e((string) ($input['background_image'] ?? '')),
            'BACKGROUND_DARKNESS' => (string) max(0, min(100, (int) ($input['background_darkness'] ?? 0))),
            'BACKGROUND_BLUR' => (string) max(0, min(20, (int) ($input['background_blur'] ?? 0))),
            'YOUTUBE_URL' => e((string) ($input['youtube_url'] ?? '')),
            'VIDEO_THUMBNAIL' => e((string) ($input['video_thumbnail'] ?? '')),
            'COURSE_MEDIA' => $this->courseMediaHtml($input),
            'PRICE' => e((string) ($input['price'] ?? '')),
            'PRICE_NOTE' => e((string) ($input['price_note'] ?? 'Flexible payment options available')),
            'HIGHLIGHTS' => $this->plainToList((string) ($input['highlights'] ?? '')),
            'OVERVIEW' => e((string) ($input['overview'] ?? '')),
            'LEARNING_OUTCOMES' => $this->plainToList((string) ($input['learning_outcomes'] ?? '')),
            'TARGET_AUDIENCE' => $this->plainToList((string) ($input['target_audience'] ?? '')),
            'CAREERS' => $this->plainToCareers((string) ($input['careers'] ?? '')),
            'SLUG' => $slug,
        ];
    }

    private function courseMediaHtml(array $input): string
    {
        $title = (string) ($input['title'] ?? 'Course');
        $image = trim((string) ($input['image'] ?? ''));
        $videoId = YoutubeVideo::extractVideoId((string) ($input['youtube_url'] ?? ''));

        if ($videoId) {
            $poster = trim((string) ($input['video_thumbnail'] ?? ''));

            if ($poster === '') {
                $poster = $image !== '' ? $image : YoutubeVideo::thumbnailUrl($videoId);
            }

            return YoutubeVideo::facadeHtml($videoId, $poster, $title);
        }

        if ($image !== '') {
            return '<img src="'.e($image).'" alt="'.e($title).'" class="course-detail-image" loading="lazy">';
        }

        return '<div class="course-image-placeholder">'."\n"
            .'                        <i class="fas fa-image"></i>'."\n"
            .'                        <p>Course media pending</p>'."\n"
            .'                        <span>Add an image or YouTube link from the admin portal.</span>'."\n"
            .'                    </div>';
    }

    private function resolveYoutubeUrl(string $youtubeComment, string $legacyVideoValue, string $embeddedVideoId): string
    {
        if ($youtubeComment !== '') {
            return $youtubeComment;
        }

        if ($embeddedVideoId !== '') {
            return 'https://www.youtube.com/watch?v='.$embeddedVideoId;
        }

        if ($legacyVideoValue !== '' && YoutubeVideo::extractVideoId($legacyVideoValue)) {
            return $legacyVideoValue;
        }

        return '';
    }

    private function templateFragments(): array
    {
        $samplePath = $this->path('uk-diploma-business-management');
        $sample = File::exists($samplePath) ? File::get($samplePath) : '';

        preg_match('/<div class=[\'"]top-bar[\'"]>.*?<\/header>/is', $sample, $headerMatch);
        preg_match('/<section class=[\'"]inquiry-section[\'"]>.*?<\/section>/is', $sample, $inquiryMatch);
        preg_match('/<footer class=[\'"]footer[\'"]>.*?<\/footer>/is', $sample, $footerMatch);

        return [
            'HEADER' => $headerMatch[0] ?? '',
            'INQUIRY_SECTION' => $inquiryMatch[0] ?? '',
            'FOOTER' => $footerMatch[0] ?? '',
        ];
    }

    private function template(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{TITLE}} | ICSA Kuwait</title>
    <meta name="description" content="{{DESCRIPTION}}">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    {{HEADER}}
    <!-- course-image: {{IMAGE}} -->
    <!-- course-poster: {{POSTER_IMAGE}} -->
    <!-- course-categories: {{CATEGORIES}} -->
    <!-- course-background: {{BACKGROUND_IMAGE}} -->
    <!-- course-background-darkness: {{BACKGROUND_DARKNESS}} -->
    <!-- course-background-blur: {{BACKGROUND_BLUR}} -->
    <!-- course-youtube: {{YOUTUBE_URL}} -->
    <!-- course-video-thumbnail: {{VIDEO_THUMBNAIL}} -->

    <section class="course-detail-hero">
        <div class="container">
            <div class="course-detail-grid">
                <div class="course-detail-content">
                    <h1>{{TITLE}}</h1>
                    <div class="course-detail-meta">
                        <span class="course-detail-meta-item"><i class="fas fa-clock"></i> {{DURATION}}</span>
                        <span class="course-detail-meta-item"><i class="fas fa-signal"></i> {{CERTIFICATION}}</span>
                        <span class="course-detail-meta-item"><i class="fas fa-certificate"></i> {{DIPLOMA_TYPE}}</span>
                    </div>
                    <p class="course-detail-description">{{DESCRIPTION}}</p>
                </div>
                <aside class="course-detail-card">
                    {{COURSE_MEDIA}}
                    <div class="course-detail-price">
                        <div class="price">{{PRICE}}</div>
                        <div class="price-note">{{PRICE_NOTE}}</div>
                    </div>
                    <div class="course-detail-features">
                        <h4>Program Highlights</h4>
                        <ul>
                            {{HIGHLIGHTS}}
                        </ul>
                    </div>
                    <a href="../contact.html?course={{SLUG}}" class="btn btn-primary">Enroll Now</a>
                </aside>
            </div>
        </div>
    </section>

    <section class="course-content-section">
        <div class="container">
            <div class="course-content-grid">
                <article class="tab-panel course-block">
                    <h3>Program Overview</h3>
                    <p>{{OVERVIEW}}</p>
                </article>
                <article class="tab-panel course-block">
                    <h3>What You Will Learn</h3>
                    <ul>
                        {{LEARNING_OUTCOMES}}
                    </ul>
                </article>
                <article class="tab-panel course-block">
                    <h3>Who Should Enroll</h3>
                    <ul>
                        {{TARGET_AUDIENCE}}
                    </ul>
                </article>
                <article class="tab-panel course-block">
                    <h3>Career Opportunities</h3>
                    <div class="career-list">
                        {{CAREERS}}
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{INQUIRY_SECTION}}
    {{FOOTER}}
</body>
</html>
HTML;
    }
}
