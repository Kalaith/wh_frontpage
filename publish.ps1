# Robust delegation script
$rootScript = "H:\WebHatchery\publish.ps1"
if (-not (Test-Path $rootScript)) {
    Write-Error "Root publish script not found at $rootScript"
    exit 1
}

$allArgs = @("-ProjectDir", $PSScriptRoot)
$allArgs += $args

# Use & to call the script with the combined arguments
& $rootScript @allArgs
