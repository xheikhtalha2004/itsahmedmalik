<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli' || getenv('APP_CONFIG_FILE') === false) {
    fwrite(STDERR, "Set APP_CONFIG_FILE to a disposable test database configuration.\n");
    exit(2);
}

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/content.php';
require_once dirname(__DIR__) . '/src/admin.php';

function integration_expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function integration_expect_conflict(callable $operation, string $message): void
{
    try {
        $operation();
    } catch (RuntimeException $exception) {
        integration_expect(str_contains($exception->getMessage(), 'changed in another tab'), $message);
        return;
    }
    throw new RuntimeException($message);
}

$pdo = db();
$expectedCounts = [
    'blog_posts' => 10,
    'certifications' => 8,
    'events' => 13,
    'event_images' => 102,
    'education_entries' => 4,
    'work_experiences' => 7,
    'projects' => 4,
    'project_members' => 12,
    'testimonials' => 5,
];
foreach ($expectedCounts as $table => $expected) {
    $actual = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    integration_expect($actual === $expected, "{$table}: expected {$expected}, found {$actual}.");
}

$storedBodies = $pdo->query('SELECT body_html FROM blog_posts ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
foreach ($storedBodies as $index => $bodyHtml) {
    integration_expect(
        is_string($bodyHtml) && sanitize_article_html($bodyHtml) === $bodyHtml,
        'Blog post ' . ($index + 1) . ' was not stored as canonical sanitized HTML.',
    );
}

$versions = $pdo->query('SELECT version FROM schema_migrations ORDER BY version')->fetchAll(PDO::FETCH_COLUMN);
foreach (['001_initial.sql', '002_reversible_embedded_content.sql', 'seed:legacy_content_v1'] as $version) {
    integration_expect(in_array($version, $versions, true), "Missing migration marker: {$version}.");
}

$columns = $pdo->query(
    "SELECT CONCAT(TABLE_NAME, '.', COLUMN_NAME)
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND ((TABLE_NAME = 'event_images' AND COLUMN_NAME = 'archived_at')
         OR (TABLE_NAME = 'project_members' AND COLUMN_NAME = 'archived_at'))"
)->fetchAll(PDO::FETCH_COLUMN);
integration_expect(count($columns) === 2, 'Reversible child-content columns are missing.');

$image = $pdo->query(
    "SELECT ei.id, ei.event_id, ei.version image_version, e.version event_version
     FROM event_images ei
     JOIN events e ON e.id = ei.event_id
     WHERE e.publication_status = 'published' AND ei.archived_at IS NULL
       AND (SELECT COUNT(*) FROM event_images siblings WHERE siblings.event_id = ei.event_id AND siblings.archived_at IS NULL) > 1
     ORDER BY ei.event_id, ei.sort_order, ei.id LIMIT 1"
)->fetch();
integration_expect(is_array($image), 'A multi-image published event fixture is required.');
admin_remove_event_image(
    (int) $image['event_id'],
    (int) $image['event_version'],
    (int) $image['id'],
    (int) $image['image_version'],
);
$archived = $pdo->prepare('SELECT archived_at FROM event_images WHERE id = :id');
$archived->execute(['id' => $image['id']]);
integration_expect($archived->fetchColumn() !== null, 'Event image was not archived.');
$archivedVersions = $pdo->prepare(
    'SELECT ei.version image_version, e.version event_version
     FROM event_images ei JOIN events e ON e.id = ei.event_id WHERE ei.id = :id'
);
$archivedVersions->execute(['id' => $image['id']]);
$archivedVersion = $archivedVersions->fetch();
integration_expect(is_array($archivedVersion), 'Archived event image versions are unavailable.');
admin_restore_event_image(
    (int) $image['event_id'],
    (int) $archivedVersion['event_version'],
    (int) $image['id'],
    (int) $archivedVersion['image_version'],
);
$archived->execute(['id' => $image['id']]);
integration_expect($archived->fetchColumn() === null, 'Event image was not restored.');
integration_expect_conflict(
    static fn () => admin_remove_event_image(
        (int) $image['event_id'],
        (int) $archivedVersion['event_version'],
        (int) $image['id'],
        (int) $archivedVersion['image_version'],
    ),
    'A stale event-image archive was not rejected.',
);

$member = $pdo->query(
    'SELECT pm.id, pm.project_id, pm.version member_version, p.version project_version
     FROM project_members pm JOIN projects p ON p.id = pm.project_id
     WHERE pm.archived_at IS NULL ORDER BY pm.id LIMIT 1'
)->fetch();
integration_expect(is_array($member), 'A project-member fixture is required.');
admin_remove_project_member(
    (int) $member['project_id'],
    (int) $member['project_version'],
    (int) $member['id'],
    (int) $member['member_version'],
);
$memberArchived = $pdo->prepare('SELECT archived_at FROM project_members WHERE id = :id');
$memberArchived->execute(['id' => $member['id']]);
integration_expect($memberArchived->fetchColumn() !== null, 'Project member was not archived.');
$archivedMemberVersions = $pdo->prepare(
    'SELECT pm.version member_version, p.version project_version
     FROM project_members pm JOIN projects p ON p.id = pm.project_id WHERE pm.id = :id'
);
$archivedMemberVersions->execute(['id' => $member['id']]);
$archivedMemberVersion = $archivedMemberVersions->fetch();
integration_expect(is_array($archivedMemberVersion), 'Archived project member versions are unavailable.');
admin_restore_project_member(
    (int) $member['project_id'],
    (int) $archivedMemberVersion['project_version'],
    (int) $member['id'],
    (int) $archivedMemberVersion['member_version'],
);
$memberArchived->execute(['id' => $member['id']]);
integration_expect($memberArchived->fetchColumn() === null, 'Project member was not restored.');
integration_expect_conflict(
    static fn () => admin_remove_project_member(
        (int) $member['project_id'],
        (int) $archivedMemberVersion['project_version'],
        (int) $member['id'],
        (int) $archivedMemberVersion['member_version'],
    ),
    'A stale project-member archive was not rejected.',
);
integration_expect_conflict(
    static fn () => admin_add_project_member(
        (int) $member['project_id'],
        (int) $archivedMemberVersion['project_version'],
        ['name' => 'Stale member', 'initials' => 'SM'],
        null,
    ),
    'A stale project-member addition was not rejected.',
);

$blogOrderRows = $pdo->query(
    "SELECT id, version FROM blog_posts WHERE publication_status <> 'archived' ORDER BY sort_order, id LIMIT 2"
)->fetchAll();
integration_expect(count($blogOrderRows) === 2, 'A blog reorder fixture is required.');
[$blogOrder, $blogNeighbor] = $blogOrderRows;
admin_reorder_record(
    'blogs',
    (int) $blogOrder['id'],
    (int) $blogOrder['version'],
    (int) $blogNeighbor['id'],
    (int) $blogNeighbor['version'],
    'down',
);
integration_expect_conflict(
    static fn () => admin_reorder_record(
        'blogs',
        (int) $blogOrder['id'],
        (int) $blogOrder['version'],
        (int) $blogNeighbor['id'],
        (int) $blogNeighbor['version'],
        'down',
    ),
    'A stale top-level reorder was not rejected.',
);
$latestBlogVersion = $pdo->prepare('SELECT id, version FROM blog_posts WHERE id IN (:current_id, :neighbor_id)');
$latestBlogVersion->execute(['current_id' => $blogOrder['id'], 'neighbor_id' => $blogNeighbor['id']]);
$latestBlogVersions = [];
foreach ($latestBlogVersion->fetchAll() as $row) {
    $latestBlogVersions[(int) $row['id']] = (int) $row['version'];
}
admin_reorder_record(
    'blogs',
    (int) $blogOrder['id'],
    $latestBlogVersions[(int) $blogOrder['id']],
    (int) $blogNeighbor['id'],
    $latestBlogVersions[(int) $blogNeighbor['id']],
    'up',
);

$eventOrder = $pdo->query(
    "SELECT ei.id, ei.version image_version, e.id event_id, e.version event_version
     FROM event_images ei JOIN events e ON e.id = ei.event_id
     WHERE ei.archived_at IS NULL
       AND (SELECT COUNT(*) FROM event_images siblings WHERE siblings.event_id = ei.event_id AND siblings.archived_at IS NULL) > 1
     ORDER BY ei.event_id, ei.sort_order, ei.id LIMIT 1"
)->fetch();
integration_expect(is_array($eventOrder), 'An event-image reorder fixture is required.');
admin_reorder_child(
    'event_image',
    (int) $eventOrder['event_id'],
    (int) $eventOrder['event_version'],
    (int) $eventOrder['id'],
    (int) $eventOrder['image_version'],
    'down',
);
integration_expect_conflict(
    static fn () => admin_reorder_child(
        'event_image',
        (int) $eventOrder['event_id'],
        (int) $eventOrder['event_version'],
        (int) $eventOrder['id'],
        (int) $eventOrder['image_version'],
        'down',
    ),
    'A stale event-image reorder was not rejected.',
);
$latestEventOrder = $pdo->prepare(
    'SELECT ei.version image_version, e.version event_version
     FROM event_images ei JOIN events e ON e.id = ei.event_id WHERE ei.id = :id'
);
$latestEventOrder->execute(['id' => $eventOrder['id']]);
$latestEventOrderVersion = $latestEventOrder->fetch();
integration_expect(is_array($latestEventOrderVersion), 'Updated event-image versions are unavailable.');
admin_reorder_child(
    'event_image',
    (int) $eventOrder['event_id'],
    (int) $latestEventOrderVersion['event_version'],
    (int) $eventOrder['id'],
    (int) $latestEventOrderVersion['image_version'],
    'up',
);

$memberOrder = $pdo->query(
    "SELECT pm.id, pm.version member_version, p.id project_id, p.version project_version
     FROM project_members pm JOIN projects p ON p.id = pm.project_id
     WHERE pm.archived_at IS NULL
       AND (SELECT COUNT(*) FROM project_members siblings WHERE siblings.project_id = pm.project_id AND siblings.archived_at IS NULL) > 1
     ORDER BY pm.project_id, pm.sort_order, pm.id LIMIT 1"
)->fetch();
integration_expect(is_array($memberOrder), 'A project-member reorder fixture is required.');
admin_reorder_child(
    'project_member',
    (int) $memberOrder['project_id'],
    (int) $memberOrder['project_version'],
    (int) $memberOrder['id'],
    (int) $memberOrder['member_version'],
    'down',
);
integration_expect_conflict(
    static fn () => admin_reorder_child(
        'project_member',
        (int) $memberOrder['project_id'],
        (int) $memberOrder['project_version'],
        (int) $memberOrder['id'],
        (int) $memberOrder['member_version'],
        'down',
    ),
    'A stale project-member reorder was not rejected.',
);
$latestMemberOrder = $pdo->prepare(
    'SELECT pm.version member_version, p.version project_version
     FROM project_members pm JOIN projects p ON p.id = pm.project_id WHERE pm.id = :id'
);
$latestMemberOrder->execute(['id' => $memberOrder['id']]);
$latestMemberOrderVersion = $latestMemberOrder->fetch();
integration_expect(is_array($latestMemberOrderVersion), 'Updated project-member versions are unavailable.');
admin_reorder_child(
    'project_member',
    (int) $memberOrder['project_id'],
    (int) $latestMemberOrderVersion['project_version'],
    (int) $memberOrder['id'],
    (int) $latestMemberOrderVersion['member_version'],
    'up',
);

$cover = $pdo->query(
    'SELECT e.id event_id, e.version event_version, current_cover.id original_image_id,
            current_cover.version original_image_version, replacement.id image_id, replacement.version image_version
     FROM events e
     JOIN event_images current_cover ON current_cover.event_id = e.id
       AND current_cover.media_id = e.cover_media_id AND current_cover.archived_at IS NULL
     JOIN event_images replacement ON replacement.event_id = e.id
       AND replacement.media_id <> e.cover_media_id AND replacement.archived_at IS NULL
     ORDER BY e.id, replacement.sort_order, replacement.id LIMIT 1'
)->fetch();
integration_expect(is_array($cover), 'An event-cover concurrency fixture is required.');
admin_set_event_cover(
    (int) $cover['event_id'],
    (int) $cover['event_version'],
    (int) $cover['image_id'],
    (int) $cover['image_version'],
);
integration_expect_conflict(
    static fn () => admin_set_event_cover(
        (int) $cover['event_id'],
        (int) $cover['event_version'],
        (int) $cover['image_id'],
        (int) $cover['image_version'],
    ),
    'A stale event-cover selection was not rejected.',
);
integration_expect_conflict(
    static fn () => admin_add_event_images(
        (int) $cover['event_id'],
        (int) $cover['event_version'],
        [],
        [],
        [],
    ),
    'A stale event-image addition was not rejected.',
);
$latestCover = $pdo->prepare(
    'SELECT e.version event_version, ei.version image_version
     FROM events e JOIN event_images ei ON ei.event_id = e.id WHERE e.id = :event_id AND ei.id = :image_id'
);
$latestCover->execute(['event_id' => $cover['event_id'], 'image_id' => $cover['original_image_id']]);
$latestCoverVersion = $latestCover->fetch();
integration_expect(is_array($latestCoverVersion), 'Updated event-cover versions are unavailable.');
admin_set_event_cover(
    (int) $cover['event_id'],
    (int) $latestCoverVersion['event_version'],
    (int) $cover['original_image_id'],
    (int) $latestCoverVersion['image_version'],
);

$mediaId = (int) $pdo->query('SELECT media_id FROM event_images WHERE archived_at IS NULL ORDER BY id LIMIT 1')->fetchColumn();
$temporaryEventId = 0;
try {
    $insertEvent = $pdo->prepare(
        "INSERT INTO events
         (title, caption, cover_media_id, publication_status, sort_order, published_at, version, created_at, updated_at)
         VALUES ('Final-image guard test', NULL, :media_id, 'published', 999999, UTC_TIMESTAMP(), 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())"
    );
    $insertEvent->execute(['media_id' => $mediaId]);
    $temporaryEventId = (int) $pdo->lastInsertId();
    $insertImage = $pdo->prepare(
        "INSERT INTO event_images
         (event_id, media_id, caption, alt_text, sort_order, version, created_at, updated_at)
         VALUES (:event_id, :media_id, NULL, 'Final image guard', 10, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())"
    );
    $insertImage->execute(['event_id' => $temporaryEventId, 'media_id' => $mediaId]);
    $temporaryImageId = (int) $pdo->lastInsertId();

    $blocked = false;
    try {
        admin_remove_event_image($temporaryEventId, 1, $temporaryImageId, 1);
    } catch (InvalidArgumentException $exception) {
        $blocked = str_contains($exception->getMessage(), 'final image');
    }
    integration_expect($blocked, 'Published event accepted removal of its final active image.');
    $stillActive = $pdo->prepare('SELECT archived_at IS NULL FROM event_images WHERE id = :id');
    $stillActive->execute(['id' => $temporaryImageId]);
    integration_expect((int) $stillActive->fetchColumn() === 1, 'Final image guard modified the image.');
} finally {
    if ($temporaryEventId > 0) {
        $delete = $pdo->prepare('DELETE FROM events WHERE id = :id');
        $delete->execute(['id' => $temporaryEventId]);
    }
}

$meetingIds = [];
try {
    $slot = (new DateTimeImmutable('now', new DateTimeZone('Asia/Karachi')))->modify('+2 days')->setTime(10, 0);
    while ((int) $slot->format('N') > 5) {
        $slot = $slot->modify('+1 day');
    }
    $requestedUtc = $slot->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $insertMeeting = $pdo->prepare(
        "INSERT INTO meeting_requests
         (submission_uuid, full_name, email, phone, requested_start_at, status, created_at, updated_at)
         VALUES (UNHEX(REPLACE(UUID(), '-', '')), :name, :email, '+923001234567', :requested, 'pending', UTC_TIMESTAMP(), UTC_TIMESTAMP())"
    );
    foreach ([1, 2] as $number) {
        $insertMeeting->execute([
            'name' => "Approval collision {$number}",
            'email' => "approval-collision-{$number}@example.test",
            'requested' => $requestedUtc,
        ]);
        $meetingIds[] = (int) $pdo->lastInsertId();
    }

    $mailFailedAfterCommit = false;
    try {
        admin_approve_meeting($meetingIds[0], $slot->format('Y-m-d'), $slot->format('H:i'), '');
    } catch (RuntimeException $exception) {
        $mailFailedAfterCommit = str_contains($exception->getMessage(), 'visitor email failed');
    }
    integration_expect($mailFailedAfterCommit, 'Approval SMTP failure was not reported for retry.');
    $firstApproval = $pdo->prepare(
        'SELECT status, approved_start_at, approval_notified_at, approval_notification_error
         FROM meeting_requests WHERE id = :id'
    );
    $firstApproval->execute(['id' => $meetingIds[0]]);
    $approvedMeeting = $firstApproval->fetch();
    integration_expect(is_array($approvedMeeting) && $approvedMeeting['status'] === 'approved', 'Meeting approval was reversed after SMTP failure.');
    integration_expect($approvedMeeting['approved_start_at'] !== null, 'Approved final time was not committed before SMTP.');
    integration_expect($approvedMeeting['approval_notified_at'] === null && $approvedMeeting['approval_notification_error'] !== null, 'Approval mail failure was not retained for Retry.');

    $slotCollision = false;
    try {
        admin_approve_meeting($meetingIds[1], $slot->format('Y-m-d'), $slot->format('H:i'), '');
    } catch (RuntimeException $exception) {
        $slotCollision = str_contains($exception->getMessage(), 'another request');
    }
    integration_expect($slotCollision, 'Two meeting requests were allowed to approve the same final UTC slot.');
    $secondStatus = $pdo->prepare('SELECT status, approved_start_at FROM meeting_requests WHERE id = :id');
    $secondStatus->execute(['id' => $meetingIds[1]]);
    $secondMeeting = $secondStatus->fetch();
    integration_expect(is_array($secondMeeting) && $secondMeeting['status'] === 'pending' && $secondMeeting['approved_start_at'] === null, 'Slot collision changed the second request.');
} finally {
    if ($meetingIds !== []) {
        $placeholders = implode(',', array_fill(0, count($meetingIds), '?'));
        $deleteMeetings = $pdo->prepare("DELETE FROM meeting_requests WHERE id IN ({$placeholders})");
        $deleteMeetings->execute($meetingIds);
    }
}

foreach ($expectedCounts as $table => $expected) {
    $actual = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    integration_expect($actual === $expected, "{$table} did not return to its seeded count after tests.");
}

fwrite(STDOUT, "MariaDB migration, seed, concurrency, archive/restore, meeting-slot, and mail-failure checks passed.\n");
