@echo off
echo ========================================
echo GitHub Repository Cleanup Script
echo Removing unnecessary files for clean repo
echo ========================================

echo.
echo WARNING: This will permanently delete files!
echo Make sure you have a backup before proceeding.
echo.
set /p confirm="Are you sure you want to continue? (y/N): "
if /i not "%confirm%"=="y" (
    echo Cleanup cancelled.
    pause
    exit /b
)

echo.
echo Starting cleanup process...

REM Remove temporary and development files
echo Removing temporary files...
if exist "tmp_*" del /q "tmp_*"
if exist "*.tmp" del /q "*.tmp"
if exist "*.log" del /q "*.log"
if exist "php_error_log" del "php_error_log"

REM Remove backup directories (keep CSV files inside if any)
echo Removing backup directories...
if exist "storage_backup_*" (
    echo Found backup directories, removing...
    for /d %%i in (storage_backup_*) do (
        echo Removing %%i
        rmdir /s /q "%%i"
    )
)

REM Remove deployment packages (they can be regenerated)
echo Removing old deployment packages...
if exist "deployment\packages" (
    echo Removing deployment packages...
    rmdir /s /q "deployment\packages"
    mkdir "deployment\packages"
    echo. > "deployment\packages\.gitkeep"
)

REM Remove releases directory (will be recreated)
echo Removing releases directory...
if exist "releases" (
    rmdir /s /q "releases"
)

REM Remove documentation files that are for development only
echo Removing development documentation...
if exist "ICON_UPGRADE_GUIDE.md" del "ICON_UPGRADE_GUIDE.md"
if exist "FEATHER_ICONS_IMPLEMENTATION.md" del "FEATHER_ICONS_IMPLEMENTATION.md"
if exist "FEATHER_ICONS_LOCAL_SETUP.md" del "FEATHER_ICONS_LOCAL_SETUP.md"
if exist "ICON_INTEGRATION_COMPLETE.md" del "ICON_INTEGRATION_COMPLETE.md"
if exist "MODAL_ENHANCEMENT_SUMMARY.md" del "MODAL_ENHANCEMENT_SUMMARY.md"
if exist "RELEASE_INSTRUCTIONS_v*.md" del "RELEASE_INSTRUCTIONS_v*.md"

REM Remove old version commit scripts (keep only latest)
echo Removing old commit scripts...
if exist "git_commit_script.ps1" del "git_commit_script.ps1"
if exist "git_commit_script.bat" del "git_commit_script.bat"
if exist "git_commit_script_v1.0.5.ps1" del "git_commit_script_v1.0.5.ps1"

REM Remove development summary files
echo Removing development summary files...
if exist "*_SUMMARY.md" del "*_SUMMARY.md"
if exist "*_IMPLEMENTATION_SUMMARY.md" del "*_IMPLEMENTATION_SUMMARY.md"
if exist "*_FEATURE_PLAN.md" del "*_FEATURE_PLAN.md"

REM Remove system files
echo Removing system files...
if exist "Thumbs.db" del /q "Thumbs.db"
if exist ".DS_Store" del /q ".DS_Store"
if exist "desktop.ini" del /q "desktop.ini"

REM Clean up data directory but preserve structure
echo Cleaning data directory...
if exist "data" (
    if exist "data\*.log" del /q "data\*.log"
    if exist "data\*.tmp" del /q "data\*.tmp"
    if exist "data\cache\*" del /q "data\cache\*" 2>nul
)

REM Clean up storage but preserve CSV files and structure
echo Cleaning storage directory (preserving CSV files)...
if exist "storage" (
    REM Remove document files but keep structure
    if exist "storage\documents" (
        for /r "storage\documents" %%f in (*) do (
            if /i not "%%~xf"==".csv" if /i not "%%~xf"==".htaccess" (
                del "%%f" 2>nul
            )
        )
    )
)

echo.
echo ========================================
echo Cleanup Summary
echo ========================================
echo.
echo Removed:
echo - Temporary files (tmp_*, *.tmp, *.log)
echo - Backup directories (storage_backup_*)
echo - Old deployment packages
echo - Development documentation files
echo - Old commit scripts
echo - System files (Thumbs.db, .DS_Store)
echo - Cache files
echo.
echo Preserved:
echo - All CSV files
echo - Essential project files
echo - Configuration files
echo - Source code
echo - Current documentation
echo - .htaccess files
echo.
echo ========================================
echo Cleanup Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Review the changes with: git status
echo 2. Add changes: git add .
echo 3. Commit: git commit -m "cleanup: Remove unnecessary files for clean repo"
echo 4. Push: git push origin main
echo.
pause