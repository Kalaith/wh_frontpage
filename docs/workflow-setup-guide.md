# 🛠️ Web Hatchery — GitHub Workflow Setup Guide

This document covers what every Web Hatchery project needs to configure for the gamification and CI workflows to function correctly.

---

## Quick Reference — What Each Workflow Needs

| Workflow | File | Secrets Needed | Labels Needed | Permissions |
|---|---|---|---|---|
| CI | `ci.yml` | `GITHUB_TOKEN` (auto) | — | `contents: write` |
| Award XP | `award-xp.yml` | `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | XP labels | — |
| Quest Bot | `quest-bot.yml` | `GITHUB_TOKEN` (auto) | Quest/class labels | `issues: write` |
| Daily Bounties | `daily-bounties.yml` | `GITHUB_TOKEN` (auto) | Quest/class/XP labels | `issues: write` |

---

## 1. Repository Secrets

### Required for XP Awards (Award XP workflow only)

Go to **Settings → Secrets and variables → Actions** in your repo and add:

| Secret | Description | Example |
|---|---|---|
| `DB_HOST` | Database server hostname | `localhost` or `db.example.com` |
| `DB_DATABASE` | Database name | `webhatchery` |
| `DB_USERNAME` | Database user | `wh_user` |
| `DB_PASSWORD` | Database password | `your-secure-password` |

> [!IMPORTANT]
> These secrets must be set for XP to be awarded on PR merges. Without them, the `award-xp.yml` workflow will fail.

### Auto-Provided (No Action Needed)

| Secret | Used By | Notes |
|---|---|---|
| `GITHUB_TOKEN` | Quest Bot, Daily Bounties, CI | Automatically provided by GitHub Actions — no setup needed |

---

## 2. GitHub Labels

Create these labels in your repository (**Issues → Labels → New Label**).

### XP Labels (used by Award XP on merge)

| Label | XP Value | Color Suggestion |
|---|---|---|
| `xp:tiny` | +10 XP | `#e6e6e6` (light grey) |
| `xp:small` | +50 XP | `#7dc67d` (green) |
| `xp:medium` | +200 XP | `#3b82f6` (blue) |
| `xp:large` | +500 XP | `#a855f7` (purple) |
| `xp:epic` | +1000 XP | `#f59e0b` (gold) |

> [!TIP]
> Every merged PR gets a base +50 XP automatically, on top of any label bonus.

### Quest Labels (used by Quest Bot)

| Label | Bot Behavior |
|---|---|
| `quest` | Posts: "⚔️ A new Quest has appeared! Claim this quest and earn XP..." |
| `boss:damage` | Posts: "💥 Boss damage opportunity! Completing this deals damage..." |
| `class:bug-hunter` | Posts: "🐛 Bug Hunters get bonus XP for this quest!" |
| `class:stylist` | Posts: "🎨 Stylists get bonus XP for this quest!" |
| `class:architect` | Posts: "🏗️ Architects get bonus XP for this quest!" |

### Difficulty Labels (used by Daily Bounties)

| Label | Purpose |
|---|---|
| `difficulty:easy` | Marks bounties as easy |
| `difficulty:medium` | Marks bounties as medium effort |

---

## 3. Workflow Files

### For New Projects

Copy the `.github/workflows/` folder from the frontpage repo into your new project.  
You need these 4 files:

```
.github/
  workflows/
    ci.yml              ← CI: lint, typecheck, test, build
    award-xp.yml        ← Award XP when PRs are merged
    quest-bot.yml       ← Comment on issues when labeled
    daily-bounties.yml  ← Auto-create 3 bounty issues daily
```

You also need to copy the `tools/` directory scripts:

```
tools/
    award_xp.php        ← XP calculation + DB insert logic
    quest_bot.php       ← Label → comment logic
    daily_bounties.php  ← Bounty template pool + issue creation
```

> [!WARNING]
> The `award_xp.php` script requires `vendor/autoload.php` and the app's PHP classes. Make sure `composer install` has been run and the backend namespace structure is intact.

### For Existing Projects

1. Copy just the workflow files you want from `.github/workflows/`
2. Copy the corresponding `tools/` scripts  
3. Add the required secrets (see Section 1)
4. Create the required labels (see Section 2)

---

## 4. Workflow Details

### CI (`ci.yml`)
- **Triggers:** PR to `main`/`master`/`development`, push to those branches, manual dispatch
- **What it does:** Runs frontend lint, typecheck, tests, build, then updates `project.json` with git metadata
- **Prerequisites:** `frontend/` directory with `package.json` containing `lint`, `type-check`, `test:run`, and `build` scripts

### Award XP (`award-xp.yml`)
- **Triggers:** PR closed (only runs if merged)
- **What it does:** Reads PR labels, calculates XP (base 50 + label bonuses), creates adventurer if new, awards XP, checks for level-ups and badges
- **Prerequisites:** DB secrets set, `setup_gamification.sql` migration run, `composer install` done

### Quest Bot (`quest-bot.yml`)
- **Triggers:** Issue labeled
- **What it does:** Posts a themed comment based on which label was applied
- **Prerequisites:** Labels exist in the repo

### Daily Bounties (`daily-bounties.yml`)
- **Triggers:** Daily at midnight UTC, or manual dispatch
- **What it does:** Creates 3 GitHub issues from a rotating pool of 7 bounty templates. Each bounty has XP rewards, class bonuses, and appropriate labels.
- **Prerequisites:** Labels exist in the repo

---

## 5. Setup Checklist

### New Project

- [ ] Copy `.github/workflows/` (all 4 yml files)
- [ ] Copy `tools/` directory (all 3 PHP scripts)
- [ ] Run `composer install` in backend
- [ ] Add `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` secrets
- [ ] Create all XP labels (`xp:tiny` through `xp:epic`)
- [ ] Create quest labels (`quest`, `boss:damage`)
- [ ] Create class labels (`class:bug-hunter`, `class:stylist`, `class:architect`)
- [ ] Create difficulty labels (`difficulty:easy`, `difficulty:medium`)
- [ ] Run `setup_gamification.sql` against the database
- [ ] Test: create a test issue and add the `quest` label (bot should comment)
- [ ] Test: open and merge a test PR with `xp:small` label

### Existing Project

- [ ] Copy desired workflow files from `.github/workflows/`
- [ ] Copy corresponding `tools/` scripts
- [ ] Add any missing secrets
- [ ] Create any missing labels
- [ ] Ensure `setup_gamification.sql` has been run
- [ ] Test each workflow
