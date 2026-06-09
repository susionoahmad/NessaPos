
$response = Invoke-RestMethod -Uri "https://nessapos.kalkulatorin.com/api/packages"
$response | ConvertTo-Json
