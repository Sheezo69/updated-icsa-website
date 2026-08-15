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
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">All Courses</button>
                <button class="filter-btn" data-filter="it">IT & Technical</button>
                <button class="filter-btn" data-filter="diploma">UK Diploma Programs</button>
                <button class="filter-btn" data-filter="language">Language & Professional</button>
            </div>
        </div>
    </section>

    <section class="section featured-courses" style="padding-top: 3rem;">
        <div class="container">
            <div class="courses-grid" id="coursesGrid">
                @forelse ($courses as $course)
                    <article class="course-card" data-category="{{ $course['listing_category'] }}">
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
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Can't Find What You're Looking For?</h2>
                <p>Contact us to learn more about our courses and find the perfect program for your career goals.</p>
                <div class="cta-buttons">
                    <a href="{{ route('site.home') }}#contact" class="btn btn-secondary btn-lg">Contact Us</a>
                    <a href="https://wa.me/96597674076" class="btn btn-outline btn-lg" style="border-color: var(--primary-dark); color: var(--primary-dark);" target="_blank" rel="noopener">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
