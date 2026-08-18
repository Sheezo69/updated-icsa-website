<?php

namespace App\Support;

class YoutubeVideo
{
    public static function extractVideoId(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
            return $url;
        }

        $patterns = [
            '/(?:youtube\.com\/watch\?(?:[^&]+&)*v=|youtube\.com\/watch\?v=)([a-zA-Z0-9_-]{11})/',
            '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/live\/([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public static function embedUrl(string $videoId, bool $autoplay = true): string
    {
        $query = http_build_query([
            'autoplay' => $autoplay ? '1' : '0',
            'rel' => '0',
            'modestbranding' => '1',
        ]);

        return 'https://www.youtube-nocookie.com/embed/'.$videoId.'?'.$query;
    }

    public static function thumbnailUrl(string $videoId, string $quality = 'maxresdefault'): string
    {
        return 'https://img.youtube.com/vi/'.$videoId.'/'.$quality.'.jpg';
    }

    public static function facadeHtml(string $videoId, string $posterUrl, string $title = 'Course video'): string
    {
        $safeId = e($videoId);
        $safePoster = e($posterUrl);
        $safeTitle = e($title);
        $fallbackPoster = e(self::thumbnailUrl($videoId, 'hqdefault'));

        return '<div class="course-detail-video-frame course-youtube-player" data-youtube-id="'.$safeId.'">'."\n"
            .'                        <button type="button" class="course-youtube-play" aria-label="Play '.$safeTitle.' video">'."\n"
            .'                            <img src="'.$safePoster.'" alt="'.$safeTitle.' video thumbnail" class="course-youtube-poster" loading="lazy" data-youtube-fallback="'.$fallbackPoster.'">'."\n"
            .'                            <span class="course-youtube-play-icon" aria-hidden="true"><i class="fas fa-play"></i></span>'."\n"
            .'                        </button>'."\n"
            .'                    </div>';
    }
}
