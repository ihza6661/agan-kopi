# 🚀 Deployment & CI/CD Guide

Panduan lengkap untuk deployment, CI/CD, dan branch protection untuk Agan Kopi POS.

## 📋 Table of Contents

- [Branch Strategy](#-branch-strategy)
- [CI/CD Workflows](#-cicd-workflows)
- [Setup Instructions](#-setup-instructions)
- [Branch Protection Rules](#-branch-protection-rules)
- [Daily Workflow](#-daily-workflow)
- [Troubleshooting](#-troubleshooting)

---

## 🌿 Branch Strategy

### Branch Structure

```
main (Development)
├── Fitur baru
├── Bug fixes
├── Eksperimen
└── CI: Auto testing

production (Production)
├── Kode stabil
├── Sudah tested
├── CD: Auto deploy ke Heroku
└── Branch protection enabled
```

### Branch Roles

| Branch | Purpose | CI/CD | Deploy Target |
|--------|---------|-------|---------------|
| `main` | Development, fitur baru | ✅ Auto test | Local only |
| `production` | Production stable code | ✅ Auto test + deploy | Heroku staging |

---

## 🤖 CI/CD Workflows

### 1. CI - Main Branch Testing

**File:** `.github/workflows/ci-main.yml`

**Trigger:**
- Push ke `main`
- Pull Request ke `main`

**Actions:**
- ✅ Setup PHP 8.2 + PostgreSQL
- ✅ Install dependencies (Composer + NPM)
- ✅ Run database migrations
- ✅ Build frontend assets
- ✅ Run PHPUnit tests
- ✅ Lint frontend code

**Duration:** ~3-5 minutes

**Badge Status:** ![CI Main](https://github.com/ihza6661/agan-kopi/actions/workflows/ci-main.yml/badge.svg)

---

### 2. CD - Production Deployment

**File:** `.github/workflows/cd-production.yml`

**Trigger:**
- Push ke `production` branch
- Manual trigger via GitHub Actions UI

**Actions:**
- ✅ Build production assets
- ✅ Deploy to Heroku
- ✅ Run database migrations on Heroku
- ✅ Clear application cache
- ✅ Verify deployment
- ✅ Create deployment summary

**Duration:** ~5-8 minutes

**Badge Status:** ![CD Production](https://github.com/ihza6661/agan-kopi/actions/workflows/cd-production.yml/badge.svg)

---

### 3. PR Check - Production Branch

**File:** `.github/workflows/pr-production.yml`

**Trigger:**
- Pull Request ke `production` branch

**Actions:**
- ✅ Validate PR description exists
- ✅ Validate source branch (only `main` or `hotfix/*`)
- ✅ Run full test suite with coverage
- ✅ Comment test results on PR

**Duration:** ~4-6 minutes

---

## 🔧 Setup Instructions

### Prerequisites

1. **GitHub Repository:** https://github.com/ihza6661/agan-kopi
2. **Heroku App:** agan-kopi-pos
3. **Heroku API Key:** Required for auto-deployment

### Step 1: Add GitHub Secrets

Navigate to: `https://github.com/ihza6661/agan-kopi/settings/secrets/actions`

Add the following secrets:

#### Required Secrets:

| Secret Name | Description | How to Get |
|-------------|-------------|------------|
| `HEROKU_API_KEY` | Heroku API Key for deployment | `heroku auth:token` |
| `HEROKU_EMAIL` | Your Heroku account email | Your login email |

**Get Heroku API Key:**
```bash
heroku auth:token
```

**Add to GitHub:**
1. Go to repo Settings → Secrets and variables → Actions
2. Click "New repository secret"
3. Name: `HEROKU_API_KEY`, Value: (paste token)
4. Click "Add secret"
5. Repeat for `HEROKU_EMAIL`

### Step 2: Enable GitHub Actions

1. Go to repo Settings → Actions → General
2. Under "Actions permissions", select: **Allow all actions and reusable workflows**
3. Under "Workflow permissions", select: **Read and write permissions**
4. Check: **Allow GitHub Actions to create and approve pull requests**
5. Click **Save**

### Step 3: Setup Branch Protection Rules

Go to: `https://github.com/ihza6661/agan-kopi/settings/branches`

#### For `production` Branch:

Click **Add branch protection rule**

**Branch name pattern:** `production`

Enable the following:

- ✅ **Require a pull request before merging**
  - Required approvals: 1 (if team) or 0 (if solo)
  - Dismiss stale pull request approvals when new commits are pushed
  
- ✅ **Require status checks to pass before merging**
  - Require branches to be up to date before merging
  - Status checks that are required:
    - `test` (from pr-production.yml)
    - `validate` (from pr-production.yml)
    
- ✅ **Require conversation resolution before merging**

- ✅ **Include administrators** (if team)

- ✅ **Restrict who can push to matching branches**
  - Add yourself or team members

Click **Create** or **Save changes**

#### For `main` Branch (Optional but Recommended):

**Branch name pattern:** `main`

Enable:
- ✅ **Require status checks to pass before merging**
  - Status checks: `test`, `lint`

This ensures all code in `main` is tested before commit.

---

## 💼 Daily Workflow

### Scenario 1: Develop New Feature

```bash
# 1. Start from main
git checkout main
git pull origin main

# 2. Create feature branch (optional)
git checkout -b feature/new-export

# 3. Develop & test locally
# ... edit code ...
npm run build
php artisan test

# 4. Commit changes
git add .
git commit -m "feat: add CSV export for shifts"

# 5. Push to main
git checkout main
git merge feature/new-export
git push origin main
# → CI automatically runs tests ✅
```

### Scenario 2: Deploy to Production

**Method A: Via Pull Request (Recommended)**

```bash
# 1. Create PR from main → production
gh pr create --base production --head main --title "Deploy v1.2.0" --body "
## Changes
- Feature: CSV export for shifts
- Fix: Opening cash display
- Fix: CSRF token refresh

## Testing
- ✅ All tests passing
- ✅ Manual testing completed
- ✅ Database migrations tested
"

# 2. Wait for PR checks to complete
# 3. Review and merge PR on GitHub UI
# → CD automatically deploys to Heroku ✅
```

**Method B: Direct Push (Quick Deploy)**

```bash
# 1. Switch to production
git checkout production

# 2. Merge from main
git merge main

# 3. Push to GitHub
git push origin production
# → CD automatically deploys to Heroku ✅

# 4. Back to main
git checkout main
```

### Scenario 3: Hotfix Critical Bug

```bash
# 1. Create hotfix branch from production
git checkout production
git checkout -b hotfix/critical-shift-bug

# 2. Fix the bug
# ... edit code ...

# 3. Test locally
php artisan test

# 4. Commit & push
git add .
git commit -m "hotfix: fix shift validation error"
git push origin hotfix/critical-shift-bug

# 5. Create PR to production
gh pr create --base production --head hotfix/critical-shift-bug \
  --title "HOTFIX: Shift validation error" \
  --body "Critical fix for production issue"

# 6. Merge PR → Auto deploys

# 7. Sync back to main
git checkout main
git merge hotfix/critical-shift-bug
git push origin main
```

---

## 🛡️ Branch Protection Rules

### Why Branch Protection?

| Without Protection | With Protection |
|-------------------|-----------------|
| ⚠️ Accidental force push | ✅ Cannot force push |
| ⚠️ Deploy untested code | ✅ Tests must pass |
| ⚠️ No code review | ✅ PR review required |
| ⚠️ Breaking production | ✅ Safe deployment |

### Protection Status

#### `production` Branch:
- ✅ Require PR with passing tests
- ✅ Require status checks (CI must pass)
- ✅ Cannot force push
- ✅ Cannot delete branch
- ✅ Require conversation resolution

#### `main` Branch:
- ✅ Require status checks (CI must pass)
- ℹ️ Direct push allowed (for quick iteration)

---

## 🔍 Monitoring & Verification

### Check CI/CD Status

**GitHub Actions:**
https://github.com/ihza6661/agan-kopi/actions

**View Logs:**
```bash
# Latest workflow run
gh run list --limit 5

# View specific run
gh run view <run-id> --log
```

### Check Heroku Deployment

```bash
# View logs
heroku logs --tail -a agan-kopi-pos

# Check app status
heroku ps -a agan-kopi-pos

# Check recent releases
heroku releases -a agan-kopi-pos

# Rollback if needed
heroku rollback -a agan-kopi-pos
```

### Health Check Endpoints

- **App URL:** https://agan-kopi-pos-b332db5d7f2e.herokuapp.com/
- **Login:** https://agan-kopi-pos-b332db5d7f2e.herokuapp.com/login

**Quick Test:**
```bash
curl -I https://agan-kopi-pos-b332db5d7f2e.herokuapp.com/
# Should return HTTP 200 or 302
```

---

## 🚨 Troubleshooting

### CI Fails on Main Branch

**Symptoms:** ❌ Red X on commit in GitHub

**Solutions:**

1. **Check workflow logs:**
   ```bash
   gh run view --log
   ```

2. **Common issues:**
   - Test failures → Fix tests locally
   - Build errors → Run `npm run build` locally
   - Database migrations → Check migration files

3. **Fix and re-push:**
   ```bash
   # Fix the issue
   git add .
   git commit -m "fix: resolve CI failure"
   git push origin main
   ```

### CD Fails on Production

**Symptoms:** Deployment to Heroku fails

**Solutions:**

1. **Check Heroku API Key:**
   ```bash
   # Regenerate token
   heroku auth:token
   
   # Update GitHub secret
   # Go to repo Settings → Secrets → Update HEROKU_API_KEY
   ```

2. **Check Heroku app:**
   ```bash
   heroku apps:info -a agan-kopi-pos
   heroku logs --tail -a agan-kopi-pos
   ```

3. **Manual deployment:**
   ```bash
   git checkout production
   git push heroku production:main
   ```

### Branch Protection Blocks Merge

**Symptoms:** Cannot merge PR to production

**Solutions:**

1. **Wait for CI checks to complete** (may take 3-5 minutes)

2. **If tests fail:**
   ```bash
   # Fix in main branch
   git checkout main
   # ... fix issues ...
   git push origin main
   
   # Update PR (if from main)
   # PR will automatically update and re-run checks
   ```

3. **If urgent hotfix needed:**
   - Admin can temporarily disable branch protection
   - Or use "Administrator override" option

### Deployment Successful but App Crashes

**Check Heroku logs:**
```bash
heroku logs --tail -a agan-kopi-pos
```

**Common issues:**

1. **Missing environment variables:**
   ```bash
   heroku config -a agan-kopi-pos
   # Make sure all required vars are set
   ```

2. **Database migration failed:**
   ```bash
   heroku run "php artisan migrate --force" -a agan-kopi-pos
   ```

3. **Build assets missing:**
   ```bash
   # Re-trigger deployment
   git commit --allow-empty -m "Trigger rebuild"
   git push origin production
   ```

4. **Rollback to previous version:**
   ```bash
   heroku releases -a agan-kopi-pos
   heroku rollback v14 -a agan-kopi-pos
   ```

---

## 📊 Workflow Visualization

### Development Flow:
```
Developer
    ↓
  Code in main branch
    ↓
  Push to GitHub
    ↓
  GitHub Actions CI
    ├─ Run Tests ✅
    ├─ Build Assets ✅
    └─ Lint Code ✅
    ↓
  (If all pass) Commit accepted
```

### Production Deployment Flow:
```
main branch (tested)
    ↓
  Create PR → production
    ↓
  PR Checks Run
    ├─ Validate PR ✅
    ├─ Run Full Tests ✅
    └─ Check Coverage ✅
    ↓
  Merge PR (if approved)
    ↓
  GitHub Actions CD
    ├─ Build Production Assets ✅
    ├─ Deploy to Heroku ✅
    ├─ Run Migrations ✅
    └─ Clear Cache ✅
    ↓
  🚀 Live on Heroku!
```

---

## 📈 Benefits of This Setup

| Benefit | Description |
|---------|-------------|
| **Automatic Testing** | Every commit tested automatically |
| **Safe Deployment** | Can't deploy broken code |
| **Fast Iteration** | Push to main = instant testing |
| **Zero-Touch Deploy** | Merge to production = auto deploy |
| **Rollback Ready** | Easy to revert if issues |
| **Audit Trail** | All changes tracked in PRs |
| **Team Ready** | PR reviews for collaboration |

---

## 🎯 Best Practices

1. ✅ **Always test locally first** before pushing
2. ✅ **Write descriptive commit messages** (feat/fix/docs/refactor)
3. ✅ **Keep main branch stable** (don't push broken code)
4. ✅ **Use PR for production deployments** (better audit trail)
5. ✅ **Monitor Heroku logs** after deployment
6. ✅ **Write tests** for new features
7. ✅ **Update .env.example** when adding new env vars
8. ✅ **Document breaking changes** in PR description

---

## 📞 Need Help?

- **GitHub Actions Logs:** https://github.com/ihza6661/agan-kopi/actions
- **Heroku Dashboard:** https://dashboard.heroku.com/apps/agan-kopi-pos
- **Heroku Logs:** `heroku logs --tail -a agan-kopi-pos`

For issues or questions, check the logs first, then refer to troubleshooting section above.

---

**Last Updated:** 2026-01-24  
**Maintained By:** Development Team
