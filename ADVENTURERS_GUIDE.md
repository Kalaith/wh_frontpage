# ⚔️ WebHatchery Adventurer's Guide

Welcome to the team! At WebHatchery, we don't just assign boring Jira tickets—we go on **Quests**. By completing work, you will earn XP, level up your Rank, and claim your place on the leaderboards. 

Here is everything you need to know to pick up your first sword (or keyboard) and get started.

---

## 1. Finding Your First Quest
Our quests live on our project's GitHub Issues board.
1. **Filter by Label:** Look for issues tagged with `quest`.
2. **Check the Rank Requirement:** As a new developer, you are starting at **Iron** rank. Look for quests that say `Rank Required: Iron`. *(Don't try to solo a Jade or Diamond quest yet!)*
3. **Check Dependencies:** Look at the `Dependency Type`:
   - `Independent`: You can start this right away.
   - `Chained`: Look at the `Depends On` field. You cannot start this quest until the prerequisites are completely finished by someone else (or you).
4. **Claim It:** Assign the GitHub issue to yourself so no one else takes your bounty!

---

## 2. Reading the Quest Card
Every quest follows a strict format so you always know exactly what to do:
- **Goal:** The high-level objective in plain English.
- **Steps:** A quick checklist of the actual work.
- **Done When:** The exact definition of done. Make sure you can check off every item here.
- **Class Fantasy / Rank:** Quests belong to a class (like `ops-ranger` or `feature-smith`) just for fun, and reward a specific amount of XP.
- **Proof Required:** **⚠️ CRITICAL ⚠️** You must provide this evidence when you submit your work. *No proof = No XP.*

---

## 3. Doing the Work & Getting Paid
1. **Branch & Code:** Create a Git branch, do the work, and put up a Pull Request (PR) on GitHub.
2. **Link the PR:** In your PR description, write "Fixes #<quest-issue-number>" so GitHub links them.
3. **Attach Your Proof:** Look at what the quest asked for under `Proof Required`. Attach your screenshots, paste your test outputs, or link to your metric changes directly in your PR description.
4. **Defeat the Quest:** Once your PR is reviewed and merged, the Quest is complete!
5. **Get XP:** The main WebHatchery site will automatically read the merged quest and credit you with your XP (e.g., +40 XP). Keep doing quests to rank up from Iron to Silver, Gold, Jade, and Diamond!

---

## 🐉 A Note on Boss Battles
Sometimes you will see a `boss` label on GitHub. These are not standard quests—they are major challenges like high-risk deployments or massive feature refactors. 

Bosses have `Checkpoints (HP)` instead of normal steps, and strict `Kill Criteria`. These require Jade or Diamond rank, but keep completing your Iron and Silver quests, level up, and you'll be joining Raid groups against them soon enough!

Good luck, Adventurer!

---

## 🛠️ Game Master's Guide: Integrating a New Project
If you are starting a brand new project and want to gamify its development with the WebHatchery Quest Board, follow these steps to hook it up.

### 1. Register the Project
Your WebHatchery backend needs to know this project exists. 
Add the new project into the WebHatchery database (in the `projects` table) and ensure the `repository_url` is set to your new GitHub repo URL (e.g., `https://github.com/Kalaith/my_new_project`).

### 2. Bind the Webhooks
WebHatchery needs permission to listen to your new repo for PR merges to award XP. Once the project is in the database, you must trigger WebHatchery's automated webhook setup.

The backend controller (`GitHubWebhookController`) will iterate through all registered projects and configure a GitHub webhook for your new project to listen for `push` events.

**Prerequisites:**
Before running the setup, ensure your backend `.env` file has these configured:

1. `GITHUB_TOKEN`: A Personal Access Token (PAT) used by the backend to talk to GitHub.
   - **How to get it:** Go to GitHub -> Settings -> Developer Settings -> Personal Access Tokens -> Tokens (classic). 
   - Click **Generate new token (classic)**.
   - Give it a name (e.g., "WebHatchery Quest Board").
   - **Required Scopes:** Check the `repo` box (this gives it full control of private repositories) and `admin:repo_hook` (to let it create the webhook).
   - Generate and copy the token into your `.env`.

2. `GITHUB_WEBHOOK_SECRET`: A secure password used to prove that incoming webhooks are actually from GitHub.
   - **How to get it:** You invent this! Just mash your keyboard or use a password generator to create a long, random string (e.g., `wh_super_secret_string_123`). Put it in your `.env`. When WebHatchery configures the webhook, it will give GitHub this password to use.

3. `APP_URL`: The public URL of your WebHatchery instance (e.g., `https://hatchery.example.com`). GitHub needs to know where to send the webhooks.

4. `ALLOWED_ADMIN_IP`: Your current IP address. (Because setting up webhooks bulk-modifies GitHub repos, this endpoint is IP-restricted to stop random people from triggering it).


**Trigger the Setup:**
Make a POST or GET request to the admin setup endpoint:
```text
GET /api/admin/webhooks/setup
```
*(Check your `api.php` routes for the exact path mapped to `GitHubWebhookController::setupWebhooks` if `admin/webhooks/setup` is incorrect).*

The backend will automatically find your new project, talk to the GitHub API, and install a webhook pointing to `/api/webhooks/github` that uses your `GITHUB_WEBHOOK_SECRET`. All done!


### 3. Create Your Quest Seed
Duplicate a quest seed file (like `xytherra_game_improvement.json`) to use as a template.
Update the `habitat` section so it targets your new project:
```json
"habitat":  {
    "slug":  "my_new_project",
    "project_path":  "apps/my_new_project/",
    "project_title":  "My New Project"
}
```

### 4. Spawn the Quests
Run the quest import script to push the quests to your new project's GitHub repo. Make sure you update the environment variables to target the new repo!

```bash
set GITHUB_TOKEN=your_personal_access_token
set REPO_OWNER=Kalaith
set REPO_NAME=my_new_project

python h:\WebHatchery\frontpage\tools\import_quests_to_github.py path_to_your_new_json.json
```

### 5. Frontend API Config
When building the frontend Quest Board for your new project, make sure the API call tells the backend which repo to fetch issues from. The backend defaults to `wh_frontpage`, so pass the `owner` and `repo` parameters:
```javascript
// Example API call modification
const data = await fetchQuests({ owner: 'Kalaith', repo: 'my_new_project' });
```
