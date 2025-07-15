# PowerShell Git Commit Script for Version 1.0.5
# Version Update and Release Preparation

Write-Host "========================================" -ForegroundColor Green
Write-Host "Git Commit Script for Version 1.0.5" -ForegroundColor Green
Write-Host "Version Update and Release Preparation" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green

Write-Host "`nStep 1: Checking git status..." -ForegroundColor Yellow
git status

Write-Host "`nStep 2: Adding all modified files..." -ForegroundColor Yellow
git add .

Write-Host "`nStep 3: Committing changes with detailed message..." -ForegroundColor Yellow
git commit -m @"
feat: Version update to 1.0.5 with release preparation

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
- Package: archiving-system-v1.0.5
"@

Write-Host "`nStep 4: Creating and pushing version tag..." -ForegroundColor Yellow
git tag -a v1.0.5 -m @"
Release v1.0.5: Version Update and Release Preparation

This release includes:
- Version increment to 1.0.5
- Updated build configuration
- Enhanced release preparation scripts
- Updated documentation and changelog

Technical updates for improved release management
and version consistency across the application.
"@

Write-Host "`nStep 5: Pushing changes to GitHub..." -ForegroundColor Yellow
git push origin main

Write-Host "`nStep 6: Pushing tags to GitHub..." -ForegroundColor Yellow
git push origin --tags

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "Commit and Release Process Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green

Write-Host "`nNext steps:" -ForegroundColor Cyan
Write-Host "1. Go to GitHub repository: https://github.com/jojosay/archiving-system" -ForegroundColor White
Write-Host "2. Create a new release from tag v1.0.5" -ForegroundColor White
Write-Host "3. Run the release script: php scripts/simple_release.php" -ForegroundColor White
Write-Host "4. Upload the generated ZIP file to the GitHub release" -ForegroundColor White
Write-Host "5. Publish the release" -ForegroundColor White

Read-Host "`nPress Enter to continue"