@echo off
echo ========================================
echo Auto-Pull Script for CMS
echo ========================================
echo.

cd /d "C:\Users\Viet Ha\cms"

echo Checking git status...
git status
echo.

echo Fetching latest changes...
git fetch origin
echo.

echo Current branch:
git branch --show-current
echo.

echo Pulling latest changes...
git pull
echo.

if %errorlevel% equ 0 (
    echo ========================================
    echo Pull completed successfully!
    echo ========================================
) else (
    echo ========================================
    echo Pull failed! Please check for conflicts.
    echo ========================================
)

echo.
pause
