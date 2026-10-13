$ErrorActionPreference = 'Stop'
Push-Location $PSScriptRoot
try {
    docker compose exec -T frontend npm run build:admin -- --outDir /app/dist-admin
    if ($LASTEXITCODE -ne 0) { throw 'Management asset build failed.' }
    New-Item -ItemType Directory -Force backend/public/admin-ui | Out-Null
    Copy-Item -Path frontend/dist-admin/* -Destination backend/public/admin-ui -Recurse -Force
    Copy-Item -LiteralPath frontend/dist-admin/.vite -Destination backend/public/admin-ui -Recurse -Force
} finally {
    Pop-Location
}
