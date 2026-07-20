<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/uploads.php';
require_once dirname(__DIR__) . '/src/admin.php';

admin_start();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        $redirect = admin_handle_post() ?? '/admin/';
    } catch (Throwable $exception) {
        error_log('Admin action failed: ' . $exception->getMessage());
        admin_flash('error', 'The action could not be completed. Check the server log and try again.');
        $redirect = '/admin/';
    }
    header('Location: ' . $redirect, true, 303);
    exit;
}

$allowedSections = array_merge(['dashboard', 'contacts', 'meetings', 'newsletter', 'settings'], array_keys(admin_modules()));
$section = (string) ($_GET['section'] ?? 'dashboard');
if (!in_array($section, $allowedSections, true)) {
    $section = 'dashboard';
}
$view = (string) ($_GET['view'] ?? 'list');
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
$before = filter_var($_GET['before'] ?? null, FILTER_VALIDATE_INT) ?: null;
$flash = admin_take_flash();
$trixAvailable = is_file(__DIR__ . '/assets/vendor/trix/trix.js') && is_file(__DIR__ . '/assets/vendor/trix/trix.css');

function admin_hidden(string $action, string $section, int $id = 0, ?int $version = null): void
{
    echo '<input type="hidden" name="csrf_token" value="' . e(admin_csrf_token()) . '">';
    echo '<input type="hidden" name="action" value="' . e($action) . '">';
    echo '<input type="hidden" name="section" value="' . e($section) . '">';
    if ($id > 0) {
        echo '<input type="hidden" name="id" value="' . $id . '">';
    }
    if ($version !== null) {
        echo '<input type="hidden" name="version" value="' . $version . '">';
    }
}

function admin_pkt_parts(?string $utc): array
{
    if (!$utc) {
        return ['', ''];
    }
    $date = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
    $date = $date->setTimezone(new DateTimeZone('Asia/Karachi'));
    return [$date->format('Y-m-d'), $date->format('H:i')];
}

function admin_pkt_display(?string $utc): string
{
    if (!$utc) {
        return '—';
    }
    return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('Asia/Karachi'))
        ->format('M j, Y · H:i') . ' PKT';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="trix-csp-nonce" content="<?= e(admin_csp_nonce()) ?>">
    <title><?= e(($section === 'dashboard' ? 'Dashboard' : (admin_modules()[$section]['label'] ?? ucfirst($section))) . ' · Ahmed Malik Admin') ?></title>
    <link rel="stylesheet" href="/admin/assets/admin.css?v=1">
    <?php if ($trixAvailable): ?><link rel="stylesheet" href="/admin/assets/vendor/trix/trix.css?v=2.1.18"><?php endif; ?>
    <script src="/admin/assets/editor.js?v=1" defer></script>
    <?php if ($trixAvailable): ?><script src="/admin/assets/vendor/trix/trix.js?v=2.1.18" defer></script><?php endif; ?>
</head>
<body>
<a class="skip-link" href="#admin-main">Skip to content</a>
<div class="admin-shell">
    <aside class="sidebar" aria-label="Admin navigation">
        <a class="brand" href="/admin/">
            <span class="brand-mark">AM</span>
            <span><strong>Ahmed Malik</strong><small>Content studio</small></span>
        </a>
        <nav>
            <p class="nav-label">Overview</p>
            <a class="<?= $section === 'dashboard' ? 'active' : '' ?>" href="/admin/">Dashboard</a>
            <p class="nav-label">Content</p>
            <?php foreach (admin_modules() as $key => $module): ?>
                <a class="<?= $section === $key ? 'active' : '' ?>" href="/admin/?section=<?= e($key) ?>"><?= e($module['label']) ?></a>
            <?php endforeach; ?>
            <p class="nav-label">Inbox</p>
            <a class="<?= $section === 'contacts' ? 'active' : '' ?>" href="/admin/?section=contacts">Contacts</a>
            <a class="<?= $section === 'meetings' ? 'active' : '' ?>" href="/admin/?section=meetings">Meetings</a>
            <a class="<?= $section === 'newsletter' ? 'active' : '' ?>" href="/admin/?section=newsletter">Newsletter</a>
            <p class="nav-label">System</p>
            <a class="<?= $section === 'settings' ? 'active' : '' ?>" href="/admin/?section=settings">Settings</a>
        </nav>
        <a class="view-site" href="/" target="_blank" rel="noopener">View website ↗</a>
    </aside>

    <main id="admin-main" class="main">
        <?php if ($flash): ?>
            <div class="flash <?= e($flash['kind']) ?>" role="status"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php try { ?>
            <?php if ($section === 'dashboard'): ?>
                <?php $counts = admin_dashboard_counts(); ?>
                <header class="page-header">
                    <div><p class="eyebrow">Private workspace</p><h1>Dashboard</h1><p>Publish content and handle new enquiries without touching the website layout.</p></div>
                </header>
                <section class="metric-grid" aria-label="Portfolio overview">
                    <?php foreach ([
                        'published' => ['Published', 'Live content'],
                        'drafts' => ['Drafts', 'Waiting for review'],
                        'contacts' => ['New contacts', 'Needs a response'],
                        'meetings' => ['Pending meetings', 'Needs a decision'],
                        'subscribers' => ['Subscribers', 'Newsletter list'],
                        'mail_failures' => ['Mail failures', 'Needs attention'],
                    ] as $metric => [$label, $detail]): ?>
                        <article class="metric <?= $metric === 'mail_failures' && ($counts[$metric] ?? 0) > 0 ? 'warning' : '' ?>">
                            <span><?= e($label) ?></span><strong><?= (int) ($counts[$metric] ?? 0) ?></strong><small><?= e($detail) ?></small>
                        </article>
                    <?php endforeach; ?>
                </section>
                <section class="panel quick-links">
                    <div class="panel-heading"><div><h2>Quick actions</h2><p>Start with a draft. Nothing appears publicly until you publish it.</p></div></div>
                    <div class="button-row">
                        <a class="button primary" href="/admin/?section=blogs&view=edit">New blog</a>
                        <a class="button" href="/admin/?section=events&view=edit">New event</a>
                        <a class="button" href="/admin/?section=certifications&view=edit">New certification</a>
                        <a class="button" href="/admin/?section=meetings">Review meetings</a>
                    </div>
                </section>

            <?php elseif (isset(admin_modules()[$section])): ?>
                <?php $module = admin_module($section); ?>
                <?php if ($view === 'edit'): ?>
                    <?php
                    $record = $id > 0 ? admin_find_record($section, $id) : null;
                    if ($id > 0 && !$record) {
                        throw new RuntimeException('The requested record no longer exists.');
                    }
                    ?>
                    <header class="page-header">
                        <div><p class="eyebrow"><?= e($module['label']) ?></p><h1><?= $record ? 'Edit ' . e($module['singular']) : 'New ' . e($module['singular']) ?></h1><p>Content changes stay inside the website’s fixed design system.</p></div>
                        <a class="button" href="/admin/?section=<?= e($section) ?>">Back to list</a>
                    </header>

                    <form class="panel editor-form" action="/admin/" method="post" enctype="multipart/form-data" data-disable-on-submit>
                        <?php admin_hidden('save_record', $section, $id, $record ? (int) $record['version'] : null); ?>
                        <div class="field-grid">
                            <?php foreach ($module['fields'] as $column => $field): ?>
                                <?php $value = (string) ($record[$column] ?? ''); $wide = in_array($field['type'], ['textarea', 'rich'], true); ?>
                                <label class="field <?= $wide ? 'wide' : '' ?>">
                                    <span><?= e($field['label']) ?><?= ($field['required'] ?? false) ? ' *' : '' ?></span>
                                    <?php if ($field['type'] === 'rich' && $trixAvailable): ?>
                                        <input type="hidden" id="rich-<?= e($column) ?>" name="<?= e($column) ?>" value="<?= e($value) ?>">
                                        <trix-editor input="rich-<?= e($column) ?>" data-rich-editor></trix-editor>
                                        <small>Allowed formatting: paragraphs, H2/H3, lists, bold, italic, quotes, and HTTP(S) links. File attachments are disabled.</small>
                                    <?php elseif ($field['type'] === 'rich'): ?>
                                        <input type="hidden" name="<?= e($column) ?>" value="<?= e($value) ?>">
                                        <p class="field-error" role="alert">The rich-text editor is unavailable. Restore the pinned Trix assets before editing this article.</p>
                                    <?php elseif ($field['type'] === 'textarea'): ?>
                                        <textarea name="<?= e($column) ?>" rows="5" <?= ($field['required'] ?? false) ? 'required' : '' ?>><?= e($value) ?></textarea>
                                    <?php elseif ($field['type'] === 'select'): ?>
                                        <select name="<?= e($column) ?>" required>
                                            <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                                                <option value="<?= e($optionValue) ?>" <?= $value === $optionValue ? 'selected' : '' ?>><?= e($optionLabel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input type="<?= e($field['type'] === 'integer' ? 'number' : $field['type']) ?>" name="<?= e($column) ?>" value="<?= e($value) ?>"
                                            <?= ($field['required'] ?? false) ? 'required' : '' ?>
                                            <?= isset($field['max']) ? 'maxlength="' . (int) $field['max'] . '"' : '' ?>
                                            <?= isset($field['min']) ? 'min="' . (int) $field['min'] . '"' : '' ?>
                                            <?= $field['type'] === 'integer' ? 'max="' . (int) $field['max'] . '"' : '' ?>>
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; ?>

                            <?php if (isset($module['media'])): ?>
                                <label class="field wide">
                                    <span><?= e($module['media']['label']) ?><?= !$record ? ' *' : '' ?></span>
                                    <?php if (($record['media_path'] ?? '') !== ''): ?>
                                        <img class="media-preview" src="<?= e('/' . ltrim((string) $record['media_path'], '/')) ?>" alt="Current image">
                                    <?php endif; ?>
                                    <input type="file" name="<?= e($module['media']['input']) ?>" accept="image/jpeg,image/png,image/webp" <?= !$record ? 'required' : '' ?>>
                                    <small>JPEG, PNG, or WebP; maximum 10 MiB and 30 megapixels. The server creates a safe WebP.</small>
                                </label>
                            <?php endif; ?>
                        </div>
                        <div class="form-actions"><button class="button primary" type="submit">Save draft changes</button></div>
                    </form>

                    <?php if ($record): ?>
                        <section class="panel status-panel">
                            <div><h2>Publication</h2><p>Current status: <span class="status <?= e($record['publication_status']) ?>"><?= e($record['publication_status']) ?></span></p></div>
                            <div class="button-row">
                                <?php if (($module['preview'] ?? false)): ?><a class="button" href="/admin/preview.php?type=<?= e($section) ?>&id=<?= $id ?>" target="_blank" rel="noopener">Preview</a><?php endif; ?>
                                <?php foreach (
                                    $record['publication_status'] === 'archived' ? ['restore' => 'Restore as draft'] :
                                    ($record['publication_status'] === 'published' ? ['unpublish' => 'Unpublish', 'archive' => 'Archive'] : ['publish' => 'Publish', 'archive' => 'Archive'])
                                    as $action => $label
                                ): ?>
                                    <form action="/admin/" method="post" data-disable-on-submit>
                                        <?php admin_hidden($action, $section, $id, (int) $record['version']); ?>
                                        <button class="button <?= $action === 'publish' ? 'primary' : '' ?>" type="submit"><?= e($label) ?></button>
                                    </form>
                                <?php endforeach; ?>
                                <form action="/admin/" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this <?= e(strtolower($module['singular'])) ?>? This action is irreversible and cannot be undone.');" data-disable-on-submit>
                                    <?php admin_hidden('delete_record', $section, $id, (int) $record['version']); ?>
                                    <button class="button danger-button" type="submit">Delete</button>
                                </form>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ($record && $section === 'events'): ?>
                        <?php
                            $allImages = admin_event_images($id, true);
                            $images = array_values(array_filter($allImages, static fn (array $image): bool => $image['archived_at'] === null));
                            $archivedImages = array_values(array_filter($allImages, static fn (array $image): bool => $image['archived_at'] !== null));
                        ?>
                        <section class="panel">
                            <div class="panel-heading"><div><h2>Event gallery</h2><p>Reorder images and select the cover without changing the public gallery layout.</p></div></div>
                            <?php if ($images): ?><div class="media-grid">
                                <?php foreach ($images as $image): ?>
                                    <article class="media-card">
                                        <img src="<?= e('/' . ltrim((string) $image['public_path'], '/')) ?>" alt="<?= e($image['alt_text']) ?>">
                                        <form class="media-meta-form" method="post" action="/admin/" data-disable-on-submit>
                                            <?php admin_hidden('event_update_image', 'events', $id); ?>
                                            <input type="hidden" name="parent_version" value="<?= (int) $record['version'] ?>">
                                            <input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>">
                                            <input type="hidden" name="image_version" value="<?= (int) $image['version'] ?>">
                                            <label><span>Alt text *</span><input name="alt_text" value="<?= e($image['alt_text']) ?>" maxlength="200" required></label>
                                            <label><span>Caption</span><input name="caption" value="<?= e((string) ($image['caption'] ?? '')) ?>" maxlength="500"></label>
                                            <button class="text-button" type="submit">Update details</button>
                                        </form>
                                        <div class="compact-actions">
                                            <?php foreach (['up' => '↑', 'down' => '↓'] as $direction => $label): ?><form method="post" action="/admin/"><?php admin_hidden('move_event_image', 'events', $id); ?><input type="hidden" name="parent_version" value="<?= (int) $record['version'] ?>"><input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>"><input type="hidden" name="image_version" value="<?= (int) $image['version'] ?>"><input type="hidden" name="direction" value="<?= $direction ?>"><button class="icon-button" type="submit" aria-label="Move image <?= $direction ?>"><?= $label ?></button></form><?php endforeach; ?>
                                            <?php if ((int) $record['cover_media_id'] !== (int) $image['media_id']): ?><form method="post" action="/admin/"><?php admin_hidden('event_set_cover', 'events', $id); ?><input type="hidden" name="parent_version" value="<?= (int) $record['version'] ?>"><input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>"><input type="hidden" name="image_version" value="<?= (int) $image['version'] ?>"><button class="text-button" type="submit">Make cover</button></form><?php else: ?><span class="cover-label">Cover</span><?php endif; ?>
                                            <form method="post" action="/admin/" data-confirm="Archive this image? It can be restored later and its media file will be retained."><?php admin_hidden('event_remove_image', 'events', $id); ?><input type="hidden" name="parent_version" value="<?= (int) $record['version'] ?>"><input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>"><input type="hidden" name="image_version" value="<?= (int) $image['version'] ?>"><button class="text-button danger" type="submit">Archive</button></form>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div><?php else: ?><p class="empty">No gallery images yet.</p><?php endif; ?>
                            <?php if ($archivedImages): ?><details class="archived-items"><summary>Archived images (<?= count($archivedImages) ?>)</summary><div class="media-grid">
                                <?php foreach ($archivedImages as $image): ?><article class="media-card">
                                    <img src="<?= e('/' . ltrim((string) $image['public_path'], '/')) ?>" alt="<?= e($image['alt_text']) ?>">
                                    <p><?= e((string) ($image['caption'] ?? $image['alt_text'])) ?></p>
                                    <form method="post" action="/admin/"><?php admin_hidden('event_restore_image', 'events', $id); ?><input type="hidden" name="parent_version" value="<?= (int) $record['version'] ?>"><input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>"><input type="hidden" name="image_version" value="<?= (int) $image['version'] ?>"><button class="text-button" type="submit">Restore</button></form>
                                </article><?php endforeach; ?>
                            </div></details><?php endif; ?>
                        </section>
                        <form class="panel" action="/admin/" method="post" enctype="multipart/form-data" data-disable-on-submit>
                            <?php admin_hidden('event_add_images', 'events', $id); ?>
                            <input type="hidden" name="parent_version" value="<?= (int) $record['version'] ?>">
                            <h2>Add gallery images</h2>
                            <label class="field"><span>Images (maximum 12)</span><input type="file" name="event_images[]" accept="image/jpeg,image/png,image/webp" multiple required data-event-files></label>
                            <div data-event-metadata><p class="muted">Choose images to enter individual alt text and captions.</p></div>
                            <button class="button primary" type="submit">Upload images</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($record && $section === 'projects'): ?>
                        <?php
                            $allMembers = admin_project_members($id, true);
                            $members = array_values(array_filter($allMembers, static fn (array $member): bool => $member['archived_at'] === null));
                            $archivedMembers = array_values(array_filter($allMembers, static fn (array $member): bool => $member['archived_at'] !== null));
                        ?>
                        <section class="panel">
                            <div class="panel-heading"><div><h2>Project members</h2><p>Ordered contributors displayed in the existing project card.</p></div></div>
                            <?php if ($members): ?><div class="member-list">
                                <?php foreach ($members as $member): ?><article class="member-row">
                                    <?php if ($member['public_path']): ?><img src="<?= e($member['public_path']) ?>" alt=""><?php else: ?><span class="member-avatar"><?= e($member['initials']) ?></span><?php endif; ?>
                                    <form class="member-edit" method="post" action="/admin/" data-disable-on-submit>
                                        <?php admin_hidden('project_update_member', 'projects', $id); ?>
                                        <input type="hidden" name="parent_version" value="<?= (int) $record['version'] ?>">
                                        <input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>"><input type="hidden" name="member_version" value="<?= (int) $member['version'] ?>">
                                        <input name="name" value="<?= e($member['name']) ?>" maxlength="100" aria-label="Member name" required>
                                        <input name="initials" value="<?= e($member['initials']) ?>" maxlength="10" aria-label="Member initials" required>
                                        <button class="text-button" type="submit">Update</button>
                                    </form>
                                    <div class="compact-actions"><?php foreach (['up' => '↑', 'down' => '↓'] as $direction => $label): ?><form method="post" action="/admin/"><?php admin_hidden('move_project_member', 'projects', $id); ?><input type="hidden" name="parent_version" value="<?= (int) $record['version'] ?>"><input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>"><input type="hidden" name="member_version" value="<?= (int) $member['version'] ?>"><input type="hidden" name="direction" value="<?= $direction ?>"><button class="icon-button" type="submit" aria-label="Move member <?= $direction ?>"><?= $label ?></button></form><?php endforeach; ?><form method="post" action="/admin/" data-confirm="Archive this member? They can be restored later."><?php admin_hidden('project_remove_member', 'projects', $id); ?><input type="hidden" name="parent_version" value="<?= (int) $record['version'] ?>"><input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>"><input type="hidden" name="member_version" value="<?= (int) $member['version'] ?>"><button class="text-button danger" type="submit">Archive</button></form></div>
                                </article><?php endforeach; ?>
                            </div><?php else: ?><p class="empty">No members yet.</p><?php endif; ?>
                            <?php if ($archivedMembers): ?><details class="archived-items"><summary>Archived members (<?= count($archivedMembers) ?>)</summary><div class="member-list">
                                <?php foreach ($archivedMembers as $member): ?><article class="member-row">
                                    <?php if ($member['public_path']): ?><img src="<?= e($member['public_path']) ?>" alt=""><?php else: ?><span class="member-avatar"><?= e($member['initials']) ?></span><?php endif; ?>
                                    <strong><?= e($member['name']) ?></strong>
                                    <form method="post" action="/admin/"><?php admin_hidden('project_restore_member', 'projects', $id); ?><input type="hidden" name="parent_version" value="<?= (int) $record['version'] ?>"><input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>"><input type="hidden" name="member_version" value="<?= (int) $member['version'] ?>"><button class="text-button" type="submit">Restore</button></form>
                                </article><?php endforeach; ?>
                            </div></details><?php endif; ?>
                        </section>
                        <form class="panel compact-form" action="/admin/" method="post" enctype="multipart/form-data" data-disable-on-submit>
                            <?php admin_hidden('project_add_member', 'projects', $id); ?>
                            <input type="hidden" name="parent_version" value="<?= (int) $record['version'] ?>">
                            <h2>Add project member</h2>
                            <label class="field"><span>Name *</span><input name="name" maxlength="100" required></label>
                            <label class="field"><span>Initials *</span><input name="initials" maxlength="10" required></label>
                            <label class="field"><span>Photo (optional)</span><input type="file" name="member_image" accept="image/jpeg,image/png,image/webp"></label>
                            <button class="button primary" type="submit">Add member</button>
                        </form>
                    <?php endif; ?>

                <?php else: ?>
                    <?php
                        $records = admin_list_records($section);
                        $activeRecords = array_values(array_filter(
                            $records,
                            static fn (array $item): bool => $item['publication_status'] !== 'archived',
                        ));
                        $activePositions = [];
                        foreach ($activeRecords as $position => $item) {
                            $activePositions[(int) $item['id']] = $position;
                        }
                    ?>
                    <header class="page-header">
                        <div><p class="eyebrow">Content</p><h1><?= e($module['label']) ?></h1><p>Draft, publish, reorder, or archive entries while the public layout stays fixed.</p></div>
                        <a class="button primary" href="/admin/?section=<?= e($section) ?>&view=edit">New <?= e(strtolower($module['singular'])) ?></a>
                    </header>
                    <section class="panel list-panel">
                        <?php if (!$records): ?><p class="empty">No entries yet. Create the first draft.</p><?php else: ?>
                            <div class="record-list">
                                <?php foreach ($records as $record): ?>
                                    <article class="record-row">
                                        <div class="record-order">
                                            <?php if ($record['publication_status'] !== 'archived'): ?>
                                                <?php foreach (['up' => ['↑', -1], 'down' => ['↓', 1]] as $direction => [$label, $offset]): ?>
                                                    <?php $neighbor = $activeRecords[$activePositions[(int) $record['id']] + $offset] ?? null; ?>
                                                    <?php if ($neighbor): ?><form method="post" action="/admin/">
                                                        <?php admin_hidden('move_record', $section, (int) $record['id'], (int) $record['version']); ?>
                                                        <input type="hidden" name="neighbor_id" value="<?= (int) $neighbor['id'] ?>">
                                                        <input type="hidden" name="neighbor_version" value="<?= (int) $neighbor['version'] ?>">
                                                        <input type="hidden" name="direction" value="<?= $direction ?>">
                                                        <button class="icon-button" type="submit" aria-label="Move <?= e((string) $record['display_title']) ?> <?= $direction ?>"><?= $label ?></button>
                                                    </form><?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="record-main"><strong><?= e($record['display_title']) ?></strong><small>Updated <?= e((string) $record['updated_at']) ?> UTC</small></div>
                                        <span class="status <?= e($record['publication_status']) ?>"><?= e($record['publication_status']) ?></span>
                                        <a class="button small" href="/admin/?section=<?= e($section) ?>&view=edit&id=<?= (int) $record['id'] ?>">Edit</a>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

            <?php elseif ($section === 'contacts'): ?>
                <?php $rows = admin_list_contacts($before); $hasMore = count($rows) > 25; $rows = array_slice($rows, 0, 25); ?>
                <header class="page-header"><div><p class="eyebrow">Inbox</p><h1>Contacts</h1><p>Messages are stored before notification email is attempted.</p></div></header>
                <section class="inbox-list">
                    <?php if (!$rows): ?><div class="panel empty">No contact messages found.</div><?php endif; ?>
                    <?php foreach ($rows as $row): ?><article class="panel inbox-card">
                        <div class="inbox-heading"><div><h2><?= e($row['full_name']) ?></h2><a href="mailto:<?= e($row['email']) ?>"><?= e($row['email']) ?></a></div><span class="status <?= e($row['status']) ?>"><?= e($row['status']) ?></span></div>
                        <dl><div><dt>Phone</dt><dd><?= e($row['phone'] ?: '—') ?></dd></div><div><dt>Service</dt><dd><?= e($row['service_code']) ?></dd></div><div><dt>Received</dt><dd><?= e($row['created_at']) ?> UTC</dd></div></dl>
                        <p class="message-body"><?= nl2br(e($row['message'])) ?></p>
                        <?php if ($row['admin_notification_error']): ?><p class="error-note">Admin email failed: <?= e($row['admin_notification_error']) ?></p><?php endif; ?>
                        <div style="margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 1rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                            <?php if ($row['status'] === 'new'): ?>
                                <form method="post" action="/admin/" style="margin: 0;">
                                    <?php admin_hidden('contact_handled', 'contacts', (int) $row['id']); ?>
                                    <button class="button primary small" type="submit">Mark handled</button>
                                </form>
                            <?php endif; ?>
                            <details class="reply-details" style="width: 100%;">
                                <summary class="button small">Reply to <?= e($row['full_name']) ?></summary>
                                <form method="post" action="/admin/" style="margin-top: 0.75rem;" data-disable-on-submit>
                                    <?php admin_hidden('contact_reply', 'contacts', (int) $row['id']); ?>
                                    <div class="field-grid" style="grid-template-columns: 1fr;">
                                        <label class="field">
                                            <span>Subject *</span>
                                            <input type="text" name="reply_subject" value="Re: Portfolio Inquiry" required>
                                        </label>
                                        <label class="field">
                                            <span>Message *</span>
                                            <textarea name="reply_message" rows="4" placeholder="Hi <?= e($row['full_name']) ?>..." required></textarea>
                                        </label>
                                    </div>
                                    <div style="margin-top: 0.75rem;">
                                        <button class="button primary small" type="submit">Send email reply</button>
                                    </div>
                                </form>
                            </details>
                        </div>
                    </article><?php endforeach; ?>
                </section>
                <?php if ($hasMore && $rows): ?><a class="button" href="/admin/?section=contacts&before=<?= (int) end($rows)['id'] ?>">Older messages</a><?php endif; ?>

            <?php elseif ($section === 'meetings'): ?>
                <?php $rows = admin_list_meetings($before); $hasMore = count($rows) > 25; $rows = array_slice($rows, 0, 25); ?>
                <header class="page-header"><div><p class="eyebrow">Inbox</p><h1>Meetings</h1><p>Approve a final PKT time after coordinating with the visitor.</p></div></header>
                <section class="inbox-list">
                    <?php if (!$rows): ?><div class="panel empty">No meeting requests found.</div><?php endif; ?>
                    <?php foreach ($rows as $row): ?><?php [$defaultDate, $defaultTime] = admin_pkt_parts($row['approved_start_at'] ?: $row['requested_start_at']); ?>
                        <article class="panel inbox-card">
                            <div class="inbox-heading"><div><h2><?= e($row['full_name']) ?></h2><a href="mailto:<?= e($row['email']) ?>"><?= e($row['email']) ?></a><p><?= e($row['phone']) ?></p></div><span class="status <?= e($row['status']) ?>"><?= e($row['status']) ?></span></div>
                            <dl><div><dt>Requested</dt><dd><?= e(admin_pkt_display($row['requested_start_at'])) ?></dd></div><div><dt>Approved</dt><dd><?= e(admin_pkt_display($row['approved_start_at'])) ?></dd></div><div><dt>Received</dt><dd><?= e($row['created_at']) ?> UTC</dd></div></dl>
                            <?php if ($row['request_notification_error']): ?><p class="error-note">Request alert failed: <?= e($row['request_notification_error']) ?></p><?php endif; ?>
                            <?php if ($row['approval_notification_error']): ?><p class="error-note">Approval email failed: <?= e($row['approval_notification_error']) ?></p><?php endif; ?>
                            <?php if ($row['status'] === 'pending'): ?><form class="meeting-actions" method="post" action="/admin/" onsubmit="return handleMeetingApproval(this);" data-disable-on-submit>
                                <?php admin_hidden('meeting_approve', 'meetings', (int) $row['id']); ?>
                                <label class="field"><span>Final date *</span><input type="date" name="final_date" value="<?= e($defaultDate) ?>" required></label>
                                <label class="field"><span>Final time (PKT) *</span><input type="time" name="final_time" value="<?= e($defaultTime) ?>" step="1800" required></label>
                                <label class="field wide"><span>Private note</span><textarea name="admin_note" maxlength="500" rows="2"><?= e((string) $row['admin_note']) ?></textarea></label>
                                <button class="button primary" type="submit">Approve and email</button>
                                <button class="button danger-button" type="submit" name="action" value="meeting_reject" data-confirm-button="Reject this meeting request?">Reject</button>
                            </form><?php elseif ($row['status'] === 'approved' && (!$row['approval_notified_at'] || $row['approval_notification_error'])): ?><form method="post" action="/admin/"><?php admin_hidden('meeting_retry_email', 'meetings', (int) $row['id']); ?><button class="button" type="submit">Retry approval email</button></form><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </section>
                <?php if ($hasMore && $rows): ?><a class="button" href="/admin/?section=meetings&before=<?= (int) end($rows)['id'] ?>">Older requests</a><?php endif; ?>

            <?php elseif ($section === 'newsletter'): ?>
                <?php $rows = admin_list_subscribers($before); $hasMore = count($rows) > 25; $rows = array_slice($rows, 0, 25); ?>
                <header class="page-header"><div><p class="eyebrow">Inbox</p><h1>Newsletter</h1><p>Subscribers are stored once; repeat submissions update their last-seen time.</p></div><form method="post" action="/admin/"><?php admin_hidden('newsletter_export', 'newsletter'); ?><button class="button primary" type="submit">Export CSV</button></form></header>
                
                <section class="panel" style="margin-bottom: 2rem;">
                    <div class="panel-heading">
                        <div>
                            <h2>Compose &amp; send newsletter</h2>
                            <p>This will send a generic email notification to all active subscribers.</p>
                        </div>
                    </div>
                    <form action="/admin/" method="post" onsubmit="return confirm('Are you sure you want to send this newsletter to all active subscribers?');" data-disable-on-submit>
                        <?php admin_hidden('send_bulk_newsletter', 'newsletter'); ?>
                        <div class="field-grid">
                            <label class="field wide">
                                <span>Email Subject *</span>
                                <input type="text" name="newsletter_subject" placeholder="Ahmed Malik's Latest Update" required>
                            </label>
                            <label class="field wide">
                                <span>Message Content *</span>
                                <textarea name="newsletter_message" rows="5" placeholder="Write your non-robotic message here..." required></textarea>
                            </label>
                        </div>
                        <div style="margin-top: 1rem;">
                            <button class="button primary" type="submit">Send to all subscribers</button>
                        </div>
                    </form>
                </section>

                <section class="panel table-wrap"><table><thead><tr><th>Email</th><th>Source</th><th>Status</th><th>First subscribed</th><th>Last submitted</th></tr></thead><tbody>
                    <?php foreach ($rows as $row): ?><tr><td><?= e($row['email']) ?></td><td><?= e($row['source_path']) ?></td><td><span class="status <?= e($row['status']) ?>"><?= e($row['status']) ?></span></td><td><?= e($row['first_subscribed_at']) ?> UTC</td><td><?= e($row['last_submitted_at']) ?> UTC</td></tr><?php endforeach; ?>
                    <?php if (!$rows): ?><tr><td colspan="5" class="empty">No subscribers found.</td></tr><?php endif; ?>
                </tbody></table></section>
                <?php if ($hasMore && $rows): ?><a class="button" href="/admin/?section=newsletter&before=<?= (int) end($rows)['id'] ?>">Older subscribers</a><?php endif; ?>

            <?php elseif ($section === 'settings'): ?>
                <header class="page-header">
                    <div><p class="eyebrow">System</p><h1>Settings</h1><p>Edit contact information, links, and details displayed on the website.</p></div>
                </header>
                <form class="panel editor-form" action="/admin/" method="post" data-disable-on-submit>
                    <?php admin_hidden('save_settings', 'settings'); ?>
                    <div class="field-grid">
                        <label class="field wide">
                            <span>Contact Page Emails (one per line) *</span>
                            <textarea name="contact_emails" rows="3" required><?= e(portfolio_setting('contact_emails', 'hello@itsahmedmalik.com')) ?></textarea>
                            <small>You can enter multiple email addresses here.</small>
                        </label>
                        <label class="field">
                            <span>Contact Phone *</span>
                            <input type="text" name="contact_phone" value="<?= e(portfolio_setting('contact_phone', '+92 315 5320243')) ?>" required>
                        </label>
                        <label class="field">
                            <span>Contact Location *</span>
                            <input type="text" name="contact_location" value="<?= e(portfolio_setting('contact_location', 'Islamabad, Pakistan')) ?>" required>
                        </label>
                        <label class="field wide">
                            <span>Instagram Link *</span>
                            <input type="url" name="social_instagram" value="<?= e(portfolio_setting('social_instagram', 'https://www.instagram.com/ahmedmalik.co?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==')) ?>" required>
                        </label>
                        <label class="field wide">
                            <span>LinkedIn Link *</span>
                            <input type="url" name="social_linkedin" value="<?= e(portfolio_setting('social_linkedin', 'https://www.linkedin.com/in/ahmed-malik-9b818a2b4/')) ?>" required>
                        </label>
                        <label class="field wide">
                            <span>Facebook Link *</span>
                            <input type="url" name="social_facebook" value="<?= e(portfolio_setting('social_facebook', 'https://www.facebook.com/share/1Qhjua7uVT/?mibextid=wwXIfr')) ?>" required>
                        </label>
                    </div>
                    <div class="form-actions"><button class="button primary" type="submit">Save dynamic settings</button></div>
                </form>
            <?php endif; ?>
        <?php } catch (Throwable $exception) { ?>
            <?php error_log('Admin page failed: ' . $exception->getMessage()); ?>
            <section class="panel setup-error"><p class="eyebrow">Setup required</p><h1>Admin data is unavailable</h1><p>Run the database migrations and verify the private Hostinger configuration. Details were written to the PHP error log.</p></section>
        <?php } ?>
    </main>
</div>
<script>
function handleMeetingApproval(form) {
    const submitter = document.activeElement;
    if (submitter && submitter.getAttribute('name') === 'action' && submitter.getAttribute('value') === 'meeting_reject') {
        return confirm('Reject this meeting request?');
    }
    
    const message = prompt('Enter a custom confirmation email message to send to the visitor (or click OK/leave empty to send the default automated email):', '');
    if (message === null) {
        return false;
    }
    
    let input = form.querySelector('input[name="custom_message"]');
    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'custom_message';
        form.appendChild(input);
    }
    input.value = message;
    return true;
}
</script>
</body>
</html>
