# Client Template Testing Checklist

**Date:** 2026-01-24
**Purpose:** Verify all client-specific export templates work correctly
**Location:** CMS → Export Templates

---

## Prerequisites

Before testing, ensure:
- [ ] Sample templates seeder has been run: `php artisan db:seed --class=SampleTemplateSeeder`
- [ ] Database has sample articles with categories, tags, and sources
- [ ] You have access to Filament admin panel

---

## Template Testing Guide

For each template below:

1. **Navigate** to Export Templates in Filament
2. **Find** the template in the list
3. **Edit** the template to open the edit page
4. **Preview** using the "Preview Template" button to check rendering
5. **Download Test Export** to verify DOCX/TXT output
6. **Verify** output matches the expected format below

---

## ✅ Template 1: Pack 1 - Vietnam News Brief Service

**Type:** Simple (Text only)
**Format:** Tagged format with [SO], [DD], [HH], [QQ] blocks
**Client:** NBS / Pack 1

### Expected Format

```
[SO] Vietnam News Brief Service
[DD] 24 : 01 : 2026

[HH] Category Name: Article Title
[QQ] First paragraph of article body.[QQ]
[QQ] Second paragraph of article body.[QQ]

[HH] Another Category: Second Article Title
[QQ] Article paragraph.[QQ]
```

### Test Checklist

- [ ] Template exists in database
- [ ] Text body field is populated
- [ ] Preview shows [SO], [DD], [HH], [QQ] tags correctly
- [ ] Source name displays correctly in [SO] tag
- [ ] Date format is dd : mm : yyyy in [DD] tag
- [ ] Category and title appear in [HH] tag
- [ ] Each paragraph wrapped in [QQ] tags
- [ ] TXT export downloads successfully
- [ ] TXT file contains proper tags and formatting

**Issues Found:** _None_

---

## ✅ Template 2: AES Mong Duong - Energy Bulletin

**Type:** Simple (HTML)
**Format:** Table layout grouped by category
**Client:** AES Mong Duong

### Expected Format

HTML table with:
- Header: "Vietnam Energy Bulletin" + date
- Table columns: Date | Headlines | Media Title | Media Type | Tone
- Category rows with category name spanning all columns
- Article rows grouped under each category

### Test Checklist

- [ ] Template exists in database
- [ ] HTML body field is populated
- [ ] Preview shows table structure correctly
- [ ] Export date displays in format "Jan 24, 2026"
- [ ] Categories appear as merged header rows
- [ ] Article data fills all 5 columns correctly:
  - [ ] Date column shows approval date (e.g., "Jan 24")
  - [ ] Headlines column shows **bold title** + excerpt
  - [ ] Media Title shows source name
  - [ ] Media Type shows tags
  - [ ] Tone column shows tone value
- [ ] Table has borders and styling
- [ ] DOCX export downloads successfully
- [ ] DOCX opens correctly in Microsoft Word
- [ ] Table formatting is preserved in Word

**Issues Found:** _None_

---

## ✅ Template 3: GIZ Energy Daily

**Type:** Simple (HTML)
**Format:** TOC + Content sections grouped by category
**Client:** GIZ

### Expected Format

- Header: "Vietnam Energy Bulletin" + date
- **TABLE OF CONTENT**
  - Category 1
    - Article title 1
    - Article title 2
  - Category 2
    - Article titles...
- **CONTENT**
  - Category 1
    - Article title as H4
    - Excerpt (600 chars max)
    - Source
    - [Back to top]

### Test Checklist

- [ ] Template exists in database
- [ ] HTML body field is populated
- [ ] Preview shows TOC structure
- [ ] Preview shows CONTENT structure
- [ ] Categories appear in both TOC and CONTENT sections
- [ ] Article titles listed in TOC
- [ ] Full content section shows:
  - [ ] Category name as H3
  - [ ] Article title as H4
  - [ ] Excerpt truncated to ~600 characters
  - [ ] Source in italics
  - [ ] "[Back to top]" link
- [ ] DOCX export downloads successfully
- [ ] DOCX has proper heading hierarchy

**Issues Found:** _None_

---

## ✅ Template 4: Vietnam Weekly Digest

**Type:** Simple (HTML)
**Format:** Company header + TOC + Content sections
**Client:** Toan Viet Company

### Expected Format

- Company header with:
  - TOAN VIET COMPANY LIMITED
  - Address, phone, email, website
- Header: "Vietnam Weekly Digest" + date
- TABLE OF CONTENT (by category)
- CONTENT (category-grouped with titles, excerpts, sources)

### Test Checklist

- [ ] Template exists in database
- [ ] HTML body field is populated
- [ ] Company information displays correctly:
  - [ ] Company name
  - [ ] Address: 502, No.27 Lane 115 Nui Truc Str...
  - [ ] Phone: (+84 24) 3772 0378
  - [ ] Email: news@vietpan.com
  - [ ] Website: www.vietpan.com
- [ ] "Vietnam Weekly Digest" appears as H1
- [ ] Export date in format "Jan 24, 2026"
- [ ] TOC shows categories and article titles
- [ ] CONTENT section shows:
  - [ ] Category names as H3
  - [ ] Article titles as H4
  - [ ] Excerpts (~600 chars)
  - [ ] Source in italics
- [ ] DOCX export works

**Issues Found:** _None_

---

## ✅ Template 5: Daily News Report

**Type:** Simple (HTML)
**Format:** Table with description paragraph
**Client:** Australia - Vietnam

### Expected Format

- Header: "Australia - Vietnam Daily News Report" + date
- Description paragraph about the newsletter
- Table with columns: Date | News | Tone | Media Outlets
- Categories as merged header rows
- Articles grouped under categories

### Test Checklist

- [ ] Template exists in database
- [ ] HTML body field is populated
- [ ] Preview shows description paragraph
- [ ] Description mentions "click 'Go to full brief'"
- [ ] Table structure appears correctly
- [ ] Category rows span all 4 columns
- [ ] Article rows show:
  - [ ] Date (e.g., "Jan 24")
  - [ ] News: **title** + excerpt + "Go to full brief" link
  - [ ] Tone value
  - [ ] Media outlet (source)
- [ ] DOCX export works
- [ ] Table is properly formatted in Word

**Issues Found:** _None_

---

## ✅ Template 6: NBS - Vietnam News Briefs

**Type:** Simple (HTML)
**Format:** Company header + article count + TOC + Content
**Client:** NBS / Toan Viet

### Expected Format

Similar to Vietnam Weekly Digest but with:
- Company header (same as template 4)
- "Vietnam News Briefs" title
- Total articles count displayed
- TOC grouped by category
- CONTENT with excerpts and sources

### Test Checklist

- [ ] Template exists in database
- [ ] HTML body field is populated
- [ ] Company information displays
- [ ] "Vietnam News Briefs" appears as H1
- [ ] Export date format: "January 24, 2026" (full month)
- [ ] "Total articles: X articles" displays correctly
- [ ] TOC structure is correct
- [ ] CONTENT structure matches expected format:
  - [ ] Categories as H3
  - [ ] Article titles as H4
  - [ ] Excerpts (~600 chars)
  - [ ] Source in italics
  - [ ] "[Back to top]" links
- [ ] DOCX export works

**Issues Found:** _None_

---

## ✅ Template 7: Zarubezhneft Clipping

**Type:** Simple (HTML)
**Format:** Press clipping with TOC + Content
**Client:** Zarubezhneft Vietnam

### Expected Format

- Header: "Press Clipping for Zarubezhneft Vietnam" + date
- TABLE OF CONTENT (by category)
- CONTENT (category-grouped articles)

### Test Checklist

- [ ] Template exists in database
- [ ] HTML body field is populated
- [ ] "Press Clipping for Zarubezhneft Vietnam" appears as H1
- [ ] Export date format: "January 24, 2026" (full month)
- [ ] TOC shows categories and titles
- [ ] CONTENT section shows:
  - [ ] Category names as H3
  - [ ] Article titles as H4
  - [ ] Excerpts (~600 chars)
  - [ ] Source in italics
  - [ ] "[Back to top]" links
- [ ] DOCX export works
- [ ] Formatting is appropriate for press clipping

**Issues Found:** _None_

---

## Additional Tests

### General Template Quality

- [ ] All templates validate without errors (Phase 1 validation)
- [ ] Preview feature works for all templates
- [ ] Duplicate feature creates working copies
- [ ] Placeholder search helps find variables quickly

### Edge Cases

- [ ] Templates work with 0 articles (show empty state)
- [ ] Templates work with 1 article
- [ ] Templates work with 50+ articles
- [ ] Templates handle articles with:
  - [ ] Missing categories (shows "Uncategorized")
  - [ ] Missing tags
  - [ ] Missing source
  - [ ] Missing tone
  - [ ] Very long titles
  - [ ] Very short excerpts
  - [ ] Special characters in titles

### Export Quality

- [ ] DOCX files open without errors in Microsoft Word
- [ ] DOCX files open without errors in LibreOffice
- [ ] TXT files have proper encoding (UTF-8)
- [ ] TXT files have correct line breaks
- [ ] Tables render properly in Word
- [ ] HTML formatting is preserved appropriately

---

## Summary Report

**Test Date:** _____________
**Tested By:** _____________
**Total Templates:** 7
**Templates Passing:** _____
**Templates Failing:** _____

### Critical Issues

_List any critical issues that prevent template use:_

1.
2.
3.

### Minor Issues

_List minor formatting or cosmetic issues:_

1.
2.
3.

### Recommendations

_Suggested improvements or adjustments:_

1.
2.
3.

---

## Next Steps

After completing this checklist:

1. **If all tests pass:**
   - Mark Phase 3 as complete
   - Templates are ready for production use
   - Consider proceeding to Phase 4 (automated testing)

2. **If issues found:**
   - Document issues in this checklist
   - Fix template syntax in Filament UI
   - Re-test affected templates
   - Update plan file with findings

3. **Optional improvements:**
   - Create additional client-specific templates
   - Refine existing templates based on user feedback
   - Add more placeholder variables if needed
   - Create shortcode versions of simple templates

---

## Quick Commands

```bash
# Run sample template seeder
php artisan db:seed --class=SampleTemplateSeeder

# Check how many templates exist
php artisan tinker --execute="echo App\Models\ExportTemplate::count();"

# List all template names
php artisan tinker --execute="App\Models\ExportTemplate::pluck('name')->each(fn(\$name) => print(\$name . PHP_EOL));"

# Clear and re-seed templates
php artisan db:seed --class=SampleTemplateSeeder
```

---

**Document Version:** 1.0
**Last Updated:** 2026-01-24
**Next Review:** After Phase 3 completion
