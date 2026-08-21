<#
.SYNOPSIS
    Installe la passerelle WhatsApp locale sur ce poste : copie les fichiers dans le
    profil utilisateur, installe les dépendances npm, écrit la config, et programme
    son démarrage automatique à l'ouverture de session Windows.

.DESCRIPTION
    Ne fait PAS ce que fait install-windows.ps1 du pont d'impression (téléchargement
    d'un runtime portable) : Node.js doit déjà être installé (https://nodejs.org, LTS).
    C'est un outil très répandu, à l'inverse d'un environnement PHP dédié imprimante —
    l'installer une fois manuellement est un compromis raisonnable ici.

.EXAMPLE
    .\install-windows.ps1 -AllowedOrigins "http://localhost/DojoManager_laravel"

.EXAMPLE
    # -AllowLan : la passerelle écoute aussi sur le réseau local (pas juste ce poste),
    # pour que DojoManager ouvert depuis un téléphone du même Wi-Fi puisse aussi l'appeler.
    .\install-windows.ps1 -AllowedOrigins "http://localhost/DojoManager_laravel" -AllowLan -BridgeToken "un-secret-partage"

.NOTES
    À exécuter en PowerShell sur le poste qui doit envoyer les reçus WhatsApp.
    Peut être relancé sans risque (réinstalle/écrase proprement, sans toucher à la
    session WhatsApp déjà liée dans auth_session/).
    Si l'exécution de scripts est bloquée :
      powershell -ExecutionPolicy Bypass -File install-windows.ps1 -AllowedOrigins "..."
#>

param(
    [Parameter(Mandatory = $true)]
    [string]$AllowedOrigins,
    [switch]$AllowLan,
    [string]$BridgeToken = '',
    [int]$Port = 9300,
    [string]$InstallDir = (Join-Path $env:LOCALAPPDATA 'DojoWhatsAppBridge')
)

$ErrorActionPreference = 'Stop'

function Write-Etape($texte) {
    Write-Host ""
    Write-Host "==> $texte" -ForegroundColor Cyan
}

Write-Etape "Vérification de Node.js"
try {
    $nodeVersion = node -v
    Write-Host "Node.js détecté : $nodeVersion"
} catch {
    Write-Host "Node.js n'est pas installé ou pas dans le PATH." -ForegroundColor Red
    Write-Host "Installez la version LTS depuis https://nodejs.org puis relancez ce script."
    exit 1
}

Write-Etape "Copie des fichiers vers $InstallDir"
New-Item -ItemType Directory -Force -Path $InstallDir | Out-Null
$source = $PSScriptRoot
Copy-Item -Path (Join-Path $source 'server.js') -Destination $InstallDir -Force
Copy-Item -Path (Join-Path $source 'package.json') -Destination $InstallDir -Force

Write-Etape "Écriture de la configuration (.env)"
$envContent = @"
PORT=$Port
ALLOW_LAN=$(if ($AllowLan) { '1' } else { '0' })
BRIDGE_TOKEN=$BridgeToken
ALLOWED_ORIGINS=$AllowedOrigins
"@
Set-Content -Path (Join-Path $InstallDir '.env') -Value $envContent -Encoding UTF8

Write-Etape "Installation des dépendances npm (peut prendre une minute)"
Push-Location $InstallDir
npm install --omit=dev
Pop-Location

Write-Etape "Programmation du démarrage automatique à l'ouverture de session"
$taskName = 'DojoManager - Passerelle WhatsApp'
$nodePath = (Get-Command node).Source
$action = New-ScheduledTaskAction -Execute $nodePath -Argument '"server.js"' -WorkingDirectory $InstallDir
$trigger = New-ScheduledTaskTrigger -AtLogOn
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Force | Out-Null

Write-Etape "Démarrage immédiat"
Start-ScheduledTask -TaskName $taskName
Start-Sleep -Seconds 2

$ip = (Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.InterfaceAlias -notmatch 'Loopback' -and $_.IPAddress -notlike '169.254.*' } | Select-Object -First 1).IPAddress

Write-Host ""
Write-Host "Installation terminée." -ForegroundColor Green
Write-Host "Page de statut / QR code : http://127.0.0.1:$Port/"
if ($AllowLan -and $ip) {
    Write-Host "Adresse à saisir dans le bouton (engrenage) de DojoManager depuis un autre appareil du Wi-Fi : $ip`:$Port"
}
Write-Host "Ouvrez la page de statut et scannez le QR code avec WhatsApp (Appareils liés) pour terminer."
