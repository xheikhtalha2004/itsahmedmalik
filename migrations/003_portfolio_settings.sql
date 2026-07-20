CREATE TABLE IF NOT EXISTS portfolio_settings (
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO portfolio_settings (setting_key, setting_value) VALUES
('contact_emails', 'hello@itsahmedmalik.com'),
('contact_phone', '+92 315 5320243'),
('contact_location', 'Islamabad, Pakistan'),
('social_instagram', 'https://www.instagram.com/ahmedmalik.co?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw=='),
('social_linkedin', 'https://www.linkedin.com/in/ahmed-malik-9b818a2b4/'),
('social_facebook', 'https://www.facebook.com/share/1Qhjua7uVT/?mibextid=wwXIfr')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
