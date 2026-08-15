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

    <section class="admin-cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); margin-top: 1rem;">
        @forelse ($courses as $course)
            <article class="admin-card">
                <span class="admin-muted">{{ $course['badge'] ?: 'Uncategorized' }}</span>
                <h2 style="margin-top: 0.45rem;">{{ $course['title'] }}</h2>
                <p class="admin-note">{{ $course['duration'] ?: 'Duration not set' }}</p>
                <p class="admin-note">{{ $course['file_name'] }}</p>

                <div class="admin-actions" style="margin-top: 1rem;">
                    <a href="{{ url('/courses/'.$course['slug'].'.html') }}" target="_blank" class="admin-btn admin-btn-secondary">View</a>
                    <a href="{{ route('admin.courses.edit', $course['slug']) }}" class="admin-btn admin-btn-primary">Edit</a>
                    <form method="POST" action="{{ route('admin.courses.destroy', $course['slug']) }}" onsubmit="return confirm('Delete this course file?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="admin-btn admin-btn-danger">Delete</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="admin-card">
                <p class="admin-empty">No course files matched your search.</p>
            </div>
        @endforelse
    </section>
@endsection
