# Start built-in PHP web server and open browser
$port = 8000
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
Push-Location $root
$env:USE_DB = '0'
if (php -m | Select-String -Quiet -Pattern '^pdo_mysql$') {
	$env:USE_DB = '1'
	if (-not $env:DB_DSN) { $env:DB_DSN = 'mysql:host=127.0.0.1;dbname=tes2;charset=utf8mb4' }
	if (-not $env:DB_USER) { $env:DB_USER = 'root' }
	if (-not $env:DB_PASS) { $env:DB_PASS = '' }
} else {
	Write-Warning 'pdo_mysql is not enabled. Using local JSON storage until the PHP MySQL extension is enabled.'
}
Write-Host "Starting PHP built-in server at http://localhost:$port (CTRL+C to stop)"
Start-Process -NoNewWindow -FilePath pwsh -ArgumentList "-NoExit","-Command","php -S localhost:$port -t . local-router.php"
Start-Sleep -Seconds 1
Start-Process "http://localhost:$port/index.php"
Pop-Location
