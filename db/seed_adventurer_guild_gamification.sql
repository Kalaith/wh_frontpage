-- =====================================================
-- Adventurer Guild Quest Planning Seed Data (Plain Language + RS Brief)
-- =====================================================
-- Mirrors data from:
--   frontpage/seeds/quests/adventurer_guild_backend_cutover.json
--
-- Usage:
--   mysql -u <user> -p webhatchery_frontpage < db/seed_adventurer_guild_gamification.sql
-- =====================================================

USE frontpage;

START TRANSACTION;

SET @project_id = (
    SELECT id FROM projects
    WHERE path = 'game_apps/adventurer_guild/frontend/'
    LIMIT 1
);
SET @project_id = COALESCE(
    @project_id,
    (SELECT id FROM projects WHERE title = 'Adventurer Guild' LIMIT 1)
);

INSERT INTO seasons (name, slug, starts_at, ends_at, is_active, path_chosen)
VALUES ('Season 1: The Awakening', 'season-1', '2026-02-18', '2026-03-20', TRUE, 'stability')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    starts_at = VALUES(starts_at),
    ends_at = VALUES(ends_at),
    is_active = VALUES(is_active),
    path_chosen = VALUES(path_chosen);

SET @season_id = (
    SELECT id FROM seasons
    WHERE slug = 'season-1'
    LIMIT 1
);

INSERT INTO quest_chains (
    slug,
    name,
    description,
    steps,
    total_steps,
    reward_xp,
    reward_badge_slug,
    reward_title,
    season_id,
    is_active
)
VALUES
(
    'adventurer-guild-backend-foundation',
    'Adventurer Guild: Backend Foundation',
    'Set up the core backend pieces so the game can save and load real progress.\n\nMetadata: {"type":"quest_chain","labels":["type:quest","chain:stabilize"]}',
    JSON_ARRAY(
        JSON_OBJECT(
            'id', 'Q1',
            'type', 'Quest',
            'title', 'Create Save Data Tables',
            'description', 'Prepare the database so player and quest progress can be stored safely.',
            'rank_required', 'Silver',
            'quest_level', 2,
            'dependency_type', 'Independent',
            'depends_on', JSON_ARRAY(),
            'unlock_condition', 'n/a',
            'goal', 'Make sure the game can store guild, adventurer, quest, run, and save-slot data.',
            'player_steps', JSON_ARRAY('Create the core migration files for save data.','Run migrations in a clean environment.','Run rollback once to prove recovery works.'),
            'done_when', JSON_ARRAY('Migration runs successfully.','Rollback runs successfully.','Schema map is attached in the proof.'),
            'due_date', '2026-02-24',
            'proof_required', JSON_ARRAY('proof:pr','proof:test-output','proof:metric-delta'),
            'rs_brief', JSON_OBJECT(
                'context', 'Backend migration layer and gameplay persistence schema.',
                'constraints', 'No breaking rename on existing stable tables.',
                'suggested_prompt', 'Help me create safe migrations for Adventurer Guild save data with up/down SQL and rollback checks.'
            ),
            'class_fantasy', 'Iron Warden',
            'class', 'ops-ranger',
            'difficulty', 2,
            'xp', 40,
            'labels', JSON_ARRAY('type:quest','difficulty:2','class:ops-ranger','xp:40','chain:stabilize')
        ),
        JSON_OBJECT(
            'id', 'Q2',
            'type', 'Quest',
            'title', 'Publish API Contract Guide',
            'description', 'Define what data the frontend and backend expect from each other.',
            'rank_required', 'Gold',
            'quest_level', 3,
            'dependency_type', 'Chained',
            'depends_on', JSON_ARRAY('Q1'),
            'unlock_condition', 'Q1 complete and schema map approved.',
            'goal', 'Lock down API contracts for the core read and write game actions.',
            'player_steps', JSON_ARRAY('List the core endpoints with sample request and response payloads.','Add error response examples for failed cases.','Get sign-off from frontend and backend owners.'),
            'done_when', JSON_ARRAY('Contract guide exists for six endpoints.','Each endpoint has success and error examples.','Frontend and backend owners approve the guide.'),
            'due_date', '2026-02-27',
            'proof_required', JSON_ARRAY('proof:pr','proof:test-output','proof:metric-delta'),
            'rs_brief', JSON_OBJECT(
                'context', 'API contract docs for guild summary, roster, quest list, assign, resolve, save/load.',
                'constraints', 'Keep one previous contract version valid during cutover.',
                'suggested_prompt', 'Draft versioned API contract documentation with JSON examples and validation rules for these endpoints.'
            ),
            'class_fantasy', 'Aether Smith',
            'class', 'feature-smith',
            'difficulty', 3,
            'xp', 60,
            'labels', JSON_ARRAY('type:quest','difficulty:3','class:feature-smith','xp:60','chain:stabilize')
        ),
        JSON_OBJECT(
            'id', 'Q3',
            'type', 'Quest',
            'title', 'Ship Read APIs',
            'description', 'Build the read-only APIs needed by the guild home flow.',
            'rank_required', 'Gold',
            'quest_level', 3,
            'dependency_type', 'Chained',
            'depends_on', JSON_ARRAY('Q2'),
            'unlock_condition', 'Q2 contract guide approved.',
            'goal', 'Enable live backend reads for guild summary, roster, and available quests.',
            'player_steps', JSON_ARRAY('Implement the three read endpoints.','Add tests for normal and edge cases.','Capture latency results with seeded data.'),
            'done_when', JSON_ARRAY('All three read endpoints return correct data.','Tests pass for expected and invalid inputs.','P95 read latency is at or below 300ms in test run.'),
            'due_date', '2026-03-01',
            'proof_required', JSON_ARRAY('proof:pr','proof:test-output','proof:metric-delta'),
            'rs_brief', JSON_OBJECT(
                'context', 'Backend controllers/services/repos for read routes.',
                'constraints', 'Do not depend on mock data in the route path.',
                'suggested_prompt', 'Generate a checklist to implement and test three read endpoints with latency measurement steps.'
            ),
            'class_fantasy', 'Aether Smith',
            'class', 'feature-smith',
            'difficulty', 3,
            'xp', 60,
            'labels', JSON_ARRAY('type:quest','difficulty:3','class:feature-smith','xp:60','chain:stabilize')
        ),
        JSON_OBJECT(
            'id', 'Q4',
            'type', 'Quest',
            'title', 'Ship Write APIs',
            'description', 'Build the write APIs for assigning and finishing quests plus save/load.',
            'rank_required', 'Jade',
            'quest_level', 4,
            'dependency_type', 'Chained',
            'depends_on', JSON_ARRAY('Q1','Q2','Q3'),
            'unlock_condition', 'Q1-Q3 complete and API contracts frozen.',
            'goal', 'Enable reliable backend writes for the full gameplay loop.',
            'player_steps', JSON_ARRAY('Implement assign quest, resolve quest, save game, and load game routes.','Validate payloads and expected failures.','Run negative tests to verify no unhandled crashes.'),
            'done_when', JSON_ARRAY('All four write routes are live.','Invalid payloads return safe errors.','Negative test run shows zero unhandled exceptions.'),
            'due_date', '2026-03-05',
            'proof_required', JSON_ARRAY('proof:pr','proof:test-output','proof:metric-delta'),
            'rs_brief', JSON_OBJECT(
                'context', 'Mutation routes and validation rules.',
                'constraints', 'Writes must be idempotent where retries can happen.',
                'suggested_prompt', 'Help me design robust validation and idempotent handling for four write endpoints with failure-case tests.'
            ),
            'class_fantasy', 'Aether Smith',
            'class', 'feature-smith',
            'difficulty', 4,
            'xp', 80,
            'labels', JSON_ARRAY('type:quest','difficulty:4','class:feature-smith','xp:80','chain:stabilize')
        )
    ),
    4,
    140,
    'steady-hands',
    'The Stabilizer',
    @season_id,
    TRUE
),
(
    'adventurer-guild-cutover-readiness',
    'Adventurer Guild: Cutover Readiness',
    'Move the frontend to live APIs and make release operations safe.\n\nMetadata: {"type":"quest_chain","labels":["type:quest","chain:ship"]}',
    JSON_ARRAY(
        JSON_OBJECT(
            'id', 'Q5',
            'type', 'Quest',
            'title', 'Connect Frontend to Live APIs',
            'description', 'Replace core-loop mock data with real API calls.',
            'rank_required', 'Jade',
            'quest_level', 4,
            'dependency_type', 'Chained',
            'depends_on', JSON_ARRAY('Q3','Q4'),
            'unlock_condition', 'Q3 and Q4 are merged to main.',
            'goal', 'Run the core loop on backend data instead of mocks.',
            'player_steps', JSON_ARRAY('Replace mock imports in core-loop pages.','Handle loading and error states for API calls.','Verify key screens work with live responses.'),
            'done_when', JSON_ARRAY('Core-loop pages have zero mock imports.','Users can complete the core flow using API data.','Screenshots are attached for key states.'),
            'due_date', '2026-03-10',
            'proof_required', JSON_ARRAY('proof:pr','proof:test-output','proof:metric-delta','proof:screenshot'),
            'rs_brief', JSON_OBJECT(
                'context', 'Frontend stores/hooks/services for core gameplay pages.',
                'constraints', 'Preserve existing UX states while swapping data source.',
                'suggested_prompt', 'Create a migration checklist to replace mock adapters with API clients in React stores and hooks.'
            ),
            'class_fantasy', 'Cogwheel Alchemist',
            'class', 'ux-alchemist',
            'difficulty', 4,
            'xp', 80,
            'labels', JSON_ARRAY('type:quest','difficulty:4','class:ux-alchemist','xp:80','chain:ship')
        ),
        JSON_OBJECT(
            'id', 'Q6',
            'type', 'Quest',
            'title', 'Stabilize Save and Load',
            'description', 'Protect players from bad save payloads and recovery failures.',
            'rank_required', 'Jade',
            'quest_level', 4,
            'dependency_type', 'Blocked',
            'depends_on', JSON_ARRAY('Q4'),
            'unlock_condition', 'Q4 complete so save payload shape is stable.',
            'goal', 'Make save/load reliable and crash-safe.',
            'player_steps', JSON_ARRAY('Add payload guards for save and load.','Support fallback behavior for older or invalid data.','Run five save/load reliability scenarios.'),
            'done_when', JSON_ARRAY('All five reliability scenarios pass.','Invalid payloads do not crash the game.','Recovery path is documented and tested.'),
            'due_date', '2026-03-12',
            'proof_required', JSON_ARRAY('proof:pr','proof:test-output','proof:metric-delta'),
            'rs_brief', JSON_OBJECT(
                'context', 'Save/load validation, fallback handlers, and compatibility shim.',
                'constraints', 'Keep existing save format readable during transition.',
                'suggested_prompt', 'Design a save/load validation matrix with fallback handling and tests for invalid payload recovery.'
            ),
            'class_fantasy', 'Iron Warden',
            'class', 'ops-ranger',
            'difficulty', 4,
            'xp', 80,
            'labels', JSON_ARRAY('type:quest','difficulty:4','class:ops-ranger','xp:80','chain:ship')
        ),
        JSON_OBJECT(
            'id', 'Q7',
            'type', 'Quest',
            'title', 'Add Full-Loop Integration Tests',
            'description', 'Cover the complete recruit-to-reload loop with integration tests.',
            'rank_required', 'Gold',
            'quest_level', 3,
            'dependency_type', 'Chained',
            'depends_on', JSON_ARRAY('Q3','Q4'),
            'unlock_condition', 'Core read/write flow is available for end-to-end tests.',
            'goal', 'Prevent regressions in the core gameplay loop.',
            'player_steps', JSON_ARRAY('Add integration tests for recruit, assign, resolve, save, and reload.','Hook the suite into CI.','Track and reduce flaky test behavior.'),
            'done_when', JSON_ARRAY('At least 8 integration tests are passing.','CI blocks merges when this suite fails.','Flake rate is at or below 2%.'),
            'due_date', '2026-03-15',
            'proof_required', JSON_ARRAY('proof:pr','proof:test-output','proof:metric-delta'),
            'rs_brief', JSON_OBJECT(
                'context', 'Integration harness and CI gating rules.',
                'constraints', 'Tests must run consistently in CI without manual prep.',
                'suggested_prompt', 'Propose an integration test suite structure and CI gate for an end-to-end gameplay loop.'
            ),
            'class_fantasy', 'Arcane Examiner',
            'class', 'test-summoner',
            'difficulty', 3,
            'xp', 60,
            'labels', JSON_ARRAY('type:quest','difficulty:3','class:test-summoner','xp:60','chain:ship')
        ),
        JSON_OBJECT(
            'id', 'Q8',
            'type', 'Quest',
            'title', 'Publish Runbooks',
            'description', 'Write and test runbooks for deploy, rollback, incidents, and data restore.',
            'rank_required', 'Gold',
            'quest_level', 3,
            'dependency_type', 'Independent',
            'depends_on', JSON_ARRAY(),
            'unlock_condition', 'n/a',
            'goal', 'Make release support repeatable for the team.',
            'player_steps', JSON_ARRAY('Write deploy, rollback, incident, and restore runbooks.','Run one dry-run using the runbooks.','Capture any gaps and patch the docs.'),
            'done_when', JSON_ARRAY('Four runbooks are published.','One dry run is completed.','Runbook fixes from dry run are merged.'),
            'due_date', '2026-03-17',
            'proof_required', JSON_ARRAY('proof:pr','proof:test-output','proof:metric-delta','proof:screenshot'),
            'rs_brief', JSON_OBJECT(
                'context', 'Operational docs and request tracing setup.',
                'constraints', 'Runbooks must be short and executable by non-authors.',
                'suggested_prompt', 'Help me produce concise deploy/rollback/incident/restore runbooks with a dry-run checklist.'
            ),
            'class_fantasy', 'Rune Scribe',
            'class', 'doc-sage',
            'difficulty', 3,
            'xp', 60,
            'labels', JSON_ARRAY('type:quest','difficulty:3','class:doc-sage','xp:60','chain:ship')
        )
    ),
    4,
    160,
    'keystone-forger',
    'Guild Architect',
    @season_id,
    TRUE
),
(
    'adventurer-guild-backend-cutover-v1',
    'Raid: Backend Cutover v1',
    'Team release event to launch the backend cutover safely.\n\nMetadata: {"type":"raid","labels":["type:raid","difficulty:5","chain:ship","raid:active"],"entry_criteria":["Q1-Q8 complete","Boss B1 defeated","No unresolved P1/P2 blockers"],"go_no_go_gates":["8/8 integration tests passing","5/5 save-load scenarios passing","P95 read latency <= 300ms","Core route error rate < 1% during observation"]}',
    JSON_ARRAY(
        JSON_OBJECT(
            'id', 'R1-P1',
            'type', 'Raid',
            'title', 'Phase 1: Final Checks',
            'description', 'Run final tests and make go/no-go decision.',
            'rank_required', 'Diamond',
            'quest_level', 5,
            'dependency_type', 'Chained',
            'depends_on', JSON_ARRAY(),
            'unlock_condition', 'Raid entry criteria must be met.',
            'goal', 'Confirm release candidate is safe to launch.',
            'player_steps', JSON_ARRAY('Run full regression suite.','Review all gate results.','Publish go/no-go decision record.'),
            'done_when', JSON_ARRAY('All release gates pass.','Decision record is published.','Stakeholders are informed.'),
            'due_date', '2026-03-18',
            'proof_required', JSON_ARRAY('proof:test-output','proof:metric-delta'),
            'rs_brief', JSON_OBJECT(
                'context', 'Release readiness and decision logging.',
                'constraints', 'No launch if any gate fails.',
                'suggested_prompt', 'Generate a release go/no-go checklist with evidence links and decision log format.'
            ),
            'class_fantasy', 'Arcane Examiner',
            'class', 'test-summoner',
            'difficulty', 5,
            'xp', 80,
            'labels', JSON_ARRAY('type:raid','difficulty:5','class:test-summoner','xp:80','raid:active')
        ),
        JSON_OBJECT(
            'id', 'R1-P2',
            'type', 'Raid',
            'title', 'Phase 2: Staged Rollout',
            'description', 'Roll out in a staged environment and monitor for 24 hours.',
            'rank_required', 'Diamond',
            'quest_level', 5,
            'dependency_type', 'Chained',
            'depends_on', JSON_ARRAY('R1-P1'),
            'unlock_condition', 'Phase 1 marked complete.',
            'goal', 'Verify stability under live-like conditions before production.',
            'player_steps', JSON_ARRAY('Deploy staged rollout build.','Monitor errors, latency, and save/load health.','Log and resolve incidents during observation.'),
            'done_when', JSON_ARRAY('24-hour observation window completes.','Error and latency stay within gates.','Incident log is complete.'),
            'due_date', '2026-03-19',
            'proof_required', JSON_ARRAY('proof:test-output','proof:metric-delta','proof:screenshot'),
            'rs_brief', JSON_OBJECT(
                'context', 'Staged deployment and telemetry checks.',
                'constraints', 'Rollback must remain ready during full observation period.',
                'suggested_prompt', 'Build a 24-hour staged rollout runbook with checkpoints and rollback triggers.'
            ),
            'class_fantasy', 'Iron Warden',
            'class', 'ops-ranger',
            'difficulty', 5,
            'xp', 80,
            'labels', JSON_ARRAY('type:raid','difficulty:5','class:ops-ranger','xp:80','raid:active')
        ),
        JSON_OBJECT(
            'id', 'R1-P3',
            'type', 'Raid',
            'title', 'Phase 3: Production Launch',
            'description', 'Launch to production, tag release, and publish report.',
            'rank_required', 'Diamond',
            'quest_level', 5,
            'dependency_type', 'Chained',
            'depends_on', JSON_ARRAY('R1-P2'),
            'unlock_condition', 'Phase 2 observation window complete.',
            'goal', 'Complete cutover and leave a clear evidence trail.',
            'player_steps', JSON_ARRAY('Execute production cutover steps.','Create release tag and notes.','Publish what-shipped and postmortem draft.'),
            'done_when', JSON_ARRAY('Production release is live.','Release tag and notes are published.','Postmortem draft is shared.'),
            'due_date', '2026-03-20',
            'proof_required', JSON_ARRAY('proof:release-tag','proof:metric-delta','proof:postmortem'),
            'rs_brief', JSON_OBJECT(
                'context', 'Production launch checklist and post-release reporting.',
                'constraints', 'Keep rollback path available until verification closes.',
                'suggested_prompt', 'Create a production cutover checklist with release-tag/report requirements and rollback guardrails.'
            ),
            'class_fantasy', 'Rune Scribe',
            'class', 'doc-sage',
            'difficulty', 5,
            'xp', 80,
            'labels', JSON_ARRAY('type:raid','difficulty:5','class:doc-sage','xp:80','raid:active')
        )
    ),
    3,
    250,
    'release-raider',
    'Release Raider',
    @season_id,
    TRUE
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    steps = VALUES(steps),
    total_steps = VALUES(total_steps),
    reward_xp = VALUES(reward_xp),
    reward_badge_slug = VALUES(reward_badge_slug),
    reward_title = VALUES(reward_title),
    season_id = VALUES(season_id),
    is_active = VALUES(is_active);

DELETE FROM bosses
WHERE project_id <=> @project_id
  AND name IN ('Contract Drift Hydra', 'Save Corruption Wraith');

INSERT INTO bosses (
    github_issue_url,
    name,
    description,
    threat_level,
    status,
    project_id,
    season_id,
    hp_total,
    hp_current,
    created_at,
    defeated_at
)
VALUES
(
    NULL,
    'Contract Drift Hydra',
    'Frontend and backend data contracts can drift during cutover.\n\nMetadata: {"labels":["type:boss","difficulty:4","class:test-summoner","xp:80","boss:active","chain:stabilize"],"risk_level":"high","rollback_plan":"Freeze contract version and route traffic to previous stable payload version.","kill_criteria":["100% contract tests pass in CI","0 runtime contract mismatch errors in staging for 48h"],"proof_required":["proof:pr","proof:test-output","proof:metric-delta","proof:postmortem"]}',
    4,
    'active',
    @project_id,
    @season_id,
    8,
    8,
    NOW(),
    NULL
),
(
    NULL,
    'Save Corruption Wraith',
    'Invalid save payloads can break loading and player progress.\n\nMetadata: {"labels":["type:boss","difficulty:4","class:ops-ranger","xp:80","boss:active","chain:stabilize"],"risk_level":"high","rollback_plan":"Disable affected save version and restore last known good snapshot.","kill_criteria":["5/5 save-load scenarios pass","0 crash on invalid payload handling"],"proof_required":["proof:pr","proof:test-output","proof:metric-delta","proof:postmortem"]}',
    4,
    'active',
    @project_id,
    @season_id,
    8,
    8,
    NOW(),
    NULL
);

COMMIT;

SELECT @project_id AS resolved_project_id, @season_id AS resolved_season_id;

SELECT slug, name, total_steps, reward_xp, is_active
FROM quest_chains
WHERE slug IN (
    'adventurer-guild-backend-foundation',
    'adventurer-guild-cutover-readiness',
    'adventurer-guild-backend-cutover-v1'
)
ORDER BY slug;

SELECT id, name, threat_level, status, hp_current, hp_total, project_id, season_id
FROM bosses
WHERE project_id <=> @project_id
  AND name IN ('Contract Drift Hydra', 'Save Corruption Wraith')
ORDER BY created_at DESC;
