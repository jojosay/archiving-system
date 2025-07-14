# How to Prevent Files from Being Uploaded to GitHub

## Using .gitignore File

The `.gitignore` file tells Git which files to ignore. I've already created one for your project.

## Current .gitignore Protection

Your project already ignores these sensitive files:
- `data/` - User data and uploads
- `storage/documents/` - Uploaded documents
- `backups/` - Database backups
- `*.log` - Log files
- `php_error_log` - Error logs
- `tmp_*` - Temporary files

## Add More Files to Ignore

### Method 1: Edit .gitignore File
Open `.gitignore` and add new lines:
```
# Add specific files
config/secret.php
my-sensitive-file.txt

# Add file patterns
*.backup
*.tmp
secret_*

# Add entire folders
private/
sensitive-data/
```

### Method 2: Ignore Already Tracked Files
If a file is already in GitHub and you want to stop tracking it:

```cmd
# Remove from Git but keep local file
git rm --cached filename.txt

# Remove entire folder from Git
git rm -r --cached foldername/

# Commit the removal
git commit -m "Remove sensitive files from tracking"

# Push changes
git push origin main
```

## Common Files to Ignore

### Sensitive Data:
```
# Database credentials
config/database.php
.env
.env.local

# User uploads
uploads/
storage/
data/

# Backups
backups/
*.sql
*.db
```

### Development Files:
```
# IDE files
.vscode/
.idea/
*.swp

# OS files
.DS_Store
Thumbs.db

# Logs
*.log
error_log
```

## Quick Commands

### Check what files Git is tracking:
```cmd
git ls-files
```

### Check what files are ignored:
```cmd
git status --ignored
```

### Test if a file is ignored:
```cmd
git check-ignore filename.txt
```

## Emergency: Remove Sensitive File Already Uploaded

If you accidentally uploaded a sensitive file:

```cmd
# Remove from all Git history (DANGEROUS!)
git filter-branch --force --index-filter 'git rm --cached --ignore-unmatch sensitive-file.txt' --prune-empty --tag-name-filter cat -- --all

# Force push to overwrite GitHub
git push --force --all
```

**⚠️ Warning: This rewrites Git history and can break things!**