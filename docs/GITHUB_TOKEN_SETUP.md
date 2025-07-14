# GitHub Personal Access Token Setup

## Step 1: Create Personal Access Token

1. **Go to GitHub.com** and sign in
2. **Click your profile picture** (top-right) → **Settings**
3. **Scroll down** → Click **"Developer settings"** (left sidebar)
4. **Click "Personal access tokens"** → **"Tokens (classic)"**
5. **Click "Generate new token"** → **"Generate new token (classic)"**
6. **Fill in the form:**
   - **Note:** `Archiving System Upload`
   - **Expiration:** `90 days` (or your preference)
   - **Scopes:** Check these boxes:
     - ✅ `repo` (Full control of private repositories)
     - ✅ `workflow` (Update GitHub Action workflows)
7. **Click "Generate token"**
8. **COPY THE TOKEN** - You won't see it again!

## Step 2: Use Token Instead of Password

When Git asks for your password, use the token instead:

```cmd
Username: jojosay
Password: [PASTE YOUR TOKEN HERE]
```

## Step 3: Try Push Again

```cmd
git push -u origin main
```

When prompted:
- **Username:** `jojosay`
- **Password:** `[YOUR_PERSONAL_ACCESS_TOKEN]`

## Alternative: Store Credentials

To avoid typing the token every time:

```cmd
git config --global credential.helper store
```

Then push once with the token, and Git will remember it.

## Quick Fix Commands

```cmd
# Remove old remote
git remote remove origin

# Add remote with token in URL (replace YOUR_TOKEN)
git remote add origin https://YOUR_TOKEN@github.com/jojosay/archiving-system.git

# Push
git push -u origin main
```

## Token Security Tips

- ✅ Treat tokens like passwords
- ✅ Don't share or commit tokens
- ✅ Set reasonable expiration dates
- ✅ Delete unused tokens