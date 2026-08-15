@extends('layouts.site')

@section('title', $course['title'].' | ICSA Kuwait')
@section('description', $course['description'])
@php($showHeaderLogin = false)

@section('content')
    <section class="course-detail-hero">
        <div class="container">
            <div class="course-back-row">
                <a href="{{ route('site.home') }}#courses" class="course-back-link"><i class="fas fa-arrow-left"></i> Back to Courses</a>
            </div>
            <div class="course-detail-grid">
                <div class="course-detail-content">
                    <span class="hero-label">{{ $course['badge'] }}</span>
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
                    <p class="course-detail-description">{{ $course['description'] }}</p>
                </div>
                <aside class="course-detail-card">
                    @if ($course['video_url'])
                        <div class="course-detail-video-frame">
                            <video class="course-detail-video" controls preload="metadata" @if ($course['video_thumbnail_url']) poster="{{ $course['video_thumbnail_url'] }}" @endif>
                                <source src="{{ $course['video_url'] }}">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    @elseif ($course['detail_image_url'])
                        <img src="{{ $course['detail_image_url'] }}" alt="{{ $course['title'] }}" class="course-detail-image" loading="lazy">
                    @endif
                    <div class="course-detail-price">
                        <div class="price">{{ $course['price'] }}</div>
                        <div class="price-note">{{ $course['price_note'] }}</div>
                    </div>
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
                    <a href="{{ route('site.home', ['course' => $course['slug']]) }}#contact" class="btn btn-primary">Enroll Now</a>
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
                                <li><i class="fas fa-check"></i> {{ $item }}</li>
                            @endforeach
                        </ul>
                    </article>
                @endif

                @if (! empty($course['target_audience_items']))
                    <article class="tab-panel course-block">
                        <h3>Who Should Enroll</h3>
                        <ul>
                            @foreach ($course['target_audience_items'] as $item)
                                <li><i class="fas fa-check"></i> {{ $item }}</li>
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
