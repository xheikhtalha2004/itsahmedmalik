<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/content.php';
require_once __DIR__ . '/views/public.php';

$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$path = '/' . ltrim($path, '/');
header('Cache-Control: no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

try {
    if ($path === '/sitemap.xml') {
        header('Content-Type: application/xml; charset=UTF-8');
        echo public_render_sitemap(content_blogs());
        exit;
    }

    if (preg_match('~^/blog-([1-9][0-9]*)\.html$~', $path, $match)) {
        $id = (int) $match[1];
        $blog = content_find_blog($id);
        if ($blog === null) {
            public_not_found();
        }
        header('Content-Type: text/html; charset=UTF-8');
        echo public_render_blog_detail($blog);
        exit;
    }

    $routes = [
        '/' => ['home.php', 'home'],
        '/index.php' => ['home.php', 'home'],
        '/index.html' => ['home.php', 'home'],
        '/about.html' => ['about.php', 'about'],
        '/work.html' => ['work.php', 'work'],
        '/contact.html' => ['contact.php', 'contact'],
        '/blog.html' => ['blog-index.php', 'blog'],
        '/certifications.html' => ['certifications.php', 'certifications'],
        '/events.html' => ['events.php', 'events'],
    ];
    if (!isset($routes[$path])) {
        public_not_found();
    }

    [$template, $page] = $routes[$path];
    $content = match ($page) {
        'home', 'about' => [
            'education' => content_education(),
            'experiences' => content_experiences(),
            'testimonials' => content_testimonials(),
        ],
        'work' => [
            'experiences' => content_experiences(),
            'projects' => content_projects(),
        ],
        'blog' => ['blogs' => content_blogs()],
        'certifications' => ['certifications' => content_certifications()],
        'events' => ['events' => content_events()],
        'contact' => null,
        default => throw new LogicException('Unsupported public route.'),
    };

    header('Content-Type: text/html; charset=UTF-8');
    echo public_render_page($template, $page, $content);
} catch (Throwable $exception) {
    app_log('public_render_failed', ['reason' => $exception->getMessage()]);
    http_response_code(503);
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'The portfolio is temporarily unavailable. Please try again shortly.';
}

function public_not_found(): never
{
    http_response_code(404);
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<meta name="robots" content="noindex"><title>Page not found | Ahmed Malik</title><link rel="stylesheet" href="/style.css"></head>'
        . '<body><main><section class="contact-hero cert-hero"><h1 class="cert-hero-title">Page not found</h1>'
        . '<p class="cert-hero-subtitle">The page you requested is unavailable.</p><p><a class="newsletter-btn" href="/index.html">Return home</a></p>'
        . '</section></main></body></html>';
    exit;
}
