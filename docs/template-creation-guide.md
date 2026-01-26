# Template Creation Guide

## Overview
This guide explains how to build export templates for bulletins. Templates are
fully customizable per client and support both HTML (DOCX export) and plain text
(TXT export).

## Quick Start
1. Go to Admin Panel -> Export Templates.
2. Click "New Export Template".
3. Enter a name and description.
4. Add an HTML template for DOCX and/or a text template for TXT.
5. Save and test with the Export Bulletin page.

## Core Template Syntax
Templates use mustache-style placeholders.

### Article Block
Wrap article fields inside an article loop:

```
{{#articles}}
{{title}}
{{excerpt}}
{{/articles}}
```

### Article Placeholders
Use these inside `{{#articles}}` blocks:

- `{{title}}` - Article title
- `{{body}}` - Full article body
- `{{excerpt}}` - Article excerpt
- `{{author}}` - Author name
- `{{category}}` - Category name
- `{{tags}}` - Comma-separated tags
- `{{tags_list}}` - Tags formatted as a list
- `{{article_index}}` - Sequential article number
- `{{approved_at}}` - Approval date and time
- `{{approved_date}}` - Approval date only
- `{{published_at}}` - Publication date and time
- `{{source_url}}` - Source URL
- `{{source}}` - Source outlet name
- `{{tone}}` - Tone label
- `{{title_uppercase}}` - Uppercase title
- `{{body_excerpt|200}}` - Body truncated to 200 characters

### Global Placeholders
Use anywhere in the template:

- `{{export_date}}` - Export date
- `{{total_articles}}` - Total article count
- `{{approved_from}}` - Filter start date
- `{{approved_to}}` - Filter end date
- `{{category_group}}` - Auto-group articles by category
- `{{source_name}}` - Optional source label for headers

### Default Values
Provide a fallback using `|`:

```
[SO] {{source_name|Vietnam News Brief Service}}
```

### Date Formatting
Date placeholders can be formatted using `|`:

```
[DD] {{export_date|dd : mm : yyyy}}
```

### Conditional Blocks
Show content only when a value exists:

```
{{#if category}}Category: {{category}}{{/if}}
```

### Body Paragraphs
Split article bodies into paragraphs:

```
{{#body_paragraphs}}
[QQ] {{paragraph}}[QQ]
{{/body_paragraphs}}
```

### Category Grouping
Group articles by category:

```
{{#group_by_category}}
=== {{category_name}} ===
{{#articles}}
- {{title}}
{{/articles}}

{{/group_by_category}}
```

## Example: Vietnam News Brief (Tagged)
```
[SO] Vietnam News Brief Service
[DD] {{export_date|dd : mm : yyyy}}

{{#articles}}
[HH] {{category}}: {{title}}
{{#body_paragraphs}}
[QQ] {{paragraph}}[QQ]
{{/body_paragraphs}}

{{/articles}}
```

## Example: HTML Table (DOCX)
```html
<h1>News Summary - {{export_date}}</h1>
<table border="1" style="width:100%; border-collapse:collapse;">
  <tr style="background:#f2f2f2;">
    <th>No.</th>
    <th>Category</th>
    <th>Headline</th>
  </tr>
  {{#articles}}
  <tr>
    <td>{{article_index}}</td>
    <td>{{category}}</td>
    <td>{{title}}</td>
  </tr>
  {{/articles}}
</table>
```

## Tips
- Use one template per client format.
- Keep HTML templates simple for DOCX rendering.
- Use `{{#articles}}` blocks to repeat content per article.
- Use default values to keep templates resilient.
- Test with sample data before sending to clients.

## Example: Category-Grouped Newsletter
```html
<h1>Weekly Newsletter - {{export_date|M d, Y}}</h1>
<p>Total Articles: {{total_articles}}</p>

<h2>TABLE OF CONTENT</h2>
{{#group_by_category}}
<h3>{{category_name}}</h3>
<ul>
{{#articles}}
<li>{{title}}</li>
{{/articles}}
</ul>
{{/group_by_category}}

<h2>CONTENT</h2>
{{#group_by_category}}
<h3>{{category_name}}</h3>
{{#articles}}
<h4>{{title}}</h4>
<p>{{body_excerpt|600}}</p>
<p><em>Source: {{source}}</em></p>
{{/articles}}
{{/group_by_category}}
```

## Example: Detailed Table with Grouping
```html
<h1>Daily News Report - {{export_date|F d, Y}}</h1>
<table border="1" style="width:100%; border-collapse:collapse;">
  <tr style="background:#003366; color:#ffffff;">
    <th>Date</th>
    <th>Headline</th>
    <th>Tone</th>
    <th>Source</th>
  </tr>
  {{#group_by_category}}
  <tr>
    <td colspan="4" style="background:#f2f2f2;"><strong>{{category_name}}</strong></td>
  </tr>
  {{#articles}}
  <tr>
    <td>{{approved_date}}</td>
    <td><strong>{{title}}</strong><br/>{{excerpt}}</td>
    <td>{{tone}}</td>
    <td>{{source}}</td>
  </tr>
  {{/articles}}
  {{/group_by_category}}
</table>
```

## Advanced: Template Modifiers

### Text Truncation
Truncate body text to specific length:
```
{{body_excerpt|300}}  // First 300 characters
{{body_excerpt|600}}  // First 600 characters
```

### Date Format Options
Format dates with pipe modifiers:
```
{{export_date|M d, Y}}          // Jan 24, 2026
{{export_date|F d, Y}}          // January 24, 2026
{{export_date|dd : mm : yyyy}}  // 24 : 01 : 2026
{{approved_date|M d}}           // Jan 24
```

### Default Values
Provide fallbacks for optional fields:
```
{{source_name|Default Source Name}}
{{tone|Neutral}}
{{author|Unknown}}
```

## Client-Specific Templates

### Pack 1 / NBS Format
Tagged format with [SO], [DD], [HH], [QQ] blocks:
```
[SO] Vietnam News Brief Service
[DD] {{export_date|dd : mm : yyyy}}

{{#articles}}
[HH] {{category}}: {{title}}
{{#body_paragraphs}}
[QQ] {{paragraph}}[QQ]
{{/body_paragraphs}}

{{/articles}}
```

### AES Energy Format
Table-based energy bulletin:
```html
<h1>Vietnam Energy Bulletin - {{export_date|M d, Y}}</h1>
<table border="1" style="width:100%; border-collapse:collapse;">
  <tr style="background:#f2f2f2;">
    <th>Date</th>
    <th>Headlines</th>
    <th>Source</th>
    <th>Tone</th>
  </tr>
  {{#group_by_category}}
  <tr><td colspan="4"><strong>{{category_name}}</strong></td></tr>
  {{#articles}}
  <tr>
    <td>{{approved_date|M d}}</td>
    <td><strong>{{title}}</strong><br/>{{excerpt}}</td>
    <td>{{source}}</td>
    <td>{{tone}}</td>
  </tr>
  {{/articles}}
  {{/group_by_category}}
</table>
```

### GIZ Development Format
Digest with table of contents:
```html
<h1>GIZ Energy Daily - {{export_date|M d, Y}}</h1>

<h2>TABLE OF CONTENT</h2>
{{#group_by_category}}
<h3>{{category_name}}</h3>
<ul>
{{#articles}}
<li>{{title}}</li>
{{/articles}}
</ul>
{{/group_by_category}}

<h2>CONTENT</h2>
{{#group_by_category}}
<h3>{{category_name}}</h3>
{{#articles}}
<h4>{{title}}</h4>
<p>{{body_excerpt|600}}</p>
<p><em>Source: {{source}}</em></p>
<p>[Back to top]</p>
{{/articles}}
{{/group_by_category}}
```

## Creating Templates for Different Clients

### Strategy
Create one template per client format:
1. **Identify client requirements** - What format do they expect?
2. **Choose template type** - Simple (most cases) or Shortcode (advanced filtering)
3. **Pick HTML or Text** - HTML for DOCX with formatting, Text for plain TXT
4. **Add grouping if needed** - Use `{{#group_by_category}}` for organized reports
5. **Test with sample data** - Use the Duplicate feature to create variants

### Naming Convention
Use descriptive names that include client and format:
- "Pack 1 - Vietnam News Brief Service"
- "AES Mong Duong - Energy Bulletin"
- "GIZ Energy Daily"
- "NBS - Vietnam News Briefs"
- "Zarubezhneft Clipping"

### Multiple Formats Per Client
If a client needs multiple formats (weekly + daily), create separate templates:
- "Client Name - Daily Brief"
- "Client Name - Weekly Digest"
- "Client Name - Monthly Report"

## Best Practices

1. **Always use `{{#articles}}` block** - Required for article iteration
2. **Test before deploying** - Use the Duplicate feature to test changes
3. **Keep HTML simple** - Avoid complex CSS or JavaScript for DOCX compatibility
4. **Use consistent formatting** - Match client's branding and style
5. **Include metadata** - Export date, article count, source information
6. **Group logically** - Use category grouping for better organization
7. **Provide context** - Include table of contents for long reports
8. **Use defaults wisely** - Add fallbacks for optional fields

## Working with Grouping

### Enable Grouping
In the template form, set:
- **Grouping Type**: Category, Tag, or Custom
- **Show Group Headers**: Yes
- **Group Header Format**: `=== {{group_name}} ===`

### Category Grouping Example
```
{{#group_by_category}}
=== {{category_name}} ===

{{#articles}}
• {{title}}
  {{excerpt}}
{{/articles}}

{{/group_by_category}}
```

### Custom Group Order
Set custom order in the "Custom Group Order" field:
```
Energy, Policy, Investment, Technology
```

Articles will be grouped and displayed in this specific order.

## Filters and Pre-filtering

Use the Template Filters section to pre-filter articles:
- **Date Range**: Approved From/To dates
- **Categories**: Select specific categories
- **Tags**: Filter by tags
- **Status**: Approved, Published, or All

These filters apply automatically when using the template, reducing manual selection during export.

## Troubleshooting

### Common Issues
- **Empty placeholders**: Confirm the field exists in your articles
- **Mismatched blocks**: Ensure `{{#articles}}` has matching `{{/articles}}`
- **Wrong output format**: Use text_body for TXT, html_body for DOCX
- **Grouping not working**: Enable grouping in Grouping Options section
- **Dates not formatting**: Check date modifier syntax `{{date|format}}`

### Validation
- Templates are validated on save - check for error messages
- Use the "Preview Placeholders" button for reference
- Test with small article sets first
- Check the bulletin_exports table for error logs

### Getting Help
- Review sample templates in the system
- Check the "Placeholder Examples" section in the form
- Duplicate existing templates as starting points
- Consult this guide for syntax reference

## Advanced: Shortcode Templates

For advanced users needing dynamic filtering, shortcode templates provide query-based rendering:

```
[list_posts args="base64_encoded_json"]
[loop]
<h3>%%post_data.post_title%%</h3>
<p>%%taxonomy.category.0.name%%</p>
<p>%%post_data.post_excerpt%%</p>
[/loop]
[/list_posts]
```

**Note**: Shortcode templates are more complex. Use simple templates unless you need advanced taxonomy queries.

## Template Library

The system includes pre-built templates for:
- Pack 1 - Vietnam News Brief Service
- AES Mong Duong - Energy Bulletin
- GIZ Energy Daily
- Vietnam Weekly Digest
- Daily News Report
- NBS - Vietnam News Briefs
- Zarubezhneft Clipping

Duplicate these as starting points for new client templates.
