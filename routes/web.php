<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\GalaxyController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\MissionControlController;
use App\Http\Controllers\Admin\PipelineController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\SiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$adminPrefix = config('admin.path', 'secure-staff-portal');

Route::middleware('analytics')->group(function (): void {
    Route::get('/', [SiteController::class, 'home'])->name('site.home');
    Route::get('/index.html', [SiteController::class, 'home']);
    Route::get('/about', [SiteController::class, 'about'])->name('site.about');
    Route::get('/about.html', [SiteController::class, 'about']);
    Route::get('/courses', [SiteController::class, 'courses'])->name('site.courses');
    Route::get('/courses.html', [SiteController::class, 'courses']);
    Route::get('/contact', [SiteController::class, 'contact'])->name('site.contact');
    Route::get('/contact.html', [SiteController::class, 'contact']);
    Route::get('/courses/{slug}', [SiteController::class, 'course'])
        ->where('slug', '[A-Za-z0-9\-]+')
        ->name('site.course');
    Route::get('/courses/{slug}.html', [SiteController::class, 'course'])
        ->where('slug', '[A-Za-z0-9\-]+');
});

Route::get('/api/csrf-token.php', [FormController::class, 'csrfToken'])->name('api.csrf');
Route::post('/api/contact-submit.php', [FormController::class, 'contact'])->name('api.contact');
Route::post('/api/inquiry-submit.php', [FormController::class, 'inquiry'])->name('api.inquiry');

Route::prefix($adminPrefix)->group(function (): void {
    Route::get('/', fn () => redirect()->route('admin.dashboard'));

    Route::middleware('admin.guest')->group(function (): void {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('admin.login');
        Route::get('/login.php', fn () => redirect()->route('admin.login'));
        Route::post('/login', [AuthController::class, 'login'])->name('admin.login.submit');
        Route::post('/login.php', [AuthController::class, 'login']);
    });

    Route::middleware('admin.auth')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');
        Route::post('/logout.php', [AuthController::class, 'logout']);

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/dashboard.php', fn () => redirect()->route('admin.dashboard'));

        Route::get('/inquiries', [InquiryController::class, 'index'])->name('admin.inquiries.index');
        Route::get('/inquiries.php', fn () => redirect()->route('admin.inquiries.index'));
        Route::get('/inquiries/pipeline', [PipelineController::class, 'index'])->name('admin.inquiries.pipeline');
        Route::get('/inquiries/pipeline/snapshot', [PipelineController::class, 'snapshot'])->name('admin.inquiries.pipeline.snapshot');
        Route::patch('/inquiries/{inquiry}/pipeline', [PipelineController::class, 'move'])
            ->middleware('throttle:60,1')->name('admin.inquiries.pipeline.move');
        Route::get('/inquiries/export', [InquiryController::class, 'export'])->name('admin.inquiries.export');
        Route::patch('/inquiries/{inquiry}', [InquiryController::class, 'update'])->name('admin.inquiries.update');
        Route::post('/inquiries/{inquiry}/forward-whatsapp', [InquiryController::class, 'forwardToReceptionist'])
            ->middleware('throttle:20,1')->name('admin.inquiries.forward-whatsapp');
        Route::post('/inquiries/{inquiry}/email/resend', [InquiryController::class, 'resend'])
            ->middleware('throttle:6,1')->name('admin.inquiries.email.resend');
        Route::delete('/inquiries/{inquiry}', [InquiryController::class, 'destroy'])->name('admin.inquiries.destroy');
        Route::post('/inquiries/bulk', [InquiryController::class, 'bulk'])->name('admin.inquiries.bulk');

        Route::get('/messages', [MessageController::class, 'index'])->name('admin.messages.index');
        Route::post('/messages', [MessageController::class, 'send'])->middleware('throttle:30,1')->name('admin.messages.send');
        Route::get('/messages/unread', [MessageController::class, 'unread'])->name('admin.messages.unread');
        Route::get('/messages/attachments/{message}', [MessageController::class, 'attachment'])->name('admin.messages.attachment');
        Route::patch('/messages/{conversation}/archive', [MessageController::class, 'archive'])->name('admin.messages.archive');
        Route::patch('/messages/{conversation}/pin', [MessageController::class, 'pin'])->name('admin.messages.pin');
        Route::get('/messages/{conversation}/poll', [MessageController::class, 'poll'])->name('admin.messages.poll');

        Route::middleware('admin.permission:courses')->group(function (): void {
            Route::get('/courses', [CourseController::class, 'index'])->name('admin.courses.index');
            Route::get('/courses.php', fn () => redirect()->route('admin.courses.index'));
            Route::get('/courses/create', [CourseController::class, 'create'])->name('admin.courses.create');
            Route::get('/courses/{slug}/edit', [CourseController::class, 'edit'])->name('admin.courses.edit');
            Route::get('/course-edit.php', function (Request $request) {
                $slug = trim((string) $request->query('slug'));

                return $slug !== ''
                    ? redirect()->route('admin.courses.edit', $slug)
                    : redirect()->route('admin.courses.create');
            });
            Route::post('/courses', [CourseController::class, 'store'])->name('admin.courses.store');
            Route::put('/courses/{slug}', [CourseController::class, 'update'])->name('admin.courses.update');
            Route::delete('/courses/{slug}', [CourseController::class, 'destroy'])->name('admin.courses.destroy');
        });

        Route::middleware('admin.permission:media')->group(function (): void {
            Route::get('/media', [MediaController::class, 'index'])->name('admin.media.index');
            Route::post('/media', [MediaController::class, 'store'])->name('admin.media.store');
            Route::post('/media/rename', [MediaController::class, 'rename'])->name('admin.media.rename');
            Route::delete('/media', [MediaController::class, 'destroy'])->name('admin.media.destroy');
        });

        Route::get('/settings', [SettingsController::class, 'edit'])->name('admin.settings.edit');
        Route::get('/settings.php', fn () => redirect()->route('admin.settings.edit'));
        Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('admin.settings.profile');
        Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('admin.settings.password');
        Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('admin.settings.notifications');
        Route::put('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('admin.settings.preferences');

        Route::middleware('admin.owner')->group(function (): void {
            Route::get('/galaxy', [GalaxyController::class, 'index'])->name('admin.galaxy.index');
            Route::get('/galaxy/snapshot', [GalaxyController::class, 'snapshot'])->name('admin.galaxy.snapshot');
            Route::patch('/galaxy/inquiries/{inquiry}', [GalaxyController::class, 'act'])->middleware('throttle:60,1')->name('admin.galaxy.act');
            Route::get('/mission-control', [MissionControlController::class, 'index'])->name('admin.mission-control.index');
            Route::get('/mission-control/snapshot', [MissionControlController::class, 'snapshot'])->name('admin.mission-control.snapshot');
            Route::post('/mission-control/automate', [MissionControlController::class, 'automate'])->middleware('throttle:6,1')->name('admin.mission-control.automate');
            Route::get('/analytics', [AnalyticsController::class, 'index'])->name('admin.analytics.index');
            Route::post('/inquiries/{inquiry}/message-assignee', [MessageController::class, 'messageAssignee'])->middleware('throttle:12,1')->name('admin.inquiries.message-assignee');
            Route::get('/activity', [ActivityController::class, 'index'])->name('admin.activity.index');
            Route::get('/activity/export', [ActivityController::class, 'export'])->name('admin.activity.export');
            Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
            Route::get('/users.php', fn () => redirect()->route('admin.users.index'));
            Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
            Route::put('/users/{user}/password', [UserController::class, 'resetPassword'])->name('admin.users.password');
            Route::put('/users/{user}/permissions', [UserController::class, 'updatePermissions'])->name('admin.users.permissions');
        });
    });
});

if ($adminPrefix !== 'admin') {
    Route::any('/admin/{any?}', fn () => abort(404))->where('any', '.*');
}
