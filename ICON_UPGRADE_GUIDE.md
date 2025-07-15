# Template Category Icons Upgrade Guide

## Current Situation
Your system currently uses text-based icon names (like 'folder', 'file-text') instead of actual visual icons. Let's upgrade to proper icons!

## Recommended Free Icon Solutions

### 1. **Feather Icons** (Recommended)
- **Website**: https://feathericons.com/
- **License**: MIT (Free for commercial use)
- **Style**: Clean, minimal, consistent
- **Format**: SVG (scalable, crisp)
- **Size**: ~1KB per icon
- **CDN**: Available via CDN (no download needed)

### 2. **Heroicons**
- **Website**: https://heroicons.com/
- **License**: MIT (Free)
- **Style**: Modern, professional
- **Format**: SVG
- **Made by**: Tailwind CSS team

### 3. **Lucide Icons**
- **Website**: https://lucide.dev/
- **License**: ISC (Free)
- **Style**: Fork of Feather Icons with more icons
- **Format**: SVG

## Implementation Options

### Option A: CDN Integration (Easiest - No Downloads)
Add this to your layout header:
```html
<script src="https://unpkg.com/feather-icons"></script>
```

### Option B: Download SVG Files
Download individual SVG files and store in `assets/icons/`

### Option C: Icon Font
Use an icon font like Font Awesome (requires account for newer versions)

## Current Icons in Your System
Based on your code, you have these icon names:
- folder, file-text, mail, bar-chart, award, grid
- briefcase, book, clipboard, database, edit, file
- home, image, layers, list, map, package
- printer, settings, star, tag, users, calendar, clock

## Recommended Implementation Plan

### Step 1: Add Feather Icons CDN
Add to your layout.php head section:
```html
<script src="https://unpkg.com/feather-icons"></script>
```

### Step 2: Update Icon Display
Instead of showing text, show actual icons:
```php
<i data-feather="<?php echo $category['icon']; ?>"></i>
```

### Step 3: Initialize Icons
Add JavaScript to render icons:
```javascript
feather.replace();
```

### Step 4: Enhanced Icon Selection
Create a visual icon picker with previews.

## Icon Mapping for Your Categories

Here's how your current text icons map to Feather icons:

| Current | Feather Icon | Description |
|---------|--------------|-------------|
| folder | folder | Folder |
| file-text | file-text | Document |
| mail | mail | Email |
| bar-chart | bar-chart-2 | Chart |
| award | award | Award |
| grid | grid | Grid |
| briefcase | briefcase | Business |
| book | book | Book |
| clipboard | clipboard | Clipboard |
| database | database | Database |
| edit | edit | Edit |
| file | file | File |
| home | home | Home |
| image | image | Image |
| layers | layers | Layers |
| list | list | List |
| map | map | Map |
| package | package | Package |
| printer | printer | Printer |
| settings | settings | Settings |
| star | star | Star |
| tag | tag | Tag |
| users | users | Users |
| calendar | calendar | Calendar |
| clock | clock | Clock |

## Additional Suggested Icons for Template Categories

For better template categorization, consider these icons:
- **Legal**: `file-text`, `scale`, `shield`
- **Financial**: `dollar-sign`, `trending-up`, `calculator`
- **HR**: `users`, `user-check`, `heart`
- **Marketing**: `megaphone`, `target`, `trending-up`
- **Reports**: `bar-chart-2`, `pie-chart`, `activity`
- **Contracts**: `file-signature`, `handshake`, `check-circle`
- **Invoices**: `receipt`, `credit-card`, `dollar-sign`
- **Letters**: `mail`, `send`, `message-square`
- **Forms**: `clipboard`, `check-square`, `edit-3`
- **Certificates**: `award`, `star`, `shield`

## Next Steps

1. **Choose your preferred option** (I recommend CDN for simplicity)
2. **I can implement the changes** to your code
3. **Test the visual improvements**
4. **Add more relevant icons** for your specific use cases

Would you like me to implement the Feather Icons integration for you?