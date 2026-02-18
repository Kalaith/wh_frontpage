# Gamification Project Plan: Web Hatchery RPG

## 1. Executive Summary

The goal is to transform the Web Hatchery development process into a high-engagement "Vibe Coding" RPG. By gamifying contribution—turning tasks into Quests, bugs into Enemies, and releases into Boss Battles—we aim to attract and retain contributors through visible progress, specific roles, and meaningful (non-financial) rewards.

This plan extends the **existing Web Hatchery Frontpage** (React/TS/Vite/Tailwind + custom PHP backend) to serve as the game interface. Rather than building a separate app, gamification features integrate directly into the live homepage, enriching what's already there—projects become Habitats, the tracker becomes a Quest Board, and profiles gain RPG progression.

---

## 2. Core Concept: "Vibe Coding" RPG

The development lifecycle is mapped to RPG mechanics:
*   **Quests:** GitHub Issues (labeled with difficulty, class, rewards).
*   **Classes:** Contributor roles (e.g., *Bug Hunter*, *Feature Smith*, *Ops Ranger*).
*   **XP & Levels:** Earned by merging PRs; unlocks permissions and prestige.
*   **Boss Battles:** Major issues or releases that require team coordination.
*   **Loot:** Badges, titles, and "Hatch Crates" (random rewards) dropped upon completion.
*   **Habitats:** Each Web Hatchery project (repo) is a "Habitat" with its own mastery track.

### 2.1 World Flavor (Naming Conventions)

| Real Concept       | RPG Name              |
|--------------------|-----------------------|
| Repository/Project | **Habitat**           |
| Sprint/Cycle       | **Season**            |
| Release            | **Hatch**             |
| CI Pipeline        | **Incubation Chamber**|
| Bug                | **Gremlin**           |
| Regression         | **Zombie Gremlin** 🧟 |
| Major Issue        | **Boss**              |
| PR Merge           | **Ship**              |

---

## 3. Platform Architecture

### 3.1 Existing Homepage Stack (What We Have)

The current frontpage is a fully functional full-stack app:

| Layer     | Technology                    | Key Files                            |
|-----------|-------------------------------|---------------------------------------|
| Frontend  | React 18 + TypeScript + Vite  | `frontend/src/`                      |
| Styling   | Tailwind CSS                  | `tailwind.config.js`                 |
| Backend   | PHP 8.1 (custom router)       | `backend/src/`                       |
| Database  | MySQL (InnoDB)                | `db/setup.sql`                          |
| Auth      | JWT (custom)                  | `Middleware/JwtAuthMiddleware.php`   |
| Deploy    | PowerShell                    | `publish.ps1`                        |

**Existing Frontend Pages:**
- `/` — **HomePage** (QuickLinks, ProjectLegend, ProjectShowcase, ProjectUpdates, HealthDashboard, Footer)
- `/projects` — **ProjectsPage** (full project grid with filtering)
- `/tracker` — **TrackerDashboard** (project tracking + analytics)
- `/tracker/requests` — Feature requests
- `/tracker/suggestions` — Project suggestions
- `/ideas` — Ideas page
- `/features` — Feature request dashboard
- `/profile` — **UserProfile** (already exists—will be extended with RPG data)
- `/login` & `/register` — Auth pages

**Existing Backend Structure:**
- `Controllers/` — 11 controllers (Project, Auth, Feature, Idea, etc.)
- `Models/` — 9 models (Project, User, etc.)
- `Repositories/` — 11 data access layers
- `Services/` — 5 business logic services
- `Middleware/` — JWT auth + CORS

**Existing Database:**
- `projects` table (40+ projects across `fiction`, `apps`, `games`, `game_design` groups)

### 3.2 Integration Strategy: Extend, Don't Rebuild

The gamification layer builds **on top of** the existing app:

```
┌─────────────────────────────────────────────────────────────┐
│                    EXISTING FRONTPAGE                         │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐    │
│  │ HomePage │  │ Projects │  │ Tracker  │  │ Profile  │    │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘    │
│       │              │              │              │          │
│  ─────┼──────────────┼──────────────┼──────────────┼──── ─── │
│       │     GAMIFICATION LAYER (NEW)│              │          │
│       ▼              ▼              ▼              ▼          │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐    │
│  │ Season   │  │ Habitat  │  │ Quest    │  │ Adventurer│    │
│  │ Widget   │  │ Mastery  │  │ Board    │  │ Profile  │    │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘    │
│                                                              │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                  │
│  │ Leader-  │  │ Boss     │  │ Hall of  │                  │
│  │ board    │  │ Battles  │  │ Heroes   │                  │
│  └──────────┘  └──────────┘  └──────────┘                  │
└─────────────────────────────────────────────────────────────┘
```

### 3.3 New Routes (Added to `App.tsx`)

| Route                | Component            | Description                                 |
|----------------------|----------------------|---------------------------------------------|
| `/quests`            | `QuestBoard`         | Filterable quest cards from GitHub Issues    |
| `/quests/:id`        | `QuestDetail`        | Individual quest with micro-milestones       |
| `/leaderboard`       | `Leaderboard`        | XP rankings (global + per-season)           |
| `/adventurers/:id`   | `AdventurerProfile`  | RPG-enhanced user profile                   |
| `/bosses`            | `BossBoard`          | Active boss battles with health bars        |
| `/bosses/:id`        | `BossDetail`         | Boss card with party roles + loot table     |
| `/hall-of-heroes`    | `HallOfHeroes`       | Achievement gallery + title holders         |
| `/seasons`           | `SeasonDashboard`    | Current season progress + path choice       |

### 3.4 Homepage Integration Points

The existing `HomePage.tsx` layout gains gamification widgets without losing current functionality:

```tsx
// Enhanced HomePage.tsx structure
<QuickLinks />
<SeasonBanner />          // NEW: Current season progress bar + theme

<Grid cols={4}>
  <ProjectLegend />       // EXISTING
  <ActiveBossWidget />    // NEW: Top active boss with health bar
  <ProjectUpdates />      // EXISTING
  <ProjectHealthDashboard /> // EXISTING
</Grid>

<WeeklyHeistMeter />      // NEW: Team-wide progress meter
<ProjectShowcase           // EXISTING: enhanced with Habitat mastery badges
  data={projectsData}
  showMastery={true}       // NEW prop: shows mastery level per project
/>
<RecentLootDrops />        // NEW: Activity feed styled as loot drops
<Footer />                 // EXISTING
```

### 3.5 Existing Component Enhancements

| Component               | Enhancement                                                        |
|------------------------|--------------------------------------------------------------------|
| `ProjectCard.tsx`       | Add Habitat mastery badge, quest count indicator, "Ship Fuel" meter |
| `ProjectShowcase.tsx`   | Add optional mastery overlay per project card                      |
| `UserProfile.tsx`       | Extend with XP bar, class badge, equipped title, badge collection  |
| `Header.tsx`/`AppHeader`| Add XP indicator, notification bell for loot drops, season name     |
| `TrackerDashboard.tsx`  | Link to Quest Board; show "quests available" per tracked project   |
| `Footer.tsx`            | Add "Current Season" info, link to Hall of Heroes                  |

### 3.6 Backend: GitHub as the Game Engine + PHP API Bridge

**GitHub (Source of Truth):**
*   **Database:** GitHub Issues and Pull Requests with labels.
*   **State Tracking:** Labels (`xp:20`, `class:bug-hunter`, `difficulty:2`), Milestones (`Season 1`).
*   **Events:** Webhooks + Actions trigger state changes.

**PHP Backend (New Endpoints):**

| Endpoint                      | Method | Description                                      |
|-------------------------------|--------|--------------------------------------------------|
| `/api/quests`                 | GET    | Fetch quest-labeled issues from GitHub API       |
| `/api/quests/{id}`            | GET    | Single quest detail                              |
| `/api/leaderboard`            | GET    | XP totals from `LEADERBOARD.md` or DB cache      |
| `/api/adventurers`            | GET    | Player profiles with XP, badges, class           |
| `/api/adventurers/{id}`       | GET    | Single adventurer detail                         |
| `/api/bosses`                 | GET    | Active boss issues                               |
| `/api/bosses/{id}`            | GET    | Boss detail with health bar state                |
| `/api/season/current`         | GET    | Current season info + progress                   |
| `/api/loot-drops/recent`      | GET    | Recent completion events (loot drop feed)        |

**New PHP Backend Files:**

| File                          | Purpose                                         |
|-------------------------------|--------------------------------------------------|
| `Controllers/QuestController.php`     | Quest API logic                         |
| `Controllers/LeaderboardController.php` | Leaderboard API logic                 |
| `Controllers/AdventurerController.php` | Player profile API                     |
| `Controllers/BossController.php`      | Boss battle API                         |
| `Controllers/SeasonController.php`    | Season state API                        |
| `Services/GitHubService.php`          | GitHub API client (issues, PRs, labels) |
| `Services/GamificationService.php`    | XP calc, level-up, badge award logic    |
| `Models/Adventurer.php`               | Player gamification state               |
| `Models/Quest.php`                    | Cached quest data                       |
| `Models/Season.php`                   | Season configuration                    |
| `Repositories/AdventurerRepository.php` | Adventurer data access                |
| `Repositories/QuestRepository.php`    | Quest data access                       |

### 3.7 Database Extensions

New tables added to the existing `webhatchery_frontpage` database alongside the `projects` table:

```sql
-- Adventurer profiles (linked to existing users table)
CREATE TABLE adventurers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    github_username VARCHAR(100) NOT NULL,
    class VARCHAR(50) DEFAULT 'hatchling',
    spec_primary VARCHAR(50) NULL,
    spec_secondary VARCHAR(50) NULL,
    xp_total INT UNSIGNED DEFAULT 0,
    level INT UNSIGNED DEFAULT 1,
    equipped_title VARCHAR(100) NULL,
    glow_streak INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_github_user (github_username),
    INDEX idx_level (level),
    INDEX idx_xp (xp_total)
);

-- Earned badges
CREATE TABLE adventurer_badges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adventurer_id BIGINT UNSIGNED NOT NULL,
    badge_slug VARCHAR(100) NOT NULL,
    badge_name VARCHAR(255) NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_adventurer_badge (adventurer_id, badge_slug)
);

-- Project mastery per adventurer
CREATE TABLE habitat_mastery (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adventurer_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    mastery_level INT UNSIGNED DEFAULT 0,
    contributions INT UNSIGNED DEFAULT 0,
    reviews INT UNSIGNED DEFAULT 0,
    UNIQUE INDEX idx_adv_project (adventurer_id, project_id)
);

-- XP transaction log
CREATE TABLE xp_ledger (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adventurer_id BIGINT UNSIGNED NOT NULL,
    amount INT NOT NULL,
    source_type ENUM('quest','boss','review','bonus','streak','crate') NOT NULL,
    source_ref VARCHAR(255) NULL,   -- GitHub issue/PR URL
    project_id BIGINT UNSIGNED NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_adventurer_time (adventurer_id, created_at)
);

-- Seasons
CREATE TABLE seasons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    starts_at DATE NOT NULL,
    ends_at DATE NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    path_chosen ENUM('stability','feature') NULL,
    INDEX idx_active (is_active)
);

-- Boss battles
CREATE TABLE bosses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    github_issue_url VARCHAR(255) NOT NULL,
    name VARCHAR(200) NOT NULL,
    threat_level TINYINT UNSIGNED DEFAULT 3,
    status ENUM('active','stabilizing','defeated') DEFAULT 'active',
    project_id BIGINT UNSIGNED NULL,
    season_id BIGINT UNSIGNED NULL,
    hp_total INT UNSIGNED DEFAULT 8,
    hp_current INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    defeated_at TIMESTAMP NULL,
    INDEX idx_status (status)
);
```

### 3.8 GitHub Actions Automation

| Action File                    | Trigger               | Effect                                          |
|--------------------------------|------------------------|-------------------------------------------------|
| `award-xp.yml`                | PR merged              | Parse `xp:*` label → update leaderboard + DB   |
| `sync-leaderboard.yml`       | Scheduled (daily)     | Aggregate stats → commit `LEADERBOARD.md`       |
| `quest-bot.yml`              | Issue closed           | Post "Loot Drop" comment with XP + badge info  |
| `boss-health.yml`            | Issue checkbox edited  | Update boss HP counter in issue body            |
| `daily-bounties.yml`         | Cron (daily)          | Auto-create 3 rotating micro-quests             |

---

## 4. Game Design Specification

### 4.1 Classes (Player Roles)
Contributors self-identify or level up into specific classes:
*   **Bug Hunter:** Finds and repros bugs. (Bonus XP for `type:bug`)
*   **Patch Crafter:** Fixes small bugs and polish items.
*   **Feature Smith:** Builds new features.
*   **Doc Sage:** Writes documentation and guides.
*   **UX Alchemist:** UI polish and accessibility.
*   **Test Summoner:** Writes tests and improves CI.
*   **Ops Ranger:** DevOps, deployment, and security.

Each class has its own quest filter on the Quest Board.

### 4.2 Quest Difficulty & Eligibility
Quests are standard GitHub issues with specific metadata labels:
*   **⭐ Tutorial (10-45m):** Setup, typos, repros. (15 XP) — *Anyone*
*   **⭐⭐ Easy (1-3h):** Small fixes, UI tweaks. (40 XP) — *Anyone*
*   **⭐⭐⭐ Standard (Half-day):** Features, tests. (80 XP) — *Lv 5+ or Mastery 2*
*   **⭐⭐⭐⭐ Hard (1-3d):** Major refactors, complex features. (140 XP) — *Lv 7+ and Mastery 3*
*   **👑 Raid (Team):** Releases, critical incidents. (XP varies + Badges) — *Invite only*

### 4.3 XP & Leveling

**XP Sources:**

| Action                            | XP    |
|-----------------------------------|-------|
| Bug report with clear repro       | 10    |
| Docs improvement accepted         | 20    |
| Test added / improved             | 25    |
| Small fix merged                  | 30    |
| Release assistance                | 40    |
| Feature merged                    | 60    |
| Performance fix with measurement  | 70    |
| Helpful PR review                 | 15    |

**Bonus Modifiers:**
- Quality multiplier (reviewer award): ×0.8 / ×1.0 / ×1.2
- Streak: 1 quest/week for 4 weeks: +50 XP bonus
- Spec match: +10% XP for quests matching your spec labels

**Level Thresholds:**

| Level | Title                  | XP Required | Key Unlock                             |
|-------|------------------------|-------------|----------------------------------------|
| 1     | Hatchling              | 0           | Claim ⭐/⭐⭐ quests, submit PRs        |
| 2     | Initiate               | 40          | —                                      |
| 3     | Contributor             | 100         | Self-assign ⭐⭐, choose Spec           |
| 4     | Adept                  | 180         | —                                      |
| 5     | Specialist             | 280         | ⭐⭐⭐ quests, non-blocking reviews      |
| 6     | Veteran                | 400         | Secondary Spec                         |
| 7     | Maintainer-in-Training | 550         | Manage labels/milestones, merge docs   |
| 8     | Senior                 | 720         | —                                      |
| 9     | Co-owner Candidate     | 900         | Scoped merge rights, Third Spec        |
| 10    | Elder                  | 1100        | Leadership roles                       |

### 4.4 Project (Habitat) Mastery

Each of the 40+ Web Hatchery projects has its own mastery track, displayed on the enhanced `ProjectCard`:

| Mastery | Title    | Requirement                               | Per-Project Unlock                      |
|---------|----------|-------------------------------------------|-----------------------------------------|
| M1      | Initiate | 1 merged PR in that project               | Self-assign ⭐⭐ quests in that project  |
| M2      | Familiar | 3 accepted contributions                  | Label/triage issues                     |
| M3      | Trusted  | 5 contributions + 2 reviews               | Approve low-risk PRs                    |
| M4      | Steward  | Release participation or ⭐⭐⭐⭐ quest     | Merge docs/tests                        |
| M5      | Champion | Maintain quest board for a season          | Drive roadmap, Project Champion title   |

### 4.5 Rewards

**Badges (Collectible, permanent):**
Hatchling, Trail Marker, Gremlin Whisperer, Bug Exorcist, Green Keeper, Steady Hands, Test Summoner, Pipeline Tamer, Shieldsmith, Polish Wizard, Cartographer, Data Wrangler, Gatekeeper, Style Architect, Release Raider, Garden Planter, Signal Keeper, Quartermaster, Guild Scribe, Comfort Crafter, Keystone Forger, Warden, Boss Slayer.

**Titles (Equip one, prestigious):**
The Stabilizer, Keeper of the Gates, The Release Smith, Project Champion: \[Habitat\], Quest Master.

**Access Tokens (Real permissions):**
Self-assign quests, label issues, approve low-risk PRs, merge docs/tests, run releases, Quest Master role, module ownership.

### 4.6 Advanced Mechanics

| Mechanic          | Description                                                        | Where it shows on-site                              |
|-------------------|--------------------------------------------------------------------|------------------------------------------------------|
| Micro-Milestones  | Quests split into 3–6 "Ping" steps, each giving mini XP           | `QuestDetail` page with checkbox progress            |
| Glow Meter        | Weekly activity streak (pause ≠ reset)                             | `AdventurerProfile` glow indicator                   |
| Hatch Crates      | Random reward drops on quest completion (badge fragments, perks)   | Loot animation on `QuestDetail` completion           |
| Perk Tokens       | Equippable buffs (Fast Track, Double Dip, Summon Reviewer)        | `AdventurerProfile` perk slot                        |
| Daily Bounties    | 3 rotating micro-quests auto-posted daily                          | `QuestBoard` daily section                           |
| Weekly Heist      | Themed team event, combined progress meter                         | `HomePage` `WeeklyHeistMeter` widget                 |
| Ship Fuel         | Per-Habitat merged PR meter → unlocks chains/badges               | `ProjectCard` fuel bar overlay                       |
| Boss Fights       | Major issues with health bars, party roles, rare loot             | `/bosses` page + `ActiveBossWidget` on home          |
| Proof of Vibe     | Bonus XP for screenshots/GIFs of completed features               | `QuestDetail` proof upload section                   |
| Kudos Cards       | 1/week peer award → +5 XP + community achievement progress       | `AdventurerProfile` kudos section                    |
| Fork Fate         | Season path choice (Stability vs Feature) per project             | `SeasonDashboard` voting UI                          |
| Hatch Ceremonies  | Milestone celebrations (10 PRs, first release, etc.)              | `HallOfHeroes` ceremony entries                      |

---

## 5. Existing Projects as Launch Habitats

The 40+ projects already in the `projects` table naturally become the first Habitats. Recommended **flagship Habitats** for Season 1 (high-traffic, good quest variety):

| Habitat (Project)        | Group    | Why It's a Good Launch Habitat                        | Quest Potential         |
|--------------------------|----------|-------------------------------------------------------|-------------------------|
| **WebHatchery Frontpage** | apps    | This site itself—meta-gaming!                         | UX, features, gamification self-hosting |
| **Name Generator API**    | apps    | Mature API + frontend, rich test surface              | API, testing, docs      |
| **Campaign Chronicle**    | apps    | D&D companion, high engagement                        | Features, UX, data      |
| **Dungeon Core**          | games   | Complex game, many systems to improve                  | Game logic, UI, tests   |
| **Story Forge**           | apps    | Creative tool, good for UX quests                      | UX, features, docs      |

Initial quests for these 5 habitats can cover the full class spectrum and difficulty range.

---

## 6. Implementation Roadmap

### Phase 1: Foundation — Manual RPG (Weeks 1–2)
*Goal: Start the game mechanics using GitHub UI + existing site.*

- [ ] **Define Standards:** Create `CONTRIBUTING.md` with class descriptions & RPG rules.
- [ ] **Label System:** Create standard labels across repos:
  - `quest`, `difficulty:1`–`difficulty:5`
  - `class:bug-hunter`, `class:patch-crafter`, `class:feature-smith`, `class:doc-sage`, `class:ux-alchemist`, `class:test-summoner`, `class:ops-ranger`
  - `xp:10`, `xp:15`, `xp:20`, `xp:30`, `xp:40`, `xp:60`, `xp:80`
  - `chain:stabilize`, `chain:polish`, `chain:ship`, `chain:security`
  - `boss:active`, `boss:stabilizing`, `boss:defeated`
- [ ] **Quest Board Setup:** Create 10 "Starter Quests" manually across 5 flagship habitats.
- [ ] **Manual Tracking:** Create initial `LEADERBOARD.md` in the frontpage repo.
- [ ] **Boss Card Template:** Add Boss Card issue template to repos.

### Phase 2: Site Integration — Read-Only Gamification (Weeks 3–5)
*Goal: Visualize game state on the existing frontpage.*

- [ ] **Database Schema:** Run migration to add gamification tables to `webhatchery_frontpage` DB.
- [ ] **Backend: GitHub Service:** `Services/GitHubService.php` — GitHub API client to fetch issues/PRs/labels.
- [ ] **Backend: Quest API:**
  - [ ] `Controllers/QuestController.php` + `Repositories/QuestRepository.php`
  - [ ] `GET /api/quests` (with filtering by difficulty, class, habitat)
  - [ ] `GET /api/quests/{id}` (single quest with micro-milestones)
- [ ] **Backend: Leaderboard API:**
  - [ ] `Controllers/LeaderboardController.php`
  - [ ] `GET /api/leaderboard` (global + per-season + per-habitat)
- [ ] **Backend: Adventurer API:**
  - [ ] `Controllers/AdventurerController.php` + `Repositories/AdventurerRepository.php`
  - [ ] `GET /api/adventurers/{id}` (profile with XP, level, badges, mastery)
- [ ] **Frontend: Quest Board Page:**
  - [ ] `pages/QuestBoard.tsx` — Filterable grid of quest cards styled as RPG quest scrolls
  - [ ] `components/QuestCard.tsx` — Individual quest with difficulty stars, class badge, XP reward
  - [ ] Route → `/quests` in `App.tsx`
- [ ] **Frontend: Leaderboard Page:**
  - [ ] `pages/Leaderboard.tsx` — Ranked list with XP bars, levels, equipped titles
  - [ ] Route → `/leaderboard` in `App.tsx`
- [ ] **Frontend: Adventurer Profile Enhancement:**
  - [ ] Extend existing `pages/UserProfile.tsx` with RPG stats panel (XP bar, class, badges, titles, mastery grid)
  - [ ] Route → `/adventurers/:id` (or enhance `/profile`)
- [ ] **Frontend: HomePage Widgets:**
  - [ ] `components/SeasonBanner.tsx` — Current season progress bar + name
  - [ ] `components/ActiveBossWidget.tsx` — Top boss with health bar
  - [ ] `components/RecentLootDrops.tsx` — Activity feed styled as loot drops
  - [ ] Integrate into `HomePage.tsx` layout
- [ ] **Frontend: Enhanced ProjectCard:**
  - [ ] Add Habitat mastery badge + quest count + Ship Fuel meter to existing `ProjectCard.tsx`
- [ ] **Frontend: Nav Update:**
  - [ ] Add "Quests", "Leaderboard" links to `AppHeader`
  - [ ] Add XP indicator / mini-level badge for logged-in users

### Phase 3: Automation & Interactivity (Weeks 6–8)
*Goal: Automate the game loop with GitHub Actions.*

- [ ] **GitHub Action: `award-xp.yml`** — On PR merge: parse `xp:*` labels, update XP in DB via API call.
- [ ] **GitHub Action: `quest-bot.yml`** — On issue close: post "Loot Drop" comment (XP gained, badges earned, progress to next unlock).
- [ ] **GitHub Action: `sync-leaderboard.yml`** — Daily cron: aggregate XP → commit `LEADERBOARD.md`.
- [ ] **Backend: Gamification Service:**
  - [ ] `Services/GamificationService.php` — XP calc, level-up logic, badge award engine, streak tracking
  - [ ] Extend existing webhook endpoint (`POST /api/webhooks/github`) for gamification Action callbacks
- [ ] **Frontend: Real-time Updates:**
  - [ ] Polling/websocket for "New Quest" and "Level Up" notifications
  - [ ] "Level Up" animation overlay component
  - [ ] "Loot Crate" opening reveal animation
- [ ] **Frontend: Boss Battle Page:**
  - [ ] `pages/BossBoard.tsx` — Active bosses grid
  - [ ] `pages/BossDetail.tsx` — Full boss card with health bar, party roles, loot table
  - [ ] Route → `/bosses` and `/bosses/:id` in `App.tsx`

### Phase 4: Advanced "Vibe" (Weeks 9–12)
*Goal: High-polish gamification with full engagement loop.*

- [ ] **Loot Crates:** Random reward logic in `GamificationService` (badge fragments, perk tokens, cosmetics).
- [ ] **Perk Tokens:** Equippable perks UI in Adventurer Profile.
- [ ] **Daily Bounties:** `daily-bounties.yml` Action auto-creates 3 rotating micro-quests.
- [ ] **Weekly Heist:** `components/WeeklyHeistMeter.tsx` team meter on HomePage.
- [ ] **Hatch Ceremonies:** Milestone celebration entries in Hall of Heroes.
- [ ] **Hall of Heroes Page:**
  - [ ] `pages/HallOfHeroes.tsx` — Achievement gallery, boss slayers, title holders
  - [ ] Route → `/hall-of-heroes` in `App.tsx`
- [ ] **Season Dashboard:**
  - [ ] `pages/SeasonDashboard.tsx` — Season info, Fork Fate path voting, season leaderboard
  - [ ] Route → `/seasons` in `App.tsx`
- [ ] **Sounds & SFX:** Audio cues on the site for loot drops, level-ups, boss defeats.
- [ ] **Proof of Vibe:** Screenshot/GIF upload on quest completion for bonus XP.
- [ ] **Kudos Cards:** Peer award system (1/week, +5 XP).

### Phase 5: Polish & Scale (Ongoing)
*Goal: Mature the system as contributors join.*

- [ ] **Quest Chains:** Structured multi-quest storylines ("Stabilize the Hatchery", "MVP Polish", "Security Apprentice") with chain-completion titles.
- [ ] **Cross-project Wanderer rewards:** Bonus XP for contributing across 3+ Habitats in a month.
- [ ] **Contribution Certificate:** Auto-generated portfolio page per adventurer (PRs shipped, badges, metrics).
- [ ] **GitHub App Bot:** Migrate from Actions to persistent Bot for richer interaction.

---

## 7. Next Steps (Immediate Actions)

1. **Repo Prep:** Apply standard gamification labels to the `wh_frontpage` repo as the first Habitat.
2. **Standards Doc:** Draft `CONTRIBUTING.md` based on the RPG rules (classes, quests, XP sources).
3. **DB Migration:** Create and run the gamification schema extension (`db/setup_gamification.sql`).
4. **GitHub Service:** Build `Services/GitHubService.php` to read quest-labeled issues.
5. **Quest Board MVP:** Build `QuestBoard.tsx` page and wire it into the existing nav.
6. **First Season:** Declare "Season 1: The Awakening" and create 10 starter quests across 5 flagship habitats.

