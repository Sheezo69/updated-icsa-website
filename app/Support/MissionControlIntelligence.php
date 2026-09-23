<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\AdminActivityLog;
use App\Models\ContactMessage;
use App\Models\WebsitePageView;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class MissionControlIntelligence
{
    public function __construct(private readonly LeadIntelligence $leads) {}

    public function dashboard(): array
    {
        $analyticsReady = $this->tableExists('website_page_views');
        $activityReady = $this->tableExists('admin_activity_logs');
        $hasLeadScore = Schema::hasColumn('contact_messages', 'lead_score');
        $active = ContactMessage::query()
            ->with('assignedTo:id,username')
            ->whereIn('status', [ContactMessage::STATUS_NEW, ContactMessage::STATUS_IN_PROGRESS])
            ->latest('created_at')->limit(150)->get();

        $signals = $this->journeySignals($active, $analyticsReady);
        $leadRows = $active->map(function (ContactMessage $inquiry) use ($signals, $hasLeadScore): array {
            $signal = $signals->get($inquiry->analytics_visitor_hash, ['views' => 0, 'course_views' => 0, 'campaign' => false, 'source' => null]);
            $score = $hasLeadScore && $inquiry->lead_score > 0
                ? (int) $inquiry->lead_score
                : $this->leads->scoreWithSignals($inquiry, $signal['views'], $signal['course_views'], $signal['campaign']);
            $createdAt = $this->utcTimestamp($inquiry->getRawOriginal('created_at'));
            $ageMinutes = $createdAt ? (int) $createdAt->diffInMinutes(now('UTC')) : 0;

            return [
                'id' => $inquiry->id,
                'name' => $inquiry->name,
                'course' => $inquiry->course_interest ?: 'General inquiry',
                'status' => $inquiry->status,
                'assignee' => $inquiry->assignedTo?->username,
                'score' => $score,
                'temperature' => $this->leads->temperature($score),
                'views' => $signal['views'],
                'source' => $signal['source'],
                'age_minutes' => $ageMinutes,
                'sla_remaining' => max(0, 30 - $ageMinutes),
                'sla_overdue' => max(0, $ageMinutes - 30),
            ];
        })->sortByDesc('score')->values();

        $traffic = $this->trafficStats($analyticsReady);
        $staff = $this->staffWorkload($active);
        $demand = $this->courseDemand($analyticsReady);
        $alerts = $this->alerts($active, $leadRows, $traffic);

        return [
            'analyticsReady' => $analyticsReady,
            'stats' => [
                'live_visitors' => $traffic['live_visitors'],
                'views_today' => $traffic['views_today'],
                'hot_leads' => $leadRows->where('temperature', 'Hot')->count(),
                'unassigned' => $active->whereNull('assigned_to')->count(),
                'traffic_delta' => $traffic['delta'],
            ],
            'leads' => $leadRows->take(10),
            'journeys' => $this->journeys($analyticsReady),
            'countries' => $this->countries($analyticsReady),
            'feed' => $this->feed($analyticsReady, $activityReady),
            'staff' => $staff,
            'demand' => $demand,
            'operationsMap' => $this->operationsMap($analyticsReady, $active, $leadRows, $staff, $demand),
            'alerts' => $alerts,
            'briefing' => $this->briefing($traffic, $leadRows, $active, $alerts),
        ];
    }

    public function command(string $command, array $data): ?array
    {
        $normalized = Str::lower(trim($command));
        if ($normalized === '') {
            return null;
        }

        if (str_contains($normalized, 'hot')) {
            return ['title' => 'Hot leads', 'message' => $data['leads']->where('temperature', 'Hot')->count().' high-intent leads need attention.', 'items' => $data['leads']->where('temperature', 'Hot')->values()->all()];
        }
        if (str_contains($normalized, 'unassigned')) {
            return ['title' => 'Unassigned workload', 'message' => $data['stats']['unassigned'].' active inquiries are waiting for an owner.', 'items' => $data['leads']->whereNull('assignee')->values()->all()];
        }
        if (str_contains($normalized, 'staff') || str_contains($normalized, 'workload')) {
            return ['title' => 'Staff workload', 'message' => 'Active inquiry distribution across the team.', 'items' => $data['staff']->all()];
        }
        if (str_contains($normalized, 'course') || str_contains($normalized, 'demand')) {
            return ['title' => 'Course demand', 'message' => 'Courses ranked using inquiry intent and page-interest signals.', 'items' => $data['demand']->all()];
        }
        if (str_contains($normalized, 'traffic') || str_contains($normalized, 'visitor')) {
            return ['title' => 'Traffic status', 'message' => $data['stats']['views_today'].' views today with '.$data['stats']['live_visitors'].' visitors active in the last 15 minutes.', 'items' => []];
        }
        if (preg_match('/inquir|lead|unresolved/', $normalized)) {
            return ['title' => 'Active leads', 'message' => $data['leads']->count().' highest-priority active leads are displayed.', 'items' => $data['leads']->all()];
        }

        return ['title' => 'Command guide', 'message' => 'Try “show hot leads”, “staff workload”, “course demand”, “traffic today” or “unassigned inquiries”.', 'items' => []];
    }

    private function journeySignals(Collection $inquiries, bool $ready): Collection
    {
        if (! $ready) {
            return collect();
        }
        $hashes = $inquiries->pluck('analytics_visitor_hash')->filter()->unique()->values();
        if ($hashes->isEmpty()) {
            return collect();
        }

        return WebsitePageView::query()->whereIn('visitor_hash', $hashes)->where('visited_at', '>=', now('UTC')->subDays(30))
            ->latest('visited_at')->get()->groupBy('visitor_hash')->map(function (Collection $views): array {
                $campaign = $views->first(fn (WebsitePageView $view) => $view->utm_campaign || $view->utm_source);

                return [
                    'views' => $views->count(),
                    'course_views' => $views->where('page_type', 'course')->count(),
                    'campaign' => $campaign !== null,
                    'source' => $campaign?->utm_campaign ?: $campaign?->utm_source,
                ];
            });
    }

    private function trafficStats(bool $ready): array
    {
        if (! $ready) {
            return ['live_visitors' => 0, 'views_today' => 0, 'delta' => 0, 'last_hour' => 0];
        }
        $lastHour = WebsitePageView::query()->where('visited_at', '>=', now('UTC')->subHour())->count();
        $previousHour = WebsitePageView::query()->whereBetween('visited_at', [now('UTC')->subHours(2), now('UTC')->subHour()])->count();

        return [
            'live_visitors' => WebsitePageView::query()->where('visited_at', '>=', now('UTC')->subMinutes(15))->distinct('visitor_hash')->count('visitor_hash'),
            'views_today' => WebsitePageView::query()->where('visited_at', '>=', now('Asia/Kuwait')->startOfDay()->utc())->count(),
            'last_hour' => $lastHour,
            'delta' => $previousHour > 0 ? (int) round((($lastHour - $previousHour) / $previousHour) * 100) : ($lastHour > 0 ? 100 : 0),
        ];
    }

    private function journeys(bool $ready): Collection
    {
        if (! $ready) {
            return collect();
        }

        return WebsitePageView::query()->where('visited_at', '>=', now('UTC')->subHours(6))->latest('visited_at')->limit(120)->get()
            ->groupBy('visitor_hash')->map(function (Collection $views, string $hash): array {
                $ordered = $views->sortBy('visited_at')->values();

                return [
                    'visitor' => strtoupper(substr($hash, 0, 6)),
                    'country' => $ordered->last()?->country_code,
                    'device' => $ordered->last()?->device_type,
                    'pages' => $ordered->pluck('page_path')->unique()->take(6)->values()->all(),
                    'views' => $views->count(),
                    'last_seen' => $this->utcTimestamp($ordered->last()?->getRawOriginal('visited_at')),
                ];
            })->sortByDesc('last_seen')->take(8)->values();
    }

    private function countries(bool $ready): Collection
    {
        if (! $ready) {
            return collect();
        }

        return WebsitePageView::query()->select('country_code', DB::raw('COUNT(*) as visits'), DB::raw('COUNT(DISTINCT visitor_hash) as visitors'))
            ->where('visited_at', '>=', now('UTC')->subDay())->whereNotNull('country_code')->groupBy('country_code')->orderByDesc('visits')->limit(8)->get();
    }

    private function feed(bool $analyticsReady, bool $activityReady): Collection
    {
        $items = collect();
        if ($analyticsReady) {
            WebsitePageView::query()->latest('visited_at')->limit(12)->get()->each(function (WebsitePageView $view) use ($items): void {
                $items->push(['type' => 'visit', 'title' => 'Anonymous visitor opened '.$view->page_path, 'meta' => implode(' · ', array_filter([$view->country_code, $view->device_type, $view->browser])), 'time' => $this->utcTimestamp($view->getRawOriginal('visited_at'))]);
            });
        }
        ContactMessage::query()->latest('created_at')->limit(8)->get()->each(function (ContactMessage $inquiry) use ($items): void {
            $items->push(['type' => 'inquiry', 'title' => $inquiry->name.' submitted an inquiry', 'meta' => $inquiry->course_interest ?: 'General inquiry', 'time' => $this->utcTimestamp($inquiry->getRawOriginal('created_at'))]);
        });
        if ($activityReady) {
            AdminActivityLog::query()->latest('created_at')->limit(8)->get()->each(function (AdminActivityLog $log) use ($items): void {
                $items->push(['type' => 'admin', 'title' => $log->actor_name.' · '.$log->actionLabel(), 'meta' => $log->subject_label ?: $log->section, 'time' => $log->created_at]);
            });
        }

        return $items->sortByDesc('time')->take(16)->values();
    }

    private function staffWorkload(Collection $active): Collection
    {
        $counts = $active->whereNotNull('assigned_to')->groupBy('assigned_to');

        return Admin::query()->where('role', Admin::ROLE_STAFF)->orderBy('username')->get()->map(function (Admin $staff) use ($counts): array {
            $assigned = $counts->get($staff->id, collect());

            return [
                'id' => $staff->id,
                'name' => $staff->username,
                'active' => $assigned->count(),
                'new' => $assigned->where('status', ContactMessage::STATUS_NEW)->count(),
                'overdue' => $assigned->filter(fn (ContactMessage $inquiry): bool => $inquiry->status === ContactMessage::STATUS_NEW && ($this->utcTimestamp($inquiry->getRawOriginal('created_at'))?->lt(now('UTC')->subMinutes(30)) ?? false))->count(),
            ];
        })->sortByDesc('active')->values();
    }

    private function courseDemand(bool $analyticsReady): Collection
    {
        $demand = [];
        ContactMessage::query()->select('course_interest', DB::raw('COUNT(*) as total'))->where('created_at', '>=', now('UTC')->subDays(90))
            ->whereNotNull('course_interest')->where('course_interest', '!=', '')->groupBy('course_interest')->get()->each(function ($row) use (&$demand): void {
                $demand[$row->course_interest] = ['course' => $row->course_interest, 'inquiries' => (int) $row->total, 'views' => 0];
            });
        if ($analyticsReady) {
            WebsitePageView::query()->select('page_path', DB::raw('COUNT(*) as total'))->where('visited_at', '>=', now('UTC')->subDays(90))
                ->where('page_path', 'like', '/courses/%')->groupBy('page_path')->get()->each(function ($row) use (&$demand): void {
                    $slug = Str::after($row->page_path, '/courses/');
                    $demand[$slug] ??= ['course' => $slug, 'inquiries' => 0, 'views' => 0];
                    $demand[$slug]['views'] += (int) $row->total;
                });
        }

        return collect($demand)->map(function (array $item): array {
            $item['signal'] = ($item['inquiries'] * 5) + $item['views'];
            $item['label'] = Str::headline($item['course']);

            return $item;
        })->sortByDesc('signal')->take(8)->values();
    }

    private function alerts(Collection $active, Collection $leads, array $traffic): Collection
    {
        $alerts = collect();
        $unassigned = $active->whereNull('assigned_to')->count();
        $stale = $active->filter(fn (ContactMessage $inquiry): bool => $inquiry->status === ContactMessage::STATUS_NEW && ($this->utcTimestamp($inquiry->getRawOriginal('created_at'))?->lt(now('UTC')->subMinutes(30)) ?? false))->count();
        $hotUnassigned = $leads->where('temperature', 'Hot')->whereNull('assignee')->count();
        if ($hotUnassigned > 0) {
            $alerts->push(['tone' => 'danger', 'icon' => 'fa-fire', 'title' => $hotUnassigned.' hot lead'.($hotUnassigned === 1 ? '' : 's').' unassigned', 'text' => 'High-intent inquiries are waiting for ownership.']);
        }
        if ($stale > 0) {
            $alerts->push(['tone' => 'warning', 'icon' => 'fa-stopwatch', 'title' => $stale.' response timer'.($stale === 1 ? '' : 's').' overdue', 'text' => 'New inquiries have waited longer than the 30-minute target.']);
        }
        if ($unassigned > 0) {
            $alerts->push(['tone' => 'info', 'icon' => 'fa-user-plus', 'title' => $unassigned.' inquiries need assignment', 'text' => 'Balance workload can distribute them across staff.']);
        }
        if ($traffic['last_hour'] >= 5 && $traffic['delta'] >= 100) {
            $alerts->push(['tone' => 'success', 'icon' => 'fa-arrow-trend-up', 'title' => 'Traffic is surging +'.$traffic['delta'].'%', 'text' => 'The last hour is substantially busier than the previous hour.']);
        }
        if ($alerts->isEmpty()) {
            $alerts->push(['tone' => 'success', 'icon' => 'fa-shield-halved', 'title' => 'Operations are stable', 'text' => 'No urgent workload or traffic anomalies detected.']);
        }

        return $alerts;
    }

    private function operationsMap(bool $analyticsReady, Collection $active, Collection $leads, Collection $staff, Collection $demand): array
    {
        $nodes = collect();
        $edges = collect();
        $nodeIds = [];
        $addNode = function (array $node) use ($nodes, &$nodeIds): void {
            if (isset($nodeIds[$node['id']])) {
                $index = $nodeIds[$node['id']];
                $nodes->put($index, array_replace($nodes->get($index), $node));

                return;
            }
            $nodeIds[$node['id']] = $nodes->count();
            $nodes->push($node);
        };
        $addEdge = function (string $from, string $to, string $label) use ($edges): void {
            $edges->push(['from' => $from, 'to' => $to, 'label' => $label]);
        };

        $leadById = $leads->keyBy('id');
        $active->take(8)->each(function (ContactMessage $inquiry) use ($addNode, $addEdge, $leadById): void {
            $lead = $leadById->get($inquiry->id, []);
            $inquiryId = 'inquiry:'.$inquiry->id;
            $courseSlug = Str::slug($inquiry->course_interest ?: 'general-inquiry');
            $courseId = 'course:'.$courseSlug;
            $addNode([
                'id' => $inquiryId, 'type' => 'inquiry', 'label' => $inquiry->name,
                'eyebrow' => 'Inquiry #'.$inquiry->id,
                'summary' => ($inquiry->course_interest ?: 'General inquiry').' · '.Str::headline($inquiry->status),
                'metrics' => array_values(array_filter([
                    isset($lead['score']) ? ['label' => 'Intent', 'value' => $lead['score'].'/100'] : null,
                    ['label' => 'Temperature', 'value' => $lead['temperature'] ?? 'Unscored'],
                    ['label' => 'Owner', 'value' => $inquiry->assignedTo?->username ?: 'Unassigned'],
                ])),
                'journey' => array_values(array_filter(['Form submitted', $inquiry->course_interest, $inquiry->assignedTo ? 'Assigned to '.$inquiry->assignedTo->username : 'Awaiting assignment'])),
                'action' => ['label' => 'Open inquiry', 'url' => route('admin.inquiries.index', ['open' => $inquiry->id])],
                'signal' => (int) ($lead['score'] ?? 45),
            ]);
            $addNode([
                'id' => $courseId, 'type' => 'course', 'label' => Str::headline($inquiry->course_interest ?: 'General inquiry'),
                'eyebrow' => 'Course signal', 'summary' => 'Connected to active inquiry demand.',
                'metrics' => [['label' => 'Active link', 'value' => $inquiry->name]],
                'journey' => ['Course interest', 'Inquiry submitted'], 'signal' => 55,
            ]);
            $addEdge($courseId, $inquiryId, 'converted');
            if ($inquiry->assignedTo) {
                $staffId = 'staff:'.$inquiry->assignedTo->id;
                $addNode([
                    'id' => $staffId, 'type' => 'staff', 'label' => $inquiry->assignedTo->username,
                    'eyebrow' => 'Staff operator', 'summary' => 'Owns active inquiry workload.',
                    'metrics' => [], 'journey' => ['Inquiry assigned', 'Response in progress'], 'signal' => 50,
                ]);
                $addEdge($inquiryId, $staffId, 'assigned');
            }
            if ($inquiry->analytics_visitor_hash) {
                $visitorId = 'visitor:'.substr($inquiry->analytics_visitor_hash, 0, 12);
                $addNode([
                    'id' => $visitorId, 'type' => 'visitor', 'label' => 'Visitor '.strtoupper(substr($inquiry->analytics_visitor_hash, 0, 6)),
                    'eyebrow' => 'Anonymous visitor', 'summary' => 'Privacy-safe journey linked to an inquiry.',
                    'metrics' => [['label' => 'Page views', 'value' => (string) ($lead['views'] ?? 0)]],
                    'journey' => ['Anonymous visit', 'Course interest', 'Inquiry submitted'], 'signal' => 65,
                ]);
                $addEdge($visitorId, $courseId, 'explored');
                $addEdge($visitorId, $inquiryId, 'submitted');
            }
        });

        $staff->take(6)->each(function (array $member) use ($addNode): void {
            $addNode([
                'id' => 'staff:'.$member['id'], 'type' => 'staff', 'label' => $member['name'],
                'eyebrow' => 'Staff operator', 'summary' => $member['active'].' active assignments · '.$member['overdue'].' overdue',
                'metrics' => [['label' => 'Active', 'value' => (string) $member['active']], ['label' => 'New', 'value' => (string) $member['new']], ['label' => 'Overdue', 'value' => (string) $member['overdue']]],
                'journey' => ['Assigned leads', 'Follow-up queue'], 'signal' => max(25, min(100, $member['active'] * 15)),
            ]);
        });

        $demand->take(6)->each(function (array $course) use ($addNode): void {
            $addNode([
                'id' => 'course:'.Str::slug($course['course']), 'type' => 'course', 'label' => $course['label'],
                'eyebrow' => 'Course demand', 'summary' => $course['views'].' page views · '.$course['inquiries'].' inquiries',
                'metrics' => [['label' => 'Demand signal', 'value' => (string) $course['signal']], ['label' => 'Views', 'value' => (string) $course['views']], ['label' => 'Inquiries', 'value' => (string) $course['inquiries']]],
                'journey' => ['Page interest', 'Demand signal'], 'signal' => max(20, min(100, $course['signal'])),
            ]);
        });

        if ($analyticsReady) {
            WebsitePageView::query()->where('visited_at', '>=', now('UTC')->subDay())->latest('visited_at')->limit(240)->get()
                ->groupBy('visitor_hash')->sortByDesc(fn (Collection $views): int => $views->count())->take(8)->each(function (Collection $views, string $hash) use ($addNode, $addEdge): void {
                    $ordered = $views->sortBy('visited_at')->values();
                    $last = $ordered->last();
                    $visitorId = 'visitor:'.substr($hash, 0, 12);
                    $pages = $ordered->pluck('page_path')->unique()->take(6)->values()->all();
                    $addNode([
                        'id' => $visitorId, 'type' => 'visitor', 'label' => 'Visitor '.strtoupper(substr($hash, 0, 6)),
                        'eyebrow' => 'Anonymous visitor', 'summary' => implode(' · ', array_filter([$last?->country_code, $last?->device_type, $last?->browser])) ?: 'Privacy-safe browsing signal',
                        'metrics' => [['label' => 'Views', 'value' => (string) $views->count()], ['label' => 'Pages', 'value' => (string) count($pages)], ['label' => 'Last seen', 'value' => $this->utcTimestamp($last?->getRawOriginal('visited_at'))?->diffForHumans() ?? 'Unknown']],
                        'journey' => $pages, 'signal' => max(25, min(100, $views->count() * 16)),
                    ]);
                    $ordered->where('page_type', 'course')->pluck('page_path')->unique()->take(3)->each(function (string $path) use ($visitorId, $addNode, $addEdge): void {
                        $slug = trim(Str::after($path, '/courses/'), '/');
                        if ($slug === '') {
                            return;
                        }
                        $courseId = 'course:'.Str::slug($slug);
                        $addNode(['id' => $courseId, 'type' => 'course', 'label' => Str::headline($slug), 'eyebrow' => 'Course page', 'summary' => 'Receiving anonymous visitor attention.', 'metrics' => [], 'journey' => ['Course page viewed'], 'signal' => 45]);
                        $addEdge($visitorId, $courseId, 'viewed');
                    });
                    $campaign = $ordered->first(fn (WebsitePageView $view) => filled($view->utm_campaign) || filled($view->utm_source));
                    if ($campaign) {
                        $campaignName = $campaign->utm_campaign ?: $campaign->utm_source;
                        $campaignId = 'campaign:'.Str::slug($campaignName);
                        $addNode([
                            'id' => $campaignId, 'type' => 'campaign', 'label' => Str::headline($campaignName),
                            'eyebrow' => 'Campaign source', 'summary' => implode(' · ', array_filter([$campaign->utm_source, $campaign->utm_medium])) ?: 'Tracked campaign acquisition',
                            'metrics' => [['label' => 'Connected visitor', 'value' => strtoupper(substr($hash, 0, 6))]],
                            'journey' => ['Campaign click', 'Website session'], 'signal' => 60,
                        ]);
                        $addEdge($campaignId, $visitorId, 'acquired');
                    }
                });
        }

        $visibleNodes = collect(['campaign' => 5, 'visitor' => 8, 'course' => 8, 'inquiry' => 8, 'staff' => 6])
            ->flatMap(fn (int $limit, string $type): Collection => $nodes->where('type', $type)->take($limit))
            ->values();
        $visibleIds = $visibleNodes->pluck('id')->flip();

        return [
            'nodes' => $visibleNodes->all(),
            'edges' => $edges->filter(fn (array $edge): bool => $visibleIds->has($edge['from']) && $visibleIds->has($edge['to']))
                ->unique(fn (array $edge): string => $edge['from'].'>'.$edge['to'])->values()->all(),
        ];
    }

    private function briefing(array $traffic, Collection $leads, Collection $active, Collection $alerts): string
    {
        $top = $leads->first();
        $brief = 'Today has '.$traffic['views_today'].' tracked page views and '.$active->count().' active inquiries. ';
        $brief .= $leads->where('temperature', 'Hot')->count().' leads are currently high intent. ';
        if ($top) {
            $brief .= 'The strongest lead is '.$top['name'].' at '.$top['score'].'/100 for '.$top['course'].'. ';
        }
        $brief .= $alerts->first()['title'].'.';

        return $brief;
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    private function utcTimestamp(mixed $value): ?Carbon
    {
        return $value ? Carbon::parse((string) $value, 'UTC') : null;
    }
}
