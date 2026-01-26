# Shortcode Export Rebuild

## Overview
We rebuilt the export system to support shortcode-based templates with Base64 JSON filters, using native Laravel taxonomies. All existing templates were wiped and replaced with DB-only templates. A shortcode parser now expands `[list_posts args="..."]` blocks, resolves tokens, and outputs normalized HTML that is safe for DOCX export.

## Goals
1. Replace legacy template system with shortcode parsing.
2. Support Base64 JSON `list_posts args` in template builder.
3. Use native Laravel taxonomy relations.
4. Store templates in DB only.
5. Ensure exports (DOCX/TXT) render complex HTML tables.

## Schema and Data Migration Steps
1. Migration: add shortcode fields to `export_templates`
   - `template_type` (default `simple`)
   - `shortcode_body` (longText, nullable)
   - File: `database/migrations/2026_01_23_000015_add_shortcode_fields_to_export_templates_table.php`
2. Migration: add `title_vi` to `articles`
   - File: `database/migrations/2026_01_23_000016_add_title_vi_to_articles_table.php`
3. Data wipe: `export_templates` cleared via seeder using `delete()` (not truncate, due to FK).
4. Seeder: `ShortcodeTemplateSeeder` inserts only shortcode templates.

## Key Code Changes

### Shortcode Engine
- `app/Services/Shortcodes/ShortcodeTemplateRenderer.php`
  - Parses `[html]`, `[bookmark]`, `[time]`, `[list_posts]`, `[loop]`
  - Decodes Base64 JSON args
  - Builds taxonomy-based queries
  - Resolves tokens:
    - `%%post_data.*%%`
    - `%%taxonomy.*%%`
    - `%%post_meta.*%%`
  - Generates HTML output for DOCX and plain text for TXT

### Base64 JSON Codec
- `app/Services/Shortcodes/ShortcodeArgsCodec.php`
  - encode/decode Base64 JSON payloads
  - normalizes args shape

### Taxonomy Registry
- `app/Services/Shortcodes/TaxonomyRegistry.php`
  - Defines taxonomy keys to Laravel relations
  - Supports: `category`, `post_tag`, `post_tone`, `post_campaign`, `opm_news_type`

## Export Pipeline Integration
- `DocxExporter` and `TxtExporter` now check `template_type`:
  - `shortcode` uses `ShortcodeTemplateRenderer`
  - `simple` uses existing `TemplateRenderer`
- HTML normalization retained for DOCX

## Filament UI Updates

### Export Template Builder
- Added `template_type` selector.
- Added `shortcode_body` field for shortcode templates.
- Added “List Posts Args Builder” inside Template Content:
  - taxonomy filters (all taxonomies)
  - relation AND/OR
  - JSON and Base64 output
  - Base64 paste to repopulate filters
  - insert `[list_posts]` snippet

File: `app/Filament/Resources/ExportTemplateResource.php`

### Template Test Export
- Updated test export page to support shortcode templates.
- Validation skipped for shortcode templates.

File: `app/Filament/Resources/ExportTemplateResource/Pages/EditExportTemplate.php`

### Export Preview
- Preview supports shortcode rendering and HTML-to-text fallback.

File: `app/Filament/Pages/ExportBulletin.php`

## Seeder (DB Only Templates)
- `database/seeders/ShortcodeTemplateSeeder.php`
  - Deletes all existing templates
  - Creates two shortcode templates (Australia + NBS)
  - Sources content from `resources/bulletins/temp parser/*.txt`

## Mapping: Shortcode Tokens

### Post meta
- `opm_title_en.0` -> `article.title`
- `opm_title_vi.0` -> `article.title_vi` or `original_title`
- `opm_content_en.0` -> excerpt/body
- `_kdn_source_urls.0.0` -> `article.sourceMetadata.url`
- `source_name.0` -> domain from source URL (`www.*` -> `*`)

### Taxonomy
- `category` -> `article.category`
- `post_tag` -> `article.tags`
- `post_tone` -> `article.tone`
- `post_campaign` -> `article.campaign`
- `opm_news_type` -> `article.tags` (tag-based for now)

## Commands Executed
- `php artisan migrate`
- `php artisan db:seed --class=ShortcodeTemplateSeeder`

## Known Constraints / Notes
- HTML from shortcodes is normalized before DOCX to satisfy PhpWord XML rules.
- `NOT IN` filters use `whereDoesntHave` to avoid incorrect results.
- `children=true` only expands for hierarchical taxonomies with `parent_id`.

## Optional Next Steps
1. Add more templates to the seeder or build through Filament.
2. Extend `TaxonomyRegistry` if new taxonomies appear.
3. Add a shortcode debugging view to show expanded HTML.
