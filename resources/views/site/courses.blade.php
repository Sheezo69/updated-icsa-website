@extends('layouts.site')

@section('title', 'Our Courses | ICSA - International Institute of Computer Science and Administration')
@section('description', 'Explore professional courses at ICSA Kuwait. IT, UK Diploma Programs, and Language courses. Enroll now and build your career.')
@php($showHeaderLogin = false)

@section('content')
    <section class="page-header">
        <div class="container">
            <h1>Our Courses</h1>
            <p>Discover professional courses designed to help you build a successful career in IT, Business, and more.</p>
            <div class="breadcrumb">
                <a href="{{ route('site.home') }}">Home</a>
                <span>/</span>
                <span>Courses</span>
            </div>
        </div>
    </section>

    <section class="course-filter">
        <div class="container">
            <div class="course-search" role="search">
                <div class="course-search-input-container">
                    <input type="search" id="courseSearch" class="course-search-input" placeholder="Search courses..." autocomplete="off" aria-label="Search courses">
                    <span class="course-search-icon" aria-hidden="true">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14 5H20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 8H17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21 11.5C21 16.75 16.75 21 11.5 21C6.25 21 2 16.75 2 11.5C2 6.25 6.25 2 11.5 2" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M22 22L20 20" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </div>
                <p class="course-search-status" id="courseSearchStatus" aria-live="polite"></p>
            </div>
            <div class="filter-buttons">
                <button type="button" class="filter-btn active" data-filter="all" aria-pressed="true">All Courses</button>
                <button type="button" class="filter-btn" data-filter="it" aria-pressed="false">IT & Technical</button>
                <button type="button" class="filter-btn" data-filter="diploma" aria-pressed="false">UK Diploma Programs</button>
                <button type="button" class="filter-btn" data-filter="language" aria-pressed="false">Language & Professional</button>
                <button type="button" class="filter-btn" data-filter="nursing" aria-pressed="false">Nursing & Healthcare</button>
                <button type="button" class="filter-btn" data-filter="design" aria-pressed="false">Design & Multimedia</button>
                <button type="button" class="filter-btn" data-filter="short-skills" aria-pressed="false">Short Skill Courses</button>
            </div>
        </div>
    </section>

    <section class="section featured-courses" style="padding-top: 3rem;">
        <div class="container">
            <div class="courses-grid" id="coursesGrid">
                @forelse ($courses as $course)
                    @php($searchText = implode(' ', array_filter([
                        $course['title'] ?? '',
                        $course['slug'] ?? '',
                        $course['badge'] ?? '',
                        $course['listing_category_label'] ?? '',
                        $course['description'] ?? '',
                        $course['overview'] ?? '',
                        $course['learning_outcomes'] ?? '',
                        $course['target_audience'] ?? '',
                        $course['careers'] ?? '',
                        $course['duration'] ?? '',
                        $course['certification'] ?? '',
                    ])))
                    <article class="course-card" data-category="{{ implode(',', $course['listing_categories'] ?? [$course['listing_category']]) }}" data-search="{{ $searchText }}">
                        <div class="course-image">
                            @if ($course['listing_image_url'])
                                <img src="{{ $course['listing_image_url'] }}" alt="{{ $course['title'] }}" loading="lazy">
                            @else
                                <div class="course-image-placeholder">
                                    <i class="fas fa-graduation-cap"></i>
                                    <p>{{ $course['title'] }}</p>
                                </div>
                            @endif
                        </div>
                        <div class="course-content">
                            <span class="course-category">{{ $course['badge'] ?: $course['listing_category_label'] }}</span>
                            <h3 class="course-title">{{ $course['title'] }}</h3>
                            <p class="course-description">{{ $course['description'] ?: 'Professional training at ICSA Kuwait.' }}</p>
                            <div class="course-meta">
                                @if ($course['duration'] !== '')
                                    <span class="course-meta-item"><i class="fas fa-clock"></i> {{ $course['duration'] }}</span>
                                @endif
                                @if ($course['certification'] !== '')
                                    <span class="course-meta-item"><i class="fas fa-signal"></i> {{ $course['certification'] }}</span>
                                @endif
                            </div>
                            <div class="course-footer">
                                <a href="{{ route('site.course', $course['slug']) }}" class="btn btn-secondary btn-sm">View Details</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="course-card">
                        <div class="course-content">
                            <h3 class="course-title">No Courses Found</h3>
                            <p class="course-description">Courses added from the admin portal will appear here.</p>
                        </div>
                    </div>
                @endforelse
            </div>
            <p class="course-search-empty" id="courseSearchEmpty" hidden>No courses match your search.</p>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Can't Find What You're Looking For?</h2>
                <p>Contact us to learn more about our courses and find the perfect program for your career goals.</p>
                <div class="cta-buttons">
                    <a href="{{ route('site.contact') }}" class="btn btn-secondary btn-lg">Contact Us</a>
                    <a href="https://wa.me/96597674076" class="btn btn-outline btn-lg" style="border-color: var(--primary-dark); color: var(--primary-dark);" target="_blank" rel="noopener">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
