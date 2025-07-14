# Upload Local Project to GitHub Repository

## Method 1: Using Git Commands (Recommended)

### Step 1: Initialize Git in Your Project
Open Command Prompt/Terminal in your project folder:
```bash
# Navigate to your project folder
cd C:\xampp\htdocs\archiving-system

# Initialize git (if not already done)
git init

# Add all files to git
git add .

# Create initial commit
git commit -m "Initial commit - Archiving System v1.0.1"
```

### Step 2: Connect to Your GitHub Repository
```bash
# Add your GitHub repository as remote origin
git remote add origin https://github.com/YOUR_USERNAME/archiving-system.git

# Push to GitHub
git push -u origin main
```

**If you get an error about 'main' vs 'master':**
```bash
# Check current branch name
git branch

# If it shows 'master', use:
git push -u origin master

# Or rename to main:
git branch -M main
git push -u origin main
```

## Method 2: GitHub Desktop (User-Friendly)

### Step 1: Download GitHub Desktop
1. Go to [desktop.github.com](https://desktop.github.com)
2. Download and install GitHub Desktop
3. Sign in with your GitHub account

### Step 2: Clone Your Repository
1. In GitHub Desktop, click **"Clone a repository from the Internet"**
2. Select your `archiving-system` repository
3. Choose where to clone it (e.g., `C:\GitHub\archiving-system`)

### Step 3: Copy Your Files
1. Copy all files from `C:\xampp\htdocs\archiving-system\`
2. Paste them into the cloned folder
3. GitHub Desktop will automatically detect changes

### Step 4: Commit and Push
1. In GitHub Desktop, you'll see all changed files
2. Add a commit message: "Initial commit - Archiving System v1.0.0"
3. Click **"Commit to main"**
4. Click **"Push origin"**

## Method 3: GitHub Web Interface (Simple Upload)

### Step 1: Prepare Files
1. Create a ZIP file of your project (excluding unnecessary files)
2. Or select files manually

### Step 2: Upload via Web
1. Go to your GitHub repository page
2. Click **"uploading an existing file"** link
3. Drag and drop files or click "choose your files"
4. Add commit message: "Initial commit - Archiving System v1.0.0"
5. Click **"Commit changes"**

## Files to Include

### ✅ Include These:
```
api/
assets/
config/
database/
docs/
includes/
pages/
scripts/
index.php
README.md
CHANGELOG.md
.htaccess
```

### ❌ Exclude These:
```
data/                    # User data
storage/documents/       # Uploaded files
backups/                # Database backups
php_error_log           # Error logs
tmp_*                   # Temporary files
.env                    # Environment files (if any)
```

## Step-by-Step for Git Method

### 1. Open Command Prompt as Administrator
```cmd
cd C:\xampp\htdocs\archiving-system
```

### 2. Check if Git is Installed
```cmd
git --version
```
If not installed, download from [git-scm.com](https://git-scm.com)

### 3. Configure Git (First Time Only)
```cmd
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"
```

### 4. Initialize and Upload
```cmd
git init
git add .
git commit -m "Initial commit - Archiving System v1.0.0"
git remote add origin https://github.com/YOUR_USERNAME/archiving-system.git
git push -u origin main
```

## Troubleshooting

### Error: "Repository not found"
- Check the repository URL
- Make sure you're signed in to GitHub
- Verify repository name spelling

### Error: "Permission denied"
- Use personal access token instead of password
- Go to GitHub Settings → Developer settings → Personal access tokens
- Create token and use it as password

### Error: "Branch 'main' doesn't exist"
```cmd
git branch -M main
git push -u origin main
```

### Large Files Warning
If you have large files (>100MB):
```cmd
# Remove large files from git
git rm --cached path/to/large/file
git commit -m "Remove large files"
```

## Verification

After uploading, check:
1. ✅ All files are visible on GitHub
2. ✅ Repository shows file count and latest commit
3. ✅ README.md displays properly
4. ✅ "Releases" section is now visible

## Next Steps

Once uploaded successfully:
1. ✅ Update `config/version.php` with correct repository details
2. ✅ Test the update system
3. ✅ Create your first release
4. ✅ Test update detection