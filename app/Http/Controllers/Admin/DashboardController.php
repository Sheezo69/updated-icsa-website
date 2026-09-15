<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Support\CourseFileRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, CourseFileRepository $courses): View
    {
        $chartPeriod = (int) $request->query('period', 7);
        if (! in_array($chartPeriod, [7, 30, 90], true)) {
            $chartPeriod = 7;
        }

        $currentPeriod = [now()->subDays(6)->startOfDay(), now()->endOfDay()];
        $previousPeriod = [now()->subDays(13)->startOfDay(), now()->subDays(7)->endOfDay()];

        $stats = [
            'total' => ContactMessage::query()->count(),
            'new' => ContactMessage::query()->where('status', ContactMessage::STATUS_NEW)->count(),
            'in_progress' => ContactMessage::query()->where('status', ContactMessage::STATUS_IN_PROGRESS)->count(),
            'resolved' => ContactMessage::query()->where('status', ContactMessage::STATUS_RESOLVED)->count(),
            'courses' => $courses->count(),
        ];

        $percentageChange = static function (int $current, int $previous): ?int {
            if ($previous === 0) {
                return $current === 0 ? 0 : null;
            }

            return (int) round((($current - $previous) / $previous) * 100);
        };

        $trendStatuses = [
            'total' => null,
            'new' => ContactMessage::STATUS_NEW,
            'in_progress' => ContactMessage::STATUS_IN_PROGRESS,
            'resolved' => ContactMessage::STATUS_RESOLVED,
        ];

        $trends = collect($trendStatuses)->map(function (?string $status) use ($currentPeriod, $previousPeriod, $percentageChange): array {
            $currentQuery = ContactMessage::query()->whereBetween('created_at', $currentPeriod);
            $previousQuery = ContactMessage::query()->whereBetween('created_at', $previousPeriod);

            if ($status) {
                $currentQuery->where('status', $status);
                $previousQuery->where('status', $status);
            }

            $current = $currentQuery->count();
            $previous = $previousQuery->count();

            return [
                'current' => $current,
                'previous' => $previous,
                'percentage' => $percentageChange($current, $previous),
            ];
        })->all();

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

        $courseInquiryTotal = ContactMessage::query()
            ->whereNotNull('course_interest')
            ->where('course_interest', '!=', '')
            ->count();

        $bucketDays = match ($chartPeriod) {
            30 => 3,
            90 => 9,
            default => 1,
        };
        $bucketCount = (int) ceil($chartPeriod / $bucketDays);

        $chartData = collect(range($bucketCount - 1, 0))
            ->map(function (int $bucketIndex) use ($bucketDays): array {
                $start = now()->subDays((($bucketIndex + 1) * $bucketDays) - 1)->startOfDay();
                $end = now()->subDays($bucketIndex * $bucketDays)->endOfDay();

                return [
                    'date' => $end->format('M j'),
                    'range' => $bucketDays === 1
                        ? $start->format('M j, Y')
                        : $start->format('M j').' – '.$end->format('M j, Y'),
                    'count' => ContactMessage::query()
                        ->whereBetween('created_at', [$start, $end])
                        ->count(),
                ];
            });

        return view('admin.dashboard', [
            'stats' => $stats,
            'recent' => $recent,
            'topCourses' => $topCourses,
            'chartData' => $chartData,
            'trends' => $trends,
            'courseInquiryTotal' => $courseInquiryTotal,
            'chartPeriod' => $chartPeriod,
        ]);
    }
}
