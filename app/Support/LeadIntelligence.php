<?php

namespace App\Support;

use App\Models\ContactMessage;
use App\Models\WebsitePageView;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LeadIntelligence
{
    public function score(ContactMessage $inquiry): int
    {
        $views = 0;
        $courseViews = 0;
        $campaignVisit = false;

        try {
            if ($inquiry->analytics_visitor_hash && Schema::hasTable('website_page_views')) {
                $rawCreatedAt = $inquiry->getRawOriginal('created_at');
                $journeyStart = $rawCreatedAt ? Carbon::parse($rawCreatedAt, 'UTC')->subDays(7) : now('UTC')->subDays(7);
                $journey = WebsitePageView::query()
                    ->where('visitor_hash', $inquiry->analytics_visitor_hash)
                    ->where('visited_at', '>=', $journeyStart);
                $views = (clone $journey)->count();
                $courseViews = (clone $journey)->where('page_path', 'like', '/courses/%')->count();
                $campaignVisit = (clone $journey)->where(function ($query): void {
                    $query->whereNotNull('utm_campaign')->orWhereNotNull('utm_source');
                })->exists();
            }
        } catch (Throwable) {
            // Analytics intelligence must never interrupt an inquiry.
        }

        return $this->scoreWithSignals($inquiry, $views, $courseViews, $campaignVisit);
    }

    public function scoreWithSignals(ContactMessage $inquiry, int $views, int $courseViews, bool $campaignVisit): int
    {
        $score = 24;
        $score += $inquiry->form_type === 'Course Enrollment' ? 24 : 8;
        $score += $inquiry->course_interest ? 14 : 0;
        $score += mb_strlen(trim((string) $inquiry->message)) >= 30 ? 7 : 2;
        $score += $inquiry->phone ? 5 : 0;
        $score += min(12, max(0, $views - 1) * 3);
        $score += min(10, $courseViews * 3);
        $score += $campaignVisit ? 6 : 0;

        return max(0, min(100, $score));
    }

    public function temperature(int $score): string
    {
        return match (true) {
            $score >= 75 => 'Hot',
            $score >= 50 => 'Warm',
            default => 'Cold',
        };
    }

    public function refresh(ContactMessage $inquiry): int
    {
        $score = $this->score($inquiry);
        if (Schema::hasColumn('contact_messages', 'lead_score') && (int) $inquiry->lead_score !== $score) {
            $inquiry->forceFill(['lead_score' => $score])->save();
        }

        return $score;
    }
}
