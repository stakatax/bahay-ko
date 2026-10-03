# Stop only the isolated evaluation processes. Never stop XAMPP's usual Apache.
$ErrorActionPreference = 'Stop'
$evaluationProject = Split-Path -Parent $PSScriptRoot
$evaluationPrivate = Join-Path $evaluationProject 'deployment\tunnel\evaluation\private'
$evaluationTargets = @(
    @{ File = 'evaluation-worker-process.txt'; Executable = (Join-Path $evaluationPrivate 'worker-python-path.txt'); Argument = (Join-Path $PSScriptRoot 'run_evaluation_worker.py'); Worker = $true },
    @{ File = 'evaluation-tunnel-process.txt'; Executable = (Join-Path $evaluationProject 'deployment\tunnel\tools\cloudflared.exe'); Argument = 'http://127.0.0.1:8080' },
    @{ File = 'evaluation-apache-process.txt'; Executable = 'C:\xampp\apache\bin\httpd.exe'; Argument = 'C:/xampp/htdocs/bahay-ko/deployment/tunnel/evaluation/apache.conf' }
)
foreach ($evaluationTarget in $evaluationTargets) {
    $evaluationPidFile = Join-Path $evaluationPrivate $evaluationTarget.File
    if (-not (Test-Path -LiteralPath $evaluationPidFile)) { continue }
    $evaluationProcessId = 0
    if (-not [int]::TryParse((Get-Content -LiteralPath $evaluationPidFile -Raw).Trim(), [ref]$evaluationProcessId)) {
        throw 'Invalid saved evaluation process ID.'
    }
    $evaluationProcess = Get-CimInstance Win32_Process -Filter "ProcessId = $evaluationProcessId"
    if (-not $evaluationProcess) { continue }
    if ($evaluationTarget.Worker) {
        $evaluationTarget.Executable = (Get-Content -LiteralPath $evaluationTarget.Executable -Raw).Trim()
    }
    if ($evaluationProcess.ExecutablePath -ne $evaluationTarget.Executable -or
        -not $evaluationProcess.CommandLine.Contains($evaluationTarget.Argument)) {
        throw 'Process identity differs; refusing to stop it.'
    }
    if ($evaluationTarget.Worker) {
        $evaluationChildren = Get-CimInstance Win32_Process -Filter "ParentProcessId = $evaluationProcessId"
        foreach ($evaluationChild in $evaluationChildren) {
            if ($evaluationChild.ExecutablePath -ne 'C:\xampp\php\php.exe' -or
                -not $evaluationChild.CommandLine.Contains('evaluation/public/scripts/dispatch_browser_push.php')) {
                throw 'Worker child identity differs; refusing to stop it.'
            }
            Stop-Process -Id $evaluationChild.ProcessId
        }
    }
    Stop-Process -Id $evaluationProcessId
}
& 'C:\xampp\php\php.exe' (Join-Path $PSScriptRoot 'set_evaluation_url.php') '--clear-url'
if ($LASTEXITCODE -ne 0) { throw 'Evaluation stopped, but clearing its URL failed.' }
Write-Output 'Evaluation tunnel/server stopped; usual XAMPP service unchanged.'
