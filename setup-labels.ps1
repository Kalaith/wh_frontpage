[CmdletBinding()]
param (
    [string]$Owner = "Kalaith",
    [string]$Repo = "wh_frontpage"
)

# Check for gh cli
if (-not (Get-Command "gh" -ErrorAction SilentlyContinue)) {
    Write-Error "GitHub CLI (gh) is not installed. Please install it to run this script: https://cli.github.com/"
    exit 1
}

# Check if authenticated
gh auth status | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Warning "You need to login to GitHub CLI first. Running 'gh auth login'..."
    gh auth login
}

$LabelsPath = Join-Path $PSScriptRoot "labels.json"
if (-not (Test-Path $LabelsPath)) {
    Write-Error "labels.json not found in script directory."
    exit 1
}

$Labels = Get-Content $LabelsPath | ConvertFrom-Json

Write-Host "Applying labels to $Owner/$Repo..." -ForegroundColor Cyan

foreach ($Label in $Labels) {
    # Check if label exists
    $Existing = gh label list --repo "$Owner/$Repo" --search $Label.name --json name | ConvertFrom-Json
    
    if ($Existing -and $Existing.name -contains $Label.name) {
        Write-Host "Updating label: $($Label.name)" -ForegroundColor Yellow
        gh label edit $Label.name --repo "$Owner/$Repo" --color $Label.color --description $Label.description
    } else {
        Write-Host "Creating label: $($Label.name)" -ForegroundColor Green
        gh label create $Label.name --repo "$Owner/$Repo" --color $Label.color --description $Label.description
    }
}

Write-Host "Done! RPG labels applied to $Owner/$Repo." -ForegroundColor Cyan
