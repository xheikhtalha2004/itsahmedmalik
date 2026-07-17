ALTER TABLE event_images
    ADD COLUMN IF NOT EXISTS archived_at DATETIME NULL AFTER sort_order,
    ADD KEY IF NOT EXISTS idx_event_image_active_order (event_id, archived_at, sort_order, id);

ALTER TABLE project_members
    ADD COLUMN IF NOT EXISTS archived_at DATETIME NULL AFTER sort_order,
    ADD KEY IF NOT EXISTS idx_project_member_active_order (project_id, archived_at, sort_order, id);
