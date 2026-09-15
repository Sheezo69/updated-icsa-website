@extends('admin.layout')

@section('title', 'Media Library')
@section('subtitle', 'Upload, preview, rename, and remove course images.')

@section('content')
    <section class="admin-card">
        <div class="admin-panel-header">
            <div>
                <h2 class="admin-section-title">Upload image</h2>
                <p class="admin-section-subtitle">JPG, PNG, WEBP, or GIF up to 20 MB.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="admin-form-grid">
            @csrf
            <div class="admin-field">
                <label for="file">Image</label>
                <input id="file" type="file" name="file" class="admin-input" accept="image/jpeg,image/png,image/webp,image/gif" required>
            </div>
            <div class="admin-field">
                <label for="directory">Use as</label>
                <select id="directory" name="directory" class="admin-select">
                    @foreach ($directories as $key => $label)
                        <option value="{{ $key }}" @selected($directory === $key || ($directory === null && $key === 'course-backgrounds'))>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="admin-field">
                <label for="name">Name (optional)</label>
                <input id="name" name="name" class="admin-input" placeholder="e.g. IT classroom hero">
            </div>
            <div class="admin-actions admin-field-full">
                <button type="submit" class="admin-btn admin-btn-primary"><i class="fas fa-upload"></i> Upload Image</button>
            </div>
        </form>
    </section>

    <section class="admin-card" style="margin-top: 1rem;">
        <div class="admin-panel-header">
            <div>
                <h2 class="admin-section-title">Image library</h2>
                <p class="admin-section-subtitle">Renaming updates course references automatically. Images in use cannot be deleted.</p>
            </div>
            <div class="admin-inline-actions">
                <a href="{{ route('admin.media.index') }}" class="admin-btn admin-btn-secondary">All</a>
                @foreach ($directories as $key => $label)
                    <a href="{{ route('admin.media.index', ['directory' => $key]) }}" class="admin-btn admin-btn-secondary">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="admin-media-grid">
            @forelse ($items as $item)
                <article class="admin-media-card">
                    <img src="{{ $item['url'] }}" alt="{{ $item['filename'] }}" class="admin-media-preview">
                    <div class="admin-media-card-body">
                        <strong title="{{ $item['filename'] }}">{{ $item['filename'] }}</strong>
                        <span class="admin-note">{{ $directories[$item['directory']] }} · {{ number_format($item['size'] / 1048576, 2) }} MB</span>
                        @if ($item['used_by'] !== [])
                            <span class="admin-badge admin-badge-success">Used by {{ implode(', ', array_column($item['used_by'], 'title')) }}</span>
                        @else
                            <span class="admin-muted">Not assigned to a course</span>
                        @endif
                        <div class="admin-media-actions">
                            <form method="POST" action="{{ route('admin.media.rename') }}" class="admin-media-rename">
                                @csrf
                                <input type="hidden" name="path" value="{{ $item['path'] }}">
                                <input name="name" class="admin-input" value="{{ pathinfo($item['filename'], PATHINFO_FILENAME) }}" aria-label="New image name">
                                <button type="submit" class="admin-btn admin-btn-secondary">Rename</button>
                            </form>
                            <form method="POST" action="{{ route('admin.media.destroy') }}" onsubmit="return confirm('Delete this image?');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="path" value="{{ $item['path'] }}">
                                <button type="submit" class="admin-delete-button" @disabled($item['used_by'] !== [])><span class="text">Delete</span><span class="icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path></svg></span></button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <p class="admin-empty">No images uploaded yet.</p>
            @endforelse
        </div>
    </section>
@endsection
