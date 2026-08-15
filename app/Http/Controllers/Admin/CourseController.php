<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CourseFileRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index(Request $request, CourseFileRepository $courses): View
    {
        $allCourses = $courses->all();
        $search = trim($request->string('search')->toString());

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $allCourses = array_values(array_filter($allCourses, static function (array $course) use ($needle): bool {
                return str_contains(mb_strtolower($course['title']), $needle)
                    || str_contains(mb_strtolower($course['badge']), $needle)
                    || str_contains(mb_strtolower($course['slug']), $needle);
            }));
        }

        return view('admin.courses.index', [
            'courses' => $allCourses,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.courses.edit', [
            'course' => $this->blankCourse(),
            'isEdit' => false,
        ]);
    }

    public function edit(string $slug, CourseFileRepository $courses): View
    {
        $course = $courses->find($slug);
        abort_if($course === null, 404);

        return view('admin.courses.edit', [
            'course' => $course,
            'isEdit' => true,
        ]);
    }

    public function store(Request $request, CourseFileRepository $courses): RedirectResponse
    {
        $data = $this->validatedCourse($request);
        $this->handleCourseMediaUploads($request, $data);
        $slug = $courses->save($data);

        return redirect()
            ->route('admin.courses.edit', $slug)
            ->with('success', 'Course saved successfully.');
    }

    public function update(Request $request, string $slug, CourseFileRepository $courses): RedirectResponse
    {
        $data = $this->validatedCourse($request);
        $this->handleCourseMediaUploads($request, $data);
        $newSlug = $courses->save($data, $slug);

        return redirect()
            ->route('admin.courses.edit', $newSlug)
            ->with('success', 'Course updated successfully.');
    }

    public function destroy(string $slug, CourseFileRepository $courses): RedirectResponse
    {
        $course = $courses->find($slug);
        if ($course) {
            $this->deletePublicMedia($course['video_path'] ?? '');
            $this->deletePublicMedia($course['video_thumbnail'] ?? '');
        }

        $courses->delete($slug);

        return redirect()->route('admin.courses.index')->with('success', 'Course deleted.');
    }

    private function validatedCourse(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:100'],
            'duration' => ['nullable', 'string', 'max:100'],
            'certification' => ['nullable', 'string', 'max:100'],
            'diploma_type' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'string', 'max:255'],
            'video_path' => ['nullable', 'string', 'max:255'],
            'video_thumbnail' => ['nullable', 'string', 'max:255'],
            'video_file' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg', 'max:204800'],
            'video_thumbnail_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_video' => ['nullable', 'boolean'],
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
            'badge' => '',
            'duration' => '',
            'certification' => 'Certified',
            'diploma_type' => '',
            'description' => '',
            'image' => '',
            'video_path' => '',
            'video_thumbnail' => '',
            'price' => 'Contact for Price',
            'price_note' => 'Flexible payment options available',
            'highlights' => "Practical classroom approach\nCertificate on completion\nCareer-focused learning\nInstructor-led guidance",
            'overview' => '',
            'learning_outcomes' => '',
            'target_audience' => '',
            'careers' => '',
        ];
    }

    private function handleCourseMediaUploads(Request $request, array &$data): void
    {
        $slug = Str::slug((string) ($data['title'] ?? 'course')) ?: 'course';

        if ($request->boolean('remove_video')) {
            $this->deletePublicMedia($data['video_path'] ?? '');
            $data['video_path'] = '';
        }

        if ($request->boolean('remove_video_thumbnail')) {
            $this->deletePublicMedia($data['video_thumbnail'] ?? '');
            $data['video_thumbnail'] = '';
        }

        if ($request->hasFile('video_file')) {
            $this->deletePublicMedia($data['video_path'] ?? '');
            $data['video_path'] = $this->storePublicMedia($request->file('video_file'), 'course-videos', $slug);
        }

        if ($request->hasFile('video_thumbnail_file')) {
            $this->deletePublicMedia($data['video_thumbnail'] ?? '');
            $data['video_thumbnail'] = $this->storePublicMedia($request->file('video_thumbnail_file'), 'course-video-thumbnails', $slug);
        }

        unset(
            $data['video_file'],
            $data['video_thumbnail_file'],
            $data['remove_video'],
            $data['remove_video_thumbnail'],
        );
    }

    private function storePublicMedia($file, string $directory, string $slug): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $filename = $slug.'-'.Str::random(8).'.'.$extension;
        $path = $file->storeAs($directory, $filename, 'public');

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
