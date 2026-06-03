param(
  [string]$PrivKey,

  [string]$PrivKeyFile,

  [Parameter(Mandatory=$true)]
  [string]$IssuedAt,

  [string]$Expiry = "",

  [Parameter(Mandatory=$true)]
  [string]$InputCsv,

  [Parameter(Mandatory=$true)]
  [string]$OutputDir
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($PrivKey) -and -not [string]::IsNullOrWhiteSpace($PrivKeyFile)) {
  if (!(Test-Path $PrivKeyFile)) {
    Write-Error "Private key file not found: $PrivKeyFile"
  }
  $PrivKey = (Get-Content -Raw $PrivKeyFile).Trim()
}

if ([string]::IsNullOrWhiteSpace($PrivKey)) {
  Write-Error "PrivKey is required. Use -PrivKey or -PrivKeyFile."
}

if (!(Test-Path $InputCsv)) {
  Write-Error "Input CSV not found: $InputCsv"
}

if (!(Test-Path $OutputDir)) {
  New-Item -ItemType Directory -Path $OutputDir | Out-Null
}

$rows = Import-Csv $InputCsv
foreach ($r in $rows) {
  if (-not $r.issued_to -or -not $r.device_id) {
    Write-Host "Skip row without issued_to/device_id"
    continue
  }

  $name = $r.issued_to -replace '[^a-zA-Z0-9_-]', '_'
  $outFile = Join-Path $OutputDir "$name.json"

  $cmd = @(
    "run", ".\cmd\licensegen\main.go",
    "-issued-to", $r.issued_to,
    "-device-id", $r.device_id,
    "-issued-at", $IssuedAt
  )
  if (-not [string]::IsNullOrWhiteSpace($Expiry)) {
    $cmd += @("-expiry", $Expiry)
  }
  $cmd += @("-priv-key", $PrivKey)

  $json = & go @cmd
  $json | Out-File -FilePath $outFile -Encoding utf8
  Write-Host "Generated: $outFile"
}
