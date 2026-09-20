<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\WebsitePageView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebsiteAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36';

    private function account(string $username, string $role): Admin
    {
        return Admin::query()->create([
            'username' => $username,
            'password_hash' => bcrypt('test-password'),
            'role' => $role,
        ]);
    }

    public function test_public_html_visits_are_recorded_without_raw_ip_addresses(): void
    {
        $token = str_repeat('a', 64);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.88'])
            ->withCookie('_icsa_vid', $token)
            ->withHeaders([
                'User-Agent' => self::BROWSER,
                'Referer' => 'https://www.google.com/search?q=icsa',
                'CF-IPCountry' => 'KW',
            ])
            ->get(route('site.contact', [
                'utm_source' => 'instagram',
                'utm_medium' => 'social',
                'utm_campaign' => 'autumn-courses',
            ]))->assertOk();

        $view = WebsitePageView::query()->sole();
        $this->assertSame(hash('sha256', $token), $view->visitor_hash);
        $this->assertSame('/contact', $view->page_path);
        $this->assertSame('google.com', $view->referrer_host);
        $this->assertSame('instagram', $view->utm_source);
        $this->assertSame('autumn-courses', $view->utm_campaign);
        $this->assertSame('Desktop', $view->device_type);
        $this->assertSame('Chrome', $view->browser);
        $this->assertSame('Windows', $view->operating_system);
        $this->assertSame('KW', $view->country_code);
        $this->assertFalse(Schema::hasColumn('website_page_views', 'ip_address'));
        $this->assertStringNotContainsString('203.0.113.88', json_encode($view->toArray()));
    }

    public function test_bots_admins_ajax_and_requests_without_a_browser_identity_are_excluded(): void
    {
        $this->withHeader('User-Agent', 'Googlebot/2.1')->get(route('site.home'))->assertOk();
        $this->get(route('site.home'))->assertOk();
        $this->withHeaders(['User-Agent' => self::BROWSER, 'X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('site.home'))->assertOk();

        $admin = $this->account('owner', Admin::ROLE_ADMIN);
        $this->withHeader('User-Agent', self::BROWSER)
            ->withSession(['admin_id' => $admin->id])
            ->get(route('site.home'))->assertOk();

        $this->assertSame(0, WebsitePageView::query()->count());
    }

    public function test_visitor_hash_estimates_unique_visitors(): void
    {
        $token = str_repeat('b', 64);
        $this->withCookie('_icsa_vid', $token)->withHeader('User-Agent', self::BROWSER)->get(route('site.home'));
        $this->withCookie('_icsa_vid', $token)->withHeader('User-Agent', self::BROWSER)->get(route('site.contact'));

        $this->assertSame(2, WebsitePageView::query()->count());
        $this->assertSame(1, WebsitePageView::query()->distinct('visitor_hash')->count('visitor_hash'));
    }

    public function test_owner_dashboard_applies_range_and_limit_to_all_analytics(): void
    {
        $owner = $this->account('owner', Admin::ROLE_ADMIN);
        $staff = $this->account('staff', Admin::ROLE_STAFF);

        foreach (range(1, 12) as $index) {
            WebsitePageView::query()->create([
                'visitor_hash' => hash('sha256', 'visitor-'.$index),
                'page_path' => '/page-'.$index,
                'page_title' => 'Page '.$index,
                'device_type' => $index % 2 ? 'Mobile' : 'Desktop',
                'browser' => 'Chrome',
                'operating_system' => 'Android',
                'referrer_host' => 'source-'.$index.'.example',
                'utm_campaign' => 'campaign-'.$index,
                'country_code' => 'KW',
                'visited_at' => now()->subDays($index % 5),
            ]);
        }
        foreach (range(1, 3) as $index) {
            WebsitePageView::query()->create([
                'visitor_hash' => hash('sha256', 'repeat-'.$index),
                'page_path' => '/page-1',
                'page_title' => 'Page 1',
                'device_type' => 'Mobile',
                'browser' => 'Chrome',
                'operating_system' => 'Android',
                'visited_at' => now(),
            ]);
        }
        WebsitePageView::query()->create([
            'visitor_hash' => hash('sha256', 'old'),
            'page_path' => '/old-page',
            'device_type' => 'Desktop',
            'browser' => 'Firefox',
            'operating_system' => 'Linux',
            'visited_at' => now()->subDays(40),
        ]);

        $this->withSession(['admin_id' => $owner->id])
            ->get(route('admin.analytics.index', ['range' => '7d', 'limit' => 8]))
            ->assertOk()
            ->assertSee('Website Analytics')
            ->assertSee('Last 7 days')
            ->assertSee('Page 1')
            ->assertDontSee('old-page')
            ->assertSee('Privacy by design');

        foreach (['30d', '90d', '12m', '3y'] as $range) {
            $this->withSession(['admin_id' => $owner->id])
                ->get(route('admin.analytics.index', ['range' => $range, 'limit' => 50]))
                ->assertOk();
        }

        $this->withSession(['admin_id' => $staff->id])
            ->get(route('admin.analytics.index'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_missing_analytics_table_never_breaks_public_pages_or_dashboard(): void
    {
        $owner = $this->account('owner', Admin::ROLE_ADMIN);
        Schema::drop('website_page_views');

        $this->withHeader('User-Agent', self::BROWSER)->get(route('site.contact'))->assertOk();
        $this->withSession(['admin_id' => $owner->id])
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertSee('Analytics storage is not ready yet');
    }
}
