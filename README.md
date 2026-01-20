# CMS Application

A Laravel-based Content Management System with Filament Admin Panel for managing articles, categories, tags, tones, and campaigns.

## 📋 Table of Contents

- [Overview](#overview)
- [Recent Changes (Phase 2)](#recent-changes-phase-2)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Database Structure](#database-structure)
- [Article Management](#article-management)
- [Migration History](#migration-history)
- [Usage](#usage)
- [Troubleshooting](#troubleshooting)

---

## 🎯 Overview

This CMS application provides a complete article management system with:
- Multi-user support with role-based access control (Admin, Editor, Writer)
- Article workflow (Draft → Pending Review → Approved → Published)
- Taxonomy system (Categories, Tags, Tones, Campaigns)
- Source tracking for crawled/imported content
- Export functionality for bulletins

---

## 🚀 Recent Changes (Phase 2)

### Major Changes from Original Version

#### 1. **Removed Slug Auto-Generation**
- ❌ Removed `slug` field from articles table
- ❌ Removed auto-generation logic from Article model
- **Reason**: Slugs were not needed for the application workflow

#### 2. **Added Source Tracking Fields**
- ✅ Added `original_title` field (nullable, VARCHAR 255)
  - Store Vietnamese or source language titles
- ✅ Added `original_url` field (nullable, TEXT)
  - Track original article source URLs
- **Reason**: Better tracking of translated/adapted content

#### 3. **Enforced Single-Choice Taxonomy**
- ✅ Changed `category_id` from nullable to **required (NOT NULL)**
- ✅ Changed `tones` from many-to-many to **one-to-one** (BelongsTo)
- ✅ Changed `campaigns` from many-to-many to **one-to-one** (BelongsTo)
- ✅ Kept `tags` as **many-to-many** (exception to single-choice rule)

**Before:**
```php
// Multiple tones and campaigns per article
$article->tones()->attach([1, 2, 3]);
$article->campaigns()->attach([1, 2]);
```

**After:**
```php
// Single tone and campaign per article
$article->tone_id = 1;
$article->campaign_id = 1;
$article->save();
```

#### 4. **Database Schema Changes**
- ✅ Dropped `article_tone` pivot table
- ✅ Dropped `article_campaign` pivot table
- ✅ Added `tone_id` foreign key to articles table
- ✅ Added `campaign_id` foreign key to articles table
- ✅ Changed category foreign key constraint from SET NULL to RESTRICT

#### 5. **UI/UX Improvements**
- ✅ Added separate navigation items for article workflows:
  - **Drafts**: View all draft articles
  - **Pending Review**: Articles awaiting approval
  - **Approved**: Approved articles ready for publishing
  - **All Articles**: View all articles regardless of status
- ✅ Updated form fields to reflect new taxonomy structure
- ✅ Added URL validation for `original_url` field

---

## ✨ Features

### Article Management
- **Required Fields**:
  - Title
  - Body content (rich text editor)
  - Author
  - Category (single-select)
  - Tone (single-select)
  - Campaign (single-select)

- **Optional Fields**:
  - Original Title (for source language)
  - Original URL (with URL validation)
  - Excerpt (auto-generated if empty)
  - Tags (multiple-select)
  - Source Reference (link to crawled metadata)

### Workflow States
1. **Draft**: Initial state, editable by author
2. **Pending Review**: Submitted for editorial review
3. **Approved**: Approved by editor, ready for publishing
4. **Published**: Live article with publication date
5. **Rejected**: Returned to draft state with feedback

### User Roles
- **Writer**: Can create and edit own articles, submit for review
- **Editor**: Can review, approve, reject, and publish articles
- **Admin**: Full system access including user management

---

## 📦 Requirements

- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.3+
- Composer
- Laragon (or similar local development environment)

---

## 🔧 Installation

### 1. Clone Repository
```bash
cd "C:\Users\Viet Ha"
git clone <repository-url> cms
cd cms
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Database
Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cms_database
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Run Migrations
```bash
php artisan migrate
```

### 6. Create Admin User
```bash
php artisan make:filament-user
```

### 7. Start Development Server
```bash
php artisan serve
```

Visit: http://127.0.0.1:8000/admin

---

## 🗄️ Database Structure

### Articles Table

| Column               | Type                  | Nullable | Description                          |
|---------------------|-----------------------|----------|--------------------------------------|
| id                  | bigint unsigned       | NO       | Primary key                          |
| title               | varchar(255)          | NO       | Article title                        |
| **original_title**  | varchar(255)          | YES      | Vietnamese/source language title     |
| **original_url**    | text                  | YES      | Source article URL                   |
| body                | longtext              | NO       | Article content (HTML)               |
| excerpt             | text                  | YES      | Brief summary (auto-generated)       |
| author_id           | bigint unsigned       | NO       | FK to users (author)                 |
| **category_id**     | bigint unsigned       | **NO**   | FK to categories (**required**)      |
| **tone_id**         | bigint unsigned       | YES      | FK to tones (single-select)          |
| **campaign_id**     | bigint unsigned       | YES      | FK to campaigns (single-select)      |
| source_metadata_id  | bigint unsigned       | YES      | FK to crawled_metadata               |
| status              | enum                  | NO       | Workflow state                       |
| published_at        | timestamp             | YES      | Publication date/time                |
| updated_by          | bigint unsigned       | YES      | FK to users (last editor)            |
| approved_by         | bigint unsigned       | YES      | FK to users (approver)               |
| approved_at         | timestamp             | YES      | Approval date/time                   |
| rejected_by         | bigint unsigned       | YES      | FK to users (rejector)               |
| rejected_at         | timestamp             | YES      | Rejection date/time                  |
| created_at          | timestamp             | YES      | Creation timestamp                   |
| updated_at          | timestamp             | YES      | Last update timestamp                |

**Notes:**
- ❌ `slug` field **removed** (was: varchar(255) UNIQUE)
- ✅ `original_title` and `original_url` **added**
- ✅ `tone_id` and `campaign_id` **added** (replaces pivot tables)
- ⚠️ `category_id` now **required** (changed from nullable)

### Taxonomy Tables

#### Categories
```sql
- id (PK)
- name
- slug (unique)
- created_at, updated_at
```

#### Tags (Many-to-Many with Articles)
```sql
- id (PK)
- name
- slug (unique)
- created_at, updated_at

Pivot Table: article_tag
- article_id (FK)
- tag_id (FK)
```

#### Tones (One-to-Many with Articles)
```sql
- id (PK)
- name
- slug (unique)
- created_at, updated_at
```

#### Campaigns (One-to-Many with Articles)
```sql
- id (PK)
- name
- slug (unique)
- created_at, updated_at
```

### Removed Tables
- ❌ `article_tone` (pivot table - **removed in Phase 2**)
- ❌ `article_campaign` (pivot table - **removed in Phase 2**)

---

## 📝 Article Management

### Creating an Article

1. Navigate to **Admin Panel** → **Articles** → **Drafts**
2. Click **"New Article"**
3. Fill in required fields:
   - **Title**: Article headline
   - **Category**: Select one category (required)
   - **Tone**: Select article tone (required)
   - **Campaign**: Select campaign (required)
4. Optional fields:
   - **Original Title**: If translated from another language
   - **Original URL**: Source article link
   - **Tags**: Add multiple tags
   - **Source Reference**: Link to crawled metadata
5. Write article body using rich text editor
6. Click **"Save"** to save as draft

### Article Workflow

```
┌─────────┐    Submit     ┌────────────────┐    Approve    ┌──────────┐    Publish    ┌───────────┐
│  Draft  │──────────────>│ Pending Review │──────────────>│ Approved │──────────────>│ Published │
└─────────┘               └────────────────┘               └──────────┘               └───────────┘
     ^                            │
     │                            │ Reject
     └────────────────────────────┘
```

### Form Validation

**Required Fields (enforced by database & form):**
- ✅ Title
- ✅ Body
- ✅ Category
- ✅ Tone
- ✅ Campaign

**Optional Fields:**
- Original Title
- Original URL (validated as URL format)
- Excerpt
- Tags (multiple)
- Source Reference

---

## 📜 Migration History

### Initial Migrations (Batch 1-6)
```
2024_01_01_000001_create_categories_table
2024_01_01_000002_create_tags_table
2024_01_01_000003_create_articles_table
2024_01_01_000004_create_article_tag_table
2024_01_01_000005_create_news_sources_table
2024_01_01_000006_create_crawled_metadata_table
2024_01_01_000007_create_imports_table
2024_01_01_000008_create_tones_table
2024_01_01_000009_create_campaigns_table
2024_01_01_000010_create_export_templates_table
2024_01_01_000011_create_bulletin_exports_table
```

### Phase 2 Migrations (Batch 7-8)
```
2026_01_21_000001_add_user_tracking_to_articles_table (Batch 7)
  - Added: updated_by, approved_by, approved_at, rejected_by, rejected_at

2026_01_21_000002_migrate_existing_taxonomy_data (Batch 8)
  - Added: tone_id, campaign_id columns
  - Migrated data from pivot tables (kept first tone/campaign)

2026_01_21_000003_modify_article_fields_and_taxonomy (Batch 8)
  - Removed: slug column and unique index
  - Added: original_title, original_url
  - Modified: category_id (now NOT NULL)
  - Added: Foreign keys for tone_id, campaign_id
  - Dropped: article_tone, article_campaign pivot tables
```

### Rollback Instructions

To rollback Phase 2 changes:
```bash
# Rollback last 2 migrations (data + schema)
php artisan migrate:rollback --step=2

# This will:
# - Restore pivot tables (article_tone, article_campaign)
# - Remove tone_id and campaign_id columns
# - Add back slug column
# - Make category_id nullable again
# - Remove original_title and original_url
```

---

## 🎮 Usage

### Starting the Application

#### Using Command Prompt (Laragon)
```bash
# 1. Start Laragon (Start All button)

# 2. Open Command Prompt
cd "C:\Users\Viet Ha\cms"

# 3. Start Laravel server
"C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe" artisan serve

# 4. Open browser
http://127.0.0.1:8000/admin
```

#### Using Laragon Terminal
```bash
# 1. Open Laragon Terminal
cd cms

# 2. Start server
php artisan serve

# 3. Open browser
http://127.0.0.1:8000/admin
```

### Common Commands

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Create new migration
php artisan make:migration migration_name

# Create new model
php artisan make:model ModelName

# Create Filament resource
php artisan make:filament-resource ModelName

# Clear Filament cache
php artisan filament:cache-components
```

---

## 🔍 Troubleshooting

### Port 8000 Already in Use
```bash
# Use different port
php artisan serve --port=8001
```

### Database Connection Error
1. Check Laragon is running
2. Verify MySQL service is started
3. Check `.env` database credentials

### Changes Not Showing in UI
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Hard refresh browser: Ctrl + Shift + R
```

### Migration Errors
```bash
# Check migration status
php artisan migrate:status

# Fresh install (⚠️ WARNING: Deletes all data)
php artisan migrate:fresh

# Rollback and re-run
php artisan migrate:rollback
php artisan migrate
```

### Filament Navigation Not Updating
```bash
# Clear Filament cache
php artisan filament:cache-components

# Clear all caches
php artisan cache:clear
php artisan config:clear

# Restart server
# Press Ctrl+C to stop, then run: php artisan serve
```

---

## 📊 Model Relationships

### Article Model

```php
// BelongsTo Relationships (One-to-Many)
$article->author;        // User who created the article
$article->category;      // Single category (REQUIRED)
$article->tone;          // Single tone (changed from many-to-many)
$article->campaign;      // Single campaign (changed from many-to-many)
$article->sourceMetadata; // Crawled metadata reference
$article->updatedBy;     // Last user who edited
$article->approvedBy;    // User who approved
$article->rejectedBy;    // User who rejected

// BelongsToMany Relationships (Many-to-Many)
$article->tags;          // Multiple tags (unchanged)
```

### Category Model
```php
$category->articles;     // HasMany
```

### Tag Model
```php
$tag->articles;          // BelongsToMany
```

### Tone Model
```php
$tone->articles;         // HasMany (changed from BelongsToMany)
```

### Campaign Model
```php
$campaign->articles;     // HasMany (changed from BelongsToMany)
```

---

## 🎨 Navigation Structure

### Sidebar Menu

```
Dashboard
└─ (Dashboard widgets)

Articles
├─ Drafts (shows draft articles)
├─ Pending Review (shows pending review articles)
├─ Approved (shows approved articles)
└─ All Articles (shows all articles)

Content
├─ Categories
├─ Tags
├─ Tones
└─ Campaigns

Sources
├─ News Sources
└─ Crawled Metadata

Export
├─ Export Templates
└─ Bulletin Exports

System
└─ Users (Admin only)
```

---

## 📄 License

[Add your license here]

---

## 👥 Contributors

[Add contributors here]

---

## 📞 Support

For issues and questions:
- Create an issue on GitHub
- Contact: [Your contact information]

---

**Last Updated**: January 21, 2026

**Version**: 2.0 (Phase 2 - Taxonomy Restructure)
