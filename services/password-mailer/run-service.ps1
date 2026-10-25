param(
    [Parameter(Mandatory = $true)]
    [string] $NodeExecutable
)

$ErrorActionPreference = 'Stop'

if (-not (Test-Path -LiteralPath $NodeExecutable -PathType Leaf)) {
    throw "Node.js executable was not found: $NodeExecutable"
}

$serviceDirectory = $PSScriptRoot
$projectRoot = (Resolve-Path (Join-Path $serviceDirectory '..\..')).Path
$logDirectory = Join-Path $projectRoot 'storage\logs'
$logFile = Join-Path $logDirectory 'password-mailer-startup.log'

New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
Set-Location $serviceDirectory
Add-Content -Path $logFile -Value "[$(Get-Date -Format o)] Starting Nodemailer password service." -Encoding UTF8

& $NodeExecutable --env-file=..\..\.env server.js 2>&1 | Out-File -FilePath $logFile -Append -Encoding utf8
$exitCode = $LASTEXITCODE

Add-Content -Path $logFile -Value "[$(Get-Date -Format o)] Nodemailer password service exited with code $exitCode." -Encoding UTF8
exit $exitCode
