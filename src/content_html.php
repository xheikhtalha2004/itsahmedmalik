<?php

declare(strict_types=1);

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Keep article markup within the formatting controls exposed by the CMS.
 */
function sanitize_article_html(string $html): string
{
    if (!class_exists(HtmlSanitizer::class) || !class_exists(HtmlSanitizerConfig::class)) {
        throw new RuntimeException('The required HTML sanitizer is unavailable. Run Composer install before using the CMS.');
    }

    $config = (new HtmlSanitizerConfig())
        ->allowElement('p')
        ->allowElement('h2')
        ->allowElement('h3')
        ->allowElement('strong')
        ->allowElement('em')
        ->allowElement('blockquote')
        ->allowElement('ol')
        ->allowElement('ul')
        ->allowElement('li')
        ->allowElement('br')
        ->allowElement('a', ['href'])
        ->allowLinkSchemes(['http', 'https']);

    return trim((new HtmlSanitizer($config))->sanitize($html));
}
