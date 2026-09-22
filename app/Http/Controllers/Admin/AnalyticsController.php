<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsitePageView;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class AnalyticsController extends Controller
{
    private const RANGES = [
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
        '12m' => 'Last 12 months',
        '3y' => 'Last 3 years',
    ];

    public function index(Request $request): View
    {
        $range = array_key_exists($request->string('range')->toString(), self::RANGES)
            ? $request->string('range')->toString()
            : '30d';
        $limit = in_array($request->integer('limit'), [8, 15, 25, 50], true) ? $request->integer('limit') : 8;
        [$start, $end, $bucket] = $this->period($range);

        $data = $this->emptyData();
        $available = false;
        try {
            $available = Schema::hasTable('website_page_views');
            if ($available) {
                $query = WebsitePageView::query()->whereBetween('visited_at', [$start, $end]);
                $data = [
                    'summary' => [
                        'views' => (clone $query)->count(),
                        'visitors' => (clone $query)->distinct('visitor_hash')->count('visitor_hash'),
                        'signed_in' => (clone $query)->where('is_signed_in', true)->count(),
                        'top_page' => $this->ranking($query, 'page_path', 1, 'page_title')->first(),
                    ],
                    'timeline' => $this->timeline($query, $start, $end, $bucket),
                    'pages' => $this->ranking($query, 'page_path', $limit, 'page_title'),
                    'referrers' => $this->ranking($query, 'referrer_host', $limit),
                    'devices' => $this->ranking($query, 'device_type', $limit),
                    'browsers' => $this->ranking($query, 'browser', $limit),
                    'systems' => $this->ranking($query, 'operating_system', $limit),
                    'campaigns' => $this->ranking($query, 'utm_campaign', $limit),
                    'countries' => $this->ranking($query, 'country_code', $limit),
                ];
            }
        } catch (Throwable $exception) {
            report($exception);
            $available = false;
        }

        return view('admin.analytics.index', [
            ...$data,
            'analyticsAvailable' => $available,
            'range' => $range,
            'rangeLabel' => self::RANGES[$range],
            'ranges' => self::RANGES,
            'limit' => $limit,
            'bucket' => $bucket,
        ]);
    }

    private function period(string $range): array
    {
        $end = now('UTC')->endOfDay();

        return match ($range) {
            '7d' => [$end->copy()->subDays(6)->startOfDay(), $end, 'day'],
            '90d' => [$end->copy()->subDays(89)->startOfDay(), $end, 'day'],
            '12m' => [$end->copy()->startOfMonth()->subMonths(11), $end, 'month'],
            '3y' => [$end->copy()->startOfYear()->subYears(2), $end, 'year'],
            default => [$end->copy()->subDays(29)->startOfDay(), $end, 'day'],
        };
    }

    private function timeline(Builder $query, Carbon $start, Carbon $end, string $bucket): Collection
    {
        $driver = DB::connection()->getDriverName();
        $expression = match ([$driver, $bucket]) {
            ['sqlite', 'month'] => "strftime('%Y-%m', visited_at)",
            ['sqlite', 'year'] => "strftime('%Y', visited_at)",
            ['sqlite', 'day'] => "strftime('%Y-%m-%d', visited_at)",
            ['mysql', 'month'] => "DATE_FORMAT(visited_at, '%Y-%m')",
            ['mysql', 'year'] => "DATE_FORMAT(visited_at, '%Y')",
            default => 'DATE(visited_at)',
        };

        $counts = (clone $query)
            ->selectRaw($expression.' as bucket_key, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors')
            ->groupBy('bucket_key')
            ->orderBy('bucket_key')
            ->get()
            ->keyBy('bucket_key');

        $cursor = $start->copy();
        $points = collect();
        while ($cursor->lte($end)) {
            $key = match ($bucket) {
                'month' => $cursor->format('Y-m'),
                'year' => $cursor->format('Y'),
                default => $cursor->format('Y-m-d'),
            };
            $row = $counts->get($key);
            $points->push([
                'key' => $key,
                'label' => match ($bucket) {
                    'month' => $cursor->format('M Y'),
                    'year' => $cursor->format('Y'),
                    default => $cursor->format('M j'),
                },
                'views' => (int) ($row->views ?? 0),
                'visitors' => (int) ($row->visitors ?? 0),
            ]);
            match ($bucket) {
                'month' => $cursor->addMonth(),
                'year' => $cursor->addYear(),
                default => $cursor->addDay(),
            };
        }

        return $points;
    }

    private function ranking(Builder $query, string $column, int $limit, ?string $secondary = null): Collection
    {
        $select = [$column, DB::raw('COUNT(*) as visits'), DB::raw('COUNT(DISTINCT visitor_hash) as visitors')];
        if ($secondary) {
            $select[] = DB::raw('MAX('.$secondary.') as secondary_label');
        }

        return (clone $query)
            ->select($select)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->groupBy($column)
            ->orderByDesc('visits')
            ->limit($limit)
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->{$column},
                'secondary' => $secondary ? (string) $row->secondary_label : null,
                'visits' => (int) $row->visits,
                'visitors' => (int) $row->visitors,
            ]);
    }

    private function emptyData(): array
    {
        return [
            'summary' => ['views' => 0, 'visitors' => 0, 'signed_in' => 0, 'top_page' => null],
            'timeline' => collect(),
            'pages' => collect(),
            'referrers' => collect(),
            'devices' => collect(),
            'browsers' => collect(),
            'systems' => collect(),
            'campaigns' => collect(),
            'countries' => collect(),
        ];
    }
}
