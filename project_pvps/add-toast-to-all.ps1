# Quick Add Toast to All HTML Files
# PowerShell script to add toast.css and toast.js to all HTML files

$files = @(
    "index.html",
    "user-dashboard.html",
    "merchant-dashboard.html",
    "about.html",
    "security.html",
    "payment-status.html"
)

foreach ($file in $files) {
    $path = "d:\Xampp\htdocs\projects\project_pvps\$file"
    
    if (Test-Path $path) {
        $content = Get-Content $path -Raw
        
        # Add toast.css after navigation.css
        if ($content -notmatch 'toast.css') {
            $content = $content -replace '(<link rel="stylesheet" href="css/navigation.css">)', '$1`r`n    <link rel="stylesheet" href="css/toast.css">'
        }
        
        # Add toast.js after theme.js
        if ($content -notmatch 'toast.js') {
            $content = $content -replace '(<script src="js/theme.js"></script>)', '$1`r`n    <script src="js/toast.js"></script>'
        }
        
        Set-Content $path -Value $content -NoNewline
        Write-Host "✅ Updated: $file"
    } else {
        Write-Host "⚠️  Not found: $file"
    }
}

Write-Host "`n✅ Toast notification system added to all HTML files!"
Write-Host "📝 Hard refresh (Ctrl + Shift + R) to see changes"
