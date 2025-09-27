# Project Update Notification System Plan

## Current State Analysis
- **Frontpage**: React frontend with PHP backend, uses centralized `projects.json` (currently empty)
- **Projects**: Individual apps/game_apps with their own build systems and publish scripts
- **Build System**: PowerShell-based deployment with individual project publish scripts
- **No Update Tracking**: Currently no mechanism to track when projects are updated, built, or when git versions differ

## Proposed Solutions

### **Option 1: Project Manifest System (Recommended)**
Create a standardized `project.json` manifest in each project's root directory.

**Structure:**
```json
{
  "name": "project_name",
  "version": "1.2.3",
  "lastUpdated": "2025-09-27T14:30:00Z",
  "lastBuild": "2025-09-27T14:25:00Z",
  "gitCommit": "abc123...",
  "status": "active|maintenance|deprecated",
  "deployment": {
    "production": "2025-09-26T10:00:00Z",
    "development": "2025-09-27T14:25:00Z"
  }
}
```

**Implementation:**
1. **Auto-generation**: Modify publish scripts to update project.json during builds
2. **Aggregation**: Frontpage backend scans all projects and aggregates manifests
3. **API endpoint**: `/api/projects/updates` returns update status for all projects
4. **Frontend display**: Show "New!", "Updated!", version mismatch badges

### **Option 2: GitHub Workflow Integration (Recommended for Git Data)**
Use GitHub Actions to maintain git-related information in project manifests.

**Workflow Responsibilities:**
- Track git commits, branches, and repository state
- Update project.json with git metadata on every push
- Generate version numbers from git tags
- Track code changes and commit messages

**Publish Script Responsibilities:**
- Update build timestamps and deployment info
- Track local build status and deployment targets
- Maintain deployment-specific metadata

### **Option 3: Centralized Build System Enhancement**
Enhance the existing PowerShell build system to track updates.

**Implementation:**
1. **Build logging**: Modify `publish.ps1` to log build events to central database
2. **Version tracking**: Compare git commits to last deployed versions
3. **Status dashboard**: Real-time build/deployment status
4. **Notification system**: Email/webhook notifications for updates

## **Recommended Implementation Plan**

### **Phase 1: Project Manifest Foundation**
1. **Create template**: Standard `project.json` template
2. **Update build scripts**: Modify existing publish scripts to maintain manifests
3. **Frontpage API**: Backend endpoint to aggregate project manifests
4. **Basic UI**: Simple "recently updated" section on homepage

### **Phase 2: Git Integration**
1. **Git tracking**: Compare manifest versions with git commits
2. **Status indicators**: "Behind git", "Up to date", "Uncommitted changes"
3. **Automated updates**: GitHub Actions to update manifests on commits

### **Phase 3: Advanced Features**
1. **News feed**: Automatic changelog generation from git commits
2. **Deployment tracking**: Track production vs development deployment status
3. **Health monitoring**: Project health checks and status reporting
4. **User notifications**: Subscribe to project update notifications

## **Technical Implementation Details**

### **File Locations:**
- `{project_root}/project.json` - Project manifest
- `frontpage/backend/src/Services/ProjectUpdateService.php` - Aggregation service
- `frontpage/frontend/src/components/ProjectUpdates.tsx` - UI component

### **API Endpoints:**
- `GET /api/projects/updates` - Get all project update status
- `GET /api/projects/{id}/status` - Individual project status
- `POST /api/projects/{id}/notify` - Mark project as viewed

### **Hybrid Approach - Workflow + Publish Script:**

**GitHub Workflow (.github/workflows/update-manifest.yml):**
```yaml
name: Update Project Manifest
on: [push]
jobs:
  update-manifest:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Update project.json with git info
        run: |
          # Read existing manifest or create new one
          if [ -f "project.json" ]; then
            manifest=$(cat project.json)
          else
            manifest='{"name":"'${GITHUB_REPOSITORY#*/}'","status":"active"}'
          fi

          # Update with git information
          echo "$manifest" | jq --arg commit "$GITHUB_SHA" \
            --arg updated "$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
            --arg branch "$GITHUB_REF_NAME" \
            '.gitCommit = $commit | .lastUpdated = $updated | .branch = $branch' \
            > project.json

      - name: Commit updated manifest
        run: |
          git config --local user.email "action@github.com"
          git config --local user.name "GitHub Action"
          git add project.json
          git diff --staged --quiet || git commit -m "Update project manifest [skip ci]"
          git push
```

**PowerShell Integration (publish.ps1):**
```powershell
# Read existing manifest and update build/deployment info only
$manifestPath = "project.json"
if (Test-Path $manifestPath) {
    $manifest = Get-Content $manifestPath | ConvertFrom-Json
} else {
    $manifest = @{
        name = $PROJECT_NAME
        status = "active"
    }
}

# Update build and deployment timestamps
$manifest.lastBuild = (Get-Date).ToString("yyyy-MM-ddTHH:mm:ssZ")
if ($Production) {
    $manifest.deployment.production = (Get-Date).ToString("yyyy-MM-ddTHH:mm:ssZ")
} else {
    $manifest.deployment.development = (Get-Date).ToString("yyyy-MM-ddTHH:mm:ssZ")
}

$manifest | ConvertTo-Json -Depth 3 | Set-Content $manifestPath
```

## Benefits
- **Real-time visibility** into project status across entire WebHatchery ecosystem
- **Automated tracking** of recent updates and deployment health
- **User awareness** of new projects and recent changes
- **Development insights** for version management and deployment tracking
- **Scalable foundation** for future monitoring and notification features

This system would provide comprehensive project lifecycle tracking while maintaining the existing build and deployment workflows.