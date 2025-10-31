Write-Host "🚀 STARTING CONTINUOUS CRON RUNNER" -ForegroundColor Green
Write-Host "===================================" -ForegroundColor Green
Write-Host "This will call the cron endpoint every 10 seconds" -ForegroundColor Yellow
Write-Host "Press Ctrl+C to stop" -ForegroundColor Yellow
Write-Host ""

$logFile = "storage\logs\continuous_cron.log"
$url = "http://127.0.0.1:8000/cron"

Write-Host "📝 Logging to: $logFile" -ForegroundColor Cyan
Write-Host "🌐 Calling: $url" -ForegroundColor Cyan
Write-Host "⏱️  Interval: 10 seconds" -ForegroundColor Cyan
Write-Host ""

$count = 0

while ($true) {
    $count++
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    
    Write-Host "[$timestamp] Call #$count... " -NoNewline
    
    try {
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 30
        if ($response.StatusCode -eq 200) {
            Write-Host "✅ SUCCESS" -ForegroundColor Green
            Add-Content -Path $logFile -Value "[$timestamp] SUCCESS - Call #$count"
        } else {
            Write-Host "❌ FAILED (Status: $($response.StatusCode))" -ForegroundColor Red
            Add-Content -Path $logFile -Value "[$timestamp] FAILED - Call #$count (Status: $($response.StatusCode))"
        }
    } catch {
        Write-Host "❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
        Add-Content -Path $logFile -Value "[$timestamp] ERROR - Call #$count: $($_.Exception.Message)"
    }
    
    Start-Sleep -Seconds 10
}
