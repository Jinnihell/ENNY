# check_auth.ps1
# Run from the project folder (PowerShell)
# Usage: powershell -ExecutionPolicy Bypass -File .\check_auth.ps1

$urls = @{
    Root = 'http://localhost/start/'
    Admin = 'http://localhost/start/admin_selection.php'
    Public = 'http://localhost/start/public_monitor.php'
}

foreach ($key in $urls.Keys) {
    try {
        $res = Invoke-WebRequest -Uri $urls[$key] -Method Head -MaximumRedirection 0 -ErrorAction Stop
        $code = $res.StatusCode
        $loc = $res.Headers.Location
    } catch [System.Net.WebException] {
        $resp = $_.Exception.Response
        if ($resp -ne $null) {
            $code = $resp.StatusCode.value__
            $loc = $resp.Headers['Location']
        } else {
            $code = 'ERROR'
            $loc = $_.Exception.Message
        }
    } catch {
        $code = 'ERROR'
        $loc = $_.Exception.Message
    }

    Write-Output "=== $key ==="
    Write-Output "StatusCode: $code"
    if ($loc) { Write-Output "Location: $loc" } else { Write-Output "Location:" }
    Write-Output ""
}
