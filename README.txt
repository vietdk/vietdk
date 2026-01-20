================================================================================
                    CMS APPLICATION - STARTUP GUIDE
================================================================================

PROJECT LOCATION: C:\Users\Viet Ha\cms

================================================================================
PREREQUISITES (DO THIS FIRST)
================================================================================

1. Start Laragon
   - Open Laragon from your desktop or start menu
   - Click "Start All" button
   - Wait until all services (Apache, MySQL) are running


================================================================================
HOW TO START THE APPLICATION
================================================================================

METHOD 1: Using Command Prompt (Recommended)
--------------------------------------------

1. Open Command Prompt (cmd)

2. Navigate to project folder:
   cd "C:\Users\Viet Ha\cms"

3. Start Laravel server:
   "C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe" artisan serve

4. You should see:
   "Server running on http://127.0.0.1:8000"

5. Open your web browser and go to:
   http://127.0.0.1:8000

6. To access Filament admin panel:
   http://127.0.0.1:8000/admin


METHOD 2: Using Laragon Terminal (Alternative)
----------------------------------------------

1. Open Laragon
2. Click "Terminal" button in Laragon
3. Navigate to project:
   cd cms
4. Start server:
   php artisan serve
5. Open browser to: http://127.0.0.1:8000


================================================================================
HOW TO STOP THE APPLICATION
================================================================================

- Press Ctrl+C in the command prompt window where the server is running
- Close the command prompt window


================================================================================
COMMON TASKS
================================================================================

Run Migrations (After Database Changes)
---------------------------------------
cd "C:\Users\Viet Ha\cms"
"C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe" artisan migrate

Rollback Last Migration
-----------------------
"C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe" artisan migrate:rollback

Clear Cache
-----------
"C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe" artisan cache:clear
"C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe" artisan config:clear
"C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe" artisan view:clear

Create New Migration
-------------------
"C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe" artisan make:migration migration_name

Create New Model
----------------
"C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe" artisan make:model ModelName


================================================================================
TROUBLESHOOTING
================================================================================

Problem: "Port 8000 is already in use"
Solution: Either:
  - Stop the existing server running on port 8000
  - Use a different port: php artisan serve --port=8001

Problem: "php: command not found"
Solution: Use the full path to PHP:
  "C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe"

Problem: Database connection error
Solution:
  - Make sure Laragon is running
  - Check MySQL is started in Laragon
  - Verify .env file has correct database credentials

Problem: Page not loading
Solution:
  - Clear browser cache
  - Try: php artisan config:clear
  - Try: php artisan cache:clear
  - Restart the server


================================================================================
CURRENT APPLICATION FEATURES
================================================================================

ARTICLE MANAGEMENT:
- Required fields: Title, Category, Tone, Campaign
- Optional fields: Original Title, Original URL, Tags
- Slug field has been removed (no longer auto-generated)

ARTICLE NAVIGATION (in sidebar):
- Drafts: Shows all draft articles
- Pending Review: Shows articles awaiting approval
- Approved: Shows approved articles ready to publish
- All Articles: Shows all articles regardless of status

TAXONOMY SYSTEM:
- Category: Required, single-select
- Tone: Required, single-select
- Campaign: Required, single-select
- Tags: Optional, multiple-select

DATABASE STRUCTURE:
- articles table includes: tone_id, campaign_id (foreign keys)
- Pivot tables article_tone and article_campaign have been removed
- category_id is now required (NOT NULL)


================================================================================
USEFUL URLS
================================================================================

Main Application:     http://127.0.0.1:8000
Admin Panel:          http://127.0.0.1:8000/admin
Laragon Dashboard:    http://localhost


================================================================================
NOTES
================================================================================

- Always make sure Laragon is running before starting the Laravel server
- Keep the command prompt window open while using the application
- The server must be running for the website to work
- If you close the command prompt, the server stops


================================================================================
QUICK START CHECKLIST
================================================================================

[ ] 1. Start Laragon
[ ] 2. Wait for services to start (Apache, MySQL)
[ ] 3. Open Command Prompt
[ ] 4. Navigate to: cd "C:\Users\Viet Ha\cms"
[ ] 5. Run: "C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe" artisan serve
[ ] 6. Open browser to: http://127.0.0.1:8000/admin
[ ] 7. Start working!


================================================================================
Last Updated: January 21, 2026
================================================================================
