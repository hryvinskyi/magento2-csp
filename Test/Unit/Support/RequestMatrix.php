<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Support;

/**
 * Fixed set of requests a store page makes, covering every request type and the matching edge cases the module's
 * policy code must not get wrong.
 */
class RequestMatrix
{
    public const NONCE = 'abc123';
    public const HASH = 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=';
    public const OTHER_HASH = 'BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB=';

    /**
     * Requests, each once.
     *
     * @return list<CspRequest>
     */
    public static function requests(): array
    {
        $url = static fn (string $type, string $url): CspRequest => new CspRequest($type, CspRequest::KIND_URL, $url);
        $requests = [];
        foreach (['script', 'style', 'worker', 'frame', 'fenced-frame', 'img', 'font', 'connect', 'media', 'object', 'manifest'] as $type) {
            foreach (self::urls() as $address) {
                $requests[] = $url($type, $address);
            }
        }
        foreach (['script', 'style'] as $type) {
            $requests[] = new CspRequest($type, CspRequest::KIND_INLINE);
            $requests[] = new CspRequest($type, CspRequest::KIND_INLINE, '', self::NONCE);
            $requests[] = new CspRequest($type, CspRequest::KIND_INLINE, '', 'other');
            $requests[] = new CspRequest($type, CspRequest::KIND_INLINE, '', null, self::HASH);
            $requests[] = new CspRequest($type, CspRequest::KIND_INLINE, '', null, self::OTHER_HASH);
            $requests[] = new CspRequest($type, CspRequest::KIND_ATTRIBUTE);
            $requests[] = new CspRequest($type, CspRequest::KIND_ATTRIBUTE, '', null, self::HASH);
        }
        $requests[] = new CspRequest('script', CspRequest::KIND_EVAL);
        $requests[] = new CspRequest('script', CspRequest::KIND_WASM);

        return $requests;
    }

    /**
     * URLs a page loads from: its own origin, allowed and foreign hosts, schemes, ports, paths.
     *
     * @return list<string>
     */
    private static function urls(): array
    {
        return [
            'https://shop.example/static/app.js',
            'http://shop.example/static/app.js',
            'https://cdn.example.com/lib/app.js',
            'http://cdn.example.com/lib/app.js',
            'https://a.example.com/x.js',
            'https://a.b.example.com/x.js',
            'https://example.com/x.js',
            'https://a.example.com:8443/x.js',
            'wss://ws.example.com/socket',
            'wss://evil.example/socket',
            'https://www.google.com/recaptcha/api.js',
            'https://www.google.com/complete/search',
            'https://royalmail.co.uk/x.js',
            'https://evil.co.uk/x.js',
            'https://d1abc.cloudfront.net/x.js',
            'https://d9evil.cloudfront.net/x.js',
            'https://region1.google-analytics.com/g/collect',
            'https://evil.example/x.js',
            'data:image/png;base64,AAAA',
            'blob:https://shop.example/1',
        ];
    }
}
