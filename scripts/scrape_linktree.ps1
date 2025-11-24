$ErrorActionPreference = 'Stop'

$url = 'https://linktr.ee/cwl1informationportal'
$outDir = Join-Path (Get-Location) 'data/cwl1informationportal'
New-Item -ItemType Directory -Force -Path $outDir | Out-Null

$rawPath = Join-Path $outDir 'raw.html'
Invoke-WebRequest -Uri $url -OutFile $rawPath

$resp = Invoke-WebRequest -Uri $url
$html = $resp.Content

$anchorPattern = '<a[^>]+href=["''](?<href>[^"'']+)["''][^>]*>(?<inner>.*?)</a>'
$regexOptions = [System.Text.RegularExpressions.RegexOptions]::IgnoreCase -bor `
                [System.Text.RegularExpressions.RegexOptions]::Singleline

$items = @()
[regex]::Matches($html, $anchorPattern, $regexOptions) | ForEach-Object {
    $href = $_.Groups['href'].Value.Trim()
    $inner = $_.Groups['inner'].Value
    $text = [regex]::Replace($inner, '<.*?>', '').Trim()
    if ($href -and $text) {
        $items += [pscustomobject]@{
            text = $text
            url  = $href
        }
    }
}

if (-not $items) {
    Write-Warning "No anchors found in the downloaded HTML. Check the page structure or parsing pattern."
}

$jsonPath = Join-Path $outDir 'links.json'
$items | ConvertTo-Json -Depth 5 | Set-Content -Encoding UTF8 $jsonPath

$mdPath = Join-Path $outDir 'links.md'
$header = "# CWL1 Information Portal Links`n`n"
$list = ($items | ForEach-Object { '- [' + ($_.text -replace '\[|\]','') + '](' + $_.url + ')' }) -join "`n"
$footer = "`n`nSource: https://linktr.ee/cwl1informationportal`n"
($header + $list + $footer) | Set-Content -Encoding UTF8 $mdPath

Write-Output "Saved:"
Write-Output $rawPath
Write-Output $jsonPath
Write-Output $mdPath


