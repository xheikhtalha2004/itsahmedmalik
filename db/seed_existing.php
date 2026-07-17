<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/content_html.php';

$root = dirname(__DIR__);
if (in_array('--verify-source', $argv ?? [], true)) {
    seed_verify_source($root);
    exit;
}

$pdo = db();
$now = gmdate('Y-m-d H:i:s');

$pdo->beginTransaction();
try {
    $firstSeed = !seed_marker_exists($pdo);
    if (!$firstSeed) {
        $pdo->commit();
        fwrite(STDOUT, "Legacy portfolio seed was already applied; no rows were changed.\n");
        exit;
    }
    seed_blogs($pdo, $root, $now);
    seed_certifications($pdo, $root, $now);
    seed_events($pdo, $root, $now);
    seed_profile_collections($pdo, $root, $now);
    seed_verify_parity($pdo);
    $marker = $pdo->prepare('INSERT INTO schema_migrations (version, applied_at) VALUES (:version, UTC_TIMESTAMP())');
    $marker->execute(['version' => seed_marker_version()]);
    $pdo->commit();
    fwrite(STDOUT, "Seeded legacy portfolio content without overwriting existing rows.\n");
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Seed failed: {$exception->getMessage()}\n");
    exit(1);
}

function seed_blogs(PDO $pdo, string $root, string $now): void
{
    for ($id = 1; $id <= 10; ++$id) {
        $document = seed_document(seed_fixture_path($root, "blog-{$id}.html"));
        $xpath = new DOMXPath($document);
        $title = seed_text($xpath, '(//*[contains(concat(" ", normalize-space(@class), " "), " blog-article-title ")])[1]');
        $meta = seed_text($xpath, '(//*[contains(concat(" ", normalize-space(@class), " "), " blog-article-meta ")])[1]');
        $author = trim((string) preg_replace('/^.*Written by\s+/u', '', $meta)) ?: 'Ahmed Malik';
        $dateText = trim((string) preg_replace('/\s+(?:—|&mdash;).*$/u', '', $meta));
        $published = new DateTimeImmutable($dateText ?: 'now');
        $image = seed_node($xpath, '(//img[contains(concat(" ", normalize-space(@class), " "), " blog-article-image ")])[1]');
        $coverPath = $image instanceof DOMElement ? $image->getAttribute('src') : "images/blog_post_{$id}.png";
        $coverAlt = $image instanceof DOMElement ? $image->getAttribute('alt') : $title;
        $coverId = seed_media($pdo, $root, $coverPath, $now);
        $body = seed_node($xpath, '(//*[contains(concat(" ", normalize-space(@class), " "), " blog-article-content ")])[1]');
        $bodyHtml = sanitize_article_html($body ? seed_inner_html($body) : '');
        $firstParagraph = seed_text($xpath, '(//*[contains(concat(" ", normalize-space(@class), " "), " blog-article-content ")]//p)[1]');
        $descriptionNode = seed_node($xpath, '(//meta[@name="description"])[1]');
        $description = $descriptionNode instanceof DOMElement ? $descriptionNode->getAttribute('content') : $firstParagraph;

        seed_preserve($pdo, 'blog_posts', [
            'id' => $id,
            'title' => $title,
            'author' => $author,
            'excerpt' => seed_clip($firstParagraph, 500),
            'meta_description' => seed_clip($description, 320),
            'published_on' => $published->format('Y-m-d'),
            'cover_media_id' => $coverId,
            'cover_alt' => $coverAlt,
            'body_html' => $bodyHtml,
            'publication_status' => 'published',
            'sort_order' => $id * 10,
            'published_at' => $published->setTime(0, 0)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'archived_at' => null,
            'version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

function seed_certifications(PDO $pdo, string $root, string $now): void
{
    $document = seed_document(seed_fixture_path($root, 'certifications.html'));
    $xpath = new DOMXPath($document);
    $cards = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " cert-focus-card ")]');
    if (!$cards) {
        return;
    }
    foreach ($cards as $index => $card) {
        if (!$card instanceof DOMElement) {
            continue;
        }
        $image = $xpath->query('.//img[1]', $card)?->item(0);
        $path = $card->getAttribute('data-cert-image');
        $alt = $image instanceof DOMElement ? $image->getAttribute('alt') : $card->getAttribute('data-cert-title');
        seed_preserve($pdo, 'certifications', [
            'id' => $index + 1,
            'title' => $card->getAttribute('data-cert-title'),
            'description' => $card->getAttribute('data-cert-desc'),
            'media_id' => seed_media($pdo, $root, $path, $now),
            'image_alt' => $alt,
            'publication_status' => 'published',
            'sort_order' => ($index + 1) * 10,
            'published_at' => $now,
            'archived_at' => null,
            'version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

/**
 * Events are intentionally captured from the image folders, not events.js.
 * The legacy controller can therefore be replaced before this seed runs.
 */
function seed_events(PDO $pdo, string $root, string $now): void
{
    $covers = [
        'comsats_university_islamabad_visit' => 'WhatsApp Image 2026-03-30 at 10.44.59 PM.jpeg',
        'ed_tech_future_forum_x_ai_summit' => 'WhatsApp Image 2026-03-30 at 10.46.40 PM.jpeg',
        'indus_ai_week' => 'WhatsApp Image 2026-03-30 at 10.47.35 PM (1).jpeg',
        'industrial_visit_from_comsats_university' => 'WhatsApp Image 2026-03-30 at 10.46.30 PM.jpeg',
        'innovate_4.0' => 'WhatsApp Image 2026-03-30 at 10.47.02 PM (1).jpeg',
        'job_fair_at_iqra_university' => 'WhatsApp Image 2026-03-30 at 10.46.35 PM.jpeg',
        'nature_with_friends' => 'WhatsApp Image 2026-03-30 at 10.55.19 PM (1).jpeg',
        'open_house_and_job_fair_at_iqra_university_islamabad' => 'WhatsApp Image 2026-03-30 at 10.22.26 PM (1).jpeg',
        'paklaunch' => 'WhatsApp Image 2026-03-30 at 10.45.42 PM.jpeg',
        'startup.exe_by_design_peeps' => 'WhatsApp Image 2026-03-30 at 10.42.52 PM.jpeg',
        'synergy_x_metaverse_deviser' => 'WhatsApp Image 2026-03-30 at 10.46.29 PM.jpeg',
        'team_medieval_empires' => 'WhatsApp Image 2026-03-30 at 10.53.37 PM (1).jpeg',
        'tik_tok_stem_feed' => 'WhatsApp Image 2026-03-30 at 10.43.33 PM (1).jpeg',
    ];
    $titles = [
        'comsats_university_islamabad_visit' => 'COMSATS University Islamabad Visit',
        'ed_tech_future_forum_x_ai_summit' => 'Ed Tech Future Forum x AI Summit',
        'indus_ai_week' => 'Indus AI Week',
        'industrial_visit_from_comsats_university' => 'Industrial Visit from COMSATS University',
        'innovate_4.0' => 'Innovate 4.0',
        'job_fair_at_iqra_university' => 'Job Fair at Iqra University',
        'nature_with_friends' => 'Nature with Friends',
        'open_house_and_job_fair_at_iqra_university_islamabad' => 'Open House and Job Fair at Iqra University Islamabad',
        'paklaunch' => 'Paklaunch',
        'startup.exe_by_design_peeps' => 'Startup.exe by Design Peeps',
        'synergy_x_metaverse_deviser' => 'Synergy x Metaverse Deviser',
        'team_medieval_empires' => 'Team Medieval Empires',
        'tik_tok_stem_feed' => 'Tik Tok STEM Feed',
    ];

    $eventId = 0;
    $imageTotal = 0;
    foreach ($covers as $slug => $coverFilename) {
        ++$eventId;
        $directory = "{$root}/images/events/{$slug}";
        $files = array_values(array_filter(
            glob($directory . '/*') ?: [],
            static fn (string $file): bool => is_file($file) && preg_match('/\.(?:jpe?g|png|webp)$/i', $file) === 1
        ));
        natsort($files);
        $files = array_values($files);
        $imageTotal += count($files);
        $title = $titles[$slug];
        $coverPath = "images/events/{$slug}/{$coverFilename}";
        $coverId = seed_media($pdo, $root, $coverPath, $now);
        seed_preserve($pdo, 'events', [
            'id' => $eventId,
            'title' => $title,
            'caption' => count($files) . ' images in this gallery.',
            'cover_media_id' => $coverId,
            'publication_status' => 'published',
            'sort_order' => $eventId * 10,
            'published_at' => $now,
            'archived_at' => null,
            'version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($files as $imageIndex => $file) {
            $relativePath = str_replace('\\', '/', substr($file, strlen($root) + 1));
            seed_preserve($pdo, 'event_images', [
                'id' => ($eventId * 1000) + $imageIndex + 1,
                'event_id' => $eventId,
                'media_id' => seed_media($pdo, $root, $relativePath, $now),
                'caption' => '',
                'alt_text' => $title . ' image ' . ($imageIndex + 1),
                'sort_order' => ($imageIndex + 1) * 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
    if ($eventId !== 13 || $imageTotal !== 102) {
        throw new RuntimeException("Legacy event snapshot changed; expected 13 galleries/102 images, found {$eventId}/{$imageTotal}.");
    }
}

function seed_profile_collections(PDO $pdo, string $root, string $now): void
{
    $indexDocument = seed_document(seed_fixture_path($root, 'index.html'));
    $indexXpath = new DOMXPath($indexDocument);
    seed_education($pdo, $indexXpath, $now);
    seed_experiences($pdo, $indexXpath, $now);
    seed_testimonials($pdo, $indexXpath, $now);

    $workDocument = seed_document(seed_fixture_path($root, 'work.html'));
    seed_projects($pdo, new DOMXPath($workDocument), $now);
}

function seed_education(PDO $pdo, DOMXPath $xpath, string $now): void
{
    $entries = $xpath->query('//*[@id="experience"]//*[contains(concat(" ", normalize-space(@class), " "), " edu-branch ")]');
    if (!$entries) {
        return;
    }
    foreach ($entries as $index => $entry) {
        seed_preserve($pdo, 'education_entries', array_merge(seed_publication_fields($index + 1, $now), [
            'degree' => seed_relative_text($xpath, $entry, './/*[contains(concat(" ", normalize-space(@class), " "), " edu-degree ")][1]'),
            'institution' => seed_relative_text($xpath, $entry, './/*[contains(concat(" ", normalize-space(@class), " "), " edu-institution ")][1]'),
            'label' => seed_relative_text($xpath, $entry, './/*[contains(concat(" ", normalize-space(@class), " "), " edu-tag ")][1]'),
        ]));
    }
}

function seed_experiences(PDO $pdo, DOMXPath $xpath, string $now): void
{
    $entries = $xpath->query('//*[@id="work-experience"]//*[contains(concat(" ", normalize-space(@class), " "), " exp-card ")]');
    if (!$entries) {
        return;
    }
    foreach ($entries as $index => $entry) {
        $companyNode = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " exp-company ")][1]', $entry)?->item(0);
        $link = $companyNode ? $xpath->query('.//a[1]', $companyNode)?->item(0) : null;
        $tags = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " exp-tag ")]', $entry);
        $rect = $xpath->query('.//svg//rect[1]', $entry)?->item(0);
        $text = $xpath->query('.//svg//text[last()]', $entry)?->item(0);
        $background = $rect instanceof DOMElement ? strtolower($rect->getAttribute('fill')) : '';
        seed_preserve($pdo, 'work_experiences', array_merge(seed_publication_fields($index + 1, $now), [
            'role_title' => seed_relative_text($xpath, $entry, './/*[contains(concat(" ", normalize-space(@class), " "), " exp-role ")][1]'),
            'company' => $companyNode ? trim((string) preg_replace('/\s*↗\s*$/u', '', trim($companyNode->textContent))) : '',
            'company_url' => $link instanceof DOMElement ? $link->getAttribute('href') : null,
            'tenure_label' => $tags?->item(0)?->textContent ? trim($tags->item(0)->textContent) : '',
            'category_label' => $tags?->item(1)?->textContent ? trim($tags->item(1)->textContent) : '',
            'icon_text' => $text ? trim($text->textContent) : '',
            'color_preset' => seed_experience_preset($background),
        ]));
    }
}

function seed_projects(PDO $pdo, DOMXPath $xpath, string $now): void
{
    $projects = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " dashboard-project-slide ")]');
    if (!$projects) {
        return;
    }
    foreach ($projects as $index => $project) {
        $id = $index + 1;
        $chip = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " dashboard-chip ")][1]', $project)?->item(0);
        $chipClass = $chip instanceof DOMElement ? $chip->getAttribute('class') : '';
        $metaValues = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " dashboard-meta-item ")]//strong', $project);
        $badges = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " dashboard-member-badge ")]', $project);
        $memberNames = array_map('trim', explode(',', $metaValues?->item(1)?->textContent ?? ''));
        seed_preserve($pdo, 'projects', array_merge(seed_publication_fields($id, $now), [
            'name' => seed_relative_text($xpath, $project, './/*[contains(concat(" ", normalize-space(@class), " "), " dashboard-project-name ")][1]'),
            'description' => seed_relative_text($xpath, $project, './/*[contains(concat(" ", normalize-space(@class), " "), " dashboard-project-description ")][1]'),
            'project_status_label' => $chip ? trim($chip->textContent) : '',
            'tone_preset' => str_contains($chipClass, 'is-positive') ? 'positive' : (str_contains($chipClass, 'is-warning') ? 'warning' : 'neutral'),
            'progress_percent' => (int) seed_relative_text($xpath, $project, './/*[contains(concat(" ", normalize-space(@class), " "), " dashboard-project-percent ")][1]'),
            'deadline_label' => trim($metaValues?->item(0)?->textContent ?? ''),
            'milestone' => trim($metaValues?->item(2)?->textContent ?? ''),
        ]));
        foreach ($memberNames as $memberIndex => $name) {
            if ($name === '') {
                continue;
            }
            seed_preserve($pdo, 'project_members', [
                'id' => ($id * 100) + $memberIndex + 1,
                'project_id' => $id,
                'name' => $name,
                'initials' => trim($badges?->item($memberIndex)?->textContent ?? seed_initials($name)),
                'media_id' => null,
                'sort_order' => ($memberIndex + 1) * 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}

function seed_testimonials(PDO $pdo, DOMXPath $xpath, string $now): void
{
    $entries = $xpath->query('//*[@id="testimonials"]//*[contains(concat(" ", normalize-space(@class), " "), " testimonial-card ")]');
    if (!$entries) {
        return;
    }
    foreach ($entries as $index => $entry) {
        $avatar = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " author-avatar ")][1]', $entry)?->item(0);
        $stars = seed_relative_text($xpath, $entry, './/*[contains(concat(" ", normalize-space(@class), " "), " testimonial-stars ")][1]');
        seed_preserve($pdo, 'testimonials', array_merge(seed_publication_fields($index + 1, $now), [
            'quote_text' => seed_relative_text($xpath, $entry, './/*[contains(concat(" ", normalize-space(@class), " "), " testimonial-text ")][1]'),
            'rating' => max(1, substr_count($stars, '★')),
            'author' => seed_relative_text($xpath, $entry, './/*[contains(concat(" ", normalize-space(@class), " "), " author-name ")][1]'),
            'role_title' => seed_relative_text($xpath, $entry, './/*[contains(concat(" ", normalize-space(@class), " "), " author-role ")][1]'),
            'initials' => $avatar ? trim($avatar->textContent) : '',
            'gradient_preset' => seed_gradient_preset($avatar instanceof DOMElement ? $avatar->getAttribute('style') : ''),
        ]));
    }
}

function seed_publication_fields(int $id, string $now): array
{
    return [
        'id' => $id,
        'publication_status' => 'published',
        'sort_order' => $id * 10,
        'published_at' => $now,
        'archived_at' => null,
        'version' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ];
}

function seed_media(PDO $pdo, string $root, string $publicPath, string $now): int
{
    $publicPath = ltrim(str_replace('\\', '/', $publicPath), '/');
    $absolute = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $publicPath);
    if (!is_file($absolute)) {
        throw new RuntimeException("Legacy media is missing: {$publicPath}");
    }
    $imageInfo = getimagesize($absolute);
    if ($imageInfo === false) {
        throw new RuntimeException("Legacy media is not a readable image: {$publicPath}");
    }
    $mime = $imageInfo['mime'] ?? 'application/octet-stream';
    $statement = $pdo->prepare(
        'INSERT INTO cms_media (public_path, mime_type, width, height, size_bytes, created_at, archived_at, version)
         VALUES (:public_path, :mime_type, :width, :height, :size_bytes, :created_at, NULL, 1)
         ON DUPLICATE KEY UPDATE public_path = VALUES(public_path)'
    );
    $statement->execute([
        'public_path' => $publicPath,
        'mime_type' => $mime,
        'width' => (int) $imageInfo[0],
        'height' => (int) $imageInfo[1],
        'size_bytes' => filesize($absolute),
        'created_at' => $now,
    ]);
    $lookup = $pdo->prepare('SELECT id FROM cms_media WHERE public_path = :path LIMIT 1');
    $lookup->execute(['path' => $publicPath]);
    $id = $lookup->fetchColumn();
    if ($id === false) {
        throw new RuntimeException("Could not register media: {$publicPath}");
    }

    $mediaId = (int) $id;
    $GLOBALS['seed_expected_media'][$mediaId] = [
        'public_path' => $publicPath,
        'mime_type' => $mime,
        'width' => (int) $imageInfo[0],
        'height' => (int) $imageInfo[1],
        'size_bytes' => (int) filesize($absolute),
        'archived_at' => null,
        'version' => 1,
    ];

    return $mediaId;
}

function seed_preserve(PDO $pdo, string $table, array $row): void
{
    $allowed = [
        'blog_posts', 'certifications', 'events', 'event_images', 'education_entries',
        'work_experiences', 'projects', 'project_members', 'testimonials',
    ];
    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Unsupported seed table.');
    }
    if (in_array($table, ['event_images', 'project_members'], true) && !array_key_exists('version', $row)) {
        $row['version'] = 1;
    }
    $GLOBALS['seed_expected'][$table][(int) $row['id']] = $row;
    $columns = array_keys($row);
    $quoted = implode(', ', array_map(static fn (string $column): string => "`{$column}`", $columns));
    $values = implode(', ', array_map(static fn (string $column): string => ":{$column}", $columns));
    $statement = $pdo->prepare(
        "INSERT INTO `{$table}` ({$quoted}) VALUES ({$values}) ON DUPLICATE KEY UPDATE id = id"
    );
    $statement->execute($row);
}

function seed_verify(PDO $pdo): void
{
    $expected = [
        'blog_posts' => [10, 'id BETWEEN 1 AND 10'],
        'certifications' => [8, 'id BETWEEN 1 AND 8'],
        'events' => [13, 'id BETWEEN 1 AND 13'],
        'event_images' => [102, 'event_id BETWEEN 1 AND 13'],
        'education_entries' => [4, 'id BETWEEN 1 AND 4'],
        'work_experiences' => [7, 'id BETWEEN 1 AND 7'],
        'projects' => [4, 'id BETWEEN 1 AND 4'],
        'project_members' => [12, 'project_id BETWEEN 1 AND 4'],
        'testimonials' => [5, 'id BETWEEN 1 AND 5'],
    ];
    foreach ($expected as $table => [$minimum, $where]) {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}` WHERE {$where}")->fetchColumn();
        if ($count < $minimum) {
            throw new RuntimeException("Seed verification failed for {$table}: expected at least {$minimum}, found {$count}.");
        }
    }
}

function seed_verify_parity(PDO $pdo): void
{
    foreach ($GLOBALS['seed_expected'] ?? [] as $table => $rows) {
        foreach ($rows as $id => $expected) {
            $statement = $pdo->prepare("SELECT * FROM `{$table}` WHERE id = :id LIMIT 1");
            $statement->execute(['id' => $id]);
            $actual = $statement->fetch();
            if (!is_array($actual)) {
                throw new RuntimeException("Seed parity failed: {$table}#{$id} is missing.");
            }
            seed_assert_fields($table, $id, $expected, $actual);
        }
    }
    foreach ($GLOBALS['seed_expected_media'] ?? [] as $id => $expected) {
        $statement = $pdo->prepare('SELECT * FROM cms_media WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $actual = $statement->fetch();
        if (!is_array($actual)) {
            throw new RuntimeException("Seed parity failed: cms_media#{$id} is missing.");
        }
        seed_assert_fields('cms_media', (int) $id, $expected, $actual);
    }
    seed_verify($pdo);
}

function seed_assert_fields(string $table, int $id, array $expected, array $actual): void
{
    foreach ($expected as $field => $value) {
        $actualValue = $actual[$field] ?? null;
        $matches = $value === null ? $actualValue === null : (string) $actualValue === (string) $value;
        if (!$matches) {
            throw new RuntimeException("Seed parity failed for {$table}#{$id}.{$field}.");
        }
    }
}

function seed_marker_exists(PDO $pdo): bool
{
    $statement = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version LIMIT 1');
    $statement->execute(['version' => seed_marker_version()]);

    return $statement->fetchColumn() !== false;
}

function seed_marker_version(): string
{
    return 'seed:legacy_content_v1';
}

function seed_verify_source(string $root): void
{
    $blogCount = 0;
    for ($id = 1; $id <= 10; ++$id) {
        $xpath = new DOMXPath(seed_document(seed_fixture_path($root, "blog-{$id}.html")));
        if (seed_text($xpath, '(//*[contains(concat(" ", normalize-space(@class), " "), " blog-article-title ")])[1]') !== '') {
            ++$blogCount;
        }
    }
    $certXpath = new DOMXPath(seed_document(seed_fixture_path($root, 'certifications.html')));
    $indexXpath = new DOMXPath(seed_document(seed_fixture_path($root, 'index.html')));
    $workXpath = new DOMXPath(seed_document(seed_fixture_path($root, 'work.html')));
    $eventDirectories = glob("{$root}/images/events/*", GLOB_ONLYDIR) ?: [];
    $eventImageCount = 0;
    foreach ($eventDirectories as $directory) {
        foreach (glob($directory . '/*') ?: [] as $file) {
            if (is_file($file) && preg_match('/\.(?:jpe?g|png|webp)$/i', $file) === 1) {
                ++$eventImageCount;
            }
        }
    }

    $counts = [
        'blogs' => $blogCount,
        'certifications' => $certXpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " cert-focus-card ")]')?->length ?? 0,
        'events' => count($eventDirectories),
        'event_images' => $eventImageCount,
        'education' => $indexXpath->query('//*[@id="experience"]//*[contains(concat(" ", normalize-space(@class), " "), " edu-branch ")]')?->length ?? 0,
        'experiences' => $indexXpath->query('//*[@id="work-experience"]//*[contains(concat(" ", normalize-space(@class), " "), " exp-card ")]')?->length ?? 0,
        'projects' => $workXpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " dashboard-project-slide ")]')?->length ?? 0,
        'project_members' => $workXpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " dashboard-member-badge ")]')?->length ?? 0,
        'testimonials' => $indexXpath->query('//*[@id="testimonials"]//*[contains(concat(" ", normalize-space(@class), " "), " testimonial-card ")]')?->length ?? 0,
    ];
    $expected = ['blogs' => 10, 'certifications' => 8, 'events' => 13, 'event_images' => 102, 'education' => 4, 'experiences' => 7, 'projects' => 4, 'project_members' => 12, 'testimonials' => 5];
    foreach ($expected as $name => $expectedCount) {
        if ($counts[$name] !== $expectedCount) {
            throw new RuntimeException("Legacy source verification failed for {$name}: expected {$expectedCount}, found {$counts[$name]}.");
        }
        fwrite(STDOUT, "{$name}: {$counts[$name]}\n");
    }
}

function seed_fixture_path(string $root, string $name): string
{
    if (preg_match('/\A(?:index|work|certifications|blog-(?:[1-9]|10))\.html\z/', $name) !== 1) {
        throw new InvalidArgumentException('Invalid legacy seed fixture name.');
    }

    return $root . '/db/seeds/legacy/' . $name;
}

function seed_document(string $path): DOMDocument
{
    $html = file_get_contents($path);
    if ($html === false) {
        throw new RuntimeException("Legacy HTML is missing: {$path}");
    }
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    return $document;
}

function seed_node(DOMXPath $xpath, string $query): ?DOMNode
{
    $nodes = $xpath->query($query);

    return $nodes && $nodes->length > 0 ? $nodes->item(0) : null;
}

function seed_text(DOMXPath $xpath, string $query): string
{
    return trim(seed_node($xpath, $query)?->textContent ?? '');
}

function seed_relative_text(DOMXPath $xpath, DOMNode $context, string $query): string
{
    $nodes = $xpath->query($query, $context);

    return trim($nodes && $nodes->length > 0 ? $nodes->item(0)->textContent : '');
}

function seed_inner_html(DOMNode $node): string
{
    $html = '';
    foreach ($node->childNodes as $child) {
        $html .= $node->ownerDocument?->saveHTML($child) ?? '';
    }

    return $html;
}

function seed_experience_preset(string $background): string
{
    return match ($background) {
        '#1a1a2e' => 'red',
        '#0f3460' => 'navy',
        '#6c3483' => 'purple',
        '#1b4332' => 'green',
        '#112d4e' => 'blue',
        '#003049' => 'gold',
        default => 'cyan',
    };
}

function seed_gradient_preset(string $style): string
{
    return match (true) {
        str_contains($style, '#11998e') => 'emerald',
        str_contains($style, '#f093fb') => 'rose',
        str_contains($style, '#4facfe') => 'cyan',
        str_contains($style, '#fa709a') => 'sunset',
        default => 'violet',
    };
}

function seed_initials(string $name): string
{
    $words = preg_split('/\s+/', trim($name)) ?: [];
    return strtoupper(implode('', array_map(static fn (string $word): string => substr($word, 0, 1), array_slice($words, 0, 2))));
}

function seed_clip(string $value, int $length): string
{
    return mb_strlen($value, 'UTF-8') <= $length ? $value : rtrim(mb_substr($value, 0, $length - 1, 'UTF-8')) . '…';
}
