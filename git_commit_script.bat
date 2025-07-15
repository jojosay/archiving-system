@echo off
echo ========================================
echo Git Commit Script for Version 1.0.3
echo Enhanced Reports Feature Implementation
echo ========================================

echo.
echo Step 1: Checking git status...
git status

echo.
echo Step 2: Adding all modified files...
git add .

echo.
echo Step 3: Committing changes with detailed message...
git commit -m "feat: Enhanced Reports with 6 new report types and Quick Actions

- Added Recent Activity Summary (Last 30 Days) with key metrics
- Added Storage Usage Analytics by document type  
- Added Upload Trends by Day of Week analysis
- Added File Size Analytics with min/max/average calculations
- Added Document Statistics by Location (geographic distribution)
- Implemented Document Archive Report with comprehensive export
- Added System Audit Log with activity tracking
- Created Custom Report Builder with dynamic filtering
- Added dual export formats (CSV and JSON) for all reports
- Added debug mode for troubleshooting report generation

Enhanced Features:
- Quick Report Actions with fully functional buttons
- Professional UI/UX with color-coded export buttons
- Advanced filtering options in Custom Report Builder
- Comprehensive error handling and logging
- Performance-optimized database queries

Bug Fixes:
- Fixed Generate Report button functionality
- Resolved CSV export issues with proper headers
- Added empty data handling with meaningful fallbacks
- Fixed output buffer conflicts in export functions
- Fixed About page version display to use dynamic version from config

Technical Improvements:
- Added 6 new methods to ReportManager class
- Enhanced CSV export with UTF-8 BOM support
- Improved error logging and debugging capabilities
- Added comprehensive documentation for new features
- Updated version references across branding configuration files

Version: 1.0.3
Build: 2025.01.14.003"

echo.
echo Step 4: Creating and pushing version tag...
git tag -a v1.0.3 -m "Release v1.0.3: Enhanced Reports Feature

Major enhancements to the reporting system:
- 6 new comprehensive report types
- Document Archive Report with full export
- System Audit Log for activity tracking  
- Custom Report Builder with dynamic filtering
- Dual format exports (CSV/JSON)
- Professional UI improvements
- Debug mode for troubleshooting

This release significantly expands the reporting capabilities
with advanced analytics and user-friendly interfaces."

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
echo 1. Go to GitHub repository
echo 2. Create a new release from tag v1.0.3
echo 3. Upload release assets if needed
echo 4. Publish the release
echo.
pause