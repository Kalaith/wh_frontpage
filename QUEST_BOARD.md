# ⚔️ Manual Quest Board (Starter Quests)

Use these templates to create the first 10 quests manually in GitHub Issues.
Copy the content, paste into a new issue, and apply the corresponding labels.

---

## Quest 1: Hatchling Setup Run
**Title:** `[Quest] Verify Local Dev Setup`
**Labels:** `quest`, `difficulty:1`, `class:patch-crafter`, `xp:15`

### Objectives
- [ ] Clone the `frontpage` repo.
- [ ] Follow `README.md` to start frontend (`npm run dev`) and backend (`php -S`).
- [ ] Confirm the homepage loads without errors.
- [ ] Post a screenshot of the running app in comments as proof.

### Rewards
- **15 XP**
- **Badge:** Hatchling 🐣

---

## Quest 2: The First Gremlin (Bug Repro)
**Title:** `[Quest] Find and Reproduce One Bug`
**Labels:** `quest`, `difficulty:1`, `class:bug-hunter`, `xp:15`

### Objectives
- [ ] Explore the `tracker` or `projects` page.
- [ ] Find a UI glitch, console error, or broken link.
- [ ] Create a *new* issue for it with "Steps to Reproduce".
- [ ] Link that new issue here in the comments.

### Rewards
- **15 XP**
- **Badge:** Gremlin Whisperer 🐞

---

## Quest 3: Documentation Sage
**Title:** `[Quest] Add "Tech Stack" to README`
**Labels:** `quest`, `difficulty:1`, `class:doc-sage`, `xp:20`

### Objectives
- [ ] Edit `README.md`.
- [ ] Add a "Tech Stack" section listing React, Vite, Tailwind, PHP, Slim (or Custom Router).
- [ ] Submit PR.

### Rewards
- **20 XP**

---

## Quest 4: The Lint Check
**Title:** `[Quest] Run and Fix Linter Warnings`
**Labels:** `quest`, `difficulty:2`, `class:patch-crafter`, `xp:40`

### Objectives
- [ ] Run `npm run lint` in `frontend/`.
- [ ] Fix at least 5 warnings (e.g., unused vars, any types).
- [ ] Submit PR.

### Rewards
- **40 XP**

---

## Quest 5: UX Polish (Empty States)
**Title:** `[Quest] Improve Empty State on Ideas Page`
**Labels:** `quest`, `difficulty:2`, `class:ux-alchemist`, `xp:40`

### Objectives
- [ ] Locate `frontend/src/pages/IdeasPage.tsx`.
- [ ] If no ideas exist, ensure the empty state looks good (add an icon or "Add Idea" button).
- [ ] Submit PR with before/after screenshots.

### Rewards
- **40 XP**
- **Badge:** Polish Wizard ✨

---

## Quest 6: Test Summoner Initiate
**Title:** `[Quest] Add Unit Test for Date Formatter`
**Labels:** `quest`, `difficulty:2`, `class:test-summoner`, `xp:40`

### Objectives
- [ ] Find a date utility in `frontend/src/utils/`.
- [ ] Write a simple Vitest test case for it.
- [ ] Verify `npm run test` passes.
- [ ] Submit PR.

### Rewards
- **40 XP**

---

## Quest 7: Feature Smith (Project Filter)
**Title:** `[Quest] Add 'Filter by Status' to Projects Page`
**Labels:** `quest`, `difficulty:3`, `class:feature-smith`, `xp:80`

### Objectives
- [ ] Update `ProjectsPage.tsx` or `ProjectShowcase.tsx`.
- [ ] Add a dropdown to filter projects by `status` (MVP, Prototype, Working).
- [ ] Submit PR.

### Rewards
- **80 XP**

---

## Quest 8: Ops Ranger (Dependabot)
**Title:** `[Quest] Configure Dependabot`
**Labels:** `quest`, `difficulty:2`, `class:ops-ranger`, `xp:40`

### Objectives
- [ ] Create `.github/dependabot.yml`.
- [ ] Configure it for `npm` and `composer` updates (weekly).
- [ ] Submit PR.

### Rewards
- **40 XP**

---

## Quest 9: Boss Battle Prep
**Title:** `[Quest] Create "Boss Battle" Issue Template`
**Labels:** `quest`, `difficulty:2`, `class:doc-sage`, `xp:40`

### Objectives
- [ ] Create `.github/ISSUE_TEMPLATE/boss_battle.md`.
- [ ] Copy the Boss Card template from the Gamification Plan.
- [ ] Submit PR.

### Rewards
- **40 XP**

---

## Quest 10: Security Check
**Title:** `[Quest] Audit NPM Dependencies`
**Labels:** `quest`, `difficulty:2`, `class:ops-ranger`, `xp:40`

### Objectives
- [ ] Run `npm audit`.
- [ ] Fix any "High" severity vulnerabilities if possible.
- [ ] Submit PR or Issue with findings.

### Rewards
- **40 XP**
