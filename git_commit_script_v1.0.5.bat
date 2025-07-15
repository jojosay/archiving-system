@echo off
echo ========================================
echo Git Commit Script for Version 1.0.5
echo Version Update and Release Preparation
echo ========================================

echo.
echo Step 1: Checking git status...
git status

echo.
echo Step 2: Adding all modified files...
git add .

echo.
echo Step 3: Committing changes with detailed message...
git commit -m "feat: Version update to 1.0.5 with release preparation

Version Updates:
- Updated APP_VERSION to 1.0.5 in config/version.php
- Updated APP_BUILD to 2025.01.15.005
- Updated RELEASE_INFO.json with new version details
- Updated release scripts for version 1.0.5

Technical Improvements:
- Prepared all version references for new release
- Updated package naming for consistency
- Enhanced release preparation workflow
- Updated CHANGELOG.md with version 1.0.5 entry

Build Configuration:
- Version: 1.0.5
- Build: 2025.01.15.005
- Package: archiving-system-v1.0.5"

echo.
echo Step 4: Creating and pushing version tag...
git tag -a v1.0.5 -m "Release v1.0.5: Version Update and Release Preparation

This release includes:
- Version increment to 1.0.5
- Updated build configuration
- Enhanced release preparation scripts
- Updated documentation and changelog

Technical updates for improved release management
and version consistency across the application."

echo.
echo Step 5: Pushing changes to GitHub...
git push origin main

echo.
echo Step 6: Pushing tags to GitHub...
git push origin --tags

echo.
echo ========================================
echo Commit and Release Process Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Go to GitHub repository: https://github.com/jojosay/archiving-system
echo 2. Create a new release from tag v1.0.5
echo 3. Run the release script: php scripts/simple_release.php
echo 4. Upload the generated ZIP file to the GitHub release
echo 5. Publish the release
echo.
echo GitHub Release URL: https://github.com/jojosay/archiving-system/releases/new
echo.
pause