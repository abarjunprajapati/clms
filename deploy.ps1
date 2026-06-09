# CLMS Deployment Script for cslweb.teleconsystems.com
# Usage: .\deploy.ps1

param(
    [string]$FTPHost = "cslweb.teleconsystems.com",
    [string]$FTPUser = "",
    [string]$FTPPass = "",
    [string]$RemotePath = "/public_html/",
    [string]$Mode = "check"
)

$LocalPath = "D:\Xampp\htdocs\clms"
$DeploymentList = @(
    "api",
    "include",
    "js",
    "css",
    "pages",
    "uploads",
    ".htaccess",
    "index.php"
)

Write-Host "CLMS Deployment Tool" -ForegroundColor Cyan
Write-Host "===================" -ForegroundColor Cyan

if ($Mode -eq "check") {
    Write-Host "`nChecking deployment requirements..." -ForegroundColor Yellow
    
    foreach ($item in $DeploymentList) {
        $path = Join-Path $LocalPath $item
        if (Test-Path $path) {
            Write-Host "[OK] $item" -ForegroundColor Green
        } else {
            Write-Host "[MISSING] $item" -ForegroundColor Red
        }
    }
    
    Write-Host "`nDeployment Guide created: DEPLOYMENT_GUIDE.md" -ForegroundColor Green
    Write-Host "Read it for FTP upload instructions" -ForegroundColor Yellow
}

