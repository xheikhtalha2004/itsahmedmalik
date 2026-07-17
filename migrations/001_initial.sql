SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(191) NOT NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS cms_media (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(50) NOT NULL,
    width SMALLINT UNSIGNED NOT NULL,
    height SMALLINT UNSIGNED NOT NULL,
    size_bytes INT UNSIGNED NOT NULL,
    archived_at DATETIME NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cms_media_public_path (public_path),
    KEY idx_cms_media_archived_created (archived_at, created_at, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS blog_posts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(100) NOT NULL,
    excerpt VARCHAR(500) NOT NULL,
    meta_description VARCHAR(320) NOT NULL,
    published_on DATE NULL,
    cover_media_id BIGINT UNSIGNED NULL,
    cover_alt VARCHAR(255) NOT NULL DEFAULT '',
    body_html MEDIUMTEXT NOT NULL,
    publication_status VARCHAR(16) NOT NULL DEFAULT 'draft',
    sort_order INT NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    archived_at DATETIME NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_blog_publication (publication_status, archived_at, sort_order, published_on, id),
    KEY idx_blog_cover_media (cover_media_id),
    CONSTRAINT fk_blog_cover_media FOREIGN KEY (cover_media_id) REFERENCES cms_media (id)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS certifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    media_id BIGINT UNSIGNED NULL,
    image_alt VARCHAR(255) NOT NULL DEFAULT '',
    publication_status VARCHAR(16) NOT NULL DEFAULT 'draft',
    sort_order INT NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    archived_at DATETIME NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_certification_publication (publication_status, archived_at, sort_order, id),
    KEY idx_certification_media (media_id),
    CONSTRAINT fk_certification_media FOREIGN KEY (media_id) REFERENCES cms_media (id)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    caption TEXT NULL,
    cover_media_id BIGINT UNSIGNED NULL,
    publication_status VARCHAR(16) NOT NULL DEFAULT 'draft',
    sort_order INT NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    archived_at DATETIME NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_event_publication (publication_status, archived_at, sort_order, id),
    KEY idx_event_cover_media (cover_media_id),
    CONSTRAINT fk_event_cover_media FOREIGN KEY (cover_media_id) REFERENCES cms_media (id)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS event_images (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id BIGINT UNSIGNED NOT NULL,
    media_id BIGINT UNSIGNED NOT NULL,
    caption VARCHAR(500) NULL,
    alt_text VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_event_image_media (event_id, media_id),
    KEY idx_event_image_order (event_id, sort_order, id),
    KEY idx_event_image_media (media_id),
    CONSTRAINT fk_event_image_event FOREIGN KEY (event_id) REFERENCES events (id)
        ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_event_image_media FOREIGN KEY (media_id) REFERENCES cms_media (id)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS education_entries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    degree VARCHAR(200) NOT NULL,
    institution VARCHAR(200) NOT NULL,
    label VARCHAR(100) NOT NULL,
    publication_status VARCHAR(16) NOT NULL DEFAULT 'draft',
    sort_order INT NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    archived_at DATETIME NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_education_publication (publication_status, archived_at, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS work_experiences (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    role_title VARCHAR(200) NOT NULL,
    company VARCHAR(200) NOT NULL,
    company_url VARCHAR(2048) NULL,
    tenure_label VARCHAR(100) NOT NULL,
    category_label VARCHAR(100) NOT NULL,
    icon_text VARCHAR(20) NOT NULL,
    color_preset VARCHAR(32) NOT NULL DEFAULT 'cyan',
    publication_status VARCHAR(16) NOT NULL DEFAULT 'draft',
    sort_order INT NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    archived_at DATETIME NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_experience_publication (publication_status, archived_at, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS projects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    project_status_label VARCHAR(100) NOT NULL,
    tone_preset VARCHAR(32) NOT NULL DEFAULT 'neutral',
    progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
    deadline_label VARCHAR(100) NULL,
    milestone VARCHAR(255) NULL,
    publication_status VARCHAR(16) NOT NULL DEFAULT 'draft',
    sort_order INT NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    archived_at DATETIME NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_project_publication (publication_status, archived_at, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS project_members (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    initials VARCHAR(10) NOT NULL,
    media_id BIGINT UNSIGNED NULL,
    sort_order INT NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_project_member_order (project_id, sort_order, id),
    KEY idx_project_member_media (media_id),
    CONSTRAINT fk_project_member_project FOREIGN KEY (project_id) REFERENCES projects (id)
        ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_project_member_media FOREIGN KEY (media_id) REFERENCES cms_media (id)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS testimonials (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    quote_text TEXT NOT NULL,
    rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
    author VARCHAR(100) NOT NULL,
    role_title VARCHAR(200) NOT NULL,
    initials VARCHAR(10) NOT NULL,
    gradient_preset VARCHAR(32) NOT NULL DEFAULT 'violet',
    publication_status VARCHAR(16) NOT NULL DEFAULT 'draft',
    sort_order INT NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    archived_at DATETIME NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_testimonial_publication (publication_status, archived_at, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS contact_submissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    submission_uuid BINARY(16) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(32) NULL,
    service_code VARCHAR(32) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'new',
    admin_notified_at DATETIME NULL,
    admin_notification_error VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_contact_submission_uuid (submission_uuid),
    KEY idx_contact_status_created (status, created_at, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS meeting_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    submission_uuid BINARY(16) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(32) NOT NULL,
    requested_start_at DATETIME NOT NULL,
    approved_start_at DATETIME NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'pending',
    admin_note VARCHAR(500) NULL,
    request_notified_at DATETIME NULL,
    request_notification_error VARCHAR(500) NULL,
    approval_notified_at DATETIME NULL,
    approval_notification_error VARCHAR(500) NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_meeting_submission_uuid (submission_uuid),
    UNIQUE KEY uq_meeting_approved_start (approved_start_at),
    KEY idx_meeting_status_created (status, created_at, id),
    KEY idx_meeting_requested_start (requested_start_at, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(254) NOT NULL,
    source_path VARCHAR(255) NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'active',
    first_subscribed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_newsletter_email (email),
    KEY idx_newsletter_status_subscribed (status, first_subscribed_at, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
