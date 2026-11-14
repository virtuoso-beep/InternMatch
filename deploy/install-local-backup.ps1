param([Parameter(Mandatory = $true)][string]$PythonPath)
$ErrorActionPreference = 'Stop'
$projectPath = Split-Path $PSScriptRoot -Parent
$scriptPath = Join-Path $PSScriptRoot 'recovery.py'
$taskName = 'InternMatch Local Daily Backup'
if (-not (Test-Path -LiteralPath $PythonPath -PathType Leaf)) { throw 'Python executable not found.' }
$action = New-ScheduledTaskAction -Execute $PythonPath -Argument ('"' + $scriptPath + '" backup') -WorkingDirectory $projectPath
$trigger = New-ScheduledTaskTrigger -Daily -At '18:00'
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -MultipleInstances IgnoreNew -ExecutionTimeLimit (New-TimeSpan -Hours 2) -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries
$principal = New-ScheduledTaskPrincipal -UserId ([System.Security.Principal.WindowsIdentity]::GetCurrent().Name) -LogonType Interactive -RunLevel Limited
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Description 'Local InternMatch database and private/public document backup. Requires Docker Desktop running and the user signed in.' -Force | Out-Null
$task = Get-ScheduledTask -TaskName $taskName
$schedulePath = Join-Path $projectPath 'backend/storage/app/backups/schedule.json'
New-Item -ItemType Directory -Path (Split-Path $schedulePath -Parent) -Force | Out-Null
@{ task_name = $taskName; schedule = 'Daily at 18:00 Windows local time'; timezone = (Get-TimeZone).Id; state = [string]$task.State; requirements = 'User signed in and Docker Desktop running. Missed runs start when available.'; configured_at = (Get-Date).ToUniversalTime().ToString('o') } | ConvertTo-Json | Set-Content -LiteralPath $schedulePath -Encoding utf8
Get-ScheduledTaskInfo -TaskName $taskName | Select-Object TaskName, NextRunTime, LastTaskResult
