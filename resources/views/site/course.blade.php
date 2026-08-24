@extends('layouts.site')

@section('title', $course['title'].' | ICSA Kuwait')
@section('description', $course['description'])
@php($showHeaderLogin = false)

@section('content')
    <section class="course-detail-hero @if (! empty($course['background_image_url'])) has-course-background @endif" style="--course-background-darkness: {{ $course['background_darkness'] ?? 0 }}%; --course-background-blur: {{ $course['background_blur'] ?? 0 }}px;">
        @if (! empty($course['background_image_url']))
            <div class="course-detail-background-media" style="background-image: url('{{ $course['background_image_url'] }}');" aria-hidden="true"></div>
            <div class="course-detail-background-overlay" aria-hidden="true"></div>
        @endif
        <div class="container">
            <div class="course-detail-grid">
                <div class="course-detail-content">
                    <h1>{{ $course['title'] }}</h1>
                    <div class="course-detail-meta">
                        @if ($course['duration'] !== '')
                            <span class="course-detail-meta-item"><i class="fas fa-clock"></i> {{ $course['duration'] }}</span>
                        @endif
                        @if ($course['certification'] !== '')
                            <span class="course-detail-meta-item"><i class="fas fa-signal"></i> {{ $course['certification'] }}</span>
                        @endif
                        @if ($course['diploma_type'] !== '')
                            <span class="course-detail-meta-item"><i class="fas fa-certificate"></i> {{ $course['diploma_type'] }}</span>
                        @endif
                    </div>
                    <p class="course-detail-description" style="margin: 0; padding: 1rem 1.2rem; border: 1px solid rgba(255,255,255,0.2); border-radius: 14px; background: rgba(3,13,28,0.62); box-shadow: 0 10px 24px rgba(0,0,0,0.16); backdrop-filter: blur(6px);">{{ $course['description'] }}</p>
                </div>
                <aside class="course-detail-card">
                    @if ($course['youtube_video_id'] && $course['video_poster_url'])
                        @include('site.partials.youtube-player', [
                            'videoId' => $course['youtube_video_id'],
                            'posterUrl' => $course['video_poster_url'],
                            'title' => $course['title'],
                        ])
                    @elseif ($course['detail_image_url'])
                        <img src="{{ $course['detail_image_url'] }}" alt="{{ $course['title'] }}" class="course-detail-image" loading="lazy">
                    @endif
                    @if ($course['price'] !== '' && strcasecmp($course['price'], 'Contact for Price') !== 0)
                        <div class="course-detail-price">
                            <div class="price">{{ $course['price'] }}</div>
                            @if ($course['price_note'] !== '')
                                <div class="price-note">{{ $course['price_note'] }}</div>
                            @endif
                        </div>
                    @endif
                    @if (! empty($course['highlight_items']))
                        <div class="course-detail-features">
                            <h4>Program Highlights</h4>
                            <ul>
                                @foreach ($course['highlight_items'] as $item)
                                    <li><i class="fas fa-check"></i> {{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <a href="#reserve-a-spot" class="btn btn-primary">Enroll Now</a>
                </aside>
            </div>
        </div>
    </section>

    <section class="course-content-section">
        <div class="container">
            <div class="course-content-grid">
                @if ($course['overview'] !== '')
                    <article class="tab-panel course-block">
                        <h3>Program Overview</h3>
                        <p>{{ $course['overview'] }}</p>
                    </article>
                @endif

                @if (! empty($course['learning_outcome_items']))
                    <article class="tab-panel course-block">
                        <h3>What You Will Learn</h3>
                            <ul>
                                @foreach ($course['learning_outcome_items'] as $item)
                                <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                    </article>
                @endif

                @if (! empty($course['target_audience_items']))
                    <article class="tab-panel course-block">
                        <h3>Who Should Enroll</h3>
                            <ul>
                                @foreach ($course['target_audience_items'] as $item)
                                <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                    </article>
                @endif

                @if (! empty($course['career_items']))
                    <article class="tab-panel course-block">
                        <h3>Career Opportunities</h3>
                        <div class="career-list">
                            @foreach ($course['career_items'] as $item)
                                <span class="career-tag">{{ $item }}</span>
                            @endforeach
                        </div>
                    </article>
                @endif
            </div>
        </div>
    </section>

    @include('site.partials.course-inquiry')
@endsection
