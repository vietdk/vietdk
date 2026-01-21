# PHP Migration and Database Update TODO

## Preconditions
- Ensure PHP is installed and available in PATH.
- Verify Composer dependencies are installed.

## Migration Steps
1) Configure database settings in `.env`.
2) Verify database connectivity.
3) Run migrations: `php artisan migrate`.
4) Confirm new columns:
   - `articles.approved_at`
   - `export_templates.html_body`
   - `export_templates.text_body`
   - `export_templates.filters`
   - `bulletin_exports.output_format`

## Data Backfill (Optional)
- Set `approved_at` for existing approved articles:
  - Option A: copy from `published_at`.
  - Option B: copy from `updated_at`.

## Indexes (Optional)
- Add an index on `articles.approved_at` if exports get slow.
- Consider composite `(status, approved_at)` if filters include both.

## Validation
- Create a test export template (HTML + TXT).
- Verify eligible articles list shows `approved_at`.
- Export both DOCX and TXT and confirm download.
