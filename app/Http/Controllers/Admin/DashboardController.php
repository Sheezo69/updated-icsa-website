<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Support\CourseFileRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(CourseFileRepository $courses): View
    {
        $stats = [
            'total' => ContactMessage::query()->count(),
            'new' => ContactMessage::query()->where('status', ContactMessage::STATUS_NEW)->count(),
            'in_progress' => ContactMessage::query()->where('status', ContactMessage::STATUS_IN_PROGRESS)->count(),
            'resolved' => ContactMessage::query()->where('status', ContactMessage::STATUS_RESOLVED)->count(),
            'courses' => $courses->count(),
        ];

        $recent = ContactMessage::query()
            ->latest('created_at')
            ->limit(5)
            ->get();

        $topCourses = ContactMessage::query()
            ->select('course_interest', DB::raw('COUNT(*) as inquiry_count'))
            ->whereNotNull('course_interest')
            ->where('course_interest', '!=', '')
            ->groupBy('course_interest')
            ->orderByDesc('inquiry_count')
            ->limit(5)
            ->get();

        $chartData = collect(range(6, 0))
            ->map(function (int $daysAgo): array {
                $date = now()->subDays($daysAgo)->startOfDay();

                return [
                    'date' => $date->format('M j'),
                    'count' => ContactMessage::query()
                        ->whereBetween('created_at', [$date, $date->copy()->endOfDay()])
                        ->count(),
                ];
            });

        return view('admin.dashboard', [
            'stats' => $stats,
            'recent' => $recent,
            'topCourses' => $topCourses,
            'chartData' => $chartData,
        ]);
    }
}
