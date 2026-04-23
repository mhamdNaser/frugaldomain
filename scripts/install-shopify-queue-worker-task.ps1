$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$workerScript = Join-Path $projectRoot 'scripts\shopify-queue-worker.bat'
$taskName = 'FrugalDomain Shopify Queue Worker'

$action = New-ScheduledTaskAction `
    -Execute 'cmd.exe' `
    -Argument "/c `"$workerScript`"" `
    -WorkingDirectory $projectRoot

$trigger = New-ScheduledTaskTrigger -AtLogOn
$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -RestartCount 999 `
    -RestartInterval (New-TimeSpan -Minutes 1)

Register-ScheduledTask `
    -TaskName $taskName `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -Description 'Runs the Laravel queue worker for FrugalDomain Shopify sync queues.' `
    -Force | Out-Null

Start-ScheduledTask -TaskName $taskName

Write-Host "Installed and started scheduled task: $taskName"
