# Crawler Blueprint (Direct URL, Simple)

## Goals
- Crawl direct article URLs only.
- Save title + URL into crawled metadata.
- Create draft articles assigned to a single user at a time.
- Assigned drafts appear only in that user's draft queue.
- Keep crawler simple; no scheduling, no advanced parsing.

---

## Current State (Key Findings)
- Crawling uses queued jobs (CrawlNewsSource), so no queue worker = no saved items.
- CrawledMetadata stores title + url; Article links via source_metadata_id.
- No source_url column on articles; source lives in crawled_metadata.url.
- ArticleResource already limits writers to their own author_id.

---

## Proposed Changes

### 1) New Filament Page: Crawl Articles
Files
- app/Filament/Pages/CrawlArticles.php
- resources/views/filament/pages/crawl-articles.blade.php

Behavior
- Form:
  - Textarea for URLs (one per line).
  - Single select "Assign to" user (default: current user).
- Submission:
  - Normalize URLs (trim, remove empty).
  - De-dupe within submission.
  - Skip duplicates already in crawled_metadata.url.
  - Fetch each URL, extract <title> only.
  - Create/reuse NewsSource from base URL (scheme + host).
  - Create CrawledMetadata with title, url, news_source_id.
- Notification summary:
  - Total submitted
  - Saved count
  - Skipped duplicates

---

### 2) Crawled Metadata -> Draft Article
File
- app/Filament/Resources/CrawledMetadataResource.php

Actions
- Replace "Create Article" with "Create Draft":
  - Single assignee selection.
  - Create Article:
    - title from metadata
    - author_id = assignee
    - source_metadata_id = metadata id
    - status = draft
  - Mark metadata as used.
- Add bulk action "Assign Drafts":
  - Single assignee for the bulk selection.
  - Create one draft per metadata and mark all as used.

Visibility
- Writers see only their own drafts via existing getEloquentQuery().

---

### 3) Article Prefill (Simple)
Files
- app/Filament/Resources/ArticleResource/Pages/CreateArticle.php
- app/Filament/Resources/ArticleResource.php

Behavior
- If source_metadata_id present in query:
  - Prefill title
  - Prefill source_metadata_id
- Display a read-only "Source URL" from related metadata in the Article form.

---

## Testing & Audit Checklist

### Automated Tests (PHPUnit)
- Feature test: Crawl page creates crawled_metadata and skips duplicates.
- Feature test: "Create Draft" action creates Article and marks metadata used.

Command
- vendor/bin/phpunit

### Manual Verification
- Crawl page saves items immediately without queue worker.
- Crawled items show in CrawledMetadata.
- "Create Draft" creates correct draft and assignment.
- Source URL visible while editing.

---

## Open Decisions (None)
All requirements defined: direct URLs only, simple title extraction, single-user assignment.

---

## Implementation Order
1) Add CrawlArticles page.
2) Update CrawledMetadata actions (single + bulk).
3) Prefill Article create + display source URL.
4) Add tests.
5) Run PHPUnit.

---

## Future Enhancements (Out of Scope)
- RSS/HTML scraping improvements.
- OG title parsing.
- Automatic scheduling.
- Better metadata taxonomy extraction.
