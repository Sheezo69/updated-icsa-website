<?php

namespace App\Http\Middleware;

use App\Models\WebsitePageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackWebsiteAnalytics
{
    private const COOKIE = '_icsa_vid';

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|bingpreview|headless|lighthouse|pagespeed|uptime|monitor|pingdom|statuscake|site24x7|newrelic|curl|wget|python|postman|insomnia|httpclient|axios|go-http|java\//i';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if (! $this->shouldTrack($request, $response) || ! Schema::hasTable('website_page_views')) {
                return $response;
            }

            $visitorToken = trim((string) $request->cookie(self::COOKIE));
            if (! preg_match('/^[a-f0-9]{64}$/', $visitorToken)) {
                $visitorToken = bin2hex(random_bytes(32));
                Cookie::queue(cookie(self::COOKIE, $visitorToken, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'Lax'));
            }

            [$device, $browser, $operatingSystem] = $this->classifyAgent((string) $request->userAgent());
            WebsitePageView::query()->create([
                'visitor_hash' => hash('sha256', $visitorToken),
                'page_path' => $this->canonicalPath($request),
                'page_title' => $this->pageTitle($response),
                'route_name' => Str::limit((string) $request->route()?->getName(), 120, '') ?: null,
                'page_type' => $this->pageType($request),
                'referrer_host' => $this->referrerHost($request),
                'utm_source' => $this->campaignValue($request, 'utm_source', 120),
                'utm_medium' => $this->campaignValue($request, 'utm_medium', 120),
                'utm_campaign' => $this->campaignValue($request, 'utm_campaign', 160),
                'device_type' => $device,
                'browser' => $browser,
                'operating_system' => $operatingSystem,
                'country_code' => $this->countryCode($request),
                'is_signed_in' => auth()->check(),
                'visited_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        $agent = trim((string) $request->userAgent());
        $contentType = strtolower((string) $response->headers->get('Content-Type'));

        return $request->isMethod('GET')
            && $response->isSuccessful()
            && str_contains($contentType, 'text/html')
            && $agent !== ''
            && ! preg_match(self::BOT_PATTERN, $agent)
            && ! $request->ajax()
            && ! $request->expectsJson()
            && ! $request->is('api/*', 'up', 'health', 'health/*', config('admin.path', 'secure-staff-portal').'/*')
            && ! $request->session()->has('admin_id')
            && ! preg_match('/\.(?:css|js|map|jpe?g|png|gif|webp|svg|ico|woff2?|ttf|eot|pdf|xml|txt)$/i', $request->path());
    }

    private function classifyAgent(string $agent): array
    {
        $device = match (true) {
            preg_match('/ipad|tablet|kindle|silk/i', $agent) === 1 => 'Tablet',
            preg_match('/mobile|iphone|ipod|android/i', $agent) === 1 => 'Mobile',
            default => 'Desktop',
        };
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Microsoft Edge',
            str_contains($agent, 'OPR/'), str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'SamsungBrowser/') => 'Samsung Internet',
            str_contains($agent, 'Chrome/'), str_contains($agent, 'CriOS/') => 'Chrome',
            str_contains($agent, 'Firefox/'), str_contains($agent, 'FxiOS/') => 'Firefox',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Other browser',
        };
        $operatingSystem = match (true) {
            preg_match('/iphone|ipad|ipod/i', $agent) === 1 => 'iOS',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Macintosh'), str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Other OS',
        };

        return [$device, $browser, $operatingSystem];
    }

    private function canonicalPath(Request $request): string
    {
        $path = '/'.ltrim($request->path(), '/');
        $path = $path === '/index.html' ? '/' : preg_replace('/\.html$/i', '', $path);

        return Str::limit($path ?: '/', 255, '');
    }

    private function pageTitle(Response $response): ?string
    {
        $content = method_exists($response, 'getContent') ? (string) $response->getContent() : '';
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $content, $match) !== 1) {
            return null;
        }

        return Str::limit(trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 190, '');
    }

    private function pageType(Request $request): ?string
    {
        $action = (string) $request->route()?->getActionMethod();

        return $action !== '' && $action !== '__invoke' ? Str::limit(Str::snake($action), 80, '') : null;
    }

    private function referrerHost(Request $request): ?string
    {
        $host = strtolower((string) parse_url((string) $request->headers->get('referer'), PHP_URL_HOST));
        if ($host === '' || $host === strtolower($request->getHost())) {
            return null;
        }

        return Str::limit(preg_replace('/^www\./', '', $host) ?: $host, 190, '');
    }

    private function campaignValue(Request $request, string $key, int $length): ?string
    {
        $value = trim($request->string($key)->toString());

        return $value === '' ? null : Str::limit($value, $length, '');
    }

    private function countryCode(Request $request): ?string
    {
        $country = strtoupper(trim((string) $request->headers->get('CF-IPCountry')));

        return preg_match('/^[A-Z]{2}$/', $country) === 1 && ! in_array($country, ['XX', 'T1'], true) ? $country : null;
    }
}
