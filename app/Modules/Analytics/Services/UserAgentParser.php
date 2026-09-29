<?php

namespace App\Modules\Analytics\Services;

/**
 * Deliberately small user-agent parser: enough to split traffic by device,
 * browser family and OS without pulling in a composer dependency.
 */
class UserAgentParser
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|headless|lighthouse|pagespeed|facebookexternalhit|embedly|preview|monitor|curl|wget|python-requests|python-urllib|go-http-client|okhttp|java\/|httpclient|phantomjs|puppeteer|playwright|selenium/i';

    public function isBot(?string $ua): bool
    {
        $ua = trim((string) $ua);

        return $ua === '' || (bool) preg_match(self::BOT_PATTERN, $ua);
    }

    /**
     * @return array{device_type: string, browser: string, os: string}
     */
    public function parse(?string $ua): array
    {
        $ua = (string) $ua;

        return [
            'device_type' => $this->device($ua),
            'browser' => $this->browser($ua),
            'os' => $this->os($ua),
        ];
    }

    private function device(string $ua): string
    {
        if (preg_match('/iPad|Tablet|PlayBook|Silk|Kindle|Nexus (7|9|10)|SM-T\d+/i', $ua)
            || (stripos($ua, 'Android') !== false && stripos($ua, 'Mobile') === false)) {
            return 'tablet';
        }

        if (preg_match('/Mobi|iPhone|iPod|Android|Windows Phone|BlackBerry|BB10|Opera Mini|IEMobile/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function browser(string $ua): string
    {
        $map = [
            '/Edg(e|A|iOS)?\//' => 'Edge',
            '/OPR\/|Opera/' => 'Opera',
            '/SamsungBrowser/' => 'Samsung Internet',
            '/YaBrowser/' => 'Yandex',
            '/UCBrowser/' => 'UC Browser',
            '/Firefox\/|FxiOS/' => 'Firefox',
            '/CriOS|Chrome\/|Chromium/' => 'Chrome',
            '/Safari\//' => 'Safari',
            '/MSIE |Trident\//' => 'Internet Explorer',
        ];

        foreach ($map as $pattern => $name) {
            if (preg_match($pattern, $ua)) {
                return $name;
            }
        }

        return 'Other';
    }

    private function os(string $ua): string
    {
        $map = [
            '/Windows Phone/' => 'Windows Phone',
            '/Windows NT|Win64|Win32/' => 'Windows',
            '/iPhone|iPad|iPod/' => 'iOS',
            '/Android/' => 'Android',
            '/CrOS/' => 'ChromeOS',
            '/Mac OS X|Macintosh/' => 'macOS',
            '/Linux|X11/' => 'Linux',
        ];

        foreach ($map as $pattern => $name) {
            if (preg_match($pattern, $ua)) {
                return $name;
            }
        }

        return 'Other';
    }
}
