<?php

declare(strict_types=1);

require_once __DIR__ . '/content_html.php';

function admin_start(): void
{
    admin_security_headers();
    $nonce = admin_csp_nonce();
    header("Content-Security-Policy: default-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'; img-src 'self' data:; object-src 'none'; script-src 'self'; style-src 'self' 'nonce-{$nonce}'");
    require_admin_auth();
    start_admin_session();
    csrf_token();
}

function admin_csp_nonce(): string
{
    static $nonce;
    return $nonce ??= rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
}

function admin_csrf_token(): string
{
    return csrf_token();
}

function admin_verify_post(): void
{
    require_admin_post();
}

function admin_flash(string $kind, string $message): void
{
    $_SESSION['flash'] = ['kind' => $kind, 'message' => $message];
}

/** @return array{kind:string,message:string}|null */
function admin_take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

/**
 * The table and column names are code-owned, never accepted from a request.
 *
 * @return array<string,array<string,mixed>>
 */
function admin_modules(): array
{
    static $modules;
    if (isset($modules)) {
        return $modules;
    }

    $text = static fn (string $label, int $max, bool $required = true): array => [
        'label' => $label, 'type' => 'text', 'max' => $max, 'required' => $required,
    ];
    $textarea = static fn (string $label, int $max, bool $required = true): array => [
        'label' => $label, 'type' => 'textarea', 'max' => $max, 'required' => $required,
    ];
    $select = static fn (string $label, array $options): array => [
        'label' => $label, 'type' => 'select', 'options' => $options, 'required' => true,
    ];

    $modules = [
        'blogs' => [
            'label' => 'Blogs', 'singular' => 'Blog post', 'table' => 'blog_posts',
            'title' => 'title', 'preview' => true,
            'fields' => [
                'title' => $text('Title', 180),
                'author' => $text('Author', 100),
                'excerpt' => $textarea('Excerpt', 500),
                'meta_description' => $textarea('Meta description', 320),
                'published_on' => ['label' => 'Publication date', 'type' => 'date', 'required' => true],
                'cover_alt' => $text('Cover image alt text', 200),
                'body_html' => ['label' => 'Article body', 'type' => 'rich', 'required' => true, 'max' => 100_000],
            ],
            'media' => ['input' => 'cover_image', 'column' => 'cover_media_id', 'label' => 'Cover image'],
        ],
        'certifications' => [
            'label' => 'Certifications', 'singular' => 'Certification', 'table' => 'certifications',
            'title' => 'title', 'preview' => true,
            'fields' => [
                'title' => $text('Title', 180),
                'description' => $textarea('Description', 1_000),
                'image_alt' => $text('Image alt text', 200),
            ],
            'media' => ['input' => 'image', 'column' => 'media_id', 'label' => 'Certificate image'],
        ],
        'events' => [
            'label' => 'Events', 'singular' => 'Event', 'table' => 'events',
            'title' => 'title', 'preview' => true,
            'fields' => [
                'title' => $text('Title', 180),
                'caption' => $textarea('Caption', 1_000),
            ],
            'children' => 'event_images',
        ],
        'education' => [
            'label' => 'Education', 'singular' => 'Education entry', 'table' => 'education_entries',
            'title' => 'degree', 'preview' => true,
            'fields' => [
                'degree' => $text('Degree', 180),
                'institution' => $text('Institution', 180),
                'label' => $text('Label', 100),
            ],
        ],
        'experience' => [
            'label' => 'Experience', 'singular' => 'Work experience', 'table' => 'work_experiences',
            'title' => 'role_title', 'preview' => true,
            'fields' => [
                'role_title' => $text('Role', 180),
                'company' => $text('Company', 180),
                'company_url' => ['label' => 'Company URL', 'type' => 'url', 'max' => 500, 'required' => false],
                'tenure_label' => $text('Tenure label', 100),
                'category_label' => $text('Category label', 100),
                'icon_text' => $text('Icon text', 8),
                'color_preset' => $select('Color preset', [
                    'cyan' => 'Cyan', 'red' => 'Red', 'navy' => 'Navy', 'purple' => 'Purple',
                    'green' => 'Green', 'blue' => 'Blue', 'gold' => 'Gold',
                ]),
            ],
        ],
        'projects' => [
            'label' => 'Projects', 'singular' => 'Project', 'table' => 'projects',
            'title' => 'name', 'children' => 'project_members', 'preview' => true,
            'fields' => [
                'name' => $text('Name', 180),
                'description' => $textarea('Description', 2_000),
                'project_status_label' => $text('Status label', 80),
                'tone_preset' => $select('Tone preset', [
                    'neutral' => 'Neutral', 'positive' => 'Positive', 'warning' => 'Warning',
                ]),
                'progress_percent' => ['label' => 'Progress', 'type' => 'integer', 'min' => 0, 'max' => 100, 'required' => true],
                'deadline_label' => $text('Deadline label', 100, false),
                'milestone' => $text('Milestone', 180, false),
            ],
        ],
        'testimonials' => [
            'label' => 'Testimonials', 'singular' => 'Testimonial', 'table' => 'testimonials',
            'title' => 'author', 'preview' => true,
            'fields' => [
                'quote_text' => $textarea('Quote', 2_000),
                'rating' => ['label' => 'Rating', 'type' => 'integer', 'min' => 1, 'max' => 5, 'required' => true],
                'author' => $text('Author', 100),
                'role_title' => $text('Role', 180),
                'initials' => $text('Initials', 8),
                'gradient_preset' => $select('Gradient preset', [
                    'violet' => 'Violet', 'emerald' => 'Emerald', 'rose' => 'Rose', 'cyan' => 'Cyan', 'sunset' => 'Sunset',
                ]),
            ],
        ],
    ];

    return $modules;
}

/** @return array<string,mixed> */
function admin_module(string $key): array
{
    $module = admin_modules()[$key] ?? null;
    if (!is_array($module)) {
        throw new InvalidArgumentException('Unknown content module.');
    }
    return $module;
}

function admin_sanitize_html(string $html): string
{
    return sanitize_article_html($html);
}

function admin_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

/** @return array<string,mixed> */
function admin_validate_record(array $module, array $input): array
{
    $values = [];
    $errors = [];
    foreach ($module['fields'] as $column => $field) {
        $type = (string) $field['type'];
        $raw = $input[$column] ?? '';
        $value = is_string($raw) ? trim($raw) : '';
        $required = (bool) ($field['required'] ?? false);

        if ($value === '' && !$required) {
            $values[$column] = null;
            continue;
        }
        if ($value === '') {
            $errors[] = $field['label'] . ' is required.';
            continue;
        }

        if ($type === 'integer') {
            $number = filter_var($value, FILTER_VALIDATE_INT);
            if ($number === false || $number < $field['min'] || $number > $field['max']) {
                $errors[] = $field['label'] . ' must be between ' . $field['min'] . ' and ' . $field['max'] . '.';
            } else {
                $values[$column] = $number;
            }
            continue;
        }
        if ($type === 'date') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) {
                $errors[] = $field['label'] . ' must be a valid date.';
            } else {
                $values[$column] = $value;
            }
            continue;
        }
        if ($type === 'url') {
            $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
            if (!filter_var($value, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
                $errors[] = $field['label'] . ' must be an HTTP or HTTPS URL.';
            } else {
                $values[$column] = $value;
            }
            continue;
        }
        if ($type === 'select') {
            if (!array_key_exists($value, $field['options'])) {
                $errors[] = 'Choose a valid ' . strtolower($field['label']) . '.';
            } else {
                $values[$column] = $value;
            }
            continue;
        }
        if ($type === 'rich') {
            if (strlen($value) > 200_000) {
                $errors[] = $field['label'] . ' is too long.';
                continue;
            }
            $value = admin_sanitize_html($value);
            if (trim(strip_tags($value)) === '') {
                $errors[] = $field['label'] . ' cannot be empty.';
            } elseif (admin_length($value) > (int) $field['max']) {
                $errors[] = $field['label'] . ' is too long.';
            } else {
                $values[$column] = $value;
            }
            continue;
        }
        if (admin_length($value) > (int) $field['max']) {
            $errors[] = $field['label'] . ' is too long.';
        } else {
            $values[$column] = $value;
        }
    }

    if ($errors !== []) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }
    return $values;
}

/** @return list<array<string,mixed>> */
function admin_list_records(string $key): array
{
    $module = admin_module($key);
    $sql = 'SELECT id, ' . $module['title'] . ' AS display_title, publication_status, sort_order, version, updated_at
            FROM ' . $module['table'] . '
            ORDER BY publication_status = \'archived\', sort_order, id';
    $statement = db()->prepare($sql);
    $statement->execute();
    return $statement->fetchAll();
}

/** @return array<string,mixed>|null */
function admin_find_record(string $key, int $id): ?array
{
    $module = admin_module($key);
    $statement = db()->prepare(
        'SELECT r.*, m.public_path AS media_path
         FROM ' . $module['table'] . ' r
         LEFT JOIN cms_media m ON m.id = ' . (isset($module['media']['column']) ? 'r.' . $module['media']['column'] : 'NULL') . '
         WHERE r.id = :id'
    );
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return is_array($row) ? $row : null;
}

function admin_save_record(string $key, array $input, array $files): int
{
    $module = admin_module($key);
    $values = admin_validate_record($module, $input);
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    $version = filter_var($input['version'] ?? null, FILTER_VALIDATE_INT) ?: 0;

    $media = $module['media'] ?? null;
    if (is_array($media)) {
        $upload = $files[$media['input']] ?? null;
        if (is_array($upload) && (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $stored = admin_store_image($upload);
            $values[$media['column']] = $stored['id'];
        } elseif ($id === 0) {
            throw new InvalidArgumentException($media['label'] . ' is required.');
        }
    }

    if ($id === 0) {
        $columns = array_keys($values);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);
        $sql = 'INSERT INTO ' . $module['table'] . ' (' . implode(', ', $columns) . ', publication_status, sort_order, version, created_at, updated_at)
                SELECT ' . implode(', ', $placeholders) . ", 'draft', COALESCE(MAX(sort_order), 0) + 10, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP()
                FROM " . $module['table'];
        $statement = db()->prepare($sql);
        $statement->execute($values);
        return (int) db()->lastInsertId();
    }

    if ($version < 1) {
        throw new InvalidArgumentException('The record version is missing. Reload and try again.');
    }
    $assignments = [];
    foreach (array_keys($values) as $column) {
        $assignments[] = $column . ' = :' . $column;
    }
    $values['id'] = $id;
    $values['version'] = $version;
    $statement = db()->prepare(
        'UPDATE ' . $module['table'] . '
         SET ' . implode(', ', $assignments) . ', version = version + 1, updated_at = UTC_TIMESTAMP()
         WHERE id = :id AND version = :version'
    );
    $statement->execute($values);
    if ($statement->rowCount() !== 1) {
        throw new RuntimeException('This record changed in another tab. Reload it before saving again.');
    }
    return $id;
}

function admin_change_publication(string $key, int $id, int $version, string $target): void
{
    $module = admin_module($key);
    $states = [
        'publish' => ["'published'", 'COALESCE(published_at, UTC_TIMESTAMP())', 'NULL', "'draft'"],
        'unpublish' => ["'draft'", 'published_at', 'NULL', "'published'"],
        'archive' => ["'archived'", 'published_at', 'UTC_TIMESTAMP()', "'draft', 'published'"],
        'restore' => ["'draft'", 'published_at', 'NULL', "'archived'"],
    ];
    if (!isset($states[$target]) || $id < 1 || $version < 1) {
        throw new InvalidArgumentException('Invalid publication action.');
    }
    if ($target === 'publish') {
        $requirement = match ($key) {
            'blogs' => 'cover_media_id IS NOT NULL AND cover_alt <> \'\' AND body_html <> \'\'',
            'certifications' => 'media_id IS NOT NULL AND image_alt <> \'\'',
            'events' => 'cover_media_id IS NOT NULL AND EXISTS (SELECT 1 FROM event_images WHERE event_id = events.id AND archived_at IS NULL)',
            default => '1 = 1',
        };
        $ready = db()->prepare('SELECT 1 FROM ' . $module['table'] . ' WHERE id = :id AND ' . $requirement);
        $ready->execute(['id' => $id]);
        if ($ready->fetchColumn() === false) {
            throw new InvalidArgumentException('Complete all required content and media before publishing.');
        }
    }
    [$status, $publishedAt, $archivedAt, $allowedFrom] = $states[$target];
    $statement = db()->prepare(
        'UPDATE ' . $module['table'] . '
         SET publication_status = ' . $status . ', published_at = ' . $publishedAt . ', archived_at = ' . $archivedAt . ',
             version = version + 1, updated_at = UTC_TIMESTAMP()
         WHERE id = :id AND version = :version AND publication_status IN (' . $allowedFrom . ')'
    );
    $statement->execute(['id' => $id, 'version' => $version]);
    if ($statement->rowCount() !== 1) {
        throw new RuntimeException('This record changed in another tab. Reload before changing its status.');
    }
}

function admin_reorder_record(
    string $key,
    int $id,
    int $version,
    int $neighborId,
    int $neighborVersion,
    string $direction,
): void
{
    $module = admin_module($key);
    if ($version < 1 || $neighborId < 1 || $neighborVersion < 1) {
        throw new InvalidArgumentException('The record version is missing. Reload and try again.');
    }
    if (!in_array($direction, ['up', 'down'], true)) {
        throw new InvalidArgumentException('Invalid reorder direction.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $currentStatement = $pdo->prepare(
            'SELECT id, sort_order, publication_status, version FROM ' . $module['table'] . '
             WHERE id = :id AND version = :version FOR UPDATE'
        );
        $currentStatement->execute(['id' => $id, 'version' => $version]);
        $current = $currentStatement->fetch();
        if (!is_array($current)) {
            throw new RuntimeException('This record changed in another tab. Reload before reordering it.');
        }
        if ($current['publication_status'] === 'archived') {
            throw new InvalidArgumentException('Restore an archived record before reordering it.');
        }

        $operator = $direction === 'up' ? '<' : '>';
        $order = $direction === 'up' ? 'DESC' : 'ASC';
        $neighborStatement = $pdo->prepare(
            'SELECT id, sort_order, version FROM ' . $module['table'] . '
             WHERE sort_order ' . $operator . ' :sort_order AND publication_status <> \'archived\'
             ORDER BY sort_order ' . $order . ', id ' . $order . ' LIMIT 1 FOR UPDATE'
        );
        $neighborStatement->execute(['sort_order' => $current['sort_order']]);
        $neighbor = $neighborStatement->fetch();
        if (!is_array($neighbor) || (int) $neighbor['id'] !== $neighborId || (int) $neighbor['version'] !== $neighborVersion) {
            throw new RuntimeException('The ordered records changed in another tab. Reload before reordering.');
        }
        $moveCurrent = $pdo->prepare(
            'UPDATE ' . $module['table'] . '
             SET sort_order = :sort_order, version = version + 1, updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND version = :version AND publication_status <> \'archived\''
        );
        $moveCurrent->execute([
            'sort_order' => $neighbor['sort_order'], 'id' => $current['id'], 'version' => $version,
        ]);
        $moveNeighbor = $pdo->prepare(
            'UPDATE ' . $module['table'] . '
             SET sort_order = :sort_order, version = version + 1, updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND version = :version AND publication_status <> \'archived\''
        );
        $moveNeighbor->execute([
            'sort_order' => $current['sort_order'], 'id' => $neighborId, 'version' => $neighborVersion,
        ]);
        if ($moveCurrent->rowCount() !== 1 || $moveNeighbor->rowCount() !== 1) {
            throw new RuntimeException('The ordered records changed in another tab. Reload before reordering.');
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** @return list<array<string,mixed>> */
function admin_event_images(int $eventId, bool $includeArchived = false): array
{
    $statement = db()->prepare(
        'SELECT ei.*, m.public_path FROM event_images ei
         JOIN cms_media m ON m.id = ei.media_id
         WHERE ei.event_id = :event_id' . ($includeArchived ? '' : ' AND ei.archived_at IS NULL') . '
         ORDER BY ei.archived_at IS NOT NULL, ei.sort_order, ei.id'
    );
    $statement->execute(['event_id' => $eventId]);
    return $statement->fetchAll();
}

function admin_add_event_images(int $eventId, int $eventVersion, array $files, array $alts, array $captions): void
{
    if ($eventVersion < 1) {
        throw new InvalidArgumentException('The event version is missing. Reload and try again.');
    }
    $ready = db()->prepare('SELECT 1 FROM events WHERE id = :id AND version = :version');
    $ready->execute(['id' => $eventId, 'version' => $eventVersion]);
    if ($ready->fetchColumn() === false) {
        throw new RuntimeException('This event changed in another tab. Reload before adding images.');
    }
    $uploads = array_values(array_filter(
        admin_normalize_uploads($files),
        static fn (array $file): bool => (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
    ));
    if ($uploads === [] || count($uploads) > 12) {
        throw new InvalidArgumentException('Choose between 1 and 12 event images.');
    }
    if (count($alts) !== count($uploads)) {
        throw new InvalidArgumentException('Provide alt text for every event image.');
    }

    $stored = [];
    foreach ($uploads as $index => $upload) {
        $alt = trim((string) ($alts[$index] ?? ''));
        $caption = trim((string) ($captions[$index] ?? ''));
        if ($alt === '' || admin_length($alt) > 200 || admin_length($caption) > 500) {
            throw new InvalidArgumentException('Every image needs alt text up to 200 characters; captions may use 500.');
        }
        $stored[] = ['media' => admin_store_image($upload), 'alt' => $alt, 'caption' => $caption];
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $event = $pdo->prepare('SELECT id, cover_media_id FROM events WHERE id = :id AND version = :version FOR UPDATE');
        $event->execute(['id' => $eventId, 'version' => $eventVersion]);
        $eventRow = $event->fetch();
        if (!is_array($eventRow)) {
            throw new RuntimeException('This event changed in another tab. Reload before adding images.');
        }
        $sort = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM event_images WHERE event_id = ' . $eventId)->fetchColumn();
        $insert = $pdo->prepare(
            'INSERT INTO event_images (event_id, media_id, caption, alt_text, sort_order, created_at, updated_at)
             VALUES (:event_id, :media_id, :caption, :alt_text, :sort_order, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
        );
        foreach ($stored as $item) {
            $sort += 10;
            $insert->execute([
                'event_id' => $eventId,
                'media_id' => $item['media']['id'],
                'caption' => $item['caption'] !== '' ? $item['caption'] : null,
                'alt_text' => $item['alt'],
                'sort_order' => $sort,
            ]);
        }
        $cover = $eventRow['cover_media_id'] ?: $stored[0]['media']['id'];
        $update = $pdo->prepare(
            'UPDATE events SET cover_media_id = :cover, version = version + 1, updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND version = :version'
        );
        $update->execute(['cover' => $cover, 'id' => $eventId, 'version' => $eventVersion]);
        if ($update->rowCount() !== 1) {
            throw new RuntimeException('This event changed in another tab. Reload before adding images.');
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function admin_set_event_cover(int $eventId, int $eventVersion, int $imageId, int $imageVersion): void
{
    if ($eventVersion < 1 || $imageVersion < 1) {
        throw new InvalidArgumentException('The event or image version is missing. Reload and try again.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $event = $pdo->prepare('SELECT id FROM events WHERE id = :id AND version = :version FOR UPDATE');
        $event->execute(['id' => $eventId, 'version' => $eventVersion]);
        if ($event->fetchColumn() === false) {
            throw new RuntimeException('This event changed in another tab. Reload before selecting its cover.');
        }
        $image = $pdo->prepare(
            'SELECT media_id FROM event_images
             WHERE id = :id AND event_id = :event_id AND version = :version AND archived_at IS NULL FOR UPDATE'
        );
        $image->execute(['id' => $imageId, 'event_id' => $eventId, 'version' => $imageVersion]);
        $mediaId = $image->fetchColumn();
        if ($mediaId === false) {
            throw new RuntimeException('That event image changed in another tab. Reload before selecting it.');
        }
        $update = $pdo->prepare(
            'UPDATE events SET cover_media_id = :media_id, version = version + 1, updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND version = :version'
        );
        $update->execute(['media_id' => $mediaId, 'id' => $eventId, 'version' => $eventVersion]);
        if ($update->rowCount() !== 1) {
            throw new RuntimeException('This event changed in another tab. Reload before selecting its cover.');
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function admin_update_event_image(int $eventId, int $eventVersion, int $imageId, int $version, string $alt, string $caption): void
{
    $alt = trim($alt);
    $caption = trim($caption);
    if ($alt === '' || admin_length($alt) > 200 || admin_length($caption) > 500 || $eventVersion < 1 || $version < 1) {
        throw new InvalidArgumentException('Alt text is required and captions may use up to 500 characters.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $event = $pdo->prepare('SELECT id FROM events WHERE id = :id AND version = :version FOR UPDATE');
        $event->execute(['id' => $eventId, 'version' => $eventVersion]);
        if ($event->fetchColumn() === false) {
            throw new RuntimeException('This event changed in another tab. Reload before saving its image.');
        }
        $update = $pdo->prepare(
            'UPDATE event_images SET alt_text = :alt, caption = :caption, version = version + 1, updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND event_id = :event_id AND version = :version AND archived_at IS NULL'
        );
        $update->execute([
            'alt' => $alt,
            'caption' => $caption !== '' ? $caption : null,
            'id' => $imageId,
            'event_id' => $eventId,
            'version' => $version,
        ]);
        if ($update->rowCount() !== 1) {
            throw new RuntimeException('That image changed in another tab. Reload before saving it.');
        }
        $touch = $pdo->prepare(
            'UPDATE events SET version = version + 1, updated_at = UTC_TIMESTAMP() WHERE id = :id AND version = :version'
        );
        $touch->execute(['id' => $eventId, 'version' => $eventVersion]);
        if ($touch->rowCount() !== 1) {
            throw new RuntimeException('This event changed in another tab. Reload before saving its image.');
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function admin_remove_event_image(int $eventId, int $eventVersion, int $imageId, int $imageVersion): void
{
    if ($eventVersion < 1 || $imageVersion < 1) {
        throw new InvalidArgumentException('The event or image version is missing. Reload and try again.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $event = $pdo->prepare(
            'SELECT publication_status FROM events WHERE id = :event_id AND version = :version FOR UPDATE'
        );
        $event->execute(['event_id' => $eventId, 'version' => $eventVersion]);
        $eventStatus = $event->fetchColumn();
        if ($eventStatus === false) {
            throw new RuntimeException('This event changed in another tab. Reload before archiving its image.');
        }
        $find = $pdo->prepare(
            'SELECT media_id FROM event_images
             WHERE id = :id AND event_id = :event_id AND version = :version AND archived_at IS NULL FOR UPDATE'
        );
        $find->execute(['id' => $imageId, 'event_id' => $eventId, 'version' => $imageVersion]);
        $mediaId = $find->fetchColumn();
        if ($mediaId === false) {
            throw new RuntimeException('That event image changed in another tab. Reload before archiving it.');
        }
        $count = $pdo->prepare('SELECT COUNT(*) FROM event_images WHERE event_id = :event_id AND archived_at IS NULL');
        $count->execute(['event_id' => $eventId]);
        if ($eventStatus === 'published' && (int) $count->fetchColumn() <= 1) {
            throw new InvalidArgumentException('Unpublish this event before archiving its final image.');
        }
        $archive = $pdo->prepare(
            'UPDATE event_images SET archived_at = UTC_TIMESTAMP(), version = version + 1, updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND event_id = :event_id AND version = :version AND archived_at IS NULL'
        );
        $archive->execute(['id' => $imageId, 'event_id' => $eventId, 'version' => $imageVersion]);
        if ($archive->rowCount() !== 1) {
            throw new RuntimeException('That event image changed in another tab. Reload before archiving it.');
        }
        $next = $pdo->prepare('SELECT media_id FROM event_images WHERE event_id = :event_id AND archived_at IS NULL ORDER BY sort_order, id LIMIT 1');
        $next->execute(['event_id' => $eventId]);
        $nextMedia = $next->fetchColumn();
        $update = $pdo->prepare(
            'UPDATE events SET cover_media_id = CASE WHEN cover_media_id = :removed THEN :next_media ELSE cover_media_id END,
             version = version + 1, updated_at = UTC_TIMESTAMP() WHERE id = :event_id AND version = :version'
        );
        $update->bindValue('removed', $mediaId, PDO::PARAM_INT);
        $update->bindValue('next_media', $nextMedia === false ? null : $nextMedia, $nextMedia === false ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $update->bindValue('event_id', $eventId, PDO::PARAM_INT);
        $update->bindValue('version', $eventVersion, PDO::PARAM_INT);
        $update->execute();
        if ($update->rowCount() !== 1) {
            throw new RuntimeException('This event changed in another tab. Reload before archiving its image.');
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function admin_restore_event_image(int $eventId, int $eventVersion, int $imageId, int $imageVersion): void
{
    if ($eventVersion < 1 || $imageVersion < 1) {
        throw new InvalidArgumentException('The event or image version is missing. Reload and try again.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $event = $pdo->prepare('SELECT id FROM events WHERE id = :event_id AND version = :version FOR UPDATE');
        $event->execute(['event_id' => $eventId, 'version' => $eventVersion]);
        if ($event->fetchColumn() === false) {
            throw new RuntimeException('This event changed in another tab. Reload before restoring its image.');
        }
        $image = $pdo->prepare(
            'SELECT media_id FROM event_images
             WHERE id = :id AND event_id = :event_id AND version = :version AND archived_at IS NOT NULL FOR UPDATE'
        );
        $image->execute(['id' => $imageId, 'event_id' => $eventId, 'version' => $imageVersion]);
        $mediaId = $image->fetchColumn();
        if ($mediaId === false) {
            throw new RuntimeException('That archived event image changed in another tab. Reload before restoring it.');
        }
        $restore = $pdo->prepare(
            'UPDATE event_images SET archived_at = NULL, version = version + 1, updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND event_id = :event_id AND version = :version AND archived_at IS NOT NULL'
        );
        $restore->execute(['id' => $imageId, 'event_id' => $eventId, 'version' => $imageVersion]);
        if ($restore->rowCount() !== 1) {
            throw new RuntimeException('That archived event image changed in another tab. Reload before restoring it.');
        }
        $update = $pdo->prepare(
            'UPDATE events SET cover_media_id = COALESCE(cover_media_id, :media_id), version = version + 1,
             updated_at = UTC_TIMESTAMP() WHERE id = :event_id AND version = :version'
        );
        $update->execute(['media_id' => $mediaId, 'event_id' => $eventId, 'version' => $eventVersion]);
        if ($update->rowCount() !== 1) {
            throw new RuntimeException('This event changed in another tab. Reload before restoring its image.');
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function admin_reorder_child(
    string $kind,
    int $parentId,
    int $parentVersion,
    int $childId,
    int $childVersion,
    string $direction,
): void
{
    $settings = match ($kind) {
        'event_image' => ['table' => 'event_images', 'parent' => 'event_id', 'parent_table' => 'events', 'label' => 'event image'],
        'project_member' => ['table' => 'project_members', 'parent' => 'project_id', 'parent_table' => 'projects', 'label' => 'project member'],
        default => throw new InvalidArgumentException('Invalid child record type.'),
    };
    if ($parentVersion < 1 || $childVersion < 1) {
        throw new InvalidArgumentException('The record version is missing. Reload and try again.');
    }
    if (!in_array($direction, ['up', 'down'], true)) {
        throw new InvalidArgumentException('Invalid reorder direction.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $parentStatement = $pdo->prepare(
            'SELECT id FROM ' . $settings['parent_table'] . ' WHERE id = :id AND version = :version FOR UPDATE'
        );
        $parentStatement->execute(['id' => $parentId, 'version' => $parentVersion]);
        if ($parentStatement->fetchColumn() === false) {
            throw new RuntimeException('This parent record changed in another tab. Reload before reordering.');
        }
        $currentStatement = $pdo->prepare(
            'SELECT id, sort_order, version FROM ' . $settings['table'] . '
             WHERE id = :id AND ' . $settings['parent'] . ' = :parent_id AND version = :version
               AND archived_at IS NULL FOR UPDATE'
        );
        $currentStatement->execute(['id' => $childId, 'parent_id' => $parentId, 'version' => $childVersion]);
        $current = $currentStatement->fetch();
        if (!is_array($current)) {
            throw new RuntimeException('That ' . $settings['label'] . ' changed in another tab. Reload before reordering.');
        }

        $operator = $direction === 'up' ? '<' : '>';
        $order = $direction === 'up' ? 'DESC' : 'ASC';
        $neighborStatement = $pdo->prepare(
            'SELECT id, sort_order, version FROM ' . $settings['table'] . '
             WHERE ' . $settings['parent'] . ' = :parent_id AND archived_at IS NULL AND sort_order ' . $operator . ' :sort_order
             ORDER BY sort_order ' . $order . ', id ' . $order . ' LIMIT 1 FOR UPDATE'
        );
        $neighborStatement->execute(['parent_id' => $parentId, 'sort_order' => $current['sort_order']]);
        $neighbor = $neighborStatement->fetch();
        if (is_array($neighbor)) {
            $moveCurrent = $pdo->prepare(
                'UPDATE ' . $settings['table'] . '
                 SET sort_order = :sort_order, version = version + 1, updated_at = UTC_TIMESTAMP()
                 WHERE id = :id AND ' . $settings['parent'] . ' = :parent_id AND version = :version AND archived_at IS NULL'
            );
            $moveCurrent->execute([
                'sort_order' => $neighbor['sort_order'],
                'id' => $current['id'],
                'parent_id' => $parentId,
                'version' => $childVersion,
            ]);
            $moveNeighbor = $pdo->prepare(
                'UPDATE ' . $settings['table'] . '
                 SET sort_order = :sort_order, version = version + 1, updated_at = UTC_TIMESTAMP()
                 WHERE id = :id AND ' . $settings['parent'] . ' = :parent_id AND version = :version AND archived_at IS NULL'
            );
            $moveNeighbor->execute([
                'sort_order' => $current['sort_order'],
                'id' => $neighbor['id'],
                'parent_id' => $parentId,
                'version' => $neighbor['version'],
            ]);
            if ($moveCurrent->rowCount() !== 1 || $moveNeighbor->rowCount() !== 1) {
                throw new RuntimeException('The ordered items changed in another tab. Reload before reordering.');
            }
            $touch = $pdo->prepare(
                'UPDATE ' . $settings['parent_table'] . '
                 SET version = version + 1, updated_at = UTC_TIMESTAMP() WHERE id = :id AND version = :version'
            );
            $touch->execute(['id' => $parentId, 'version' => $parentVersion]);
            if ($touch->rowCount() !== 1) {
                throw new RuntimeException('This parent record changed in another tab. Reload before reordering.');
            }
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** @return list<array<string,mixed>> */
function admin_project_members(int $projectId, bool $includeArchived = false): array
{
    $statement = db()->prepare(
        'SELECT pm.*, m.public_path FROM project_members pm
         LEFT JOIN cms_media m ON m.id = pm.media_id
         WHERE pm.project_id = :project_id' . ($includeArchived ? '' : ' AND pm.archived_at IS NULL') . '
         ORDER BY pm.archived_at IS NOT NULL, pm.sort_order, pm.id'
    );
    $statement->execute(['project_id' => $projectId]);
    return $statement->fetchAll();
}

function admin_add_project_member(int $projectId, int $projectVersion, array $input, ?array $file): void
{
    $name = trim((string) ($input['name'] ?? ''));
    $initials = strtoupper(trim((string) ($input['initials'] ?? '')));
    if ($projectVersion < 1) {
        throw new InvalidArgumentException('The project version is missing. Reload and try again.');
    }
    if ($name === '' || admin_length($name) > 100 || $initials === '' || admin_length($initials) > 10) {
        throw new InvalidArgumentException('Member name and initials are required.');
    }
    $ready = db()->prepare('SELECT 1 FROM projects WHERE id = :id AND version = :version');
    $ready->execute(['id' => $projectId, 'version' => $projectVersion]);
    if ($ready->fetchColumn() === false) {
        throw new RuntimeException('This project changed in another tab. Reload before adding a member.');
    }
    $mediaId = null;
    if (is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $mediaId = admin_store_image($file)['id'];
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $project = $pdo->prepare('SELECT id FROM projects WHERE id = :id AND version = :version FOR UPDATE');
        $project->execute(['id' => $projectId, 'version' => $projectVersion]);
        if ($project->fetchColumn() === false) {
            throw new RuntimeException('This project changed in another tab. Reload before adding a member.');
        }
        $sortStatement = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM project_members WHERE project_id = :id');
        $sortStatement->execute(['id' => $projectId]);
        $insert = $pdo->prepare(
            'INSERT INTO project_members (project_id, name, initials, media_id, sort_order, created_at, updated_at)
             VALUES (:project_id, :name, :initials, :media_id, :sort_order, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
        );
        $insert->execute([
            'project_id' => $projectId,
            'name' => $name,
            'initials' => $initials,
            'media_id' => $mediaId,
            'sort_order' => (int) $sortStatement->fetchColumn(),
        ]);
        $touch = $pdo->prepare(
            'UPDATE projects SET version = version + 1, updated_at = UTC_TIMESTAMP() WHERE id = :id AND version = :version'
        );
        $touch->execute(['id' => $projectId, 'version' => $projectVersion]);
        if ($touch->rowCount() !== 1) {
            throw new RuntimeException('This project changed in another tab. Reload before adding a member.');
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function admin_remove_project_member(int $projectId, int $projectVersion, int $memberId, int $memberVersion): void
{
    if ($projectVersion < 1 || $memberVersion < 1) {
        throw new InvalidArgumentException('The project or member version is missing. Reload and try again.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $project = $pdo->prepare('SELECT id FROM projects WHERE id = :id AND version = :version FOR UPDATE');
        $project->execute(['id' => $projectId, 'version' => $projectVersion]);
        if ($project->fetchColumn() === false) {
            throw new RuntimeException('This project changed in another tab. Reload before archiving its member.');
        }
        $archive = $pdo->prepare(
            'UPDATE project_members SET archived_at = UTC_TIMESTAMP(), version = version + 1, updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND project_id = :project_id AND version = :version AND archived_at IS NULL'
        );
        $archive->execute(['id' => $memberId, 'project_id' => $projectId, 'version' => $memberVersion]);
        if ($archive->rowCount() !== 1) {
            throw new RuntimeException('That project member changed in another tab. Reload before archiving them.');
        }
        $touch = $pdo->prepare(
            'UPDATE projects SET version = version + 1, updated_at = UTC_TIMESTAMP() WHERE id = :id AND version = :version'
        );
        $touch->execute(['id' => $projectId, 'version' => $projectVersion]);
        if ($touch->rowCount() !== 1) {
            throw new RuntimeException('This project changed in another tab. Reload before archiving its member.');
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function admin_restore_project_member(int $projectId, int $projectVersion, int $memberId, int $memberVersion): void
{
    if ($projectVersion < 1 || $memberVersion < 1) {
        throw new InvalidArgumentException('The project or member version is missing. Reload and try again.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $project = $pdo->prepare('SELECT id FROM projects WHERE id = :id AND version = :version FOR UPDATE');
        $project->execute(['id' => $projectId, 'version' => $projectVersion]);
        if ($project->fetchColumn() === false) {
            throw new RuntimeException('This project changed in another tab. Reload before restoring its member.');
        }
        $restore = $pdo->prepare(
            'UPDATE project_members SET archived_at = NULL, version = version + 1, updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND project_id = :project_id AND version = :version AND archived_at IS NOT NULL'
        );
        $restore->execute(['id' => $memberId, 'project_id' => $projectId, 'version' => $memberVersion]);
        if ($restore->rowCount() !== 1) {
            throw new RuntimeException('That archived project member changed in another tab. Reload before restoring them.');
        }
        $touch = $pdo->prepare(
            'UPDATE projects SET version = version + 1, updated_at = UTC_TIMESTAMP() WHERE id = :id AND version = :version'
        );
        $touch->execute(['id' => $projectId, 'version' => $projectVersion]);
        if ($touch->rowCount() !== 1) {
            throw new RuntimeException('This project changed in another tab. Reload before restoring its member.');
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function admin_update_project_member(
    int $projectId,
    int $projectVersion,
    int $memberId,
    int $version,
    string $name,
    string $initials,
): void
{
    $name = trim($name);
    $initials = strtoupper(trim($initials));
    if ($name === '' || admin_length($name) > 100 || $initials === '' || admin_length($initials) > 10 || $projectVersion < 1 || $version < 1) {
        throw new InvalidArgumentException('Member name and initials are required.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $project = $pdo->prepare('SELECT id FROM projects WHERE id = :id AND version = :version FOR UPDATE');
        $project->execute(['id' => $projectId, 'version' => $projectVersion]);
        if ($project->fetchColumn() === false) {
            throw new RuntimeException('This project changed in another tab. Reload before saving its member.');
        }
        $update = $pdo->prepare(
            'UPDATE project_members SET name = :name, initials = :initials, version = version + 1, updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND project_id = :project_id AND version = :version AND archived_at IS NULL'
        );
        $update->execute([
            'name' => $name, 'initials' => $initials, 'id' => $memberId,
            'project_id' => $projectId, 'version' => $version,
        ]);
        if ($update->rowCount() !== 1) {
            throw new RuntimeException('That project member changed in another tab. Reload before saving.');
        }
        $touch = $pdo->prepare(
            'UPDATE projects SET version = version + 1, updated_at = UTC_TIMESTAMP() WHERE id = :id AND version = :version'
        );
        $touch->execute(['id' => $projectId, 'version' => $projectVersion]);
        if ($touch->rowCount() !== 1) {
            throw new RuntimeException('This project changed in another tab. Reload before saving its member.');
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** @return array<string,int> */
function admin_dashboard_counts(): array
{
    $sql = "SELECT 'published' metric, SUM(total) value FROM (
                SELECT COUNT(*) total FROM blog_posts WHERE publication_status = 'published'
                UNION ALL SELECT COUNT(*) FROM certifications WHERE publication_status = 'published'
                UNION ALL SELECT COUNT(*) FROM events WHERE publication_status = 'published'
                UNION ALL SELECT COUNT(*) FROM education_entries WHERE publication_status = 'published'
                UNION ALL SELECT COUNT(*) FROM work_experiences WHERE publication_status = 'published'
                UNION ALL SELECT COUNT(*) FROM projects WHERE publication_status = 'published'
                UNION ALL SELECT COUNT(*) FROM testimonials WHERE publication_status = 'published'
            ) published_counts
            UNION ALL
            SELECT 'drafts', SUM(total) FROM (
                SELECT COUNT(*) total FROM blog_posts WHERE publication_status = 'draft'
                UNION ALL SELECT COUNT(*) FROM certifications WHERE publication_status = 'draft'
                UNION ALL SELECT COUNT(*) FROM events WHERE publication_status = 'draft'
                UNION ALL SELECT COUNT(*) FROM education_entries WHERE publication_status = 'draft'
                UNION ALL SELECT COUNT(*) FROM work_experiences WHERE publication_status = 'draft'
                UNION ALL SELECT COUNT(*) FROM projects WHERE publication_status = 'draft'
                UNION ALL SELECT COUNT(*) FROM testimonials WHERE publication_status = 'draft'
            ) draft_counts
            UNION ALL SELECT 'contacts', COUNT(*) FROM contact_submissions WHERE status = 'new'
            UNION ALL SELECT 'meetings', COUNT(*) FROM meeting_requests WHERE status = 'pending'
            UNION ALL SELECT 'subscribers', COUNT(*) FROM newsletter_subscribers WHERE status = 'active'
            UNION ALL SELECT 'mail_failures',
                (SELECT COUNT(*) FROM contact_submissions WHERE admin_notification_error IS NOT NULL)
                + (SELECT COUNT(*) FROM meeting_requests WHERE request_notification_error IS NOT NULL OR approval_notification_error IS NOT NULL)";
    $counts = [];
    foreach (db()->query($sql) as $row) {
        $counts[(string) $row['metric']] = (int) $row['value'];
    }
    return $counts;
}

/** @return list<array<string,mixed>> */
function admin_list_contacts(?int $before = null): array
{
    $sql = 'SELECT id, full_name, email, phone, service_code, message, status, admin_notified_at, admin_notification_error, created_at
            FROM contact_submissions';
    $parameters = [];
    if ($before !== null && $before > 0) {
        $sql .= ' WHERE id < :before';
        $parameters['before'] = $before;
    }
    $sql .= ' ORDER BY id DESC LIMIT 26';
    $statement = db()->prepare($sql);
    $statement->execute($parameters);
    return $statement->fetchAll();
}

/** @return list<array<string,mixed>> */
function admin_list_meetings(?int $before = null): array
{
    $sql = 'SELECT id, full_name, email, phone, requested_start_at, approved_start_at, status, admin_note,
                   request_notification_error, approval_notified_at, approval_notification_error, created_at
            FROM meeting_requests';
    $parameters = [];
    if ($before !== null && $before > 0) {
        $sql .= ' WHERE id < :before';
        $parameters['before'] = $before;
    }
    $sql .= ' ORDER BY id DESC LIMIT 26';
    $statement = db()->prepare($sql);
    $statement->execute($parameters);
    return $statement->fetchAll();
}

/** @return list<array<string,mixed>> */
function admin_list_subscribers(?int $before = null): array
{
    $sql = 'SELECT id, email, source_path, status, first_subscribed_at, last_submitted_at FROM newsletter_subscribers';
    $parameters = [];
    if ($before !== null && $before > 0) {
        $sql .= ' WHERE id < :before';
        $parameters['before'] = $before;
    }
    $sql .= ' ORDER BY id DESC LIMIT 26';
    $statement = db()->prepare($sql);
    $statement->execute($parameters);
    return $statement->fetchAll();
}

function admin_mark_contact_handled(int $id): void
{
    $statement = db()->prepare("UPDATE contact_submissions SET status = 'handled', updated_at = UTC_TIMESTAMP() WHERE id = :id AND status = 'new'");
    $statement->execute(['id' => $id]);
    if ($statement->rowCount() !== 1) {
        throw new InvalidArgumentException('That contact message is already handled or no longer exists.');
    }
}

function admin_meeting_utc(string $date, string $time): string
{
    $timezone = new DateTimeZone('Asia/Karachi');
    $local = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time, $timezone);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$local || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $local->format('Y-m-d H:i') !== $date . ' ' . $time) {
        throw new InvalidArgumentException('Choose a valid final meeting date and time.');
    }
    $now = new DateTimeImmutable('now', $timezone);
    if ($local <= $now || (int) $local->format('N') > 5 || (int) $local->format('i') % 30 !== 0 || $local > $now->modify('+90 days')) {
        throw new InvalidArgumentException('Meetings must be a future weekday, on a 30-minute boundary, within 90 days.');
    }
    return $local->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}

function admin_approve_meeting(int $id, string $date, string $time, string $note): void
{
    $approvedUtc = admin_meeting_utc($date, $time);
    if (admin_length($note) > 500) {
        throw new InvalidArgumentException('The admin note is too long.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $find = $pdo->prepare('SELECT * FROM meeting_requests WHERE id = :id FOR UPDATE');
        $find->execute(['id' => $id]);
        $meeting = $find->fetch();
        if (!is_array($meeting) || $meeting['status'] !== 'pending') {
            throw new InvalidArgumentException('Only pending meeting requests can be approved.');
        }
        $update = $pdo->prepare(
            "UPDATE meeting_requests SET status = 'approved', approved_start_at = :approved, admin_note = :note,
             reviewed_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE id = :id AND status = 'pending'"
        );
        $update->execute(['approved' => $approvedUtc, 'note' => $note !== '' ? $note : null, 'id' => $id]);
        $pdo->commit();
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($exception->getCode() === '23000') {
            throw new RuntimeException('That meeting slot was approved for another request. Choose a different time.');
        }
        throw $exception;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    admin_send_approval($id);
}

function admin_reject_meeting(int $id, string $note): void
{
    if (admin_length($note) > 500) {
        throw new InvalidArgumentException('The admin note is too long.');
    }
    $statement = db()->prepare(
        "UPDATE meeting_requests SET status = 'rejected', admin_note = :note, reviewed_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()
         WHERE id = :id AND status = 'pending'"
    );
    $statement->execute(['note' => $note !== '' ? $note : null, 'id' => $id]);
    if ($statement->rowCount() !== 1) {
        throw new InvalidArgumentException('Only pending meeting requests can be rejected.');
    }
}

function admin_send_approval(int $id): void
{
    $statement = db()->prepare('SELECT * FROM meeting_requests WHERE id = :id');
    $statement->execute(['id' => $id]);
    $meeting = $statement->fetch();
    if (!is_array($meeting) || $meeting['status'] !== 'approved') {
        throw new InvalidArgumentException('Only approved meetings can send an approval email.');
    }
    if (!function_exists('mail_meeting_approval')) {
        throw new RuntimeException('The mailer is not available. The meeting remains approved.');
    }
    $result = mail_meeting_approval($meeting);
    $ok = (bool) ($result['ok'] ?? false);
    $error = $ok ? null : substr((string) ($result['error'] ?? 'Delivery failed.'), 0, 1_000);
    $update = db()->prepare(
        'UPDATE meeting_requests SET approval_notified_at = :sent_at, approval_notification_error = :error, updated_at = UTC_TIMESTAMP() WHERE id = :id'
    );
    $update->execute(['sent_at' => $ok ? gmdate('Y-m-d H:i:s') : null, 'error' => $error, 'id' => $id]);
    if (!$ok) {
        throw new RuntimeException('The meeting was approved, but the visitor email failed. Use Retry email after checking SMTP.');
    }
}

function admin_export_newsletter(): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="newsletter-subscribers-' . gmdate('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'wb');
    if ($output === false) {
        throw new RuntimeException('The CSV export could not be opened.');
    }
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Email', 'Source', 'Status', 'First subscribed (UTC)', 'Last submitted (UTC)']);
    $statement = db()->query(
        'SELECT email, source_path, status, first_subscribed_at, last_submitted_at FROM newsletter_subscribers ORDER BY id'
    );
    while ($row = $statement->fetch()) {
        fputcsv($output, array_map('admin_csv_cell', array_values($row)));
    }
    fclose($output);
    exit;
}

function admin_csv_cell(mixed $value): string
{
    $cell = (string) $value;
    return preg_match('/^\s*[=+\-@]/u', $cell) === 1 ? "'" . $cell : $cell;
}

/**
 * Execute one allowlisted admin mutation and return its safe redirect URL.
 */
function admin_handle_post(): ?string
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return null;
    }
    admin_verify_post();
    $action = (string) ($_POST['action'] ?? '');
    $section = (string) ($_POST['section'] ?? 'dashboard');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;

    try {
        switch ($action) {
            case 'save_record':
                $savedId = admin_save_record($section, $_POST, $_FILES);
                admin_flash('success', 'Saved successfully.');
                return '/admin/?section=' . rawurlencode($section) . '&view=edit&id=' . $savedId;

            case 'publish':
            case 'unpublish':
            case 'archive':
            case 'restore':
                admin_change_publication(
                    $section,
                    $id,
                    filter_var($_POST['version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    $action
                );
                admin_flash('success', ucfirst($action) . ' completed.');
                break;

            case 'move_record':
                admin_reorder_record(
                    $section,
                    $id,
                    filter_var($_POST['version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['neighbor_id'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['neighbor_version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    (string) ($_POST['direction'] ?? '')
                );
                admin_flash('success', 'Order updated.');
                break;

            case 'event_add_images':
                admin_add_event_images(
                    $id,
                    filter_var($_POST['parent_version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    $_FILES['event_images'] ?? [],
                    is_array($_POST['image_alt'] ?? null) ? $_POST['image_alt'] : [],
                    is_array($_POST['image_caption'] ?? null) ? $_POST['image_caption'] : []
                );
                admin_flash('success', 'Event images added.');
                return '/admin/?section=events&view=edit&id=' . $id;

            case 'event_set_cover':
                admin_set_event_cover(
                    $id,
                    filter_var($_POST['parent_version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['image_id'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['image_version'] ?? null, FILTER_VALIDATE_INT) ?: 0
                );
                admin_flash('success', 'Event cover updated.');
                return '/admin/?section=events&view=edit&id=' . $id;

            case 'event_update_image':
                admin_update_event_image(
                    $id,
                    filter_var($_POST['parent_version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['image_id'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['image_version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    (string) ($_POST['alt_text'] ?? ''),
                    (string) ($_POST['caption'] ?? '')
                );
                admin_flash('success', 'Event image details updated.');
                return '/admin/?section=events&view=edit&id=' . $id;

            case 'event_remove_image':
                admin_remove_event_image(
                    $id,
                    filter_var($_POST['parent_version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['image_id'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['image_version'] ?? null, FILTER_VALIDATE_INT) ?: 0
                );
                admin_flash('success', 'Event image archived. It can be restored later.');
                return '/admin/?section=events&view=edit&id=' . $id;

            case 'event_restore_image':
                admin_restore_event_image(
                    $id,
                    filter_var($_POST['parent_version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['image_id'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['image_version'] ?? null, FILTER_VALIDATE_INT) ?: 0
                );
                admin_flash('success', 'Event image restored.');
                return '/admin/?section=events&view=edit&id=' . $id;

            case 'move_event_image':
                admin_reorder_child(
                    'event_image',
                    $id,
                    filter_var($_POST['parent_version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['image_id'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['image_version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    (string) ($_POST['direction'] ?? '')
                );
                admin_flash('success', 'Event image order updated.');
                return '/admin/?section=events&view=edit&id=' . $id;

            case 'project_add_member':
                admin_add_project_member(
                    $id,
                    filter_var($_POST['parent_version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    $_POST,
                    $_FILES['member_image'] ?? null
                );
                admin_flash('success', 'Project member added.');
                return '/admin/?section=projects&view=edit&id=' . $id;

            case 'project_remove_member':
                admin_remove_project_member(
                    $id,
                    filter_var($_POST['parent_version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['member_id'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['member_version'] ?? null, FILTER_VALIDATE_INT) ?: 0
                );
                admin_flash('success', 'Project member archived. They can be restored later.');
                return '/admin/?section=projects&view=edit&id=' . $id;

            case 'project_restore_member':
                admin_restore_project_member(
                    $id,
                    filter_var($_POST['parent_version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['member_id'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['member_version'] ?? null, FILTER_VALIDATE_INT) ?: 0
                );
                admin_flash('success', 'Project member restored.');
                return '/admin/?section=projects&view=edit&id=' . $id;

            case 'project_update_member':
                admin_update_project_member(
                    $id,
                    filter_var($_POST['parent_version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['member_id'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['member_version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    (string) ($_POST['name'] ?? ''),
                    (string) ($_POST['initials'] ?? '')
                );
                admin_flash('success', 'Project member updated.');
                return '/admin/?section=projects&view=edit&id=' . $id;

            case 'move_project_member':
                admin_reorder_child(
                    'project_member',
                    $id,
                    filter_var($_POST['parent_version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['member_id'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    filter_var($_POST['member_version'] ?? null, FILTER_VALIDATE_INT) ?: 0,
                    (string) ($_POST['direction'] ?? '')
                );
                admin_flash('success', 'Project member order updated.');
                return '/admin/?section=projects&view=edit&id=' . $id;

            case 'contact_handled':
                admin_mark_contact_handled($id);
                admin_flash('success', 'Contact marked handled.');
                $section = 'contacts';
                break;

            case 'meeting_approve':
                admin_approve_meeting(
                    $id,
                    (string) ($_POST['final_date'] ?? ''),
                    (string) ($_POST['final_time'] ?? ''),
                    trim((string) ($_POST['admin_note'] ?? ''))
                );
                admin_flash('success', 'Meeting approved and visitor notified.');
                $section = 'meetings';
                break;

            case 'meeting_reject':
                admin_reject_meeting($id, trim((string) ($_POST['admin_note'] ?? '')));
                admin_flash('success', 'Meeting rejected.');
                $section = 'meetings';
                break;

            case 'meeting_retry_email':
                admin_send_approval($id);
                admin_flash('success', 'Approval email sent.');
                $section = 'meetings';
                break;

            case 'newsletter_export':
                admin_export_newsletter();

            default:
                throw new InvalidArgumentException('Unknown admin action.');
        }
    } catch (InvalidArgumentException|RuntimeException $exception) {
        admin_flash('error', $exception->getMessage());
        if (in_array($section, array_keys(admin_modules()), true) && $id > 0) {
            return '/admin/?section=' . rawurlencode($section) . '&view=edit&id=' . $id;
        }
    }

    $allowedSections = array_merge(['dashboard', 'contacts', 'meetings', 'newsletter'], array_keys(admin_modules()));
    if (!in_array($section, $allowedSections, true)) {
        $section = 'dashboard';
    }
    return '/admin/?section=' . rawurlencode($section);
}
