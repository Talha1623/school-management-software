# Run on your PC before uploading to the live server.
# Confirms local code is saved in Git and shows what is not on GitHub yet.

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

Write-Host ""
Write-Host "=== Before upload check ===" -ForegroundColor Cyan
Write-Host "Project: $root"
Write-Host ""

git fetch origin 2>$null
$branch = git rev-parse --abbrev-ref HEAD
$local = git rev-parse HEAD
$remote = git rev-parse origin/$branch 2>$null

if (-not $remote) {
    Write-Host "WARNING: No remote branch origin/$branch" -ForegroundColor Yellow
} elseif ($local -eq $remote) {
    Write-Host "OK: Local and GitHub are the SAME ($($local.Substring(0,7)))" -ForegroundColor Green
} else {
    Write-Host "WARNING: Local and GitHub are DIFFERENT" -ForegroundColor Red
    Write-Host "  Local:  $($local.Substring(0,7))"
    Write-Host "  GitHub: $($remote.Substring(0,7))"
    Write-Host "  Push first: git add -A; git commit -m 'your message'; git push"
}

$dirty = git status --porcelain
if ($dirty) {
    Write-Host ""
    Write-Host "WARNING: Uncommitted changes (uploading now may be lost later):" -ForegroundColor Yellow
    git status -s
} else {
    Write-Host "OK: No uncommitted changes" -ForegroundColor Green
}

Write-Host ""
Write-Host "Upload from: $root" -ForegroundColor Cyan
Write-Host "On server after upload, run: bash scripts/after-deploy.sh"
Write-Host ""
