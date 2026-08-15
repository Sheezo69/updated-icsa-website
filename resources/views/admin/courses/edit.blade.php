@extends('admin.layout')

@section('title', $isEdit ? 'Edit Course' : 'Add Course')
@section('subtitle', 'Update the course HTML content while keeping the legacy frontend structure intact.')

@section('content')
    <section class="admin-card">
        <form method="POST" action="{{ $isEdit ? route('admin.courses.update', $course['slug']) : route('admin.courses.store') }}" class="admin-form-grid" enctype="multipart/form-data">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif
            <input type="hidden" name="video_path" value="{{ old('video_path', $course['video_path'] ?? '') }}">
            <input type="hidden" name="video_thumbnail" value="{{ old('video_thumbnail', $course['video_thumbnail'] ?? '') }}">

            <div class="admin-field">
                <label for="title">Title</label>
                <input id="title" name="title" class="admin-input" value="{{ old('title', $course['title']) }}" required>
            </div>

            <div class="admin-field">
                <label for="badge">Badge / Category</label>
                <input id="badge" name="badge" class="admin-input" value="{{ old('badge', $course['badge']) }}">
            </div>

            <div class="admin-field">
                <label for="duration">Duration</label>
                <input id="duration" name="duration" class="admin-input" value="{{ old('duration', $course['duration']) }}">
            </div>

            <div class="admin-field">
                <label for="certification">Level / Meta</label>
                <input id="certification" name="certification" class="admin-input" value="{{ old('certification', $course['certification']) }}">
            </div>

            <div class="admin-field">
                <label for="diploma_type">Certificate Type</label>
                <input id="diploma_type" name="diploma_type" class="admin-input" value="{{ old('diploma_type', $course['diploma_type']) }}">
            </div>

            <div class="admin-field">
                <label for="image">Image Path</label>
                <input id="image" name="image" class="admin-input" value="{{ old('image', $course['image']) }}" placeholder="../images/detail-course.jpg">
            </div>

            <div class="admin-field">
                <label for="video_file">Course Video</label>
                <input id="video_file" type="file" name="video_file" class="admin-input" accept="video/mp4,video/webm,video/ogg">
                @if (! empty($course['video_path']))
                    <p class="admin-note" style="margin-top: 0.5rem;">Current: {{ $course['video_path'] }}</p>
                    <label class="admin-note" style="display: inline-flex; align-items: center; gap: 0.45rem; margin-top: 0.5rem;">
                        <input type="checkbox" name="remove_video" value="1">
                        Remove video
                    </label>
                @endif
            </div>

            <div class="admin-field">
                <label for="video_thumbnail_file">Video Thumbnail</label>
                <input id="video_thumbnail_file" type="file" name="video_thumbnail_file" class="admin-input" accept="image/jpeg,image/png,image/webp">
                @if (! empty($course['video_thumbnail']))
                    <p class="admin-note" style="margin-top: 0.5rem;">Current: {{ $course['video_thumbnail'] }}</p>
                    <label class="admin-note" style="display: inline-flex; align-items: center; gap: 0.45rem; margin-top: 0.5rem;">
                        <input type="checkbox" name="remove_video_thumbnail" value="1">
                        Remove thumbnail
                    </label>
                @else
                    <p class="admin-note" style="margin-top: 0.5rem;">If empty, the course image is used as the video poster.</p>
                @endif
            </div>

            <div class="admin-field admin-field-full">
                <label for="description">Short Description</label>
                <textarea id="description" name="description" class="admin-textarea">{{ old('description', $course['description']) }}</textarea>
            </div>

            <div class="admin-field">
                <label for="price">Price Label</label>
                <input id="price" name="price" class="admin-input" value="{{ old('price', $course['price']) }}">
            </div>

            <div class="admin-field">
                <label for="price_note">Price Note</label>
                <input id="price_note" name="price_note" class="admin-input" value="{{ old('price_note', $course['price_note']) }}">
            </div>

            <div class="admin-field admin-field-full">
                <label for="highlights">Highlights</label>
                <textarea id="highlights" name="highlights" class="admin-textarea">{{ old('highlights', $course['highlights']) }}</textarea>
            </div>

            <div class="admin-field admin-field-full">
                <label for="overview">Overview</label>
                <textarea id="overview" name="overview" class="admin-textarea">{{ old('overview', $course['overview']) }}</textarea>
            </div>

            <div class="admin-field admin-field-full">
                <label for="learning_outcomes">Learning Outcomes</label>
                <textarea id="learning_outcomes" name="learning_outcomes" class="admin-textarea">{{ old('learning_outcomes', $course['learning_outcomes']) }}</textarea>
            </div>

            <div class="admin-field admin-field-full">
                <label for="target_audience">Target Audience</label>
                <textarea id="target_audience" name="target_audience" class="admin-textarea">{{ old('target_audience', $course['target_audience']) }}</textarea>
            </div>

            <div class="admin-field admin-field-full">
                <label for="careers">Career Opportunities</label>
                <textarea id="careers" name="careers" class="admin-textarea">{{ old('careers', $course['careers']) }}</textarea>
            </div>

            <div class="admin-actions admin-field-full">
                <button type="submit" class="admin-btn admin-btn-primary">{{ $isEdit ? 'Update Course' : 'Create Course' }}</button>
                <a href="{{ route('admin.courses.index') }}" class="admin-btn admin-btn-secondary">Back</a>
                @if ($isEdit)
                    <a href="{{ url('/courses/'.$course['slug'].'.html') }}" target="_blank" class="admin-btn admin-btn-secondary">Preview</a>
                @endif
            </div>
        </form>
    </section>
@endsection
