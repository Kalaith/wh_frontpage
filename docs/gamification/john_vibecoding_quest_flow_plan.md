# John Vibe-Coding Quest Flow Plan

## 1. Objective

Design the simplest possible end-to-end contributor flow for a beginner like John:

1. Sign up
2. Accept a quest
3. Do the work (without Git knowledge)
4. Submit completion
5. Close the quest/project cleanly

Success means John can ship value without learning Git or programming, while still producing reviewable, safe, and trackable submissions.

## 2. Persona

**John (Primary Persona)**
- Cannot program
- Does not know Git
- Wants fast momentum and visible progress
- Can describe what he wants in plain language ("vibe coding")
- Needs guided prompts and low-friction tooling

## 3. Product Principles

1. No Git required for first success.
2. One clear next action on every screen.
3. Quest text must be human-first, not internal jargon.
4. Submission should feel like "turning in homework," not CI/CD ops.
5. Safety rails should be automatic (validation, checks, rollback paths).
6. Prompt-first workflow, code-second internals.

## 4. Target Journey (Happy Path)

### Step A: Signup + Quick Setup (3-5 minutes)
- User creates account.
- User picks a class (for example: Ranger, Summoner, Sage, Crafter).
- System shows what that class focuses on and the typical quest style for it.
- System asks preferred work mode:
  - Guided vibe mode (recommended)
  - Guided upload mode
  - Advanced mode (for coders)
- Show a 60-second "How quests work" tutorial.

### Step B: Quest Discovery + Acceptance
- Quest Board only shows unlocked quests.
- Cards show plain meta tags (Date, Quest type, chain name).
- "Accept Quest" creates a personal quest run:
  - Status: `accepted`
  - Personal deadline and checklist snapshot
  - Workspace link (guided vibe workspace)

### Step C: Do the Work (No Git Track)
- User gets a dedicated **Quest Workspace** with:
  - Plain-language task checklist
  - "Describe what you want to change" prompt box
  - RuneSage-assisted draft generation
  - "Apply draft" and "Undo" controls
  - "Run checks" button
- System auto-saves prompts, generated drafts, and changed files.
- User can pause/resume without losing context.

### Step D: Submit Completion
- User clicks `Submit Quest`.
- Submission form requires:
  - Summary: "What changed?" (auto-generated, user-editable)
  - Changed files (auto-collected or uploaded)
  - Validation output (auto-attached from checks)
  - Optional screenshot/video
- Backend runs policy checks and sets status:
  - `submitted` if valid
  - Inline errors if incomplete

### Step E: Review + Close
- Reviewer sees clean submission package:
  - Diff/files
  - Quest checklist
  - Test results
  - Risk + rollback notes (if required)
- Reviewer actions:
  - `complete`
  - `request_changes`
- On complete:
  - XP/rewards granted
  - Next quests unlocked
  - "What to do next" CTA shown

## 5. UX Requirements

## 5.1 Quest Card
- No internal labels like `class:ops-ranger`.
- Show plain tags only:
  - `Date: Feb 24, 2026`
  - `Quest`
  - `Stabilize`
  - `Independent/Chained`
- Keep "Suggested class" as flavor text only.

## 5.2 Quest Detail
- Replace technical proof labels with human checklist:
  - "The app runs"
  - "Checks pass"
  - "Feature works as described"
- Keep technical mapping internal in backend.

## 5.3 Submission UI
- Single-screen "Turn In Quest" flow:
  - Step 1: Summary
  - Step 2: Attachments/changed files
  - Step 3: Run checks
  - Step 4: Submit

## 6. Backend/Platform Requirements

1. **Quest Run Model**
   - `accepted`, `submitted`, `completed`, `rejected`
   - Track accepted_at/submitted_at/completed_at

2. **Workspace Snapshot**
   - Store prompts, generated drafts, changed files, and metadata for each submission
   - Preserve exact state reviewer sees

3. **Validation Pipeline**
   - Fast checks for syntax/tests/build as quest-configured gates
   - Return actionable errors, not generic failures

4. **Unlock Rules**
   - Chained quests require dependency completion
   - Hidden until unlocked

5. **No-Git Submission Adapter**
   - Accept:
     - Guided vibe workspace diffs
     - Zip/folder uploads
     - Structured patch text
   - Normalize into one internal review format

## 7. Reviewer Experience Requirements

1. One-click open "submission packet"
2. View:
   - What user changed
   - Whether acceptance criteria are met
   - Validation status
3. Quick decision buttons:
   - Complete
   - Needs Revision (with required comment)

## 8. Rollout Plan

### Phase 1 (MVP: 2-3 weeks)
- Hidden locked quests
- Clean quest tags
- Accept quest
- Basic non-git submission form
- Reviewer complete/reject

### Phase 2 (2-4 weeks)
- Guided vibe workspace with auto-save
- Run checks in UI
- Submission packet improvements

### Phase 3 (3-4 weeks)
- Smart helper prompts
- Better onboarding/tutorial
- Quality scoring + better recommendations

## 9. KPIs

1. Time to first accepted quest
2. Quest acceptance -> submission conversion rate
3. Submission success rate (no resubmission needed)
4. Reviewer turnaround time
5. % of contributors succeeding without Git

## 10. Risks and Mitigations

1. Risk: Users submit low-quality or incomplete work
   - Mitigation: Required checks + structured submission form

2. Risk: Reviewer load becomes bottleneck
   - Mitigation: Better packet quality + quick action UI + queue prioritization

3. Risk: No-git flow diverges from repo standards
   - Mitigation: Normalize all submissions into a consistent internal patch/review format

## 11. Recommended Immediate Implementation Order

1. Finalize "human-first" quest metadata display.
2. Ship non-git submission adapter (upload/patch/workspace diff).
3. Build "Turn In Quest" 4-step submit UI.
4. Build reviewer packet + complete/revise actions.
5. Add onboarding path for first quest completion.
