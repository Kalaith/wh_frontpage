# Database Setup Files

- `setup.sql` - Base WebHatchery frontpage schema and project seed.
- `setup_gamification.sql` - Gamification schema extension tables.
- `seed_adventurer_guild_gamification.sql` - Plain-language quest/boss/raid seed (with optional RuneSage briefs) for adventurer_guild.
- `guild_master_migration.sql` - Adds `projects.owner_user_id` for guild master project ownership.
- `setup-labels.ps1` - Helper script for creating GitHub label sets.

Typical order:
1. Run `db/setup.sql`
2. Run `db/setup_gamification.sql`
3. Run `db/guild_master_migration.sql` if upgrading existing installs
4. Optional: run `db/seed_adventurer_guild_gamification.sql`
