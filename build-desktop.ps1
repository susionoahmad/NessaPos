$ErrorActionPreference = 'Stop'

Write-Host 'Building frontend-pos for Wails...'
Set-Location -Path '.\frontend-pos'
npm install
npm run build:wails

Write-Host 'Building Wails desktop app...'
Set-Location -Path '..\wails-app'
wails build

Write-Host 'Build complete. Check build/bin for the desktop executable.'
