import os
import sys
import json
import urllib.request
import urllib.error

def get_env_or_exit(key):
    val = os.environ.get(key)
    if not val:
        print(f"Error: Missing required environment variable '{key}'")
        sys.exit(1)
    return val

def github_request(endpoint, token, owner, repo, method="GET", data=None):
    url = f"https://api.github.com/repos/{owner}/{repo}/{endpoint}"
    req = urllib.request.Request(url, method=method)
    req.add_header("Authorization", f"Bearer {token}")
    req.add_header("Accept", "application/vnd.github.v3+json")
    req.add_header("User-Agent", "WebHatchery-Quest-Importer")

    if data:
        req.add_header("Content-Type", "application/json")
        req.data = json.dumps(data).encode("utf-8")

    try:
        with urllib.request.urlopen(req) as response:
            if response.status == 204:
                return True
            return json.loads(response.read().decode())
    except urllib.error.HTTPError as e:
        print(f"  [!] GitHub API Error ({e.code}): {e.read().decode()}")
        return None
    except Exception as e:
        print(f"  [!] Network Error: {e}")
        return None

def format_quest_body(quest):
    if 'body' in quest: return quest['body']
    
    body = "### Quest Card\n"
    body += f"- **Type**: `{quest.get('type', 'Quest')}`\n"
    body += f"- **Rank Required**: `{quest.get('rank_required', 'Iron')}`\n"
    body += f"- **Quest Level**: `{quest.get('quest_level', 1)}`\n"
    body += f"- **Dependency Type**: `{quest.get('dependency_type', 'Independent')}`\n"
    
    deps = quest.get('depends_on', [])
    deps_str = ', '.join(deps) if deps else 'none'
    body += f"- **Depends On**: `{deps_str}`\n"
    body += f"- **Unlock Condition**: `{quest.get('unlock_condition', 'n/a')}`\n\n"
    
    if 'goal' in quest: body += f"**Goal**: {quest['goal']}\n\n"
    
    if 'player_steps' in quest:
        body += "**Steps**:\n"
        for step in quest['player_steps']: body += f"- {step}\n"
        body += "\n"
        
    if 'done_when' in quest:
        body += "**Done When**:\n"
        for cond in quest['done_when']: body += f"- [ ] {cond}\n"
        body += "\n"
        
    if 'due_date' in quest: body += f"**Due Date**: `{quest['due_date']}`\n\n"
        
    if 'proof_required' in quest:
        body += "**Proof Required**:\n"
        for proof in quest['proof_required']: body += f"- `{proof}`\n"
        body += "\n"
        
    if 'xp' in quest: body += f"**Reward**: {quest['xp']} XP\n"
    if 'class_fantasy' in quest: body += f"**Class**: {quest['class_fantasy']} ({quest.get('class', '')})\n"
            
    return body

def format_boss_body(boss):
    body = "### Boss Card\n"
    body += f"- **Type**: `Boss`\n"
    body += f"- **Threat Level**: `{boss.get('threat_level', 4)}`\n"
    body += f"- **Status**: `{boss.get('status', 'active')}`\n\n"
    
    if 'description' in boss: body += f"**Goal**: {boss['description']}\n\n"
        
    if 'hp_tasks' in boss:
        body += "**Checkpoints (HP)**:\n"
        for task in boss['hp_tasks']: body += f"- [ ] {task}\n"
        body += "\n"
        
    if 'kill_criteria' in boss:
        body += "**Done When (Kill Criteria)**:\n"
        for crit in boss['kill_criteria']: body += f"- [ ] {crit}\n"
        body += "\n"
        
    if 'deadline' in boss: body += f"**Due Date**: `{boss['deadline']}`\n\n"
        
    if 'proof_required' in boss:
        body += "**Proof Required**:\n"
        for proof in boss['proof_required']: body += f"- `{proof}`\n"
        body += "\n"
        
    if 'rollback_plan' in boss: body += f"**Rollback Plan**: {boss['rollback_plan']}\n"
    return body

def import_quests(json_path, token, owner, repo):
    try:
        with open(json_path, 'r', encoding='utf-8') as f:
            seed_data = json.load(f)
    except FileNotFoundError:
        print(f"Error: Could not find file '{json_path}'")
        sys.exit(1)
    except json.JSONDecodeError as e:
        print(f"Error: Invalid JSON format in '{json_path}'\nDetails: {e}")
        sys.exit(1)

    quests = seed_data.get('quests', [])
    
    # Extract from quest_chains
    for chain in seed_data.get('quest_chains', []):
        for step in chain.get('steps', []):
            quests.append({
                'title': step.get('title', 'Untitled Quest'),
                'body': format_quest_body(step),
                'labels': step.get('labels', ['quest']),
                'type': 'quest'
            })
            
    # Extract from bosses
    for boss in seed_data.get('bosses', []):
        quests.append({
            'title': f"Boss: {boss.get('name', 'Unknown')}",
            'body': format_boss_body(boss),
            'labels': boss.get('labels', ['boss']),
            'type': 'boss'
        })

    if not quests:
        print("No quests or bosses found in the JSON file. Nothing to import.")
        sys.exit(0)

    print(f"Found {len(quests)} quests/bosses. Connecting to GitHub ({owner}/{repo})...")
    
    success_count = 0
    for idx, quest in enumerate(quests, start=1):
        title = quest.get('title', f"Untitled Quest {idx}")
        body = quest.get('body', "")
        labels = quest.get('labels', [])
        
        # Ensure 'quest' or 'boss' is in labels
        target_label = quest.get('type', 'quest')
        if target_label not in labels:
            labels.append(target_label)

        print(f"→ Creating {target_label.capitalize()} [{idx}/{len(quests)}]: {title}")
        
        payload = {
            "title": title,
            "body": body,
            "labels": labels
        }
        
        res = github_request("issues", token, owner, repo, method="POST", data=payload)
        if res and "html_url" in res:
            print(f"  [✓] Success! URL: {res['html_url']}")
            success_count += 1
        else:
            print("  [X] Failed.")
            
    print(f"\nImport Complete! {success_count}/{len(quests)} imported successfully.")

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python import_quests_to_github.py <path_to_json_file>")
        print("Example: python import_quests_to_github.py example_quest_seed.json")
        sys.exit(1)

    json_file_path = sys.argv[1]
    
    # Require credentials as env vars to prevent hardcoding
    GITHUB_TOKEN = get_env_or_exit("GITHUB_TOKEN")
    REPO_OWNER = get_env_or_exit("REPO_OWNER")
    REPO_NAME = get_env_or_exit("REPO_NAME")
    
    import_quests(json_file_path, GITHUB_TOKEN, REPO_OWNER, REPO_NAME)
