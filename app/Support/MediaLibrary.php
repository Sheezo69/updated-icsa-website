<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaLibrary
{
    public const DIRECTORIES = [
        'course-backgrounds' => 'Course backgrounds',
        'course-video-thumbnails' => 'Video thumbnails',
    ];

    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function all(?string $directory = null): array
    {
        $directories = $directory && isset(self::DIRECTORIES[$directory])
            ? [$directory]
            : array_keys(self::DIRECTORIES);

        $media = [];
        foreach ($directories as $folder) {
            foreach (Storage::disk('public')->files($folder) as $path) {
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (! in_array($extension, self::EXTENSIONS, true)) {
                    continue;
                }

                $media[] = [
                    'path' => '/storage/'.$path,
                    'directory' => $folder,
                    'filename' => basename($path),
                    'size' => Storage::disk('public')->size($path),
                    'modified' => Storage::disk('public')->lastModified($path),
                    'url' => asset('storage/'.$path),
                ];
            }
        }

        usort($media, static fn (array $left, array $right): int => $right['modified'] <=> $left['modified']);

        return $media;
    }

    public function store(UploadedFile $file, string $directory, ?string $name = null): string
    {
        $this->assertDirectory($directory);
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $base = Str::slug(pathinfo((string) ($name ?: $file->getClientOriginalName()), PATHINFO_FILENAME)) ?: 'image';
        $filename = $base.'.'.$extension;

        if (Storage::disk('public')->exists($directory.'/'.$filename)) {
            $filename = $base.'-'.Str::lower(Str::random(6)).'.'.$extension;
        }

        return '/storage/'.Storage::disk('public')->putFileAs($directory, $file, $filename);
    }

    public function rename(string $path, string $name): string
    {
        [$directory, $filename] = $this->splitPath($path);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $newBase = Str::slug(pathinfo($name, PATHINFO_FILENAME));
        if ($newBase === '') {
            throw new \InvalidArgumentException('Enter a valid filename.');
        }

        $newFilename = $newBase.'.'.$extension;
        $newPath = $directory.'/'.$newFilename;
        $suffix = 2;
        while ($newPath !== $directory.'/'.$filename && Storage::disk('public')->exists($newPath)) {
            $newFilename = $newBase.'-'.$suffix.'.'.$extension;
            $newPath = $directory.'/'.$newFilename;
            $suffix++;
        }

        Storage::disk('public')->move($directory.'/'.$filename, $newPath);

        return '/storage/'.$newPath;
    }

    public function delete(string $path): void
    {
        [$directory, $filename] = $this->splitPath($path);
        Storage::disk('public')->delete($directory.'/'.$filename);
    }

    public function splitPath(string $path): array
    {
        $relative = ltrim(Str::after(trim($path), '/storage/'), '/');
        $directory = dirname($relative);
        $filename = basename($relative);

        $this->assertDirectory($directory);
        if ($filename === '' || $filename === '.' || $filename === '..' || $filename !== basename($filename)) {
            throw new \InvalidArgumentException('Invalid media path.');
        }

        return [$directory, $filename];
    }

    private function assertDirectory(string $directory): void
    {
        if (! isset(self::DIRECTORIES[$directory])) {
            throw new \InvalidArgumentException('Invalid media directory.');
        }
    }
}
