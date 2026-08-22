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
            <input type="hidden" name="background_position_x" value="{{ old('background_position_x', $course['background_position_x'] ?? 50) }}">
            <input type="hidden" name="background_position_y" value="{{ old('background_position_y', $course['background_position_y'] ?? 50) }}">

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
                        <label for="background_image_file">Background Image <span class="admin-note">(up to 20 MB)</span></label>
                        <input id="background_image_file" type="file" name="background_image_file" class="admin-input" accept="image/jpeg,image/png,image/webp,image/gif">
                        @error('background_image_file')
                            <p class="admin-error">{{ $message }}</p>
                        @enderror
                        @if (! empty($course['background_image']))
                            <p class="admin-note">Current: {{ $course['background_image'] }}</p>
                            <label class="admin-checkbox">
                                <input type="checkbox" name="remove_background_image" value="1">
                                Remove current image
                            </label>
                        @endif
                        <label for="background_image_library" style="margin-top: 0.75rem;">Or choose from Media Library</label>
                        <select id="background_image_library" class="admin-select" onchange="document.querySelector('[name=background_image]').value = this.value">
                            <option value="" @selected(old('background_image', $course['background_image'] ?? '') === '')>No background image</option>
                            @foreach ($backgroundMedia as $media)
                                <option value="{{ $media['path'] }}" @selected(old('background_image', $course['background_image'] ?? '') === $media['path'])>{{ $media['filename'] }}</option>
                            @endforeach
                        </select>
                        <a href="{{ route('admin.media.index', ['directory' => 'course-backgrounds']) }}" class="admin-note" style="display: inline-block; margin-top: 0.5rem;">Manage images in Media Library</a>
                    </div>

                    <div class="admin-background-controls">
                        <div id="background-preview" class="admin-background-preview" style="background-image: {{ !empty($course['background_image']) ? "url('".e(asset(ltrim($course['background_image'], '/')))."')" : 'none' }}; background-position: {{ old('background_position_x', $course['background_position_x'] ?? 50) }}% {{ old('background_position_y', $course['background_position_y'] ?? 50) }}%;" title="Drag to reposition the background">
                            <span>Preview · drag image to reposition</span>
                        </div>
                        <div class="admin-range-row">
                            <div class="admin-range-label"><label for="background_position_x_range">Horizontal position</label><output id="background_position_x_output">{{ old('background_position_x', $course['background_position_x'] ?? 50) }}%</output></div>
                            <input id="background_position_x_range" type="range" min="0" max="100" value="{{ old('background_position_x', $course['background_position_x'] ?? 50) }}">
                        </div>
                        <div class="admin-range-row">
                            <div class="admin-range-label"><label for="background_position_y_range">Vertical position</label><output id="background_position_y_output">{{ old('background_position_y', $course['background_position_y'] ?? 50) }}%</output></div>
                            <input id="background_position_y_range" type="range" min="0" max="100" value="{{ old('background_position_y', $course['background_position_y'] ?? 50) }}">
                        </div>
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
                <button type="button" id="course-live-preview" class="admin-btn admin-btn-secondary"><i class="fas fa-eye"></i> Preview Unsaved Course</button>
                <a href="{{ route('admin.courses.index') }}" class="admin-btn admin-btn-secondary">Back</a>
                @if ($isEdit)
                    <a href="{{ url('/courses/'.$course['slug'].'.html') }}" target="_blank" class="admin-btn admin-btn-secondary">Preview</a>
                @endif
            </div>
        </form>
    </section>
@endsection

@push('scripts')
<script>
(() => {
    const preview = document.getElementById('background-preview');
    const fileInput = document.getElementById('background_image_file');
    const library = document.getElementById('background_image_library');
    const xInput = document.querySelector('[name="background_position_x"]');
    const yInput = document.querySelector('[name="background_position_y"]');
    const xRange = document.getElementById('background_position_x_range');
    const yRange = document.getElementById('background_position_y_range');
    if (!preview || !xInput || !yInput) return;

    const updatePosition = (x, y) => {
        x = Math.max(0, Math.min(100, Math.round(x)));
        y = Math.max(0, Math.min(100, Math.round(y)));
        xInput.value = x;
        yInput.value = y;
        xRange.value = x;
        yRange.value = y;
        document.getElementById('background_position_x_output').textContent = x + '%';
        document.getElementById('background_position_y_output').textContent = y + '%';
        preview.style.backgroundPosition = x + '% ' + y + '%';
    };

    const setPreviewImage = (url) => {
        preview.style.backgroundImage = url ? "url('" + url.replaceAll("'", "%27") + "')" : 'none';
        preview.querySelector('span').style.display = url ? 'none' : 'block';
    };

    xRange.addEventListener('input', () => updatePosition(xRange.value, yInput.value));
    yRange.addEventListener('input', () => updatePosition(xInput.value, yRange.value));
    library?.addEventListener('change', () => {
        document.querySelector('[name="background_image"]').value = library.value;
        setPreviewImage(library.value ? '{{ asset('') }}' + library.value.replace(/^\//, '') : '');
    });
    fileInput?.addEventListener('change', () => {
        const file = fileInput.files?.[0];
        if (file) setPreviewImage(URL.createObjectURL(file));
    });

    let dragging = false;
    let startX = 0;
    let startY = 0;
    let originX = 50;
    let originY = 50;
    preview.addEventListener('pointerdown', (event) => {
        dragging = true;
        preview.setPointerCapture(event.pointerId);
        startX = event.clientX;
        startY = event.clientY;
        originX = Number(xInput.value);
        originY = Number(yInput.value);
    });
    preview.addEventListener('pointermove', (event) => {
        if (!dragging) return;
        updatePosition(originX + (event.clientX - startX) / preview.clientWidth * 100, originY + (event.clientY - startY) / preview.clientHeight * 100);
    });
    preview.addEventListener('pointerup', () => dragging = false);
    preview.addEventListener('pointercancel', () => dragging = false);
})();

(() => {
    const button = document.getElementById('course-live-preview');
    const form = button?.closest('form');
    if (!button || !form) return;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>\"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#039;'}[char]));
    const lines = (value) => String(value ?? '').split(/\r?\n/).map((item) => item.trim()).filter(Boolean);
    const pathUrl = (value) => {
        value = String(value ?? '').trim();
        if (!value) return '';
        if (/^https?:\/\//i.test(value) || value.startsWith('/')) return value;
        return '/' + value.replace(/^\.\.\//, '').replace(/^\//, '');
    };

    button.addEventListener('click', () => {
        const data = new FormData(form);
        const value = (name) => data.get(name) || '';
        const selectedFile = document.getElementById('background_image_file')?.files?.[0];
        const backgroundUrl = selectedFile
            ? URL.createObjectURL(selectedFile)
            : pathUrl(value('background_image'));
        const imageUrl = pathUrl(value('image'));
        const positionX = value('background_position_x') || 50;
        const positionY = value('background_position_y') || 50;
        const darkness = value('background_darkness') || 0;
        const blur = value('background_blur') || 0;
        const title = escapeHtml(value('title') || 'Course Preview');
        const preview = window.open('', '_blank');
        if (!preview) return;

        const meta = [value('duration'), value('certification'), value('diploma_type')]
            .filter(Boolean).map((item) => `<span class="course-detail-meta-item"><i class="fas fa-circle-check"></i> ${escapeHtml(item)}</span>`).join('');
        const list = (text) => lines(text).map((item) => `<li><i class="fas fa-check"></i> ${escapeHtml(item)}</li>`).join('');
        const section = (heading, text, listMode = false) => {
            if (!String(text).trim()) return '';
            return `<article class="tab-panel course-block"><h3>${heading}</h3>${listMode ? `<ul>${list(text)}</ul>` : `<p>${escapeHtml(text)}</p>`}</article>`;
        };
        const backgroundStyle = backgroundUrl
            ? `background-image:url('${backgroundUrl.replaceAll("'", '%27')}');background-position:${positionX}% ${positionY}%;filter:blur(${blur}px);`
            : '';
        const darknessOverlay = backgroundUrl ? `<div class="course-detail-background-overlay" style="background:rgba(0,0,0,${Number(darkness) / 100});"></div>` : '';
        const heroClass = backgroundUrl ? ' has-course-background' : '';

        preview.document.write(`<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${title} | Preview</title><link rel="stylesheet" href="{{ asset('css/style.css') }}"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></head><body>
            <div style="position:sticky;top:0;z-index:20;padding:.75rem 1rem;background:#081a33;color:#fff;text-align:center;font-weight:700;">UNSAVED COURSE PREVIEW — close this tab to return to editing</div>
            <section class="course-detail-hero${heroClass}">
                ${backgroundUrl ? `<div class="course-detail-background-media" style="${backgroundStyle}"></div>${darknessOverlay}` : ''}
                <div class="container"><div class="course-detail-grid"><div class="course-detail-content"><h1>${title}</h1><div class="course-detail-meta">${meta}</div><p class="course-detail-description">${escapeHtml(value('description'))}</p></div><aside class="course-detail-card">${imageUrl ? `<img src="${escapeHtml(imageUrl)}" alt="${title}" class="course-detail-image">` : ''}${value('price') ? `<div class="course-detail-price"><div class="price">${escapeHtml(value('price'))}</div><div class="price-note">${escapeHtml(value('price_note'))}</div></div>` : ''}${value('highlights') ? `<div class="course-detail-features"><h4>Program Highlights</h4><ul>${list(value('highlights'))}</ul></div>` : ''}<a class="btn btn-primary" href="#">Enroll Now</a></aside></div></div>
            </section><section class="course-content-section"><div class="container"><div class="course-content-grid">${section('Program Overview', value('overview'))}${section('What You Will Learn', value('learning_outcomes'), true)}${section('Who Should Enroll', value('target_audience'), true)}${section('Career Opportunities', value('careers'), true)}</div></div></section>
        </body></html>`);
        preview.document.close();
    });
})();
</script>
@endpush
