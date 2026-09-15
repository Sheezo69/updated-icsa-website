@extends('admin.layout')

@section('title', 'Courses')
@section('subtitle', 'Manage the file-based course detail pages now served through Laravel routes.')

@section('content')
    <section class="admin-card">
        <div class="admin-panel-header">
            <form method="GET" action="{{ route('admin.courses.index') }}" class="admin-inline-actions" style="flex: 1;">
                <input name="search" class="admin-input" value="{{ $search }}" placeholder="Search title, category, or slug">
                <button type="submit" class="admin-btn admin-btn-secondary">Search</button>
                <a href="{{ route('admin.courses.index') }}" class="admin-btn admin-btn-secondary">Clear</a>
            </form>

            <a href="{{ route('admin.courses.create') }}" class="admin-btn admin-btn-primary">
                <i class="fas fa-plus"></i> Add Course
            </a>
        </div>
    </section>

    <section class="admin-cards-grid admin-course-grid">
        @forelse ($courses as $course)
            <article class="admin-card admin-course-card">
                <div class="admin-course-copy">
                <span class="admin-muted">
                    {{ implode(' · ', array_map(static fn (string $category): string => match ($category) {
                        'diploma' => 'UK Diploma Programs',
                        'language' => 'Language & Professional',
                        'nursing' => 'Nursing & Healthcare',
                        'design' => 'Design & Multimedia',
                        'short-skills' => 'Short Skill Courses',
                        default => 'IT & Technical',
                    }, $course['categories'] ?? ['it'])) }}
                </span>
                <h2 style="margin-top: 0.45rem;">{{ $course['title'] }}</h2>
                <p class="admin-note">{{ $course['duration'] ?: 'Duration not set' }}</p>
                <p class="admin-note">{{ $course['file_name'] }}</p>
                </div>

                <div class="admin-course-actions">
                    <a href="{{ url('/courses/'.$course['slug'].'.html') }}" target="_blank" rel="noopener noreferrer" class="course-view-button">
                        <svg viewBox="0 0 24 24" class="arr-2" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M16.1716 10.9999L10.8076 5.63589L12.2218 4.22168L20 11.9999L12.2218 19.778L10.8076 18.3638L16.1716 12.9999H4V10.9999H16.1716Z"></path></svg>
                        <span class="text">View</span>
                        <span class="circle" aria-hidden="true"></span>
                        <svg viewBox="0 0 24 24" class="arr-1" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M16.1716 10.9999L10.8076 5.63589L12.2218 4.22168L20 11.9999L12.2218 19.778L10.8076 18.3638L16.1716 12.9999H4V10.9999H16.1716Z"></path></svg>
                    </a>
                    <a href="{{ route('admin.courses.edit', $course['slug']) }}" class="course-edit-button">
                        <span>Edit</span>
                        <span class="course-edit-blobs" aria-hidden="true"><i></i><i></i><i></i></span>
                    </a>
                    <form method="POST" action="{{ route('admin.courses.destroy', $course['slug']) }}" onsubmit="return confirm('Delete this course file?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="admin-delete-button"><span class="text">Delete</span><span class="icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path></svg></span></button>
                    </form>
                </div>
            </article>
        @empty
            <div class="admin-card">
                <p class="admin-empty">No course files matched your search.</p>
            </div>
        @endforelse
    </section>

    <svg xmlns="http://www.w3.org/2000/svg" class="course-goo-filter" aria-hidden="true" focusable="false">
        <defs>
            <filter id="course-goo">
                <feGaussianBlur in="SourceGraphic" stdDeviation="10" result="blur"></feGaussianBlur>
                <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 18 -7" result="goo"></feColorMatrix>
                <feBlend in="SourceGraphic" in2="goo"></feBlend>
            </filter>
        </defs>
    </svg>
@endsection
