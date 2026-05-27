<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * HTTP route middleware that protects GraphQL endpoints against Cross-Site
 * Request Forgery (CSRF) when cookie/session-based authentication is used.
 *
 * The guard ensures that every incoming request could NOT have been initiated
 * by a cross-origin HTML form, link, or redirect -- i.e. that a spec-compliant
 * browser would have performed a CORS preflight before sending it.
 *
 * ## When to enable
 *
 * Enable this middleware on any GraphQL schema that authenticates requests via
 * ambient credentials (session cookies, Laravel Sanctum cookie-mode, etc.).
 * It is NOT needed when the endpoint exclusively uses explicit credentials
 * (Bearer tokens, API keys) that a browser would never attach automatically.
 *
 * ## How it works (decision tree)
 *
 * 1. Reject GET (configurable) -- GET requests can be triggered by `<img>`,
 *    `<script>`, `<link>` tags cross-origin without any preflight.
 * 2. If `Sec-Fetch-Site` is present and equals `same-origin` or `same-site`,
 *    the browser certifies the request originated from the same site → ALLOW.
 * 3. If `Sec-Fetch-Site` equals `cross-site` or `none` → REJECT.
 * 4. If a custom header (default: `X-Requested-With`) is present → ALLOW
 *    (custom headers force a CORS preflight).
 * 5. If the `Content-Type` is non-simple (`application/json`, etc.) → ALLOW
 *    (non-simple content types force a CORS preflight).
 * 6. Otherwise the request is ambiguous (no Fetch-Metadata, no custom header,
 *    simple Content-Type). Behaviour depends on `$strictWhenAmbiguous`:
 *    - `true` (default): REJECT -- assumes the worst case.
 *    - `false`: ALLOW -- permits non-browser clients that send none of the
 *      above signals (e.g. legacy HTTP clients, curl without extra headers).
 *
 * @see https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html
 * @see https://web.dev/articles/fetch-metadata
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS#simple_requests
 */
class CsrfGuard
{
    /**
     * Content types that browsers can send from HTML forms without triggering
     * a CORS preflight request.
     *
     * @see https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#attr-fs-enctype
     * @see https://fetch.spec.whatwg.org/#cors-safelisted-request-header
     */
    private const SIMPLE_CONTENT_TYPES = [
        'application/x-www-form-urlencoded',
        'multipart/form-data',
        'text/plain',
    ];

    /**
     * Sec-Fetch-Site values indicating the request came from the same origin
     * or a same-site context.  These are trustworthy because the header is set
     * by the browser and cannot be spoofed by JavaScript on a cross-origin page.
     */
    private const TRUSTED_FETCH_SITE_VALUES = [
        'same-origin',
        'same-site',
    ];

    /** Sec-Fetch-Site values indicating the request was initiated cross-origin. */
    private const HOSTILE_FETCH_SITE_VALUES = [
        'cross-site',
        'none',
    ];

    public function __construct(
        private readonly bool $rejectGet = true,
        private readonly bool $checkFetchMetadata = true,
        private readonly bool $allowCustomHeader = true,
        private readonly string $customHeaderName = 'X-Requested-With',
        private readonly bool $allowNonSimpleContentType = true,
        private readonly bool $strictWhenAmbiguous = true,
    ) {
    }

    /**
     * Create a middleware string with custom parameters for per-route configuration.
     *
     * Use this factory when different schemas require different CSRF policies.
     * Each parameter corresponds to a step in the decision tree documented above.
     *
     * Example:
     *
     *     // Strict (default) -- suitable for session-authenticated browser SPAs:
     *     CsrfGuard::using()
     *
     *     // Permissive -- allows non-browser clients that don't send Sec-Fetch-Site
     *     // or custom headers (mobile apps, legacy integrations):
     *     CsrfGuard::using(strictWhenAmbiguous: false)
     *
     *     // Custom header name (Apollo ecosystem convention):
     *     CsrfGuard::using(customHeaderName: 'Apollo-Require-Preflight')
     *
     * @param bool $rejectGet
     *                        Whether to unconditionally reject GET requests.
     *
     *     GET requests can be triggered cross-origin by navigation (`<a>`),
     *     resource embeds (`<img>`, `<script>`), or `window.open()` without any
     *     CORS preflight.  Any query parameters (including the full GraphQL query
     *     string) are visible in Referer headers, proxy logs, and browser history.
     *
     *     Disable only if you intentionally serve GET queries (e.g. for CDN-cached
     *     persisted queries) and have alternative protections in place (e.g.
     *     ReadOnlyOperationMiddleware to block mutations via GET).
     *
     *     Default: `true`
     *
     * @param bool $checkFetchMetadata
     *                                 Whether to inspect the `Sec-Fetch-Site` request header.
     *
     *     Modern browsers (Chrome 76+, Firefox 90+, Safari 16.4+, Edge 79+) send
     *     this header on every request.  It reliably indicates whether the request
     *     was initiated from the same origin, same site, or cross-site.  Unlike
     *     custom headers or content-type checks, this signal requires zero client
     *     cooperation -- it simply works.
     *
     *     When enabled:
     *     - `same-origin` / `same-site` → allow immediately (short-circuit)
     *     - `cross-site` / `none` → reject immediately
     *     - absent → fall through to subsequent checks
     *
     *     Disable only if your deployment strips `Sec-Fetch-*` headers (unusual).
     *
     *     Default: `true`
     *
     * @param bool $allowCustomHeader
     *                                Whether to allow requests that include a specific custom HTTP header.
     *
     *     Browsers cannot send custom headers on "simple" (non-preflighted)
     *     cross-origin requests.  Requiring a custom header is the mechanism
     *     recommended by OWASP and used by Apollo Server/Router.  Any JavaScript
     *     GraphQL client (Apollo Client, urql, Relay) sets `X-Requested-With`
     *     or an equivalent header by default when configured to do so.
     *
     *     This check passes if the header specified by `$customHeaderName` is
     *     present in the request, regardless of its value.
     *
     *     Disable if you want to rely exclusively on Fetch-Metadata and
     *     Content-Type checks (steps 2-3 and 5).
     *
     *     Default: `true`
     *
     * @param string $customHeaderName
     *                                 The name of the custom header to check (case-insensitive).
     *
     *     Common choices:
     *     - `X-Requested-With` (jQuery/Axios convention, widely supported)
     *     - `Apollo-Require-Preflight` (Apollo ecosystem convention)
     *     - `GraphQL-Require-Preflight` (GraphQL-over-HTTP community convention)
     *
     *     The header name itself does not matter for security -- any non-standard
     *     header forces a CORS preflight.  Choose whichever your client library
     *     sends or is easiest to configure.
     *
     *     Default: `'X-Requested-With'`
     *
     * @param bool $allowNonSimpleContentType
     *                                        Whether to allow requests whose Content-Type is NOT one of the three
     *                                        CORS-safelisted form types (`application/x-www-form-urlencoded`,
     *                                        `multipart/form-data`, `text/plain`).
     *
     *     The CORS spec guarantees that a browser will preflight any request
     *     with a non-safelisted Content-Type.  Since standard GraphQL clients
     *     send `application/json`, this check alone is sufficient to block
     *     form-based CSRF for JSON payloads.
     *
     *     However, file uploads use `multipart/form-data` (a simple type).
     *     If your endpoint supports uploads, clients must also send the custom
     *     header (step 4) or the request must pass Fetch-Metadata (step 2).
     *     All mainstream JavaScript upload libraries do this by default.
     *
     *     Disable if you want to force all clients to use the custom header
     *     regardless of Content-Type (belt-and-suspenders approach).
     *
     *     Default: `true`
     *
     * @param bool $strictWhenAmbiguous
     *                                  What to do when none of the above checks produced a definitive answer.
     *
     *     This situation arises when:
     *     - The browser does not send `Sec-Fetch-Site` (pre-2019 browsers), AND
     *     - No custom header is present, AND
     *     - The Content-Type is "simple" (or absent)
     *
     *     In strict mode (default), such requests are rejected.  This is the
     *     safest choice: it assumes any ambiguous request might be a forged form
     *     submission from an old browser.
     *
     *     In permissive mode, ambiguous requests are allowed through.  Use this
     *     when non-browser clients (mobile apps, server-to-server, CLI tools)
     *     call the endpoint without sending Sec-Fetch-Site or custom headers
     *     and you cannot modify them to do so.  The trade-off is that users on
     *     very old browsers (pre-2019) would not be protected.
     *
     *     Default: `true`
     *
     * @return string Middleware string for use in route/schema configuration
     */
    public static function using(
        bool $rejectGet = true,
        bool $checkFetchMetadata = true,
        bool $allowCustomHeader = true,
        string $customHeaderName = 'X-Requested-With',
        bool $allowNonSimpleContentType = true,
        bool $strictWhenAmbiguous = true,
    ): string {
        $params = [];

        if (!$rejectGet) {
            $params[] = 'no-reject-get';
        }

        if (!$checkFetchMetadata) {
            $params[] = 'no-fetch-metadata';
        }

        if (!$allowCustomHeader) {
            $params[] = 'no-custom-header';
        }

        if ('X-Requested-With' !== $customHeaderName) {
            $params[] = 'header:' . $customHeaderName;
        }

        if (!$allowNonSimpleContentType) {
            $params[] = 'no-content-type-check';
        }

        if (!$strictWhenAmbiguous) {
            $params[] = 'permissive';
        }

        if ([] === $params) {
            return static::class;
        }

        return static::class . ':' . implode(',', $params);
    }

    public function handle(Request $request, Closure $next, string ...$params): mixed
    {
        /** @var list<string> $params */
        [$rejectGet, $checkFetchMetadata, $allowCustomHeader, $customHeaderName, $allowNonSimpleContentType, $strictWhenAmbiguous] = $this->resolveOptions($params);

        if ($rejectGet && $this->isGetRequest($request)) {
            $this->reject('GET requests are not allowed by CSRF guard');
        }

        if ($checkFetchMetadata) {
            $fetchSiteVerdict = $this->evaluateFetchMetadata($request);

            if (true === $fetchSiteVerdict) {
                return $next($request);
            }

            if (false === $fetchSiteVerdict) {
                $this->reject('Cross-site requests are not allowed');
            }

            // null = header absent, fall through to other checks
        }

        if ($allowCustomHeader && $this->hasCustomHeader($request, $customHeaderName)) {
            return $next($request);
        }

        if ($allowNonSimpleContentType && $this->hasNonSimpleContentType($request)) {
            return $next($request);
        }

        if ($strictWhenAmbiguous) {
            $this->reject('Request lacks indicators that it was not forged (no Sec-Fetch-Site, no custom header, simple Content-Type)');
        }

        return $next($request);
    }

    private function isGetRequest(Request $request): bool
    {
        return 'GET' === $request->getRealMethod();
    }

    /**
     * Evaluate the Sec-Fetch-Site header.
     *
     * @return bool|null true = trusted, false = hostile, null = absent/unknown
     */
    private function evaluateFetchMetadata(Request $request): ?bool
    {
        $value = $request->header('Sec-Fetch-Site');

        if (null === $value || '' === $value) {
            return null;
        }

        $value = strtolower($value);

        if (\in_array($value, self::TRUSTED_FETCH_SITE_VALUES, true)) {
            return true;
        }

        if (\in_array($value, self::HOSTILE_FETCH_SITE_VALUES, true)) {
            return false;
        }

        // Unknown value -- treat as absent (fall through)
        return null;
    }

    private function hasCustomHeader(Request $request, string $headerName): bool
    {
        return $request->hasHeader($headerName);
    }

    private function hasNonSimpleContentType(Request $request): bool
    {
        $contentType = $request->header('Content-Type', '');

        if ('' === $contentType) {
            return false;
        }

        foreach (self::SIMPLE_CONTENT_TYPES as $simpleType) {
            if (str_starts_with(strtolower($contentType), $simpleType)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<string> $params
     * @return array{bool, bool, bool, string, bool, bool}
     */
    private function resolveOptions(array $params): array
    {
        $rejectGet = $this->rejectGet;
        $checkFetchMetadata = $this->checkFetchMetadata;
        $allowCustomHeader = $this->allowCustomHeader;
        $customHeaderName = $this->customHeaderName;
        $allowNonSimpleContentType = $this->allowNonSimpleContentType;
        $strictWhenAmbiguous = $this->strictWhenAmbiguous;

        foreach ($params as $param) {
            match (true) {
                'no-reject-get' === $param => $rejectGet = false,
                'no-fetch-metadata' === $param => $checkFetchMetadata = false,
                'no-custom-header' === $param => $allowCustomHeader = false,
                str_starts_with($param, 'header:') => $customHeaderName = substr($param, 7),
                'no-content-type-check' === $param => $allowNonSimpleContentType = false,
                'permissive' === $param => $strictWhenAmbiguous = false,
                default => null, // Ignore unknown params for forward compatibility
            };
        }

        return [$rejectGet, $checkFetchMetadata, $allowCustomHeader, $customHeaderName, $allowNonSimpleContentType, $strictWhenAmbiguous];
    }

    private function reject(string $reason): never
    {
        throw new BadRequestHttpException($reason);
    }
}
