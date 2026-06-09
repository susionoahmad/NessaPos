
try {
    $response = Invoke-RestMethod -Uri "http://localhost:8000/api/packages"
    $response | ConvertTo-Json
} catch {
    Write-Error "Could not connect to local server. Make sure 'php artisan serve' is running."
}
