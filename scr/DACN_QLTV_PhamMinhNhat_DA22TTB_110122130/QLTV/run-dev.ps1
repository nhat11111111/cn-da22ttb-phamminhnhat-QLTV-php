param(
    [int]$Port = 8000
)

# Move to repo root (script location)
Push-Location $PSScriptRoot

# Root của thư mục QLTV (nơi frontend/ và backend/ cùng nằm)
$rootDir = $PSScriptRoot
if (-not (Test-Path (Join-Path $rootDir 'frontend'))) {
    Write-Error "Cannot find 'frontend' folder at: $rootDir"
    Pop-Location
    exit 1
}

# If port is in use, attempt to stop the owning process (best-effort)
try {
    $listener = Get-NetTCPConnection -LocalPort $Port -ErrorAction SilentlyContinue | Where-Object { $_.State -eq 'Listen' }
    if ($listener) {
        $pid = $listener.OwningProcess
        Write-Host "Port $Port is in use by PID $pid. Attempting to stop process..."
        try {
            Stop-Process -Id $pid -Force -ErrorAction Stop
            Start-Sleep -Milliseconds 500
            Write-Host "Stopped process $pid"
        } catch {
            Write-Warning "Unable to stop process $pid. You can choose another port, e.g. -Port 8001"
        }
    }
} catch {
    Write-Verbose "Could not check existing listeners: $_"
}

# Start PHP built-in server as a background process
# Serve từ root để cả /frontend và /backend/api đều accessible
$router = Join-Path $rootDir "index.php"
$quotedRoot = '"' + $rootDir + '"'
$quotedRouter = '"' + $router + '"'
$argArray = @('-S', "127.0.0.1:$Port", '-t', $quotedRoot, $quotedRouter)

# Create log files for stdout/stderr to diagnose failures
$logDir = Join-Path $rootDir 'tmp_logs'
if (-not (Test-Path $logDir)) { New-Item -ItemType Directory -Path $logDir | Out-Null }
$outLog = Join-Path $logDir 'php_stdout.log'
$errLog = Join-Path $logDir 'php_stderr.log'

Write-Host "Starting PHP dev server on a new PowerShell window (will show logs)."

# Ensure no leftover php processes
Get-Process -Name php -ErrorAction SilentlyContinue | ForEach-Object {
    try { Stop-Process -Id $_.Id -Force -ErrorAction Stop; Write-Host "Stopped php PID $($_.Id)" } catch { }
}


# Build command to run PHP built-in server in a new PowerShell window so logs remain visible
$phpCmd = 'php -S 127.0.0.1:' + $Port + ' -t "' + $rootDir + '" "' + $router + '"'
$argList = @('-NoExit','-Command',$phpCmd)

Start-Process -FilePath powershell -ArgumentList $argList -WorkingDirectory $rootDir

# Give server a moment to start and write logs
Start-Sleep -Milliseconds 800

# Open default browser to the login view
$loginUrl = "http://127.0.0.1:$Port/views/Dangnhap.html"
Write-Host "Opening login page: $loginUrl"
Start-Process $loginUrl

Write-Host "Server started (check the new PowerShell window for PHP logs)."

Pop-Location
