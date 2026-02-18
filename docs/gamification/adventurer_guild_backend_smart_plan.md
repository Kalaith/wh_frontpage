# Adventurer Guild Backend Cutover - SMART Draft (Cycle 1)

Date drafted: February 18, 2026
Habitat: adventurer_guild
Program window: February 18, 2026 -> March 20, 2026

## Class System (Fantasy/Steampunk Style)

Use these class names in planning and UI flavor text.

| Fantasy/Steampunk Class | System Label Compatibility |
|---|---|
| Gremlin Tracker | `class:bug-hunter` |
| Brass Tinker | `class:patch-crafter` |
| Aether Smith | `class:feature-smith` |
| Rune Scribe | `class:doc-sage` |
| Cogwheel Alchemist | `class:ux-alchemist` |
| Arcane Examiner | `class:test-summoner` |
| Iron Warden | `class:ops-ranger` |

## 1) Outcome Brief (Program Goal)

- Outcome: Move adventurer_guild from mock-driven frontend to persistent backend-driven gameplay loop.
- Baseline (as of February 18, 2026): Core loop uses frontend mock/static data for key systems.
- Target (by March 20, 2026):
  - Core loop pages read/write through backend APIs only.
  - Save/load persists in DB and survives refresh/restart.
  - No runtime contract mismatch on core loop.
- KPI + Measurement:
  - KPI-1: Mock usage on core loop pages = 0 imports from mock/static gameplay data.
  - KPI-2: Contract test pass rate = 100% for core API payloads.
  - KPI-3: Integration suite pass rate = 100% on defined core flow scenarios.
  - KPI-4: Save/load verification = 5/5 scenarios passing.
  - KPI-5: P95 read API latency <= 300ms in staging-like environment.
- Constraints:
  - Keep existing player-facing flows stable during migration.
  - No schema-breaking changes after contract freeze date (March 6, 2026).
- Relevance:
  - Enables real progression, live balancing, and future multiplayer/event systems.

## 2) SMART Quests (8)

All quests require: PR link, test evidence, and update note in changelog/worklog.

### Quest Q1 - Backend Domain Schema
- Specific: Define DB schema + migration files for guild state, adventurers, quests, runs, and save slots.
- Class Focus: Iron Warden + Aether Smith
- Measurable:
  - 1 migration set applied cleanly from empty DB.
  - 1 rollback tested successfully.
  - ERD or schema map included in docs.
- Achievable: Backend engineer + reviewer, no frontend dependency.
- Relevant: Foundation for persistence and API behavior.
- Time-bound: Due February 24, 2026.
- Deliverables:
  - SQL migrations
  - Schema doc (`tables, keys, indexes`)
  - Migration test log

### Quest Q2 - API Contract Freeze (Core Loop)
- Specific: Publish versioned API contracts for guild summary, roster, quest board, assign quest, resolve quest, save/load.
- Class Focus: Aether Smith + Cogwheel Alchemist + Arcane Examiner
- Measurable:
  - 6 endpoint contracts documented.
  - JSON schema validation for request/response on all 6.
  - Contract review sign-off from frontend + backend.
- Achievable: API + frontend lead alignment session.
- Relevant: Prevents contract drift and rework.
- Time-bound: Due February 27, 2026.
- Deliverables:
  - Contract spec doc
  - Schema files
  - Signed review checklist

### Quest Q3 - Backend Core Endpoints (Read)
- Specific: Implement read endpoints for guild summary, roster, available quests.
- Class Focus: Aether Smith
- Measurable:
  - 3 GET routes live and tested.
  - 95%+ unit test pass in endpoint modules.
  - P95 latency <= 300ms locally with seeded data.
- Achievable: Depends on Q1/Q2 only.
- Relevant: Unlocks frontend read integration.
- Time-bound: Due March 1, 2026.
- Deliverables:
  - Controller/service/repository implementations
  - Unit tests
  - Latency sample report

### Quest Q4 - Backend Mutation Endpoints (Write)
- Specific: Implement assign quest, resolve quest, save game, load game mutation flows.
- Class Focus: Aether Smith + Iron Warden
- Measurable:
  - 4 mutation routes live.
  - Idempotency/validation checks for duplicate submissions.
  - 0 unhandled exceptions in negative test matrix.
- Achievable: Requires Q1/Q2 and partial Q3.
- Relevant: Enables true gameplay progression and persistence.
- Time-bound: Due March 5, 2026.
- Deliverables:
  - Mutation route implementations
  - Validation/error response matrix
  - Negative test run output

### Quest Q5 - Frontend Data Layer Migration
- Specific: Replace core loop mock adapters with API client adapters.
- Class Focus: Cogwheel Alchemist + Brass Tinker
- Measurable:
  - 0 mock imports on core loop pages.
  - 100% of core loop state transitions use API responses.
  - Error/loading states implemented for each migrated screen.
- Achievable: Parallel frontend work after Q2.
- Relevant: Main cutover from mock mode to live mode.
- Time-bound: Due March 10, 2026.
- Deliverables:
  - Refactored hooks/stores/services
  - Removed mock wiring in core loop
  - UI error/loading behavior proof

### Quest Q6 - Save/Load Reliability Pack
- Specific: Harden serialization, migration guards, and fallback handling for invalid save payloads.
- Class Focus: Iron Warden + Arcane Examiner
- Measurable:
  - 5/5 save/load scenarios pass:
    1) new save, 2) mid-run save, 3) retired member state, 4) partial payload, 5) older schema payload.
  - No crash on invalid payload; user-safe recovery message shown.
- Achievable: Depends on Q4/Q5.
- Relevant: Protects player progression.
- Time-bound: Due March 12, 2026.
- Deliverables:
  - Reliability tests
  - Recovery handler
  - Migration compatibility notes

### Quest Q7 - End-to-End Integration Suite
- Specific: Add integration tests for full loop: recruit -> assign -> resolve -> persist -> reload.
- Class Focus: Arcane Examiner
- Measurable:
  - At least 8 integration test cases.
  - 100% pass on CI main branch before raid start.
  - Flake rate <= 2% over 10 reruns.
- Achievable: Test engineer + CI maintainer.
- Relevant: Release confidence gate.
- Time-bound: Due March 15, 2026.
- Deliverables:
  - Integration test suite
  - CI config updates
  - Flake audit log

### Quest Q8 - Observability + Runbook
- Specific: Add request tracing/logging and operational runbook for cutover support.
- Class Focus: Iron Warden + Rune Scribe
- Measurable:
  - Structured logs include route, status, duration, request ID.
  - 4 operational runbooks complete: deploy, rollback, incident triage, data restore.
  - On-call checklist dry-run completed once.
- Achievable: Ops + docs collaboration.
- Relevant: Needed for safe rollout and incident response.
- Time-bound: Due March 17, 2026.
- Deliverables:
  - Logging middleware updates
  - Runbook docs
  - Dry-run checklist evidence

## 3) Boss (Cross-Cutting Risk)

### Boss B1 - Contract Drift Hydra
- Threat: Frontend/backend payload mismatch during migration causing runtime failures.
- Party Archetypes:
  - Gremlin Tracker (repro and mismatch capture)
  - Aether Smith (contract and implementation fixes)
  - Arcane Examiner (contract test gates)
  - Iron Warden (CI enforcement and staging verification)
- HP Model: 8 HP (1 HP each checkpoint unless noted)
  - [ ] HP1: Contract spec published and versioned
  - [ ] HP2: Backend schema validators enabled
  - [ ] HP3: Frontend runtime validation enabled
  - [ ] HP4: Backward compatibility check for one previous contract version
  - [ ] HP5: Contract tests in CI block merge on failure (2 HP)
  - [ ] HP6: Zero contract mismatch errors in staging for 48h
  - [ ] HP7: Final compatibility sign-off (frontend + backend)
- Kill Criteria (Measurable):
  - 100% contract test pass rate in CI.
  - 0 staging runtime contract mismatch errors over 48h before release.
- Deadline: Defeat by March 18, 2026.
- Rewards: Boss Slayer progress + release gate unlock.

## 4) Raid (Release Event)

### Raid R1 - Backend Cutover v1 (adventurer_guild)
- Objective: Ship backend-driven core gameplay loop to production-like environment safely.
- Party Roles:
  - Quest Master (release lead)
  - Aether Smith (backend lead)
  - Cogwheel Alchemist or Brass Tinker (frontend lead)
  - Arcane Examiner (integration/acceptance)
  - Iron Warden (deploy/rollback/monitoring)
- Entry Criteria:
  - Q1-Q8 complete
  - Boss B1 defeated
  - No P1/P2 unresolved blockers
- Phases:
  - Phase 1 (March 18, 2026): Final regression + go/no-go review
  - Phase 2 (March 19, 2026): Staged cutover + 24h monitoring
  - Phase 3 (March 20, 2026): Production cutover + release notes
- Go/No-Go Checks (Measurable):
  - 8/8 integration tests passing
  - 5/5 save/load scenarios passing
  - P95 read latency <= 300ms
  - Error rate < 1% on core routes during observation window
- Completion Proof:
  - Release tag
  - Cutover checklist
  - Monitoring summary
  - "What shipped" report

## 5) Process Rules (to avoid generic quests)

- No quest is valid without baseline metric + target metric + due date.
- No quest closes without proof artifact links.
- Bosses must define kill criteria in measurable terms, not narrative terms.
- Raids require go/no-go gates and explicit rollback criteria.
- Carry-over quests must include cause and scope adjustment before re-planning.

## 6) Suggested Weekly Cadence

- Monday: Commit weekly quest targets and owner assignments.
- Wednesday: Midweek metric checkpoint and risk review.
- Friday: Close/report cycle with evidence, update boss HP, and adjust next sprint scope.
