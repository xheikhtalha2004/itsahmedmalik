<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/views/public.php';

function expect_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expect_contains(string $needle, string $haystack, string $message): void
{
    expect_true(str_contains($haystack, $needle), $message);
}

function expect_unique_ids(string $html): void
{
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $seen = [];
    foreach ((new DOMXPath($document))->query('//*[@id]') ?: [] as $element) {
        $id = $element instanceof DOMElement ? $element->getAttribute('id') : '';
        expect_true($id === '' || !isset($seen[$id]), "Duplicate rendered id: {$id}");
        $seen[$id] = true;
    }
}

$contact = public_render_page('contact.php', 'contact', null);
expect_contains('<a class="skip-link" href="#main-content">', $contact, 'Skip link is missing.');
expect_contains('<main id="main-content"', $contact, 'Main landmark target is missing.');
expect_contains('action="/api/contact.php"', $contact, 'Contact API action is missing.');
expect_contains('name="submission_id"', $contact, 'Contact idempotency field is missing.');
expect_contains('action="/api/meeting.php"', $contact, 'Meeting API action is missing.');
expect_contains('action="/api/newsletter.php"', $contact, 'Newsletter API action is missing.');
expect_contains('onload=portfolioTurnstileReady', $contact, 'Turnstile callback is missing.');
expect_true(strpos($contact, 'forms.js') < strpos($contact, 'challenges.cloudflare.com'), 'Forms controller must load before Turnstile.');
expect_true(strpos($contact, '>Work</a>') < strpos($contact, '>Events</a>'), 'Events must follow Work in the left desktop navigation.');
expect_true(strpos($contact, '>Events</a>') < strpos($contact, 'nav-logo'), 'Events must remain on the left side of the desktop navigation.');
expect_true(!str_contains($contact, 'my_cv.pdf'), 'Missing CV link should be hidden.');
expect_unique_ids($contact);

$education = [[
    'degree' => 'Test degree', 'institution' => 'Test institution', 'label' => '2026',
]];
$experiences = [[
    'role_title' => 'Test role', 'company' => 'Test company', 'company_url' => 'https://example.test',
    'tenure_label' => 'Current', 'category_label' => 'Engineering', 'icon_text' => 'TC', 'color_preset' => 'cyan',
]];
$testimonials = [[
    'quote_text' => 'A useful testimonial.', 'rating' => 5, 'author' => 'Test Person',
    'role_title' => 'Founder', 'initials' => 'TP', 'gradient_preset' => 'violet',
]];
$home = public_render_page('home.php', 'home', [
    'education' => $education, 'experiences' => $experiences, 'testimonials' => $testimonials,
]);
expect_contains('<h1 class="hero-wordmark">AHMED MALIK</h1>', $home, 'Home H1 is missing.');
expect_true(!str_contains($home, '20260403b'), 'Legacy cache-version strings should not remain in public output.');
expect_contains('Test degree', $home, 'Education was not rendered.');
expect_contains('Test role', $home, 'Experience was not rendered.');
expect_contains('A useful testimonial.', $home, 'Testimonial was not rendered.');
expect_true(!str_contains($home, 'testimonial-slider-controls'), 'One testimonial should not render controls.');
expect_unique_ids($home);

$about = public_render_page('about.php', 'about', [
    'education' => $education, 'experiences' => $experiences, 'testimonials' => $testimonials,
]);
expect_contains('Test degree', $about, 'About education was not synchronized.');
expect_contains('A useful testimonial.', $about, 'About testimonial was not synchronized.');
expect_unique_ids($about);

$emptyHome = public_render_page('home.php', 'home', [
    'education' => [], 'experiences' => [], 'testimonials' => [],
]);
expect_true(!str_contains($emptyHome, 'id="experience"'), 'Empty education section should be hidden.');
expect_true(!str_contains($emptyHome, 'id="work-experience"'), 'Empty experience section should be hidden.');
expect_true(!str_contains($emptyHome, 'id="testimonials"'), 'Empty testimonial section should be hidden.');

$project = [[
    'name' => 'Test project', 'description' => 'Project description', 'project_status_label' => 'On track',
    'tone_preset' => 'positive', 'progress_percent' => 50, 'deadline_label' => 'Q4', 'milestone' => 'Beta',
    'members' => [['name' => 'Test Member', 'initials' => 'TM']],
]];
$work = public_render_page('work.php', 'work', ['experiences' => $experiences, 'projects' => $project]);
expect_contains('Test project', $work, 'Project was not rendered.');
expect_true((bool) preg_match('~<div class="dashboard-pagination"[^>]*\bhidden\b~', $work), 'One project should hide carousel pagination.');
expect_unique_ids($work);

$workWithoutProjects = public_render_page('work.php', 'work', ['experiences' => $experiences, 'projects' => []]);
expect_true(!str_contains($workWithoutProjects, 'dashboard-projects-panel'), 'Empty project collection should be hidden.');

$projectWithoutMembers = $project;
$projectWithoutMembers[0]['members'] = [];
$workWithoutMembers = public_render_page('work.php', 'work', ['experiences' => $experiences, 'projects' => $projectWithoutMembers]);
expect_true(!str_contains($workWithoutMembers, 'Responsible Team'), 'Projects without members should hide team metadata.');
expect_true(!str_contains($workWithoutMembers, 'dashboard-members-row'), 'Projects without members should hide the member collection.');

$projectWithoutOptionalMeta = $projectWithoutMembers;
$projectWithoutOptionalMeta[0]['deadline_label'] = null;
$projectWithoutOptionalMeta[0]['milestone'] = null;
$workWithoutOptionalMeta = public_render_page('work.php', 'work', ['experiences' => $experiences, 'projects' => $projectWithoutOptionalMeta]);
expect_true(!str_contains($workWithoutOptionalMeta, 'dashboard-project-meta'), 'Projects without optional metadata should hide the empty metadata row.');
expect_true(!str_contains($workWithoutOptionalMeta, '>Deadline<'), 'Projects without a deadline should not render a blank deadline label.');
expect_true(!str_contains($workWithoutOptionalMeta, '>Milestone<'), 'Projects without a milestone should not render a blank milestone label.');

$blog = [
    'id' => 11, 'title' => 'A new CMS article', 'author' => 'Ahmed Malik',
    'excerpt' => 'Article excerpt', 'meta_description' => 'Article description',
    'published_on' => '2026-07-17', 'published_at' => '2026-07-17 00:00:00',
    'cover_path' => '/images/blog.png', 'cover_alt' => 'Article cover',
    'body_html' => '<p>Sanitized article body.</p>', 'updated_at' => '2026-07-17 00:00:00',
];
$article = public_render_blog_detail($blog);
expect_contains('https://itsahmedmalik.com/blog-11.html', $article, 'New blog canonical URL is incorrect.');
expect_contains('<h1 class="blog-article-title">A new CMS article</h1>', $article, 'Blog title was not rendered.');
expect_contains('<p>Sanitized article body.</p>', $article, 'Blog body was not rendered.');
expect_contains('article:published_time', $article, 'Blog publication metadata is missing.');
expect_unique_ids($article);

$blogIndex = public_render_page('blog-index.php', 'blog', ['blogs' => [$blog]]);
expect_contains('EDITORIAL / 1 ARTICLES', $blogIndex, 'Blog count is incorrect.');
expect_true(!str_contains($blogIndex, 'blog-archive-grid'), 'One article should not render an empty archive grid.');

$emptyBlog = public_render_page('blog-index.php', 'blog', ['blogs' => []]);
expect_contains('New articles are being prepared.', $emptyBlog, 'Blog empty state is missing.');

$certification = [[
    'id' => 1, 'title' => 'Test certification', 'description' => 'Certificate description',
    'image_path' => '/images/blog.png', 'image_alt' => 'Certificate image',
]];
$certifications = public_render_page('certifications.php', 'certifications', ['certifications' => $certification]);
expect_contains('Test certification', $certifications, 'Certification was not rendered.');
expect_true((bool) preg_match('~class="cert-focus-nav"[^>]*\bhidden\b~', $certifications), 'One certification should hide rail navigation.');
expect_unique_ids($certifications);

$emptyCertifications = public_render_page('certifications.php', 'certifications', ['certifications' => []]);
expect_contains('New credentials will appear here soon.', $emptyCertifications, 'Certification empty state is missing.');
expect_true(!str_contains($emptyCertifications, 'id="certifications-modal"'), 'Empty certification state should omit its modal.');

$event = [[
    'id' => 1, 'title' => 'AI Test event', 'caption' => 'Event caption', 'cover_path' => '/images/blog.png',
    'images' => [[
        'public_path' => '/images/blog.png', 'alt_text' => 'Event image', 'caption' => 'Image caption',
    ]],
]];
$events = public_render_page('events.php', 'events', ['events' => $event]);
expect_contains('id="events-data"', $events, 'Event JSON payload is missing.');
expect_contains('AI Test event', $events, 'Event title is missing.');
expect_contains('Event image', $events, 'Event image alt text is missing.');
expect_contains('class="events-collection-card"', $events, 'Event card was not server-rendered.');
expect_contains('<span class="events-acronym">AI</span> Test event', $events, 'Event title styling was not server-rendered.');
expect_contains('data-event-index="0"', $events, 'Event card is not bound to its lightbox payload.');
expect_unique_ids($events);

$emptyEvents = public_render_page('events.php', 'events', ['events' => []]);
expect_contains('No event galleries are published yet.', $emptyEvents, 'Event empty state is missing.');
expect_true(!str_contains($emptyEvents, 'class="events-collection-card"'), 'Empty events state should not render an event card.');

$sitemap = public_render_sitemap([$blog]);
expect_true((new DOMDocument())->loadXML($sitemap, LIBXML_NONET) === true, 'Sitemap XML is invalid.');
expect_contains('blog-11.html', $sitemap, 'Published blog is missing from the sitemap.');

echo "public render checks passed\n";
