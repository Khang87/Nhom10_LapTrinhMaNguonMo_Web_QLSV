$ErrorActionPreference = 'Stop'

$taskName = 'Nhom10-PasswordMailer'
$serviceDirectory = $PSScriptRoot
$projectRoot = (Resolve-Path (Join-Path $serviceDirectory '..\..')).Path
$runnerPath = Join-Path $serviceDirectory 'run-service.ps1'
$environmentPath = Join-Path $projectRoot '.env'
$nodeCommand = Get-Command node.exe -ErrorAction Stop
$nodePath = $nodeCommand.Source
$taskUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
$powerShellPath = Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe'

if (-not (Test-Path -LiteralPath $environmentPath -PathType Leaf)) {
    throw "The project .env file was not found: $environmentPath"
}

if (-not (Test-Path -LiteralPath $runnerPath -PathType Leaf)) {
    throw "The service runner was not found: $runnerPath"
}

if (Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue) {
    throw "Scheduled task '$taskName' already exists. Remove it before reinstalling."
}

$existingListener = Get-NetTCPConnection -LocalPort 3001 -State Listen -ErrorAction SilentlyContinue
if ($existingListener) {
    throw 'Port 3001 is already in use. Stop the existing mailer before installing the startup task.'
}

$arguments = '-NoLogo -NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File "{0}" -NodeExecutable "{1}"' -f $runnerPath, $nodePath
$action = New-ScheduledTaskAction -Execute $powerShellPath -Argument $arguments -WorkingDirectory $projectRoot
$trigger = New-ScheduledTaskTrigger -AtLogOn -User $taskUser
$principal = New-ScheduledTaskPrincipal -UserId $taskUser -LogonType Interactive -RunLevel Limited
$settings = New-ScheduledTaskSettingsSet `
    -StartWhenAvailable `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -MultipleInstances IgnoreNew `
    -RestartCount 3 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -ExecutionTimeLimit ([TimeSpan]::Zero)

Register-ScheduledTask `
    -TaskName $taskName `
    -Action $action `
    -Trigger $trigger `
    -Principal $principal `
    -Settings $settings `
    -Description 'Runs the local Laravel password-reset email service at Windows logon.' | Out-Null

Start-ScheduledTask -TaskName $taskName

$deadline = (Get-Date).AddSeconds(15)
do {
    Start-Sleep -Milliseconds 500
    $listener = Get-NetTCPConnection -LocalPort 3001 -State Listen -ErrorAction SilentlyContinue
} while (-not $listener -and (Get-Date) -lt $deadline)

if (-not $listener) {
    $task = Get-ScheduledTask -TaskName $taskName
    $info = Get-ScheduledTaskInfo -TaskName $taskName
    throw "Startup task was installed but the service did not bind to port 3001. Task state: $($task.State); last result: $($info.LastTaskResult). Check storage\logs\password-mailer.log."
}

Write-Output "Installed and started scheduled task '$taskName' for $taskUser."
Write-Output 'The service runs hidden at logon and writes output to storage\logs\password-mailer.log.'
