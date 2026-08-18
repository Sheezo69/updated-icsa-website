@extends('admin.layout')

@section('title', $isEdit ? 'Edit Course' : 'Add Course')
@section('subtitle', 'Manage the course details, media, and page appearance.')

@section('content')
    <section class="admin-card">
        <form method="POST" action="{{ $isEdit ? route('admin.courses.update', $course['slug'], false) : route('admin.courses.store', [], false) }}" class="admin-form-grid" enctype="multipart/form-data">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif
            <input type="hidden" name="video_thumbnail" value="{{ old('video_thumbnail', $course['video_thumbnail'] ?? '') }}">
            <input type="hidden" name="background_image" value="{{ old('background_image', $course['background_image'] ?? '') }}">

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

            <div class="admin-background-panel admin-field-full">
                <div class="admin-background-panel-header">
                    <div>
                        <h2>Hero Background</h2>
                        <p class="admin-note">Choose an image and adjust how it appears behind the course title.</p>
                    </div>
                    <span class="admin-background-chip">JPG · PNG · WEBP · GIF</span>
                </div>

                <div class="admin-background-grid">
                    <div class="admin-field">
                        <label for="background_image_file">Background Image</label>
                        <input id="background_image_file" type="file" name="background_image_file" class="admin-input" accept="image/jpeg,image/png,image/webp,image/gif">
                        @if (! empty($course['background_image']))
                            <p class="admin-note">Current: {{ $course['background_image'] }}</p>
                            <label class="admin-checkbox">
                                <input type="checkbox" name="remove_background_image" value="1">
                                Remove current image
                            </label>
                        @endif
                    </div>

                    <div class="admin-background-controls">
                        <div class="admin-range-row">
                            <div class="admin-range-label"><label for="background_darkness">Darkness</label><output>{{ old('background_darkness', $course['background_darkness'] ?? 0) }}%</output></div>
                            <input id="background_darkness" type="range" name="background_darkness" min="0" max="100" step="1" value="{{ old('background_darkness', $course['background_darkness'] ?? 0) }}" oninput="this.previousElementSibling.querySelector('output').value = this.value + '%'">
                        </div>
                        <div class="admin-range-row">
                            <div class="admin-range-label"><label for="background_blur">Blur</label><output>{{ old('background_blur', $course['background_blur'] ?? 0) }}px</output></div>
                            <input id="background_blur" type="range" name="background_blur" min="0" max="20" step="1" value="{{ old('background_blur', $course['background_blur'] ?? 0) }}" oninput="this.previousElementSibling.querySelector('output').value = this.value + 'px'">
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-field admin-field-full">
                <label for="youtube_url">YouTube Video URL</label>
                <input id="youtube_url" name="youtube_url" class="admin-input" value="{{ old('youtube_url', $course['youtube_url'] ?? '') }}" placeholder="https://www.youtube.com/watch?v=...">
                <p class="admin-note" style="margin-top: 0.5rem;">Paste a YouTube link. The video plays inline on the course page without leaving the site.</p>
            </div>

            <div class="admin-field">
                <label for="video_thumbnail_file">Video Thumbnail (optional)</label>
                <input id="video_thumbnail_file" type="file" name="video_thumbnail_file" class="admin-input" accept="image/jpeg,image/png,image/webp">
                @if (! empty($course['video_thumbnail']))
                    <p class="admin-note" style="margin-top: 0.5rem;">Current: {{ $course['video_thumbnail'] }}</p>
                    <label class="admin-note" style="display: inline-flex; align-items: center; gap: 0.45rem; margin-top: 0.5rem;">
                        <input type="checkbox" name="remove_video_thumbnail" value="1">
                        Remove custom thumbnail
                    </label>
                @else
                    <p class="admin-note" style="margin-top: 0.5rem;">If empty, the course image or YouTube preview image is used.</p>
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
