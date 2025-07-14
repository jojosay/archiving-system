# GitHub Repository Setup Guide

## Step 1: Create a New Repository

### Option A: Via GitHub Website (Recommended)
1. **Go to GitHub.com** and sign in to your account
2. **Click the "+" icon** in the top-right corner
3. **Select "New repository"**
4. **Fill in repository details:**
   - **Repository name:** `archiving-system` (or your preferred name)
   - **Description:** `Document archiving and management system`
   - **Visibility:** Choose Public or Private
   - **Initialize:** ✅ Check "Add a README file"
   - **Add .gitignore:** Select "PHP" template
   - **Choose a license:** MIT License (recommended)
5. **Click "Create repository"**

### Option B: Via GitHub CLI (Advanced)
```bash
gh repo create archiving-system --public --description "Document archiving and management system"
```

## Step 2: Clone Repository to Your Local Machine

After creating the repository, you'll see a page with clone instructions:

```bash
# Clone the repository
git clone https://github.com/YOUR_USERNAME/archiving-system.git

# Navigate to the directory
cd archiving-system
```

## Step 3: Add Your Project Files

### Method A: Copy Files Manually
1. Copy all your project files to the cloned repository folder
2. **Include:**
   - All PHP files (api/, includes/, pages/, etc.)
   - Configuration files (config/)
   - Assets (assets/)
   - Database schema (database/)
   - Documentation (docs/)
   - index.php, README.md, CHANGELOG.md

### Method B: Use the Preparation Script
```bash
# Run the release preparation script
php scripts/prepare_release.php

# Extract the created ZIP to your repository folder
```

## Step 4: Configure Git and Push

```bash
# Add all files
git add .

# Commit the files
git commit -m "Initial commit - Archiving System v1.0.0"

# Push to GitHub
git push origin main
```

## Step 5: Update Configuration

After pushing, update your `config/version.php`:

```php
// Update these with your actual GitHub details
define('GITHUB_REPO_OWNER', 'your-actual-username');
define('GITHUB_REPO_NAME', 'archiving-system');
```

## Step 6: Access Releases

Once your repository is created and has files:

1. **Go to your repository page:** `https://github.com/YOUR_USERNAME/archiving-system`
2. **Look for the "Releases" section** on the right sidebar
3. **Or click the "Releases" link** near the top of the repository
4. **If you don't see it:** Look for a small "0 releases" link

### Alternative Ways to Access Releases:
- **Direct URL:** `https://github.com/YOUR_USERNAME/REPO_NAME/releases`
- **Repository tabs:** Code | Issues | Pull requests | Actions | Projects | Wiki | Security | Insights | **Releases**

## Troubleshooting

### "I don't see the Releases section"
- Make sure you're on your repository page (not the main GitHub page)
- The repository must have at least one commit
- Look on the right sidebar under "About"
- Try the direct URL: `/releases`

### "Repository is empty"
- You need to push at least one commit first
- Follow Step 4 above to add and push files

### "Permission denied"
- Make sure you're signed in to GitHub
- Verify you have write access to the repository
- Check if you're using the correct repository URL

## What's Next?

After setting up the repository:
1. ✅ Repository created and files pushed
2. ✅ Configuration updated with correct repo details
3. 🎯 **Ready to create your first release!**

Follow the "FIRST_GITHUB_RELEASE_GUIDE.md" for the next steps.