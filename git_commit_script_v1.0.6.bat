@echo off
echo ========================================
echo Git Commit Script for Version 1.0.6
echo Visual Icons and Modal Enhancement
echo ========================================

echo.
echo Step 1: Checking git status...
git status

echo.
echo Step 2: Adding all modified files...
git add .

echo.
echo Step 3: Committing changes with detailed message...
git commit -m "feat: Visual Icon System and Modal Enhancement v1.0.6

Major UI/UX Enhancements:
- Implemented visual SVG icon system for template categories
- Added 30+ professional Feather icons stored locally
- Created categorized icon selection interface
- Enhanced modal responsiveness and accessibility

Visual Icon System:
- IconManager class for efficient SVG handling
- Categorized icons (Business, Documents, Communication, etc.)
- Real-time icon preview during selection
- Professional icon grid with hover effects
- Local storage (no CDN dependencies)

Modal Enhancements:
- Fixed modal height issues and cut-off problems
- Added scrollable content with sticky header/footer
- Mobile-optimized responsive design
- Touch-friendly interface improvements

Technical Improvements:
- New IconManager class for SVG management
- Enhanced Template Category Manager integration
- Professional CSS styling for icons and modals
- Improved responsive design across all devices

User Experience:
- Visual icons replace text-based names
- Smooth selection with visual feedback
- Better mobile experience
- Professional appearance throughout

Files Added/Modified:
- includes/icon_manager.php (NEW)
- assets/icons/feather/ (30+ SVG files)
- assets/css/custom/icons.css (NEW)
- Enhanced template category pages
- Updated modal CSS for responsiveness

Version: 1.0.6
Build: 2025.01.15.006"

echo.
echo Step 4: Creating and pushing version tag...
git tag -a v1.0.6 -m "Release v1.0.6: Visual Icon System and Modal Enhancement

This release introduces a complete visual overhaul of the template 
category system with professional SVG icons and enhanced modals.

Key Features:
- Visual SVG icon system with 30+ professional icons
- Categorized icon selection interface
- Real-time icon preview functionality
- Enhanced responsive modal design
- Mobile-optimized user experience
- Local asset storage for better performance

Technical Enhancements:
- IconManager class for SVG handling
- Professional CSS styling
- Improved responsive design
- Touch-friendly mobile interface

This release significantly improves the user experience with 
modern visual design and enhanced functionality."

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
echo 2. Create a new release from tag v1.0.6
echo 3. Run the release script: php scripts/simple_release.php
echo 4. Upload the generated ZIP file to the GitHub release
echo 5. Publish the release
echo.
echo GitHub Release URL: https://github.com/jojosay/archiving-system/releases/new
echo.
pause