<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CourseFileRepository;
use App\Support\MediaLibrary;
use App\Support\YoutubeVideo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CourseController extends Controller
{
    public function index(Request $request, CourseFileRepository $courses): View
    {
        $allCourses = array_map(function (array $course): array {
            $course['categories'] = $course['categories'] ?: $this->inferCategories($course);

            return $course;
        }, $courses->all());
        $search = trim($request->string('search')->toString());

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $allCourses = array_values(array_filter($allCourses, static function (array $course) use ($needle): bool {
                return str_contains(mb_strtolower($course['title']), $needle)
                    || str_contains(mb_strtolower($course['badge']), $needle)
                    || str_contains(mb_strtolower(implode(' ', $course['categories'])), $needle)
                    || str_contains(mb_strtolower($course['slug']), $needle);
            }));
        }

        return view('admin.courses.index', [
            'courses' => $allCourses,
            'search' => $search,
        ]);
    }

    public function create(MediaLibrary $media): View
    {
        return view('admin.courses.edit', [
            'course' => $this->blankCourse(),
            'isEdit' => false,
            'backgroundMedia' => $media->all('course-backgrounds'),
            'posterMedia' => $media->all('course-posters'),
        ]);
    }

    public function edit(string $slug, CourseFileRepository $courses, MediaLibrary $media): View
    {
        $course = $courses->find($slug);
        abort_if($course === null, 404);

        return view('admin.courses.edit', [
            'course' => array_merge($course, ['categories' => $course['categories'] ?: $this->inferCategories($course)]),
            'isEdit' => true,
            'backgroundMedia' => $media->all('course-backgrounds'),
            'posterMedia' => $media->all('course-posters'),
        ]);
    }

    public function store(Request $request, CourseFileRepository $courses): RedirectResponse
    {
        $data = $this->validatedCourse($request);
        $this->handleCourseThumbnailUpload($request, $data);
        $slug = $courses->save($data);

        return redirect()
            ->route('admin.courses.edit', $slug)
            ->with('success', 'Course saved successfully.');
    }

    public function update(Request $request, string $slug, CourseFileRepository $courses): RedirectResponse
    {
        $data = $this->validatedCourse($request);
        $this->handleCourseThumbnailUpload($request, $data);
        $newSlug = $courses->save($data, $slug);

        return redirect()
            ->route('admin.courses.edit', $newSlug)
            ->with('success', 'Course updated successfully.');
    }

    public function destroy(string $slug, CourseFileRepository $courses): RedirectResponse
    {
        $course = $courses->find($slug);
        if ($course) {
            $this->deletePublicMedia($course['video_thumbnail'] ?? '');
            $this->deletePublicMedia($course['poster_image'] ?? '');
            $this->deletePublicMedia($course['background_image'] ?? '');
            $this->deletePublicMedia($course['image'] ?? '');
        }

        $courses->delete($slug);

        return redirect()->route('admin.courses.index')->with('success', 'Course deleted.');
    }

    private function inferCategories(array $course): array
    {
        $text = Str::lower(implode(' ', [
            $course['title'] ?? '',
            $course['badge'] ?? '',
        ]));

        if (str_contains($text, 'diploma') || str_contains($text, 'uk')) {
            return ['diploma'];
        }

        if (str_contains($text, 'english') || str_contains($text, 'ielts') || str_contains($text, 'arabic') || str_contains($text, 'airline') || str_contains($text, 'travel') || str_contains($text, 'language') || str_contains($text, 'professional')) {
            return ['language'];
        }

        return ['it'];
    }

    private function validatedCourse(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'categories' => ['required', 'array', 'min:1', 'max:4'],
            'categories.*' => [Rule::in(['it', 'diploma', 'language', 'nursing', 'design', 'short-skills'])],
            'badge' => ['nullable', 'string', 'max:100'],
            'duration' => ['nullable', 'string', 'max:100'],
            'certification' => ['nullable', 'string', 'max:100'],
            'diploma_type' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'string', 'max:255'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:20480'],
            'remove_image' => ['nullable', 'boolean'],
            'poster_image' => ['nullable', 'string', 'max:255'],
            'poster_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:20480'],
            'remove_poster_image' => ['nullable', 'boolean'],
            'background_image' => ['nullable', 'string', 'max:255'],
            'background_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:20480'],
            'remove_background_image' => ['nullable', 'boolean'],
            'background_darkness' => ['nullable', 'integer', 'min:0', 'max:100'],
            'background_blur' => ['nullable', 'integer', 'min:0', 'max:20'],
            'youtube_url' => [
                'nullable',
                'string',
                'max:500',
                static function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value && ! YoutubeVideo::extractVideoId((string) $value)) {
                        $fail('Enter a valid YouTube link (watch, youtu.be, or embed URL).');
                    }
                },
            ],
            'video_thumbnail' => ['nullable', 'string', 'max:255'],
            'video_thumbnail_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_video_thumbnail' => ['nullable', 'boolean'],
            'price' => ['nullable', 'string', 'max:120'],
            'price_note' => ['nullable', 'string', 'max:255'],
            'highlights' => ['nullable', 'string'],
            'overview' => ['nullable', 'string'],
            'learning_outcomes' => ['nullable', 'string'],
            'target_audience' => ['nullable', 'string'],
            'careers' => ['nullable', 'string'],
        ]);
    }

    private function blankCourse(): array
    {
        return [
            'slug' => '',
            'title' => '',
            'categories' => ['it'],
            'badge' => '',
            'duration' => '',
            'certification' => 'Certified',
            'diploma_type' => '',
            'description' => '',
            'image' => '',
            'poster_image' => '',
            'background_image' => '',
            'background_darkness' => 0,
            'background_blur' => 0,
            'youtube_url' => '',
            'video_thumbnail' => '',
            'price' => '',
            'price_note' => 'Flexible payment options available',
            'highlights' => "Practical classroom approach\nCertificate on completion\nCareer-focused learning\nInstructor-led guidance",
            'overview' => '',
            'learning_outcomes' => '',
            'target_audience' => '',
            'careers' => '',
        ];
    }

    private function handleCourseThumbnailUpload(Request $request, array &$data): void
    {
        $slug = Str::slug((string) ($data['title'] ?? 'course')) ?: 'course';

        if ($request->boolean('remove_image')) {
            $this->deletePublicMedia($data['image'] ?? '');
            $data['image'] = '';
        }

        if ($request->hasFile('image_file')) {
            $oldPath = $data['image'] ?? '';
            $newPath = $this->storePublicMedia($request->file('image_file'), 'course-covers', $slug, 'image_file');
            $this->deletePublicMedia($oldPath);
            $data['image'] = $newPath;
        }

        if ($request->boolean('remove_poster_image')) {
            $this->deletePublicMedia($data['poster_image'] ?? '');
            $data['poster_image'] = '';
        }

        if ($request->hasFile('poster_image_file')) {
            $oldPath = $data['poster_image'] ?? '';
            $newPath = $this->storePublicMedia($request->file('poster_image_file'), 'course-posters', $slug, 'poster_image_file');
            $this->deletePublicMedia($oldPath);
            $data['poster_image'] = $newPath;
        }

        if ($request->boolean('remove_video_thumbnail')) {
            $this->deletePublicMedia($data['video_thumbnail'] ?? '');
            $data['video_thumbnail'] = '';
        }

        if ($request->hasFile('video_thumbnail_file')) {
            $oldPath = $data['video_thumbnail'] ?? '';
            $newPath = $this->storePublicMedia($request->file('video_thumbnail_file'), 'course-video-thumbnails', $slug);
            $this->deletePublicMedia($oldPath);
            $data['video_thumbnail'] = $newPath;
        }

        if ($request->boolean('remove_background_image')) {
            $this->deletePublicMedia($data['background_image'] ?? '');
            $data['background_image'] = '';
        }

        if ($request->hasFile('background_image_file')) {
            $oldPath = $data['background_image'] ?? '';
            $newPath = $this->storePublicMedia($request->file('background_image_file'), 'course-backgrounds', $slug);
            $this->deletePublicMedia($oldPath);
            $data['background_image'] = $newPath;
        }

        $data['background_darkness'] = (int) ($data['background_darkness'] ?? 0);
        $data['background_blur'] = (int) ($data['background_blur'] ?? 0);

        unset(
            $data['image_file'],
            $data['remove_image'],
            $data['poster_image_file'],
            $data['remove_poster_image'],
            $data['video_thumbnail_file'],
            $data['remove_video_thumbnail'],
            $data['background_image_file'],
            $data['remove_background_image'],
        );
    }

    private function storePublicMedia($file, string $directory, string $slug, string $errorField = 'background_image_file'): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $filename = $slug.'-'.Str::random(8).'.'.$extension;
        $directoryPath = storage_path('app/public/'.$directory);

        try {
            File::ensureDirectoryExists($directoryPath, 0755, true);
            $path = $file->storeAs($directory, $filename, 'public');
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                $errorField => 'The image could not be saved. Check storage permissions and try again.',
            ]);
        }

        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages([
                $errorField => 'The image could not be saved. Check storage permissions and try again.',
            ]);
        }

        return '/storage/'.$path;
    }

    private function deletePublicMedia(?string $publicPath): void
    {
        $path = trim((string) $publicPath);

        if ($path === '' || ! Str::startsWith($path, '/storage/')) {
            return;
        }

        Storage::disk('public')->delete(Str::after($path, '/storage/'));
    }
}
