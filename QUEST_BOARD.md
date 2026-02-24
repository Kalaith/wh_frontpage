# WebHatchery Quest Board Planning Guide

Purpose: plan quests that everyday users can complete, while still keeping technical notes for each user's AI helper (`RuneSage` / `RS`).

Last updated: February 24, 2026

---

## 1) Core Rule

Write quests in plain language first.  
If a non-coder cannot understand the quest in under 30 seconds, rewrite it.

Each quest has two layers:
- Player Layer: simple task and clear finish line
- RS Layer: optional technical details for AI help

---

## 2) Rank Tiers (Hard Gate)

Users can only accept quests at or below their rank.

| Rank | Allowed Quest Levels | Typical Scope |
|---|---|---|
| Iron | 1 | small starter task |
| Silver | 1-2 | short task with light follow-up |
| Gold | 1-3 | medium task with a few steps |
| Jade | 1-4 | advanced task with dependencies |
| Diamond | 1-5 | hardest quests, bosses, raids |

Rules:
- Never show locked quests as claimable.
- Show lock reason: `Requires <Rank>`.
- Rank-up should be tied to completed quests and proof quality.

---

## 3) Quest Types

- Quest: one focused task
- Boss: one major challenge split into checkpoints
- Raid: team effort with phases and strict entry conditions

Use these for planning only; keep wording user-friendly.

---

## 4) Quest Writing Standard

Every quest must include:
- Goal: what the user is trying to achieve
- Steps: 2-5 plain actions
- Done When: simple pass/fail result
- Due Date: exact date (`YYYY-MM-DD`)
- Rank Required: Iron/Silver/Gold/Jade/Diamond
- Proof: what the user submits (visual proof first)
- RS Brief: technical hints for RuneSage (required for code quests)
- Why this matters: one plain line of user value
- Where to look: page/feature area in plain language
- Safe boundaries: what not to change
- Quick verify steps: manual checks in user language

Hard rule: no proof, no completion.

Player-facing text rules (KISS):
- Use plain language that a non-coder can understand in under 30 seconds.
- Keep title short and action-based (target: 12 words or fewer).
- Avoid jargon in player text (examples: schema, contract, observability, artifact, regression).
- If technical detail is needed, put it in `RS Brief`, not in Goal/Steps/Done When.

PR scope rules (required):
- A normal quest must be completable with one PR that changes code/docs/config.
- The quest must name at least one concrete deliverable (file, page, workflow, script, or test).
- If work is only coordination, sign-off, or a live operation with no code/docs diff, it is not a normal quest.
- Non-PR operational work should be modeled as Boss checkpoints (HP), not regular quests.

Proof priority rules:
- Visual proof is primary: screenshot or short screen recording.
- PR link is required for normal quests.
- Include a short note: what changed and what was tested (1-3 sentences).
- Test output is optional unless the quest explicitly requires it.

---

## 5) Chaining Rules (Required)

Use this check before publishing quests:

- Use `Independent` when work can be done safely without waiting on another quest.
- Use `Chained` when output from one quest is required by another quest.
- Use `Blocked` when starting now would create rework, conflicts, or broken assumptions.

Chain triggers (must chain):
- Shared schema or contract changes
- Same files/components likely to conflict
- Work that depends on an approval/sign-off from another quest
- Validation work that needs another quest's output first

Independent triggers (can run in parallel):
- Different systems with no shared contract/schema
- Docs/QA tasks that use stable, already-approved artifacts
- UI polish tasks that do not alter backend/API assumptions

Hard rule:
- If two quests can invalidate each other, they must be chained.

Required dependency fields on every quest:
- Dependency Type: `Independent|Chained|Blocked`
- Depends On: `<quest id(s) or none>`
- Unlock Condition (if chained/blocked): `<what must be true first>`

Dependency Decision Tree (quick check):
1. Does this quest change shared schema/API contracts?
   - Yes -> `Chained`
   - No -> go to 2
2. Can another active quest change the same files/components at the same time?
   - Yes -> `Chained`
   - No -> go to 3
3. Does this quest need another quest's output, approval, or test artifact first?
   - Yes -> `Blocked` (until ready), then `Chained`
   - No -> go to 4
4. If this quest starts now and another quest lands first, would rework be likely?
   - Yes -> `Blocked` or `Chained`
   - No -> `Independent`

---

## 6) Quest Template (Copy/Paste)

### Quest Card
- Title: `<simple action title>`
- Type: `Quest`
- Rank Required: `<Iron|Silver|Gold|Jade|Diamond>`
- Quest Level: `<1-5>`
- Dependency Type: `<Independent|Chained|Blocked>`
- Depends On: `<quest id(s) or none>`
- Unlock Condition: `<required completion/sign-off or n/a>`
- Player Layer:
  - Goal: `<one plain sentence>`
  - Why this matters: `<one plain sentence>`
  - Where to look: `<page/feature area in plain words>`
  - Steps:
    - `<step 1>`
    - `<step 2>`
    - `<step 3 optional>`
  - Quick verify steps (manual):
    - `<open page>`
    - `<do action>`
    - `<check visible result>`
  - Safe boundaries:
    - `<do not rename unrelated files>`
    - `<do not change unrelated behavior>`
  - Done When:
    - `<clear completion check>`
- Due Date: `<YYYY-MM-DD>`
- Proof Required:
  - `<screenshot or short recording>`
  - `<PR link>`
  - `<1-3 sentence note: what changed + what I tested>`
  - `<optional test output>`
- Reward:
  - `XP: <number>`
  - `<optional badge/loot>`
- RS Brief (required for code quests):
  - Context: `<system/page/tool name>`
  - Constraints: `<must avoid / must include>`
  - Suggested prompt: `"<prompt user can give RS>"`

---

## 7) Boss Template (Copy/Paste)

### Boss Card
- Title: `<challenge name>`
- Type: `Boss`
- Rank Required: `Jade` or `Diamond`
- Quest Level: `4` or `5`
- Goal: `<major outcome in plain language>`
- Checkpoints:
  - [ ] `<checkpoint 1>`
  - [ ] `<checkpoint 2>`
  - [ ] `<checkpoint 3>`
- Done When:
  - `<measurable result>`
- Due Date: `<YYYY-MM-DD>`
- Proof Required:
  - `<links/screenshots/report>`
- RS Brief (Optional):
  - Technical notes: `<short bullets>`
  - Suggested prompt: `"<prompt user can give RS>"`

---

## 8) Raid Template (Copy/Paste)

### Raid Card
- Title: `<team objective>`
- Type: `Raid`
- Rank Required: `Diamond`
- Quest Level: `5`
- Party Size: `3-5`
- Goal: `<release or major coordinated outcome>`
- Entry Requirements:
  - [ ] `<required quests complete>`
  - [ ] `<required boss defeated>`
- Phases:
  - Phase 1: `<prep>`
  - Phase 2: `<execution>`
  - Phase 3: `<verification>`
- Done When:
  - `<go/no-go checks pass>`
- Due Date: `<YYYY-MM-DD>`
- Proof Required:
  - `<release proof + validation proof>`
- RS Brief (Optional):
  - Technical notes: `<short bullets>`
  - Suggested prompt: `"<prompt user can give RS>"`

---

## 9) Weekly Planning Ritual

Run once per week per habitat.

Template:
- Wins completed:
  - `<quest titles>`
- Stuck or blocked:
  - `<short blocker>`
- Rank progress:
  - `<user/team>: <old rank> -> <new rank if earned>`
- Next quests to publish:
  - `<titles + required rank>`
- Chain review:
  - `<new chains created>`
  - `<quests moved from blocked to ready>`

---

## 10) Anti-Patterns (Reject)

Reject quests that:
- use technical jargon in the player-facing section
- have no required rank
- have no due date
- have no proof requirement
- should be chained but are marked independent
- are blocked but still shown as claimable
- have vague completion text like "improve things"
- cannot be completed with one PR
- require only meetings/manual ops and no code/docs/config change
- hide technical complexity in Player Layer instead of RS Brief
- use proof tokens in player text (use plain proof instructions instead)

---

## 11) Player/RS Language Lint

Player Layer must NOT contain:
- `api`, `schema`, `endpoint`, `payload`, `contract`, `artifact`, `regression`, `observability`, `handler`, `upstream`
- deep implementation wording that only makes sense to engineers

Player Layer SHOULD contain:
- page names, button names, and visible behavior
- before/after outcomes users can see
- click/check style manual verify steps
- clear proof instructions in plain language

RS Layer CAN contain:
- technical terms, architecture details, test strategy, and implementation constraints

---

## 12) Quick Start Checklist

- [ ] Define this cycle's main outcome in plain language
- [ ] Draft quests users can understand without coding knowledge
- [ ] Add `Why this matters` and `Where to look` to every quest
- [ ] Add rank requirement to every quest
- [ ] Mark each quest as `Independent`, `Chained`, or `Blocked`
- [ ] Add dependency links for chained/blocked quests
- [ ] Add `RS Brief` to every code quest with a copy/paste starter prompt
- [ ] Add Safe boundaries and Quick verify steps to every quest
- [ ] Publish only quests with visual-first proof requirements
