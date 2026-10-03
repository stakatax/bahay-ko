<?php

require_once __DIR__
    . '/../models/GovernmentAdvisory.php';

require_once __DIR__
    . '/../../vendor/autoload.php';

class TrustedGovernmentSourceFetcher
{
    private GovernmentAdvisory $model;

    private const MAXIMUM_RESPONSE_BYTES =
    5 * 1024 * 1024;

    public function __construct(
        ?GovernmentAdvisory $model = null
    ) {
        $this->model =
            $model
            ?? new GovernmentAdvisory();
    }

    /* ==========================================
       FETCH TRUSTED GOVERNMENT PAGE
    ========================================== */

    public function fetch(
        string $url,
        ?string $manualTitle = null
    ): array {
        $normalizedUrl =
            $this->normalizeAndValidateUrl(
                $url
            );

        $host =
            strtolower(
                (string) parse_url(
                    $normalizedUrl,
                    PHP_URL_HOST
                )
            );

        $source =
            $this->model
            ->findActiveSourceByHost(
                $host
            );

        if (!$source) {
            throw new InvalidArgumentException(
                'The URL does not belong to an active trusted government source.'
            );
        }

        $this->assertPublicHost(
            $host
        );

        $body = '';

        $responseTooLarge =
            false;

        $curl =
            curl_init(
                $normalizedUrl
            );

        if ($curl === false) {
            throw new RuntimeException(
                'Unable to initialize the government source request.'
            );
        }

        $options = [
            CURLOPT_RETURNTRANSFER =>
            false,

            CURLOPT_FOLLOWLOCATION =>
            true,

            CURLOPT_MAXREDIRS =>
            3,

            CURLOPT_CONNECTTIMEOUT =>
            6,

            CURLOPT_TIMEOUT =>
            20,

            CURLOPT_SSL_VERIFYPEER =>
            true,

            CURLOPT_SSL_VERIFYHOST =>
            2,

            CURLOPT_USERAGENT =>
            'OLSHCO-Digital-Hub/1.0 Government-Advisory-Intake',

            CURLOPT_HTTPHEADER => [
                'Accept: text/html, application/xhtml+xml, application/pdf, text/plain;q=0.9',
                'Accept-Language: en-PH,en;q=0.9'
            ],

            CURLOPT_WRITEFUNCTION =>
            static function (
                $curlHandle,
                string $chunk
            ) use (
                &$body,
                &$responseTooLarge
            ): int {
                if (
                    strlen($body)
                    + strlen($chunk)
                    >
                    self::MAXIMUM_RESPONSE_BYTES
                ) {
                    $responseTooLarge =
                        true;

                    return 0;
                }

                $body .=
                    $chunk;

                return strlen(
                    $chunk
                );
            }
        ];

        if (defined('CURLOPT_PROTOCOLS')) {
            $options[CURLOPT_PROTOCOLS] =
                CURLPROTO_HTTPS;
        }

        if (defined('CURLOPT_REDIR_PROTOCOLS')) {
            $options[CURLOPT_REDIR_PROTOCOLS] =
                CURLPROTO_HTTPS;
        }

        curl_setopt_array(
            $curl,
            $options
        );

        $executed =
            curl_exec(
                $curl
            );

        $curlError =
            curl_error(
                $curl
            );

        $statusCode =
            (int) curl_getinfo(
                $curl,
                CURLINFO_RESPONSE_CODE
            );

        $contentType =
            strtolower(
                trim(
                    (string) curl_getinfo(
                        $curl,
                        CURLINFO_CONTENT_TYPE
                    )
                )
            );

        $effectiveUrl =
            trim(
                (string) curl_getinfo(
                    $curl,
                    CURLINFO_EFFECTIVE_URL
                )
            );

        curl_close(
            $curl
        );

        if ($responseTooLarge) {
            throw new RuntimeException(
                'The government source response exceeds the 5 MB safety limit.'
            );
        }

        if ($executed === false) {
            throw new RuntimeException(
                'Unable to retrieve the government source: '
                    . (
                        $curlError !== ''
                        ? $curlError
                        : 'Unknown network error.'
                    )
            );
        }

        if (
            $statusCode < 200 ||
            $statusCode >= 300
        ) {
            throw new RuntimeException(
                'The government source returned HTTP '
                    . $statusCode
                    . '.'
            );
        }

        if ($body === '') {
            throw new RuntimeException(
                'The government source returned an empty response.'
            );
        }

        $effectiveUrl =
            $this->normalizeAndValidateUrl(
                $effectiveUrl !== ''
                    ? $effectiveUrl
                    : $normalizedUrl
            );

        $effectiveHost =
            strtolower(
                (string) parse_url(
                    $effectiveUrl,
                    PHP_URL_HOST
                )
            );

        $effectiveSource =
            $this->model
            ->findActiveSourceByHost(
                $effectiveHost
            );

        if (
            !$effectiveSource ||
            (int) $effectiveSource['government_source_id'] !==
            (int) $source['government_source_id']
        ) {
            throw new RuntimeException(
                'The government source redirected outside its approved domain.'
            );
        }

        $this->assertPublicHost(
            $effectiveHost
        );

        if (
            str_contains(
                $contentType,
                'application/pdf'
            )
        ) {
            $manualTitle =
                trim(
                    (string) $manualTitle
                );

            if ($manualTitle === '') {
                throw new InvalidArgumentException(
                    'This source is a PDF. Enter its official title so it can be reviewed safely.'
                );
            }

            try {
                $parser =
                    new \Smalot\PdfParser\Parser();

                $pdfDocument =
                    $parser->parseContent(
                        $body
                    );

                $extractedText =
                    trim(
                        (string) $pdfDocument
                            ->getText()
                    );

                $extractedText =
                    preg_replace(
                        '/\s+/u',
                        ' ',
                        $extractedText
                    )
                    ?? $extractedText;

                $extractedText =
                    $this->truncate(
                        trim(
                            $extractedText
                        ),
                        50000
                    );
            } catch (Throwable $exception) {
                error_log(
                    'Government PDF extraction failed for '
                        . $effectiveUrl
                        . ': '
                        . $exception->getMessage()
                );

                $extractedText =
                    '';
            }

            return [
                'source' =>
                $source,

                'requested_url' =>
                $normalizedUrl,

                'effective_url' =>
                $effectiveUrl,

                'content_type' =>
                'application/pdf',

                'title' =>
                $this->truncate(
                    $manualTitle,
                    255
                ),

                'summary' =>
                '',

                'extracted_text' =>
                $extractedText,

                'content_hash' =>
                hash(
                    'sha256',
                    $body
                ),

                'fetched_at' =>
                date(
                    'Y-m-d H:i:s'
                )
            ];
        }

        $allowedContentTypes = [
            'text/html',
            'application/xhtml+xml',
            'text/plain'
        ];

        $isAllowedContentType =
            false;

        foreach (
            $allowedContentTypes
            as $allowedContentType
        ) {
            if (
                str_contains(
                    $contentType,
                    $allowedContentType
                )
            ) {
                $isAllowedContentType =
                    true;

                break;
            }
        }

        if (!$isAllowedContentType) {
            throw new RuntimeException(
                'Unsupported government source content type: '
                    . (
                        $contentType !== ''
                        ? $contentType
                        : 'unknown'
                    )
                    . '.'
            );
        }

        $metadata =
            $this->extractMetadata(
                $body
            );

        $manualTitle =
            trim(
                (string) $manualTitle
            );

        $title =
            $manualTitle !== ''
            ? $manualTitle
            : (
                $metadata['title']
                ?? ''
            );

        $title =
            $this->truncate(
                trim(
                    $title
                ),
                255
            );

        if ($title === '') {
            throw new RuntimeException(
                'The government source title could not be identified.'
            );
        }

        $summary =
            $this->truncate(
                trim(
                    (string) (
                        $metadata['summary']
                        ?? ''
                    )
                ),
                2000
            );

        $extractedText =
            $this->truncate(
                trim(
                    (string) (
                        $metadata['extracted_text']
                        ?? ''
                    )
                ),
                50000
            );

        $hashContent =
            $this->normalizeHashContent(
                $title
                    . ' '
                    . $summary
                    . ' '
                    . $extractedText
            );

        return [
            'source' =>
            $source,

            'requested_url' =>
            $normalizedUrl,

            'effective_url' =>
            $effectiveUrl,

            'content_type' =>
            $contentType,

            'title' =>
            $title,

            'summary' =>
            $summary,

            'extracted_text' =>
            $extractedText,

            'content_hash' =>
            hash(
                'sha256',
                $hashContent
            ),

            'fetched_at' =>
            date(
                'Y-m-d H:i:s'
            )
        ];
    }

    /* ==========================================
       URL VALIDATION
    ========================================== */

    private function normalizeAndValidateUrl(
        string $url
    ): string {
        $url =
            trim(
                $url
            );

        if (
            $url === '' ||
            filter_var(
                $url,
                FILTER_VALIDATE_URL
            ) === false
        ) {
            throw new InvalidArgumentException(
                'Enter a valid government source URL.'
            );
        }

        $parts =
            parse_url(
                $url
            );

        if (!is_array($parts)) {
            throw new InvalidArgumentException(
                'The government source URL is invalid.'
            );
        }

        $scheme =
            strtolower(
                (string) (
                    $parts['scheme']
                    ?? ''
                )
            );

        $host =
            strtolower(
                (string) (
                    $parts['host']
                    ?? ''
                )
            );

        if (
            $scheme !== 'https' ||
            $host === ''
        ) {
            throw new InvalidArgumentException(
                'Government source URLs must use HTTPS.'
            );
        }

        if (
            isset($parts['user']) ||
            isset($parts['pass'])
        ) {
            throw new InvalidArgumentException(
                'Government source URLs cannot contain credentials.'
            );
        }

        if (
            isset($parts['port']) &&
            (int) $parts['port'] !== 443
        ) {
            throw new InvalidArgumentException(
                'Government source URLs must use the standard HTTPS port.'
            );
        }

        $path =
            (string) (
                $parts['path']
                ?? '/'
            );

        if ($path === '') {
            $path = '/';
        }

        $normalized =
            'https://'
            . $host
            . $path;

        if (
            isset($parts['query']) &&
            $parts['query'] !== ''
        ) {
            $normalized .=
                '?'
                . $parts['query'];
        }

        return $normalized;
    }

    /* ==========================================
       PUBLIC DNS SAFETY
    ========================================== */

    private function assertPublicHost(
        string $host
    ): void {
        $addresses =
            gethostbynamel(
                $host
            )
            ?: [];

        if ($addresses === []) {
            throw new RuntimeException(
                'The trusted government host could not be resolved.'
            );
        }

        foreach ($addresses as $address) {
            $isPublic =
                filter_var(
                    $address,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_NO_PRIV_RANGE |
                        FILTER_FLAG_NO_RES_RANGE
                );

            if ($isPublic === false) {
                throw new RuntimeException(
                    'The government source resolved to a restricted network address.'
                );
            }
        }
    }

    /* ==========================================
       HTML METADATA EXTRACTION
    ========================================== */

    private function extractMetadata(
        string $html
    ): array {
        $previousState =
            libxml_use_internal_errors(
                true
            );

        /*
         * DOMDocument otherwise treats HTML as a
         * legacy single-byte encoding and produces
         * text such as "MalacaÃ±ang".
         */
        $utf8Html =
            '<?xml encoding="UTF-8">'
            . preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $html
            );

        $document =
            new DOMDocument(
                '1.0',
                'UTF-8'
            );

        $loaded =
            $document->loadHTML(
                $utf8Html,
                LIBXML_NOWARNING |
                    LIBXML_NOERROR |
                    LIBXML_NONET
            );

        libxml_clear_errors();

        libxml_use_internal_errors(
            $previousState
        );

        if (!$loaded) {
            throw new RuntimeException(
                'Unable to parse the government source page.'
            );
        }

        $xpath =
            new DOMXPath(
                $document
            );

        $title =
            $this->firstMetadataValue(
                $xpath,
                [
                    "//meta[@property='og:title']/@content",
                    "//meta[@name='twitter:title']/@content",
                    '//title/text()'
                ]
            );

        $summary =
            $this->firstMetadataValue(
                $xpath,
                [
                    "//meta[@property='og:description']/@content",
                    "//meta[@name='description']/@content",
                    "//meta[@name='twitter:description']/@content"
                ]
            );

        /*
         * Remove navigation, controls, sidebars,
         * and non-content elements before extraction.
         */
        $removalQueries = [
            '//script',
            '//style',
            '//noscript',
            '//svg',
            '//form',
            '//nav',
            '//footer',
            '//header',
            '//aside',

            "//*[contains(
                concat(
                    ' ',
                    normalize-space(@class),
                    ' '
                ),
                ' breadcrumb '
            )]",

            "//*[contains(
                concat(
                    ' ',
                    normalize-space(@class),
                    ' '
                ),
                ' sidebar '
            )]",

            "//*[contains(
                concat(
                    ' ',
                    normalize-space(@class),
                    ' '
                ),
                ' site-navigation '
            )]"
        ];

        foreach (
            $removalQueries
            as $query
        ) {
            $nodes =
                $xpath->query(
                    $query
                );

            if ($nodes === false) {
                continue;
            }

            $nodesToRemove = [];

            foreach ($nodes as $node) {
                $nodesToRemove[] =
                    $node;
            }

            foreach (
                $nodesToRemove
                as $node
            ) {
                $node->parentNode
                    ?->removeChild(
                        $node
                    );
            }
        }

        /*
         * Prefer article-specific containers before
         * falling back to the complete page body.
         */
        $contentQueries = [
            "//*[contains(
                concat(
                    ' ',
                    normalize-space(@class),
                    ' '
                ),
                ' entry-content '
            )]",

            "//*[contains(
                concat(
                    ' ',
                    normalize-space(@class),
                    ' '
                ),
                ' post-content '
            )]",

            "//*[contains(
                concat(
                    ' ',
                    normalize-space(@class),
                    ' '
                ),
                ' article-content '
            )]",

            '//main//article',
            '//article',
            "//*[@role='main']",
            '//main',
            '//body'
        ];

        $contentNode =
            null;

        foreach (
            $contentQueries
            as $query
        ) {
            $nodes =
                $xpath->query(
                    $query
                );

            if (
                $nodes !== false &&
                $nodes->length > 0
            ) {
                $contentNode =
                    $nodes->item(
                        0
                    );

                break;
            }
        }

        $contentHtml =
            $contentNode
            ? (
                $document->saveHTML(
                    $contentNode
                )
                ?: ''
            )
            : '';

        /*
         * Preserve block boundaries so paragraphs
         * and list entries remain readable.
         */
        $contentHtml =
            preg_replace(
                '/<(?:br\b[^>]*|\/(?:p|div|li|h[1-6]|section|article|tr|td|th|ul|ol))\s*>/iu',
                "\n",
                $contentHtml
            )
            ?? $contentHtml;

        $extractedText =
            html_entity_decode(
                strip_tags(
                    $contentHtml
                ),
                ENT_QUOTES |
                    ENT_HTML5,
                'UTF-8'
            );

        $decodedTitle =
            html_entity_decode(
                $title,
                ENT_QUOTES |
                    ENT_HTML5,
                'UTF-8'
            );

        $decodedSummary =
            html_entity_decode(
                $summary,
                ENT_QUOTES |
                    ENT_HTML5,
                'UTF-8'
            );

        $decodedTitle =
            $this->repairGovernmentTextEncoding(
                $decodedTitle
            );

        $decodedSummary =
            $this->repairGovernmentTextEncoding(
                $decodedSummary
            );

        $extractedText =
            $this->repairGovernmentTextEncoding(
                $extractedText
            );

        /*
         * Normalize horizontal spacing without
         * destroying paragraph and list breaks.
         */
        $extractedText =
            preg_replace(
                '/[^\S\r\n]+/u',
                ' ',
                $extractedText
            )
            ?? $extractedText;

        $extractedText =
            preg_replace(
                '/[ \t]*\R[ \t]*/u',
                "\n",
                $extractedText
            )
            ?? $extractedText;

        $extractedText =
            preg_replace(
                '/\n{3,}/u',
                "\n\n",
                $extractedText
            )
            ?? $extractedText;

        return [
            'title' =>
            trim(
                $decodedTitle
            ),

            'summary' =>
            trim(
                $decodedSummary
            ),

            'extracted_text' =>
            trim(
                $extractedText
            )
        ];
    }

    /*
     * Repair common UTF-8 text that government
     * websites incorrectly expose as Windows-1252.
     */
    private function repairGovernmentTextEncoding(
        string $value
    ): string {
        return strtr(
            $value,
            [
                "\u{00E2}\u{0080}\u{0098}" => "\u{2018}",
                "\u{00E2}\u{0080}\u{0099}" => "\u{2019}",
                "\u{00E2}\u{0080}\u{009C}" => "\u{201C}",
                "\u{00E2}\u{0080}\u{009D}" => "\u{201D}",
                "\u{00E2}\u{0080}\u{0093}" => "\u{2013}",
                "\u{00E2}\u{0080}\u{0094}" => "\u{2014}",
                "\u{00E2}\u{0080}\u{00A6}" => "\u{2026}",

                "\u{00E2}\u{20AC}\u{02DC}" => "\u{2018}",
                "\u{00E2}\u{20AC}\u{2122}" => "\u{2019}",
                "\u{00E2}\u{20AC}\u{0153}" => "\u{201C}",
                "\u{00E2}\u{20AC}\u{009D}" => "\u{201D}",
                "\u{00E2}\u{20AC}\u{201C}" => "\u{2013}",
                "\u{00E2}\u{20AC}\u{201D}" => "\u{2014}",
                "\u{00E2}\u{20AC}\u{00A6}" => "\u{2026}",

                "\u{00C3}\u{00B1}" => "\u{00F1}",
                "\u{00C3}\u{0091}" => "\u{00D1}",
                "\u{00C2}\u{00A0}" => ' '
            ]
        );
    }

    private function firstMetadataValue(
        DOMXPath $xpath,
        array $queries
    ): string {
        foreach ($queries as $query) {
            $nodes =
                $xpath->query(
                    $query
                );

            if (
                $nodes !== false &&
                $nodes->length > 0
            ) {
                $value =
                    trim(
                        (string) $nodes
                            ->item(0)
                            ?->nodeValue
                    );

                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    private function normalizeHashContent(
        string $value
    ): string {
        $value =
            preg_replace(
                '/\s+/u',
                ' ',
                $value
            )
            ?? $value;

        return mb_strtolower(
            trim(
                $value
            ),
            'UTF-8'
        );
    }

    private function truncate(
        string $value,
        int $maximumLength
    ): string {
        if (
            mb_strlen(
                $value,
                'UTF-8'
            ) <= $maximumLength
        ) {
            return $value;
        }

        return mb_substr(
            $value,
            0,
            $maximumLength,
            'UTF-8'
        );
    }
}
