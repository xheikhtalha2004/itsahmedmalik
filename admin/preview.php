<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/admin.php';

admin_start();

$type = (string) ($_GET['type'] ?? '');
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
try {
    $module = admin_module($type);
    $record = $id > 0 ? admin_find_record($type, $id) : null;
} catch (Throwable) {
    $module = [];
    $record = null;
}
if (!$record) {
    http_response_code(404);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title><?= $record ? e((string) $record[$module['title']]) . ' · Protected preview' : 'Preview unavailable' ?></title>
    <link rel="stylesheet" href="/admin/assets/admin.css?v=1">
</head>
<body class="preview-body">
<header class="preview-bar">
    <div><strong>Protected preview</strong><span>This content is <?= $record ? e($record['publication_status']) : 'unavailable' ?> and visible only to the authenticated administrator.</span></div>
    <a class="button" href="<?= $record ? '/admin/?section=' . e($type) . '&view=edit&id=' . $id : '/admin/' ?>">Back to admin</a>
</header>
<main class="preview-page">
    <?php if (!$record): ?>
        <section class="panel setup-error"><h1>Preview unavailable</h1><p>The record does not exist.</p></section>
    <?php else: ?>
        <article class="content-preview">
            <p class="eyebrow"><?= e($module['singular']) ?> preview</p>
            <h1><?= e((string) $record[$module['title']]) ?></h1>
            <?php if (($record['media_path'] ?? '') !== ''): ?><img class="preview-cover" src="<?= e($record['media_path']) ?>" alt="<?= e((string) ($record['cover_alt'] ?? $record['image_alt'] ?? '')) ?>"><?php endif; ?>
            <?php if ($type === 'blogs'): ?>
                <p class="preview-byline">By <?= e($record['author']) ?> · <?= e($record['published_on']) ?></p>
                <p class="preview-lead"><?= e($record['excerpt']) ?></p>
                <div class="article-body"><?= $record['body_html'] ?></div>
            <?php elseif ($type === 'certifications'): ?>
                <p class="preview-lead"><?= e($record['description']) ?></p>
            <?php elseif ($type === 'events'): ?>
                <p class="preview-lead"><?= e($record['caption']) ?></p>
                <div class="media-grid"><?php foreach (admin_event_images($id) as $image): ?><figure class="media-card"><img src="<?= e($image['public_path']) ?>" alt="<?= e($image['alt_text']) ?>"><figcaption><?= e((string) ($image['caption'] ?? '')) ?></figcaption></figure><?php endforeach; ?></div>
            <?php else: ?>
                <dl class="preview-fields">
                    <?php foreach ($module['fields'] as $column => $field): ?><div><dt><?= e($field['label']) ?></dt><dd><?= nl2br(e((string) ($record[$column] ?? ''))) ?></dd></div><?php endforeach; ?>
                </dl>
                <?php if ($type === 'projects'): ?><section><h2>Project members</h2><div class="member-list"><?php foreach (admin_project_members($id) as $member): ?><article class="member-row"><?php if ($member['public_path']): ?><img src="<?= e($member['public_path']) ?>" alt=""><?php else: ?><span class="member-avatar"><?= e($member['initials']) ?></span><?php endif; ?><strong><?= e($member['name']) ?></strong><small><?= e($member['initials']) ?></small></article><?php endforeach; ?></div></section><?php endif; ?>
            <?php endif; ?>
        </article>
    <?php endif; ?>
</main>
</body>
</html>
