<div class="course-detail-video-frame course-youtube-player" data-youtube-id="{{ $videoId }}">
    <button type="button" class="course-youtube-play" aria-label="Play {{ $title }} video">
        <img
            src="{{ $posterUrl }}"
            alt="{{ $title }} video thumbnail"
            class="course-youtube-poster"
            loading="lazy"
            data-youtube-fallback="{{ \App\Support\YoutubeVideo::thumbnailUrl($videoId, 'hqdefault') }}"
        >
        <span class="course-youtube-play-icon" aria-hidden="true"><i class="fas fa-play"></i></span>
    </button>
</div>
