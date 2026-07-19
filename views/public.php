<?php

declare(strict_types=1);

function public_render_page(string $template, string $page, ?array $content, ?string $requestPath = null): string
{
    $publicPaths = [
        'home.php' => '/',
        'about.php' => '/about.html',
        'work.php' => '/work.html',
        'contact.php' => '/contact.html',
        'blog-index.php' => '/blog.html',
        'blog-detail.php' => '/blog-1.html',
        'certifications.php' => '/certifications.html',
        'events.php' => '/events.html',
    ];
    if (!isset($publicPaths[$template])) {
        throw new RuntimeException('Public page template is unavailable.');
    }
    $path = __DIR__ . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . $template;
    $bufferLevel = ob_get_level();
    ob_start();
    try {
        require $path;
        $html = (string) ob_get_clean();
    } catch (Throwable $exception) {
        while (ob_get_level() > $bufferLevel) {
            ob_end_clean();
        }
        throw $exception;
    }

    $html = (string) preg_replace(
        '~(<body\b[^>]*>)~i',
        '$1' . "\n  " . '<a class="skip-link" href="#main-content">Skip to content</a>',
        $html,
        1
    );
    $html = (string) preg_replace('~<main\b(?![^>]*\bid\s*=)([^>]*)>~i', '<main id="main-content"$1>', $html, 1);
    $html = public_replace_element($html, 'header', 'id', 'header', public_view_fragment('partials/header.php'));
    $html = public_replace_element(
        $html,
        'footer',
        'class',
        'footer',
        public_view_fragment('partials/footer.php', [
            'sourcePath' => $requestPath ?? $publicPaths[$template],
            'footerId' => preg_replace('/[^a-z0-9]+/i', '-', $page),
        ])
    );

    $siteKey = e((string) app_config('turnstile.site_key', ''));
    $headExtras = <<<HTML
  <meta name="turnstile-site-key" content="{$siteKey}" />
HTML;
    $html = str_replace('</head>', $headExtras . "\n</head>", $html);
    $html = str_replace(
        '</body>',
        "  <script src=\"forms.js\"></script>\n  <script src=\"https://challenges.cloudflare.com/turnstile/v0/api.js?onload=portfolioTurnstileReady&amp;render=explicit\" async defer></script>\n</body>",
        $html
    );

    if ($page === 'contact') {
        return public_render_contact($html);
    }
    if ($content === null) {
        return $html;
    }

    return match ($page) {
        'home' => public_render_home($html, $content),
        'about' => public_render_about($html, $content),
        'work' => public_render_work($html, $content),
        'blog' => public_render_blog_index($html, $content['blogs']),
        'certifications' => public_render_certifications($html, $content['certifications']),
        'events' => public_render_events($html, $content['events']),
        default => $html,
    };
}

function public_render_blog_detail(array $blog): string
{
    $id = (int) $blog['id'];
    $html = public_render_page('blog-detail.php', 'blog-detail', null, "/blog-{$id}.html");
    $title = (string) $blog['title'];
    $author = (string) ($blog['author'] ?: 'Ahmed Malik');
    $description = (string) ($blog['meta_description'] ?: $blog['excerpt']);
    $coverPath = ltrim((string) ($blog['cover_path'] ?: 'images/blog.png'), '/');
    $coverAlt = (string) ($blog['cover_alt'] ?: $title);
    $canonical = app_url("blog-{$id}.html");
    $imageUrl = app_url($coverPath);

    $html = public_replace_meta($html, 'title', $title . ' | Ahmed Malik');
    $html = public_replace_meta($html, 'description', $description, 'name');
    $html = public_replace_meta($html, 'canonical', $canonical, 'link');
    $html = public_replace_meta($html, 'og:title', $title . ' | Ahmed Malik', 'property');
    $html = public_replace_meta($html, 'og:description', $description, 'property');
    $html = public_replace_meta($html, 'og:url', $canonical, 'property');
    $html = public_replace_meta($html, 'og:image', $imageUrl, 'property');
    $html = public_replace_meta($html, 'og:image:alt', $coverAlt, 'property');
    $html = public_replace_meta($html, 'twitter:title', $title . ' | Ahmed Malik', 'name');
    $html = public_replace_meta($html, 'twitter:description', $description, 'name');
    $html = public_replace_meta($html, 'twitter:image', $imageUrl, 'name');
    $html = public_replace_meta($html, 'article:author', $author, 'property');

    $published = new DateTimeImmutable((string) ($blog['published_on'] ?: $blog['published_at'] ?: 'now'));
    $publishedIso = e($published->format('Y-m-d'));
    $html = str_replace('</head>', '  <meta property="article:published_time" content="' . $publishedIso . '" />' . "\n</head>", $html);
    $date = $published->format('F d, Y');
    $article = '<article class="blog-article">'
        . '<div class="blog-article-hero">'
        . '<span class="blog-article-meta">' . e($date) . ' &mdash; Written by ' . e($author) . '</span>'
        . '<h1 class="blog-article-title">' . e($title) . '</h1></div>'
        . '<div class="blog-article-image-container"><img src="' . e($coverPath) . '" alt="' . e($coverAlt) . '" class="blog-article-image" /></div>'
        . '<div class="blog-article-content">' . (string) $blog['body_html'] . '</div>'
        . '</article>';

    return public_replace_element($html, 'article', 'class', 'blog-article', $article);
}

function public_render_sitemap(array $blogs): string
{
    $base = rtrim(app_url(), '/');
    $fixed = ['', 'about.html', 'work.html', 'contact.html', 'certifications.html', 'events.html', 'blog.html'];
    $urls = [];
    foreach ($fixed as $path) {
        $urls[] = ['loc' => $base . '/' . $path, 'lastmod' => null];
    }
    foreach ($blogs as $blog) {
        $urls[] = [
            'loc' => $base . '/blog-' . (int) $blog['id'] . '.html',
            'lastmod' => substr((string) ($blog['updated_at'] ?? $blog['published_on']), 0, 10),
        ];
    }

    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    foreach ($urls as $url) {
        $xml .= '  <url><loc>' . htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
        if ($url['lastmod']) {
            $xml .= '<lastmod>' . htmlspecialchars($url['lastmod'], ENT_XML1, 'UTF-8') . '</lastmod>';
        }
        $xml .= "</url>\n";
    }

    return $xml . "</urlset>\n";
}

function public_view_fragment(string $name, array $variables = []): string
{
    extract($variables, EXTR_SKIP);
    $bufferLevel = ob_get_level();
    ob_start();
    try {
        require __DIR__ . DIRECTORY_SEPARATOR . $name;

        return (string) ob_get_clean();
    } catch (Throwable $exception) {
        while (ob_get_level() > $bufferLevel) {
            ob_end_clean();
        }
        throw $exception;
    }
}

function public_replace_meta(string $html, string $key, string $value, string $kind = 'title'): string
{
    $escaped = e($value);
    if ($kind === 'title') {
        return (string) preg_replace('~<title>.*?</title>~is', '<title>' . $escaped . '</title>', $html, 1);
    }
    if ($kind === 'link') {
        return (string) preg_replace(
            '~<link\b(?=[^>]*\brel=["\']canonical["\'])[^>]*>~i',
            '<link rel="canonical" href="' . $escaped . '" />',
            $html,
            1
        );
    }

    $attribute = $kind === 'property' ? 'property' : 'name';
    $pattern = '~<meta\b(?=[^>]*\b' . $attribute . '=["\']' . preg_quote($key, '~') . '["\'])[^>]*>~i';
    $tag = '<meta ' . $attribute . '="' . e($key) . '" content="' . $escaped . '" />';

    return (string) preg_replace($pattern, $tag, $html, 1);
}

function public_replace_element(string $html, string $tag, string $attribute, string $value, string $replacement): string
{
    $bounds = public_element_bounds($html, $tag, $attribute, $value);
    if ($bounds === null) {
        return $html;
    }

    return substr($html, 0, $bounds['start']) . $replacement . substr($html, $bounds['end']);
}

function public_replace_element_inner(string $html, string $tag, string $attribute, string $value, string $replacement): string
{
    $bounds = public_element_bounds($html, $tag, $attribute, $value);
    if ($bounds === null) {
        return $html;
    }

    return substr($html, 0, $bounds['openEnd']) . $replacement . substr($html, $bounds['closeStart']);
}

function public_element_bounds(string $html, string $tag, string $attribute, string $value): ?array
{
    preg_match_all('~<' . preg_quote($tag, '~') . '\b[^>]*>~i', $html, $openings, PREG_OFFSET_CAPTURE);
    $start = null;
    $openTag = '';
    foreach ($openings[0] as [$candidate, $offset]) {
        if (public_tag_has_attribute($candidate, $attribute, $value)) {
            $start = $offset;
            $openTag = $candidate;
            break;
        }
    }
    if ($start === null) {
        return null;
    }

    $openEnd = $start + strlen($openTag);
    preg_match_all('~</?' . preg_quote($tag, '~') . '\b[^>]*>~i', substr($html, $start), $tokens, PREG_OFFSET_CAPTURE);
    $depth = 0;
    foreach ($tokens[0] as [$token, $relativeOffset]) {
        $closing = str_starts_with($token, '</');
        if ($closing) {
            --$depth;
            if ($depth === 0) {
                $closeStart = $start + $relativeOffset;
                return [
                    'start' => $start,
                    'openEnd' => $openEnd,
                    'closeStart' => $closeStart,
                    'end' => $closeStart + strlen($token),
                ];
            }
        } elseif (!str_ends_with(rtrim($token), '/>')) {
            ++$depth;
        }
    }

    return null;
}

function public_tag_has_attribute(string $tag, string $attribute, string $value): bool
{
    if (!preg_match('~\b' . preg_quote($attribute, '~') . '\s*=\s*(["\'])(.*?)\1~is', $tag, $match)) {
        return false;
    }
    if ($attribute === 'class') {
        return in_array($value, preg_split('/\s+/', trim($match[2])) ?: [], true);
    }

    return $match[2] === $value;
}

function public_render_home(string $html, array $content): string
{
    $html = public_replace_element($html, 'section', 'id', 'experience', public_education_section($content['education']));
    $html = public_replace_element($html, 'section', 'id', 'work-experience', public_experience_section($content['experiences']));

    return public_replace_element($html, 'section', 'id', 'testimonials', public_testimonials_section($content['testimonials']));
}

function public_render_about(string $html, array $content): string
{
    $html = public_replace_element($html, 'section', 'id', 'testimonials', public_testimonials_section($content['testimonials']));

    return public_replace_element($html, 'section', 'id', 'experience', public_education_section($content['education']));
}

function public_render_work(string $html, array $content): string
{
    $html = public_replace_element($html, 'section', 'id', 'work-experience', public_experience_section($content['experiences']));
    $slides = public_project_slides($content['projects']);
    $html = public_replace_element_inner($html, 'div', 'id', 'dashboard-carousel-track', $slides);

    if ($content['projects'] === []) {
        $html = public_replace_element($html, 'div', 'class', 'dashboard-projects-panel', '');
    } elseif (count($content['projects']) === 1) {
        $html = (string) preg_replace(
            '~<div class="dashboard-pagination"([^>]*)>~i',
            '<div class="dashboard-pagination"$1 hidden>',
            $html,
            1
        );
    }

    return $html;
}

function public_education_section(array $entries): string
{
    if ($entries === []) {
        return '';
    }

    $branches = '';
    foreach ($entries as $index => $entry) {
        $branches .= '<div class="edu-branch"><div class="edu-connector"><div class="edu-line"></div><div class="edu-dot"></div></div>'
            . '<div class="edu-card"><div class="edu-badge">' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '</div>'
            . '<div class="edu-info"><h3 class="edu-degree">' . e($entry['degree']) . '</h3>'
            . '<p class="edu-institution">' . e($entry['institution']) . '</p>'
            . '<span class="edu-tag">' . e($entry['label']) . '</span></div></div></div>';
    }

    return '<section id="experience" class="experience-section"><h2 class="experience-title">Educational Background</h2>'
        . '<div class="edu-tree"><div class="edu-trunk"></div><div class="edu-branches">'
        . $branches . '</div></div></section>';
}

function public_experience_section(array $entries): string
{
    if ($entries === []) {
        return '';
    }

    $cards = '';
    foreach ($entries as $index => $entry) {
        $company = e($entry['company']);
        if (!empty($entry['company_url'])) {
            $company = '<a href="' . e($entry['company_url']) . '" target="_blank" rel="noopener noreferrer" class="exp-link">'
                . $company . ' &#8599;</a>';
        }
        [$background, $foreground] = public_experience_colors((string) $entry['color_preset']);
        $cards .= '<div class="exp-card' . ($index === 0 ? ' exp-full-width' : '') . '"><div class="exp-card-top"><div>'
            . '<h3 class="exp-role">' . e($entry['role_title']) . '</h3><p class="exp-company">' . $company . '</p></div>'
            . '<div class="exp-icon"><svg viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg" width="28" height="28">'
            . '<rect width="28" height="28" rx="6" fill="' . e($background) . '" />'
            . '<text x="14" y="19" font-size="9" font-weight="900" fill="' . e($foreground) . '" text-anchor="middle" font-family="sans-serif">'
            . e($entry['icon_text']) . '</text></svg></div></div><div class="exp-tags">'
            . '<span class="exp-tag">' . e($entry['tenure_label']) . '</span><span class="exp-tag">' . e($entry['category_label']) . '</span>'
            . '</div></div>';
    }

    return '<section id="work-experience" class="experience-section"><h2 class="experience-title">Work Experience</h2>'
        . '<div class="experience-grid">' . $cards . '</div></section>';
}

function public_experience_colors(string $preset): array
{
    return match ($preset) {
        'navy' => ['#0f3460', '#e2b96f'],
        'purple' => ['#6c3483', '#ffffff'],
        'green' => ['#1b4332', '#52b788'],
        'blue' => ['#112d4e', '#3f72af'],
        'gold' => ['#003049', '#fcbf49'],
        'red' => ['#1a1a2e', '#e94560'],
        default => ['#02111b', '#00b4d8'],
    };
}

function public_testimonials_section(array $entries): string
{
    if ($entries === []) {
        return '';
    }

    $cards = '';
    foreach ($entries as $entry) {
        $rating = max(1, min(5, (int) $entry['rating']));
        $gradient = public_testimonial_gradient((string) $entry['gradient_preset']);
        $cards .= '<article class="testimonial-card"><div class="testimonial-card-top"><span class="testimonial-quote-mark">&quot;</span>'
            . '<div class="testimonial-stars" aria-label="' . $rating . ' out of 5 stars">'
            . str_repeat('&#9733;', $rating) . '</div></div>'
            . '<p class="testimonial-text">' . e($entry['quote_text']) . '</p><div class="testimonial-author">'
            . '<div class="author-avatar" style="background: ' . e($gradient) . ';">' . e($entry['initials']) . '</div>'
            . '<div class="author-meta"><p class="author-name">' . e($entry['author']) . '</p>'
            . '<p class="author-role">' . e($entry['role_title']) . '</p></div></div></article>';
    }

    $controls = count($entries) > 1
        ? '<div class="testimonial-slider-controls"><button class="testimonial-nav-btn" id="testimonial-prev" type="button" aria-label="Previous testimonial">&#8249;</button>'
            . '<div class="testimonial-dots" id="testimonial-dots" aria-label="Testimonial navigation"></div>'
            . '<button class="testimonial-nav-btn" id="testimonial-next" type="button" aria-label="Next testimonial">&#8250;</button></div>'
        : '';

    return '<section id="testimonials" class="endorsements-section"><h2 class="testimonials-title">WHAT FOUNDERS &amp; PEERS SAY ABOUT AHMED MALIK?</h2>'
        . '<div class="testimonials-slider" aria-label="Ahmed Malik endorsements"><div class="testimonials-viewport">'
        . '<div class="testimonials-track" id="testimonials-track">' . $cards . '</div></div>' . $controls . '</div></section>';
}

function public_testimonial_gradient(string $preset): string
{
    return match ($preset) {
        'emerald' => 'linear-gradient(135deg, #11998e, #38ef7d)',
        'rose' => 'linear-gradient(135deg, #f093fb, #f5576c)',
        'cyan' => 'linear-gradient(135deg, #4facfe, #00f2fe)',
        'sunset' => 'linear-gradient(135deg, #fa709a, #fee140)',
        default => 'linear-gradient(135deg, #667eea, #764ba2)',
    };
}

function public_project_slides(array $projects): string
{
    $slides = '';
    foreach ($projects as $project) {
        $progress = max(0, min(100, (int) $project['progress_percent']));
        $tone = match ((string) $project['tone_preset']) {
            'positive' => ' is-positive',
            'warning' => ' is-warning',
            default => '',
        };
        $memberNames = implode(', ', array_map(static fn (array $member): string => (string) $member['name'], $project['members']));
        $badges = '';
        foreach ($project['members'] as $member) {
            $badges .= '<span class="dashboard-member-badge">' . e($member['initials']) . '</span>';
        }
        $teamMeta = $project['members'] === []
            ? ''
            : '<div class="dashboard-meta-item"><span>Responsible Team</span><strong>' . e($memberNames) . '</strong></div>';
        $teamBadges = $project['members'] === []
            ? ''
            : '<div class="dashboard-members-row" aria-label="Responsible team members">' . $badges . '</div>';
        $deadlineMeta = trim((string) ($project['deadline_label'] ?? '')) === ''
            ? ''
            : '<div class="dashboard-meta-item"><span>Deadline</span><strong>' . e($project['deadline_label']) . '</strong></div>';
        $milestoneMeta = trim((string) ($project['milestone'] ?? '')) === ''
            ? ''
            : '<div class="dashboard-meta-item"><span>Milestone</span><strong>' . e($project['milestone']) . '</strong></div>';
        $projectMetaItems = $deadlineMeta . $teamMeta . $milestoneMeta;
        $projectMeta = $projectMetaItems === ''
            ? ''
            : '<div class="dashboard-project-meta">' . $projectMetaItems . '</div>';
        $slides .= '<article class="dashboard-project-slide"><div class="dashboard-project-top"><div>'
            . '<span class="dashboard-chip' . $tone . '">' . e($project['project_status_label']) . '</span>'
            . '<h4 class="dashboard-project-name">' . e($project['name']) . '</h4>'
            . '<p class="dashboard-project-description">' . e($project['description']) . '</p></div>'
            . '<strong class="dashboard-project-percent">' . $progress . '%</strong></div>'
            . '<div class="dashboard-progress-block"><div class="dashboard-progress-row"><span>Progress</span><span>' . $progress . '% complete</span></div>'
            . '<div class="dashboard-progress-track"><span class="dashboard-progress-fill" style="width: ' . $progress . '%;"></span></div></div>'
            . $projectMeta
            . $teamBadges . '</article>';
    }

    return $slides;
}

function public_render_blog_index(string $html, array $blogs): string
{
    if ($blogs === []) {
        $section = '<section class="blog-showcase-section"><div class="blog-showcase-shell">'
            . '<div class="blog-showcase-header"><span class="blog-showcase-background" aria-hidden="true">BLOG</span>'
            . '<p class="blog-showcase-kicker">EDITORIAL / 0 ARTICLES</p><h1 class="blog-showcase-title">Latest Articles</h1>'
            . '<p class="blog-showcase-description">New articles are being prepared. Please check back soon.</p></div></div></section>';

        return public_replace_element($html, 'section', 'class', 'blog-showcase-section', $section);
    }

    $featured = array_slice($blogs, 0, 3);
    $archive = array_slice($blogs, 3);
    $featureCards = '';
    foreach ($featured as $index => $blog) {
        $featureCards .= '<a href="blog-' . (int) $blog['id'] . '.html" class="blog-feature-card' . ($index === 0 ? ' blog-feature-card-primary' : '') . '">'
            . '<img src="' . e(ltrim((string) $blog['cover_path'], '/')) . '" alt="' . e($blog['cover_alt']) . '" class="blog-feature-image" />'
            . '<div class="blog-feature-overlay"></div><div class="blog-feature-content">'
            . '<span class="blog-feature-meta">' . e(public_blog_date($blog['published_on'] ?: $blog['published_at'])) . '</span>'
            . '<h2 class="blog-feature-title">' . e($blog['title']) . '</h2>'
            . '<span class="blog-feature-cta">Read Article <span aria-hidden="true">&#8594;</span></span></div></a>';
    }

    $archiveCards = '';
    foreach ($archive as $blog) {
        $archiveCards .= '<a href="blog-' . (int) $blog['id'] . '.html" class="blog-archive-card"><div class="blog-archive-image-wrap">'
            . '<img src="' . e(ltrim((string) $blog['cover_path'], '/')) . '" alt="' . e($blog['cover_alt']) . '" class="blog-archive-image" /></div>'
            . '<div class="blog-archive-content"><span class="blog-archive-date">' . e(public_blog_date($blog['published_on'] ?: $blog['published_at'])) . '</span>'
            . '<h2 class="blog-archive-post-title">' . e($blog['title']) . '</h2>'
            . '<span class="blog-archive-cta">Read Article <span aria-hidden="true">&#8594;</span></span></div></a>';
    }

    $archiveSection = $archive === [] ? ''
        : '<div class="blog-archive-head"><p class="blog-archive-kicker">MORE FROM THE JOURNAL</p><h2 class="blog-archive-title">Recent Writing</h2></div>'
            . '<div class="blog-archive-grid">' . $archiveCards . '</div>';
    $section = '<section class="blog-showcase-section"><div class="blog-showcase-shell"><div class="blog-showcase-header">'
        . '<span class="blog-showcase-background" aria-hidden="true">BLOG</span><p class="blog-showcase-kicker">EDITORIAL / ' . count($blogs) . ' ARTICLES</p>'
        . '<h1 class="blog-showcase-title">Latest Articles</h1><p class="blog-showcase-description">Insights, tutorials, and thoughts on AI engineering, startups, and product building.</p>'
        . '</div><div class="blog-feature-grid">' . $featureCards . '</div>' . $archiveSection . '</div></section>';

    return public_replace_element($html, 'section', 'class', 'blog-showcase-section', $section);
}

function public_blog_date(?string $date): string
{
    return (new DateTimeImmutable($date ?: 'now'))->format('F d, Y');
}

function public_render_certifications(string $html, array $certifications): string
{
    if ($certifications === []) {
        $empty = '<section class="certifications-section"><div class="cert-focus-shell"><div class="cert-focus-header"><div>'
            . '<h2 class="cert-focus-title">Certification Gallery</h2><p class="cert-focus-text">New credentials will appear here soon.</p>'
            . '</div></div></div></section>';
        $html = public_replace_element($html, 'section', 'class', 'certifications-section', $empty);

        return public_replace_element($html, 'div', 'id', 'certifications-modal', '');
    }

    $cards = '';
    foreach ($certifications as $index => $certification) {
        $badge = 'Credential ' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
        $image = ltrim((string) $certification['image_path'], '/');
        $cards .= '<article class="cert-focus-card" data-cert-badge="' . e($badge) . '" data-cert-title="' . e($certification['title'])
            . '" data-cert-desc="' . e($certification['description']) . '" data-cert-image="' . e($image) . '">'
            . '<img src="' . e($image) . '" alt="' . e($certification['image_alt']) . '" class="cert-focus-card-image" /></article>';
    }

    $first = $certifications[0];
    $firstImage = ltrim((string) $first['image_path'], '/');
    $single = count($certifications) === 1 ? ' hidden' : '';
    $controls = '<div class="cert-focus-nav" aria-label="Certification rail controls"' . $single . '><button type="button" class="cert-focus-btn" id="certifications-prev" aria-label="Show previous certification">&larr;</button>'
        . '<span class="cert-focus-counter" id="certifications-counter">01 / ' . str_pad((string) count($certifications), 2, '0', STR_PAD_LEFT) . '</span>'
        . '<button type="button" class="cert-focus-btn" id="certifications-next" aria-label="Show next certification">&rarr;</button></div>';
    $section = '<section class="certifications-section"><div class="cert-focus-shell"><div class="cert-focus-header"><div>'
        . '<h2 class="cert-focus-title">Certification Gallery</h2><p class="cert-focus-text">Navigate the rail to review training records, verified milestones, and portfolio-ready credentials.</p>'
        . '</div></div><div class="cert-focus-rail" id="certifications-rail" tabindex="0" aria-label="Certification focus rail">'
        . '<div class="cert-focus-ambience" aria-hidden="true"><img src="' . e($firstImage) . '" alt="" class="cert-focus-ambience-image" id="certifications-ambience-image" />'
        . '<div class="cert-focus-ambience-overlay"></div></div><div class="cert-focus-body"><div class="cert-focus-stage">'
        . '<div class="cert-focus-track" id="certifications-track">' . $cards . '</div></div><div class="cert-focus-footer">'
        . '<div class="cert-focus-details"><span class="cert-focus-meta" id="certifications-badge">Credential 01</span>'
        . '<h3 class="cert-focus-name" id="certifications-title">' . e($first['title']) . '</h3>'
        . '<p class="cert-focus-description" id="certifications-desc">' . e($first['description']) . '</p></div>'
        . '<div class="cert-focus-controls">' . $controls . '<button type="button" class="cert-focus-expand" id="certifications-expand">Expand Certificate</button>'
        . '<div class="cert-focus-dots" id="certifications-dots" aria-label="Certification slides"' . $single . '></div></div></div></div></div></div></section>';
    $modal = '<div class="cert-viewer-modal" id="certifications-modal" aria-hidden="true"><div class="cert-viewer-backdrop" data-close-certifications-modal></div>'
        . '<div class="cert-viewer-dialog" role="dialog" aria-modal="true" aria-labelledby="certifications-modal-title"><button type="button" class="cert-viewer-close" id="close-certifications-modal" aria-label="Close certificate viewer">&times;</button>'
        . '<div class="cert-viewer-header"><div><span class="cert-viewer-badge" id="certifications-modal-badge">Credential 01</span>'
        . '<h2 class="cert-viewer-title" id="certifications-modal-title">' . e($first['title']) . '</h2></div></div>'
        . '<div class="cert-viewer-stage"><img src="' . e($firstImage) . '" alt="' . e($first['image_alt']) . '" class="cert-viewer-image" id="certifications-modal-image" /></div></div></div>';
    $html = public_replace_element($html, 'section', 'class', 'certifications-section', $section);

    return public_replace_element($html, 'div', 'id', 'certifications-modal', $modal);
}

function public_render_events(string $html, array $events): string
{
    $payload = [];
    $cards = '';
    foreach ($events as $event) {
        $images = array_values(array_filter(
            array_map(static fn (array $image): array => [
                'src' => ltrim((string) ($image['public_path'] ?? ''), '/'),
                'alt' => (string) ($image['alt_text'] ?? ''),
                'caption' => (string) ($image['caption'] ?? ''),
            ], $event['images'] ?? []),
            static fn (array $image): bool => $image['src'] !== '',
        ));
        if ($images === []) {
            continue;
        }

        $title = (string) $event['title'];
        $cover = ltrim((string) ($event['cover_path'] ?: $images[0]['src']), '/');
        $coverAlt = $title;
        foreach ($images as $image) {
            if ($image['src'] === $cover) {
                $coverAlt = $image['alt'] ?: $title;
                break;
            }
        }
        $index = count($payload);
        $imageCount = count($images);
        $payload[] = [
            'id' => (int) $event['id'],
            'title' => $title,
            'caption' => (string) $event['caption'],
            'cover' => $cover,
            'images' => $images,
        ];
        $cards .= '<button type="button" class="events-collection-card" data-event-index="' . $index
            . '" aria-label="Open ' . e($title) . ' gallery"><span class="events-card-media">'
            . '<img src="' . e($cover) . '" alt="' . e($coverAlt) . '" loading="lazy" decoding="async" /></span>'
            . '<span class="events-card-overlay"></span><span class="events-card-content">'
            . '<strong class="events-card-title">' . public_event_title_html($title) . '</strong>'
            . '<span class="events-card-meta">' . $imageCount . ' ' . ($imageCount === 1 ? 'image' : 'images') . '</span>'
            . '</span></button>';
    }
    $json = json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $data = '<script type="application/json" id="events-data">' . $json . '</script>';
    $html = str_replace('<script src="events.js"></script>', $data . "\n  <script src=\"events.js\"></script>", $html);

    $grid = $payload === []
        ? '<div class="events-empty-state"><p>No event galleries are published yet.</p></div>'
        : $cards;

    return public_replace_element_inner($html, 'div', 'id', 'events-collections-grid', $grid);
}

function public_event_title_html(string $title): string
{
    $escaped = e($title);

    return preg_replace_callback(
        '/\b(?:AI|STEM|COMSATS)\b/i',
        static fn (array $match): string => '<span class="events-acronym">' . strtoupper($match[0]) . '</span>',
        $escaped,
    ) ?? $escaped;
}

function public_render_contact(string $html): string
{
    $contactSubmissionId = public_uuid();
    $meetingSubmissionId = public_uuid();
    $contactForm = '<form class="contact-form-card" action="/api/contact.php" method="post">'
        . '<h2 class="contact-form-title">Tell us a bit about yourself</h2>'
        . '<input type="hidden" name="submission_id" value="' . e($contactSubmissionId) . '" />'
        . '<div class="honeypot-field" aria-hidden="true" style="display: none !important;"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" /></label></div>'
        . '<div class="contact-form-row"><div class="contact-input-group" style="grid-column: span 2;">'
        . '<label for="fullName">Full Name</label><input type="text" id="fullName" name="full_name" class="contact-input" placeholder="Jackson Ethan" autocomplete="name" minlength="2" maxlength="100" required />'
        . '</div></div><div class="contact-form-row"><div class="contact-input-group"><label for="emailAddr">Email Address</label>'
        . '<input type="email" id="emailAddr" name="email" class="contact-input" placeholder="Type your mail address" autocomplete="email" maxlength="254" required /></div>'
        . '<div class="contact-input-group"><label for="phoneNum">Phone</label><input type="tel" id="phoneNum" name="phone" class="contact-input" placeholder="+92" autocomplete="tel" minlength="7" maxlength="32" /></div></div>'
        . '<div class="contact-input-group"><label for="serviceReq">Required Service</label><select id="serviceReq" name="service" class="contact-select" required>'
        . '<option value="" disabled selected>Select your required services</option><option value="software">Software Development</option>'
        . '<option value="ai">AI Consulting / Integration</option><option value="startup">Startup Advisory</option><option value="other">Other Inquiry</option></select></div>'
        . '<div class="contact-input-group"><label for="messageText">Messages</label><textarea id="messageText" name="message" class="contact-textarea" placeholder="Type a message" minlength="10" maxlength="5000" required></textarea></div>'
        . '<div class="turnstile-widget" data-action="contact" data-turnstile-action="contact"></div><p class="form-status" data-form-status aria-live="polite"></p>'
        . '<div class="contact-form-actions"><button type="submit" class="contact-submit-btn">Contact Now '
        . '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></button>'
        . '<button type="button" class="contact-submit-btn contact-secondary-btn" id="open-meeting-modal">Schedule a meeting '
        . '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></button></div></form>';
    $html = public_replace_element($html, 'form', 'class', 'contact-form-card', $contactForm);

    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Karachi'));
    $minDate = $now->format('Y-m-d');
    $maxDate = $now->modify('+90 days')->format('Y-m-d');
    $meeting = '<div class="meeting-modal" id="meeting-modal" aria-hidden="true"><div class="meeting-modal-backdrop" data-close-meeting-modal></div>'
        . '<div class="meeting-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="meeting-modal-title">'
        . '<button type="button" class="meeting-modal-close" id="close-meeting-modal" aria-label="Close schedule a meeting dialog">&times;</button>'
        . '<div class="meeting-modal-header"><p class="meeting-modal-eyebrow">Schedule a meeting</p><h2 class="meeting-modal-title" id="meeting-modal-title">Request a date and time</h2>'
        . '<p class="meeting-modal-subtitle">Choose any weekday in PKT. I will call to confirm before approving the final time.</p></div>'
        . '<form class="meeting-form" action="/api/meeting.php" method="post"><input type="hidden" name="submission_id" value="' . e($meetingSubmissionId) . '" />'
        . '<div class="honeypot-field" aria-hidden="true" style="display: none !important;"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" /></label></div>'
        . '<div class="meeting-scheduler">'
        . '<div class="meeting-calendar-panel">'
        . '<div class="meeting-calendar-header"><button type="button" class="meeting-calendar-nav" id="calendar-prev" aria-label="Previous month">&#8249;</button>'
        . '<span class="meeting-calendar-month" id="calendar-month-year"></span>'
        . '<button type="button" class="meeting-calendar-nav" id="calendar-next" aria-label="Next month">&#8250;</button></div>'
        . '<div class="meeting-weekdays"><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span><span>Su</span></div>'
        . '<div class="meeting-calendar-grid" id="calendar-days-grid"></div></div>'
        . '<div class="meeting-slots-panel">'
        . '<div class="contact-input-group"><label for="meeting-full-name">Full Name</label><input class="contact-input" id="meeting-full-name" name="full_name" type="text" autocomplete="name" minlength="2" maxlength="100" required /></div>'
        . '<div class="contact-input-group"><label for="meeting-email">Email Address</label><input class="contact-input" id="meeting-email" name="email" type="email" autocomplete="email" maxlength="254" required /></div>'
        . '<div class="contact-input-group"><label for="meeting-phone">Phone</label><input class="contact-input" id="meeting-phone" name="phone" type="tel" autocomplete="tel" minlength="7" maxlength="32" required /></div>'
        . '<div class="contact-input-group"><label>Selected Date</label><input class="contact-input" id="meeting-date-display" type="text" readonly placeholder="Click a date on the calendar" required style="cursor: default;" />'
        . '<input type="hidden" id="meeting-date" name="date" required /></div>'
        . '<div class="contact-input-group"><label for="meeting-time">Preferred time (PKT)</label><select class="contact-select" id="meeting-time" name="time" required>'
        . '<option value="" disabled selected>Select a time slot</option>'
        . '<option value="09:00">9:00 AM</option>'
        . '<option value="09:30">9:30 AM</option>'
        . '<option value="10:00">10:00 AM</option>'
        . '<option value="10:30">10:30 AM</option>'
        . '<option value="11:00">11:00 AM</option>'
        . '<option value="11:30">11:30 AM</option>'
        . '<option value="12:00">12:00 PM</option>'
        . '<option value="12:30">12:30 PM</option>'
        . '<option value="13:00">1:00 PM</option>'
        . '<option value="13:30">1:30 PM</option>'
        . '<option value="14:00">2:00 PM</option>'
        . '<option value="14:30">2:30 PM</option>'
        . '<option value="15:00">3:00 PM</option>'
        . '<option value="15:30">3:30 PM</option>'
        . '<option value="16:00">4:00 PM</option>'
        . '<option value="16:30">4:30 PM</option>'
        . '<option value="17:00">5:00 PM</option>'
        . '</select></div>'
        . '<div class="turnstile-widget" data-action="meeting" data-turnstile-action="meeting"></div></div></div>'
        . '<div class="meeting-modal-footer"><p class="meeting-summary form-status" id="meeting-summary" data-form-status aria-live="polite">Select a weekday and time for your request.</p>'
        . '<button type="submit" class="contact-submit-btn meeting-confirm-btn" id="confirm-meeting">Request meeting</button></div></form></div></div>';
    $html = public_replace_element($html, 'div', 'id', 'meeting-modal', $meeting);

    if (!is_file(dirname(__DIR__) . '/my_cv.pdf')) {
        $html = (string) preg_replace('~<a\b(?=[^>]*\bhref=["\']my_cv\.pdf["\'])[^>]*>.*?</a>~is', '', $html, 1);
    }

    return $html;
}

function public_uuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);

    return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4)
        . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
}
