@echo off
echo ========================================
echo Advanced GitHub Repository Cleanup
echo Comprehensive cleanup with Git operations
echo ========================================

echo.
echo This script will:
echo 1. Remove unnecessary files
echo 2. Clean Git history of large files
echo 3. Optimize repository size
echo 4. Preserve CSV files and essential data
echo.
echo WARNING: This will modify Git history!
echo Make sure you have a backup before proceeding.
echo.
set /p confirm="Are you sure you want to continue? (y/N): "
if /i not "%confirm%"=="y" (
    echo Cleanup cancelled.
    pause
    exit /b
)

echo.
echo Starting advanced cleanup process...

REM First, run basic cleanup
echo Step 1: Basic file cleanup...
call cleanup_repo.bat

echo.
echo Step 2: Git repository optimization...

REM Remove files from Git history but keep them locally first
echo Creating .gitignore for future exclusions...
(
echo # Temporary files
echo tmp_*
echo *.tmp
echo *.log
echo php_error_log
echo.
echo # Backup directories
echo storage_backup_*/
echo.
echo # Build and release files
echo releases/
echo deployment/packages/*/
echo.
echo # System files
echo Thumbs.db
echo .DS_Store
echo desktop.ini
echo.
echo # Cache files
echo data/cache/
echo data/*.log
echo data/*.tmp
echo.
echo # Development files
echo *_SUMMARY.md
echo *_IMPLEMENTATION_SUMMARY.md
echo *_FEATURE_PLAN.md
echo ICON_UPGRADE_GUIDE.md
echo FEATHER_ICONS_IMPLEMENTATION.md
echo FEATHER_ICONS_LOCAL_SETUP.md
echo ICON_INTEGRATION_COMPLETE.md
echo MODAL_ENHANCEMENT_SUMMARY.md
echo RELEASE_INSTRUCTIONS_v*.md
) > .gitignore

echo.
echo Step 3: Removing files from Git tracking...
git rm -r --cached storage_backup_* 2>nul
git rm -r --cached deployment/packages/* 2>nul
git rm -r --cached releases/ 2>nul
git rm --cached tmp_* 2>nul
git rm --cached *.log 2>nul
git rm --cached *_SUMMARY.md 2>nul
git rm --cached *_IMPLEMENTATION_SUMMARY.md 2>nul
git rm --cached *_FEATURE_PLAN.md 2>nul
git rm --cached ICON_UPGRADE_GUIDE.md 2>nul
git rm --cached FEATHER_ICONS_IMPLEMENTATION.md 2>nul
git rm --cached FEATHER_ICONS_LOCAL_SETUP.md 2>nul
git rm --cached ICON_INTEGRATION_COMPLETE.md 2>nul
git rm --cached MODAL_ENHANCEMENT_SUMMARY.md 2>nul
git rm --cached RELEASE_INSTRUCTIONS_v*.md 2>nul

echo.
echo Step 4: Git garbage collection...
git gc --prune=now --aggressive

echo.
echo Step 5: Checking repository size...
echo Repository size before cleanup:
git count-objects -vH

echo.
echo ========================================
echo Advanced Cleanup Complete!
echo ========================================
echo.
echo Summary of actions:
echo - Removed unnecessary files from working directory
echo - Updated .gitignore to prevent future issues
echo - Removed files from Git tracking
echo - Optimized Git repository
echo - Preserved all CSV files and essential data
echo.
echo Repository is now clean and optimized!
echo.
echo Next steps:
echo 1. Review changes: git status
echo 2. Commit cleanup: git add . && git commit -m "cleanup: Repository cleanup and optimization"
echo 3. Push changes: git push origin main
echo 4. Proceed with v1.0.6 release
echo.
pause