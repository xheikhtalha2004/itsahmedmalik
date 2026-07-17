<?php

declare(strict_types=1);

/**
 * Read-only content queries used by public pages and admin previews.
 *
 * Every collection is deliberately typed by table instead of accepting a
 * table name from the request. This keeps the public rendering surface small
 * and makes the publication/archive policy impossible to forget.
 */

function content_blogs(bool $includeUnpublished = false): array
{
    $where = $includeUnpublished
        ? 'b.archived_at IS NULL'
        : "b.publication_status = 'published' AND b.archived_at IS NULL";

    return content_rows(
        "SELECT b.*, m.public_path AS cover_path
         FROM blog_posts b
         LEFT JOIN cms_media m ON m.id = b.cover_media_id
         WHERE {$where}
         ORDER BY b.sort_order, b.published_on DESC, b.id"
    );
}

function content_find_blog(int $id, bool $includeUnpublished = false): ?array
{
    if ($id < 1) {
        return null;
    }

    $where = $includeUnpublished
        ? 'b.id = :id AND b.archived_at IS NULL'
        : "b.id = :id AND b.publication_status = 'published' AND b.archived_at IS NULL";
    $rows = content_rows(
        "SELECT b.*, m.public_path AS cover_path
         FROM blog_posts b
         LEFT JOIN cms_media m ON m.id = b.cover_media_id
         WHERE {$where}
         LIMIT 1",
        ['id' => $id]
    );

    return $rows[0] ?? null;
}

function content_certifications(bool $includeUnpublished = false): array
{
    $where = $includeUnpublished
        ? 'c.archived_at IS NULL'
        : "c.publication_status = 'published' AND c.archived_at IS NULL";

    return content_rows(
        "SELECT c.*, m.public_path AS image_path
         FROM certifications c
         LEFT JOIN cms_media m ON m.id = c.media_id
         WHERE {$where}
         ORDER BY c.sort_order, c.id"
    );
}

function content_events(bool $includeUnpublished = false): array
{
    $where = $includeUnpublished
        ? 'e.archived_at IS NULL'
        : "e.publication_status = 'published' AND e.archived_at IS NULL
           AND EXISTS (SELECT 1 FROM event_images active_image WHERE active_image.event_id = e.id AND active_image.archived_at IS NULL)";
    $events = content_rows(
        "SELECT e.*, cover.public_path AS cover_path
         FROM events e
         LEFT JOIN cms_media cover ON cover.id = e.cover_media_id
         WHERE {$where}
         ORDER BY e.sort_order, e.id"
    );

    if ($events === []) {
        return [];
    }

    $eventIds = array_map(static fn (array $event): int => (int) $event['id'], $events);
    $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
    $statement = db()->prepare(
        "SELECT ei.*, m.public_path
         FROM event_images ei
         INNER JOIN cms_media m ON m.id = ei.media_id
         WHERE ei.event_id IN ({$placeholders}) AND ei.archived_at IS NULL AND m.archived_at IS NULL
         ORDER BY ei.event_id, ei.sort_order, ei.id"
    );
    $statement->execute($eventIds);

    $imagesByEvent = [];
    foreach ($statement->fetchAll() as $image) {
        $imagesByEvent[(int) $image['event_id']][] = $image;
    }
    foreach ($events as &$event) {
        $event['images'] = $imagesByEvent[(int) $event['id']] ?? [];
    }
    unset($event);

    return $events;
}

function content_education(bool $includeUnpublished = false): array
{
    return content_simple_collection('education_entries', $includeUnpublished);
}

function content_experiences(bool $includeUnpublished = false): array
{
    return content_simple_collection('work_experiences', $includeUnpublished);
}

function content_testimonials(bool $includeUnpublished = false): array
{
    return content_simple_collection('testimonials', $includeUnpublished);
}

function content_projects(bool $includeUnpublished = false): array
{
    $where = $includeUnpublished
        ? 'p.archived_at IS NULL'
        : "p.publication_status = 'published' AND p.archived_at IS NULL";
    $projects = content_rows(
        "SELECT p.* FROM projects p WHERE {$where} ORDER BY p.sort_order, p.id"
    );

    if ($projects === []) {
        return [];
    }

    $projectIds = array_map(static fn (array $project): int => (int) $project['id'], $projects);
    $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
    $statement = db()->prepare(
        "SELECT pm.*, m.public_path AS image_path
         FROM project_members pm
         LEFT JOIN cms_media m ON m.id = pm.media_id
         WHERE pm.project_id IN ({$placeholders}) AND pm.archived_at IS NULL
         ORDER BY pm.project_id, pm.sort_order, pm.id"
    );
    $statement->execute($projectIds);

    $membersByProject = [];
    foreach ($statement->fetchAll() as $member) {
        $membersByProject[(int) $member['project_id']][] = $member;
    }
    foreach ($projects as &$project) {
        $project['members'] = $membersByProject[(int) $project['id']] ?? [];
    }
    unset($project);

    return $projects;
}

function content_public_snapshot(): array
{
    return [
        'blogs' => content_blogs(),
        'certifications' => content_certifications(),
        'events' => content_events(),
        'education' => content_education(),
        'experiences' => content_experiences(),
        'projects' => content_projects(),
        'testimonials' => content_testimonials(),
    ];
}

function content_simple_collection(string $table, bool $includeUnpublished): array
{
    $allowed = ['education_entries', 'work_experiences', 'testimonials'];
    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Unsupported content collection.');
    }

    $where = $includeUnpublished
        ? 'archived_at IS NULL'
        : "publication_status = 'published' AND archived_at IS NULL";

    return content_rows("SELECT * FROM {$table} WHERE {$where} ORDER BY sort_order, id");
}

function content_rows(string $sql, array $parameters = []): array
{
    $statement = db()->prepare($sql);
    $statement->execute($parameters);

    return $statement->fetchAll();
}
