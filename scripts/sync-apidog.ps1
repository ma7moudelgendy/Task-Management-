# TaskFlow: Laravel -> Apidog Sync Workflow
param (
    [switch]$Force,
    [switch]$Overwrite,
    [string]$ProjectId = $env:APIDOG_PROJECT_ID,
    [string]$Token = $env:APIDOG_ACCESS_TOKEN
)

$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Resolve-Path "$ScriptDir\.."
$LaravelDir = "$ProjectRoot\laravel"

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host " TaskFlow: Laravel -> Apidog Sync Workflow   " -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan

# Step 1: Regenerate OpenAPI specification
Write-Host "`n[1/3] Generating OpenAPI 3.1.0 specification..." -ForegroundColor Yellow
Push-Location $LaravelDir
try {
    php artisan openapi:generate
} finally {
    Pop-Location
}

# Step 2: Validate generated specification
$SpecPath = "$ProjectRoot\docs\openapi.json"
Write-Host "`n[2/3] Validating OpenAPI specification at $SpecPath..." -ForegroundColor Yellow
if (-not (Test-Path $SpecPath)) {
    Write-Error "Specification file not found at $SpecPath!"
    exit 1
}

try {
    $spec = Get-Content $SpecPath -Raw | ConvertFrom-Json
    $endpointCount = ($spec.paths | Get-Member -MemberType NoteProperty).Count
    Write-Host "[OK] Specification is valid JSON (OpenAPI $($spec.openapi), $endpointCount paths found)." -ForegroundColor Green
} catch {
    Write-Error "Specification JSON parsing failed: $_"
    exit 1
}

# Step 3: Sync to Apidog
Write-Host "`n[3/3] Synchronizing with Apidog..." -ForegroundColor Yellow
Push-Location $LaravelDir
try {
    $cmdArgs = @('artisan', 'apidog:sync')
    if ($Force) {
        $cmdArgs += '--force'
    } else {
        $cmdArgs += '--dry-run'
    }

    if ($Overwrite) {
        $cmdArgs += '--overwrite'
    }

    if ($ProjectId) {
        $cmdArgs += "--project-id=$ProjectId"
    }

    if ($Token) {
        $cmdArgs += "--token=$Token"
    }

    php @cmdArgs
} finally {
    Pop-Location
}

