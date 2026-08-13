<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\CompanyRate;
use Illuminate\Http\Response;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $platformRate = CompanyRate::resolveFor(null);

        return view('marketing.home', [
            'platformRatePerSms' => $platformRate !== null
                ? number_format((float) $platformRate->rate_per_sms, 6, '.', '')
                : null,
        ]);
    }

    public function robots(): Response
    {
        $sitemap = url('/sitemap.xml');

        $body = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /dashboard',
            'Disallow: /admin',
            'Disallow: /settings',
            'Disallow: /sender-ids',
            'Disallow: /campaigns',
            'Disallow: /invoices',
            'Disallow: /horizon',
            'Disallow: /email',
            'Disallow: /user',
            'Disallow: /api',
            '',
            "Sitemap: {$sitemap}",
            '',
        ]);

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(): Response
    {
        $urls = [
            ['loc' => url('/'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => url('/login'), 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => url('/register'), 'changefreq' => 'monthly', 'priority' => '0.8'],
        ];

        $body = collect($urls)
            ->map(function (array $url): string {
                $loc = htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8');

                return implode("\n", [
                    '    <url>',
                    "        <loc>{$loc}</loc>",
                    "        <changefreq>{$url['changefreq']}</changefreq>",
                    "        <priority>{$url['priority']}</priority>",
                    '    </url>',
                ]);
            })
            ->implode("\n");

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$body}
</urlset>

XML;

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
