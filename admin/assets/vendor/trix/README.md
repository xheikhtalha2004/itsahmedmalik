# Trix admin assets

The unmodified browser files from the pinned `trix@2.1.18` release are self-hosted here as:

- `trix.js`
- `trix.css`

The admin page detects both files and enables the WYSIWYG editor automatically. If either file is missing, it fails safely to a plain HTML textarea. Keep the bundled license when updating and do not replace these files with a CDN link: the admin Content Security Policy is self-only, and production deployments must be reproducible.
