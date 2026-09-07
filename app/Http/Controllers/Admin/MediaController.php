<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CourseFileRepository;
use App\Support\MediaLibrary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function index(Request $request, MediaLibrary $media, CourseFileRepository $courses): View
    {
        $directory = $request->string('directory')->toString() ?: null;
        $items = $media->all($directory);

        foreach ($items as &$item) {
            $item['used_by'] = $courses->mediaUsage($item['path']);
        }

        return view('admin.media.index', [
            'items' => $items,
            'directory' => $directory,
            'directories' => MediaLibrary::DIRECTORIES,
        ]);
    }

    public function store(Request $request, MediaLibrary $media): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:20480'],
            'directory' => ['required', 'in:course-backgrounds,course-posters,course-video-thumbnails'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $media->store($data['file'], $data['directory'], $data['name'] ?? null);

        return back()->with('success', 'Image uploaded successfully.');
    }

    public function rename(Request $request, MediaLibrary $media, CourseFileRepository $courses): RedirectResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:120'],
        ]);

        try {
            $newPath = $media->rename($data['path'], $data['name']);
            $courses->replaceMediaPath($data['path'], $newPath);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Image renamed successfully as '.basename($newPath).'.');
    }

    public function destroy(Request $request, MediaLibrary $media, CourseFileRepository $courses): RedirectResponse
    {
        $data = $request->validate(['path' => ['required', 'string', 'max:255']]);
        $usage = $courses->mediaUsage($data['path']);

        if ($usage !== []) {
            return back()->with('error', 'This image is used by: '.implode(', ', array_column($usage, 'title')).'. Remove it from those courses before deleting it.');
        }

        try {
            $media->delete($data['path']);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Image deleted successfully.');
    }
}
