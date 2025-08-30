# Enhanced Publishing Script with FTP Support
# Publishes frontend and PHP backend to file system and/or FTP server

param(
    [Alias('f')]
    [switch]$Frontend,
    [Alias('b')]
    [switch]$Backend,
    [Alias('a')]
    [switch]$All,
    [Alias('c')]
    [switch]$Clean,
    [Alias('v')]
    [switch]$Verbose,
    [Alias('p')]
    [switch]$Production,
    [switch]$FTP,
    [Alias('sv')]
    [switch]$SkipVendor,
    [Alias('fs')]
    [switch]$FileSystemOnly,
    [string]$FTPProfile = "default"
)

# Auto-detect project name from current directory
$PROJECT_NAME = Split-Path -Leaf $PSScriptRoot

# Load .env file for FTP credentials and file system paths
$envFile = Join-Path $PSScriptRoot ".env"
if (Test-Path $envFile) {
    Get-Content $envFile | ForEach-Object {
        if ($_ -match "^(\w+)=(.*)$") {
            $name = $matches[1]
            $value = $matches[2].Trim('"')  # Remove quotes if present
            Set-Variable -Name $name -Value $value -Scope Script
        }
    }
} else {
    Write-Error ".env file not found! Please create a .env file with required configuration."
    exit 1
}

# FTP Configuration from .env (using script variables, not $env)
$FTPConfig = @{
    Server = $FTP_SERVER
    Username = $FTP_USERNAME  
    Password = $FTP_PASSWORD
    Port = if ($FTP_PORT) { $FTP_PORT -as [int] } else { 21 }
    RemoteRoot = if ($FTP_REMOTE_ROOT) { $FTP_REMOTE_ROOT } else { "/" }
    UseSSL = ($FTP_USE_SSL -eq "true")
    PassiveMode = ($FTP_PASSIVE_MODE -ne "false")  # Default true
}

# Validate FTP configuration if FTP deployment is requested
if ($FTP -and -not $FileSystemOnly) {
    $missingFTPConfig = @()
    if (-not $FTPConfig.Server) { $missingFTPConfig += "FTP_SERVER" }
    if (-not $FTPConfig.Username) { $missingFTPConfig += "FTP_USERNAME" }
    if (-not $FTPConfig.Password) { $missingFTPConfig += "FTP_PASSWORD" }
    
    if ($missingFTPConfig.Count -gt 0) {
        Write-Error "Missing FTP configuration in .env file: $($missingFTPConfig -join ', ')"
        Write-Host @"
Add the following to your .env file:
FTP_SERVER=your.ftp.server.com
FTP_USERNAME=your_username  
FTP_PASSWORD=your_password
FTP_PORT=21
FTP_REMOTE_ROOT=/public_html
FTP_USE_SSL=false
FTP_PASSIVE_MODE=true
"@ -ForegroundColor Yellow
        exit 1
    }
}

# Set destination based on Production flag
$DEST_ROOT = if ($Production) { $PRODUCTION_ROOT } else { $PREVIEW_ROOT }
$DEST_DIR = $DEST_ROOT
$FRONTEND_SRC = "$PSScriptRoot\frontend"
$BACKEND_SRC = "$PSScriptRoot\backend"
$FRONTEND_DEST = $DEST_DIR
$BACKEND_DEST = "$DEST_DIR\backend"

# Color output functions
function Write-Success($message) {
    Write-Host $message -ForegroundColor Green
}

function Write-Info($message) {
    Write-Host $message -ForegroundColor Cyan
}

function Write-Warning($message) {
    Write-Host $message -ForegroundColor Yellow
}

function Write-Error($message) {
    Write-Host $message -ForegroundColor Red
}

function Write-Progress($message) {
    Write-Host $message -ForegroundColor Magenta
}

# FTP Helper Functions
function Test-FTPConnection {
    param($config)
    
    Write-Progress "Testing FTP connection to $($config.Server)..."
    
    try {
        $ftp = [System.Net.FtpWebRequest]::Create("ftp://$($config.Server):$($config.Port)/")
        $ftp.Credentials = New-Object System.Net.NetworkCredential($config.Username, $config.Password)
        $ftp.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
        $ftp.UseBinary = $true
        $ftp.UsePassive = $config.PassiveMode
        $ftp.EnableSsl = $config.UseSSL
        $ftp.Timeout = 10000  # 10 seconds
        
        $response = $ftp.GetResponse()
        $response.Close()
        
        Write-Success " FTP connection successful"
        return $true
    }
    catch {
        Write-Error " FTP connection failed: $($_.Exception.Message)"
        return $false
    }
}

function Upload-FileToFTP {
    param(
        [string]$LocalPath,
        [string]$RemotePath,
        [hashtable]$Config,
        [switch]$CreateDirectories
    )
    
    try {
        # Create remote directories if needed
        if ($CreateDirectories) {
            $remoteDir = Split-Path $RemotePath -Parent
            if ($remoteDir -and $remoteDir -ne "/" -and $remoteDir -ne ".") {
                Create-FTPDirectory -RemotePath $remoteDir -Config $Config
            }
        }
        
        $uri = "ftp://$($Config.Server):$($Config.Port)$RemotePath"
        $ftp = [System.Net.FtpWebRequest]::Create($uri)
        $ftp.Credentials = New-Object System.Net.NetworkCredential($Config.Username, $Config.Password)
        $ftp.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $ftp.UseBinary = $true
        $ftp.UsePassive = $Config.PassiveMode
        $ftp.EnableSsl = $Config.UseSSL
        
        $fileContent = [System.IO.File]::ReadAllBytes($LocalPath)
        $ftp.ContentLength = $fileContent.Length
        
        $requestStream = $ftp.GetRequestStream()
        $requestStream.Write($fileContent, 0, $fileContent.Length)
        $requestStream.Close()
        
        $response = $ftp.GetResponse()
        $response.Close()
        
        if ($Verbose) {
            Write-Host "   $RemotePath" -ForegroundColor Gray
        }
        return $true
    }
    catch {
        Write-Warning " Failed to upload $RemotePath`: $($_.Exception.Message)"
        return $false
    }
}

function Create-FTPDirectory {
    param(
        [string]$RemotePath,
        [hashtable]$Config
    )
    
    $pathParts = $RemotePath.TrimStart('/').Split('/') | Where-Object { $_ -ne '' }
    $currentPath = $Config.RemoteRoot.TrimEnd('/')
    
    foreach ($part in $pathParts) {
        $currentPath += "/$part"
        
        try {
            $uri = "ftp://$($Config.Server):$($Config.Port)$currentPath"
            $ftp = [System.Net.FtpWebRequest]::Create($uri)
            $ftp.Credentials = New-Object System.Net.NetworkCredential($Config.Username, $Config.Password)
            $ftp.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
            $ftp.UsePassive = $Config.PassiveMode
            $ftp.EnableSsl = $Config.UseSSL
            
            $response = $ftp.GetResponse()
            $response.Close()
            
            if ($Verbose) {
                Write-Host "   Created directory: $currentPath" -ForegroundColor DarkGray
            }
        }
        catch {
            # Directory might already exist, which is OK
            if (-not $_.Exception.Message.Contains("550")) {  # 550 = directory exists
                Write-Verbose "Note: $currentPath - $($_.Exception.Message)"
            }
        }
    }
}

function Upload-DirectoryToFTP {
    param(
        [string]$LocalPath,
        [string]$RemotePath,
        [hashtable]$Config,
        [array]$ExcludePatterns = @(),
        [switch]$SkipVendorUploads
    )
    
    Write-Progress "Uploading $LocalPath to FTP: $RemotePath"
    
    $items = Get-ChildItem -Path $LocalPath -Recurse -File
    $uploadCount = 0
    $skipCount = 0
    
    foreach ($item in $items) {
        $relativePath = $item.FullName.Substring($LocalPath.Length + 1).Replace('\', '/')
        $remoteFilePath = "$RemotePath/$relativePath".Replace('//', '/')
        
        # Check exclusion patterns
        $shouldExclude = $false
        foreach ($pattern in $ExcludePatterns) {
            if ($relativePath -like $pattern) {
                $shouldExclude = $true
                break
            }
        }
        
        # Skip vendor uploads if requested and we're in vendor directory
        if ($SkipVendorUploads -and $relativePath.StartsWith("vendor/")) {
            $shouldExclude = $true
            if ($Verbose) {
                Write-Host "    Skipped (vendor): $relativePath" -ForegroundColor Yellow
            }
        }
        
        if (-not $shouldExclude) {
            if (Upload-FileToFTP -LocalPath $item.FullName -RemotePath $remoteFilePath -Config $Config -CreateDirectories) {
                $uploadCount++
            }
        } else {
            $skipCount++
        }
    }
    
    Write-Success " Uploaded $uploadCount files to FTP (skipped $skipCount)"
}

# Check if vendor directory has changed (for smart vendor skipping)
function Test-VendorChanged {
    param([string]$BackendPath)
    
    $composerLock = $BackendPath + "\composer.lock"
    $vendorDir = $BackendPath + "\vendor"
    $vendorMarker = $vendorDir + "\.last_upload_hash"

    if (-not (Test-Path $composerLock)) {
        return $true  # No lock file, assume changed
    }
    
    $currentHash = Get-FileHash $composerLock -Algorithm MD5 | Select-Object -ExpandProperty Hash
    
    if (Test-Path $vendorMarker) {
        $lastHash = Get-Content $vendorMarker -Raw
        if ($currentHash -eq $lastHash.Trim()) {
            Write-Info "Vendor directory unchanged (hash: $currentHash), skipping upload"
            return $false
        }
    }
    
    # Update the marker file
    $currentHash | Out-File -FilePath $vendorMarker -NoNewline
    Write-Info "Vendor directory changed, will upload"
    return $true
}

# Ensure destination directory exists
function Ensure-Directory($path) {
    if (!(Test-Path $path)) {
        New-Item -ItemType Directory -Path $path -Force | Out-Null
        Write-Info "Created directory: $path"
    }
}

# Clean destination directory
function Clean-Directory($path) {
    if (Test-Path $path) {
        Write-Warning "Cleaning directory: $path"
        Remove-Item -Path "$path\*" -Recurse -Force
        Write-Success "Directory cleaned"
    }
}

# Copy files with exclusions (existing function preserved)
function Copy-WithExclusions($source, $destination, $excludePatterns) {
    Write-Progress "Copying from $source to $destination"
    
    Ensure-Directory $destination
    
    $items = Get-ChildItem -Path $source -Recurse
    
    foreach ($item in $items) {
        $relativePath = $item.FullName.Substring($source.Length + 1)
        $destPath = Join-Path $destination $relativePath
        
        $shouldExclude = $false
        foreach ($pattern in $excludePatterns) {
            if ($relativePath -like $pattern) {
                $shouldExclude = $true
                break
            }
        }
        
        if (-not $shouldExclude) {
            if ($item.PSIsContainer) {
                Ensure-Directory $destPath
            } else {
                $destDir = Split-Path $destPath -Parent
                Ensure-Directory $destDir
                Copy-Item $item.FullName $destPath -Force
                if ($Verbose) {
                    Write-Host "  Copied: $relativePath" -ForegroundColor Gray
                }
            }
        } else {
            if ($Verbose) {
                Write-Host "  Excluded: $relativePath" -ForegroundColor DarkGray
            }
        }
    }
}

# Build frontend (existing function preserved)
function Build-Frontend {
    Write-Progress "Building frontend..."
    Set-Location $FRONTEND_SRC
    
    if (!(Test-Path "node_modules")) {
        Write-Info "Installing frontend dependencies..."
        npm install
        if ($LASTEXITCODE -ne 0) {
            Write-Error "Failed to install frontend dependencies"
            return $false
        }
    }
    
    $environment = if ($Production) { "production" } else { "preview" }
    Write-Info "Setting up $environment environment for frontend build..."
    $envSrc = ".env.$environment"
    $envTemp = ".env.local"
    
    if (Test-Path $envSrc) {
        Copy-Item $envSrc $envTemp -Force
        Write-Info "Using $envSrc for frontend build"
    } else {
        Write-Warning "$envSrc not found - using default environment"
    }
    
    Write-Info "Building frontend for production..."
    $env:NODE_ENV = "production"
    $env:VITE_BASE_PATH = "/"
    
    if ($Production) {
        npx vite build --mode production
    } else {
        npx vite build --mode preview
    }
    
    $buildResult = $LASTEXITCODE
    
    if (Test-Path $envTemp) {
        Remove-Item $envTemp -Force
    }
    
    if ($buildResult -ne 0) {
        Write-Error "Failed to build frontend"
        return $false
    }
    
    Write-Success "Frontend build completed"
    return $true
}

# Enhanced frontend publishing with FTP support
function Publish-Frontend {
    Write-Progress "Publishing frontend..."
    
    if (!(Build-Frontend)) {
        return $false
    }
    
    $success = $true
    $distPath = "$FRONTEND_SRC\dist"
    
    if (-not (Test-Path $distPath)) {
        Write-Error "Frontend build output not found at $distPath"
        return $false
    }
    
    # File system deployment (if not FTP-only)
    if (-not $FTP -or $FileSystemOnly) {
        if ($Clean) {
            Write-Warning "Cleaning specific frontend files from root directory..."
            $frontendFiles = @("index.html", "assets")
            foreach ($item in $frontendFiles) {
                $itemPath = Join-Path $FRONTEND_DEST $item
                if (Test-Path $itemPath) {
                    Write-Info "Removing existing: $item"
                    Remove-Item -Path $itemPath -Recurse -Force -ErrorAction SilentlyContinue
                }
            }
        }
        
        Write-Info "Copying built frontend files to file system..."
        Get-ChildItem -Path $distPath | ForEach-Object {
            $sourceItem = $_.FullName
            $itemName = $_.Name
            $destPath = Join-Path $FRONTEND_DEST $itemName
            
            if ($itemName -ne "backend") {
                if ((Test-Path $destPath) -and (Get-Item $destPath).PSIsContainer) {
                    Remove-Item $destPath -Recurse -Force -ErrorAction SilentlyContinue
                }
                
                if ($_.PSIsContainer) {
                    Copy-Item $sourceItem $destPath -Recurse -Force
                } else {
                    Copy-Item $sourceItem $destPath -Force
                }
                
                if ($Verbose) {
                    Write-Host "  Copied: $itemName" -ForegroundColor Gray
                }
            }
        }
        Write-Success "Frontend published to file system: $FRONTEND_DEST"
    }
    
    # FTP deployment
    if ($FTP) {
        if (Test-FTPConnection -config $FTPConfig) {
            $excludePatterns = @("*.map", "*.tmp")
            Upload-DirectoryToFTP -LocalPath $distPath -RemotePath $FTPConfig.RemoteRoot -Config $FTPConfig -ExcludePatterns $excludePatterns
            Write-Success "Frontend published to FTP server"
        } else {
            Write-Error "FTP connection failed - skipping FTP upload"
            $success = $false
        }
    }
    
    return $success
}

# Install PHP backend dependencies (existing function preserved)
function Install-BackendDependencies {
    Write-Progress "Installing PHP backend dependencies..."
    Set-Location $BACKEND_SRC
    
    try {
        composer --version | Out-Null
    } catch {
        Write-Error "Composer not found. Please install Composer first."
        return $false
    }
    
    composer install --no-dev --optimize-autoloader
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Failed to install PHP dependencies"
        return $false
    }
    
    Write-Success "PHP dependencies installed"
    return $true
}

# Enhanced backend publishing with FTP support
function Publish-Backend {
    Write-Progress "Publishing PHP backend..."
    
    if (!(Install-BackendDependencies)) {
        return $false
    }
    
    $success = $true
    
    # File system deployment (if not FTP-only)
    if (-not $FTP -or $FileSystemOnly) {
        if ($Clean) {
            Clean-Directory $BACKEND_DEST
        }
        
        $excludePatterns = @(
            "node_modules\*", ".git\*", ".env", ".env.local", ".env.example",
            "tests\*", "*.log", "*.tmp", "storage\logs\*", "storage\cache\*",
            "var\cache\*", "vendor\*\tests\*", "vendor\*\test\*", "vendor\*\.git\*",
            "*.md", "composer.lock", "phpunit.xml", "*.ps1", "debug*.php",
            "test*.php", "install.php", "*.md"
        )
        
        Copy-WithExclusions $BACKEND_SRC $BACKEND_DEST $excludePatterns
        
        # Handle environment configuration
        $environment = if ($Production) { "production" } else { "preview" }
        Write-Info "Setting up $environment environment configuration..."
        $envSrc = "$BACKEND_SRC\.env.$environment"
        $envDest = "$BACKEND_DEST\.env"
        
        if (Test-Path $envSrc) {
            Copy-Item $envSrc $envDest -Force
            Write-Success "Copied $envSrc to .env for $environment use"
        } else {
            Write-Warning "$envSrc not found - using base .env"
            $baseEnvSrc = "$BACKEND_SRC\.env"
            if (Test-Path $baseEnvSrc) {
                Copy-Item $baseEnvSrc $envDest -Force
            }
        }
        
        # Create necessary directories
        Ensure-Directory "$BACKEND_DEST\storage\logs"
        Ensure-Directory "$BACKEND_DEST\var\cache"
        
        Write-Success "PHP backend published to file system: $BACKEND_DEST"
    }
    
    # FTP deployment
    if ($FTP) {
        if (Test-FTPConnection -config $FTPConfig) {
            $excludePatterns = @(
                "node_modules/*", ".git/*", ".env*", "tests/*", "*.log", "*.tmp",
                "storage/logs/*", "storage/cache/*", "var/cache/*", "vendor/*/tests/*",
                "vendor/*/test/*", "vendor/*/.git/*", "*.md", "composer.lock",
                "phpunit.xml", "*.ps1", "debug*.php", "test*.php", "install.php"
            )
            
            $backendRemotePath = "$($FTPConfig.RemoteRoot)/backend".Replace('//', '/')
            
            # Check if we should skip vendor uploads
            $shouldUploadVendor = -not $SkipVendor
            
            Upload-DirectoryToFTP -LocalPath $BACKEND_SRC -RemotePath $backendRemotePath -Config $FTPConfig -ExcludePatterns $excludePatterns -SkipVendorUploads:(-not $shouldUploadVendor)
            
            # Upload environment file
            $environment = if ($Production) { "production" } else { "preview" }
            $envSrc = "$BACKEND_SRC\.env.$environment"
            if (Test-Path $envSrc) {
                $envRemotePath = "$backendRemotePath/.env"
                Upload-FileToFTP -LocalPath $envSrc -RemotePath $envRemotePath -Config $FTPConfig -CreateDirectories
                Write-Success "Uploaded environment configuration"
            }
            
            Write-Success "PHP backend published to FTP server"
        } else {
            Write-Error "FTP connection failed - skipping FTP upload"
            $success = $false
        }
    }
    
    return $success
}

# Enhanced main execution function
function Main {
    Write-Info $PROJECT_NAME + " Enhanced Publishing Script"
    Write-Info "========================================="
    Write-Info "Deployment mode: $(if ($FTP) { 'FTP' } else { 'File System' }) $(if ($Production) { '(Production)' } else { '(Preview)' })"
    
    if (-not $FTP -or $FileSystemOnly) {
        Ensure-Directory $DEST_DIR
    }
    
    $success = $true
    
    if ($All -or (!$Frontend -and !$Backend)) {
        Write-Info "Publishing both frontend and backend..."
        $Frontend = $true
        $Backend = $true
    }
    
    $originalLocation = Get-Location
    
    try {
        if ($Frontend) {
            if (!(Publish-Frontend)) {
                $success = $false
            }
        }
        
        if ($Backend) {
            if (!(Publish-Backend)) {
                $success = $false
            }
        }
        
        if ($success) {
            # Copy root .htaccess for file system deployment
            if (-not $FTP -or $FileSystemOnly) {
                $rootHtaccessSrc = "$PSScriptRoot\.htaccess"
                $rootHtaccessDest = "$DEST_DIR\.htaccess"
                if (Test-Path $rootHtaccessSrc) {
                    Copy-Item $rootHtaccessSrc $rootHtaccessDest -Force
                    Write-Info "Copied root .htaccess file"
                }
            }
            
            # Upload root .htaccess for FTP deployment
            if ($FTP) {
                $rootHtaccessSrc = "$PSScriptRoot\.htaccess"
                if (Test-Path $rootHtaccessSrc) {
                    $htaccessRemotePath = "$($FTPConfig.RemoteRoot)/.htaccess"
                    Upload-FileToFTP -LocalPath $rootHtaccessSrc -RemotePath $htaccessRemotePath -Config $FTPConfig
                    Write-Info "Uploaded root .htaccess file to FTP"
                }
            }
            
            Write-Success "`n Publishing completed successfully!"
            if (-not $FTP -or $FileSystemOnly) {
                Write-Info "File system location: $DEST_DIR"
            }
            if ($FTP) {
                Write-Info "FTP server: $($FTPConfig.Server)$($FTPConfig.RemoteRoot)"
            }
        } else {
            Write-Error "`n Publishing failed!"
            exit 1
        }
        
    } finally {
        Set-Location $originalLocation
    }
}

# Enhanced help function
function Show-Help {
    Write-Host @"
$PROJECT_NAME Enhanced Publishing Script with FTP Support
========================================================

Usage: .\publish-ftp.ps1 [OPTIONS]

OPTIONS:
    -Frontend, -f         Publish only the frontend
    -Backend, -b          Publish only the PHP backend  
    -All, -a              Publish both (default if no specific option given)
    -Clean, -c            Clean destination directories before publishing
    -Verbose, -v          Show detailed output during copying/uploading
    -Production, -p       Deploy to production environment
    -FTP, -ftp            Deploy to FTP server (requires FTP configuration in .env)
    -SkipVendor           Skip vendor directory upload if unchanged (based on composer.lock hash)
    -FileSystemOnly, -fs  Force file system deployment even when -FTP is specified
    -FTPProfile           Use named FTP profile from .env (default: "default")
    -Help                 Show this help message

EXAMPLES:
    .\publish-ftp.ps1                           # File system deployment to preview
    .\publish-ftp.ps1 -f -p                     # Frontend only to production file system
    .\publish-ftp.ps1 -ftp -p                   # Both to production FTP server
    .\publish-ftp.ps1 -b -ftp -SkipVendor       # Backend to FTP, skip vendor if unchanged
    .\publish-ftp.ps1 -All -Clean -FTP -Verbose # Clean deploy both to FTP with details

REQUIRED .ENV CONFIGURATION:
File System Deployment:
    PREVIEW_ROOT=H:\xampp\htdocs
    PRODUCTION_ROOT=F:\WebHatchery

FTP Deployment:
    FTP_SERVER=your.server.com
    FTP_USERNAME=your_username
    FTP_PASSWORD=your_password
    FTP_PORT=21
    FTP_REMOTE_ROOT=/public_html
    FTP_USE_SSL=false
    FTP_PASSIVE_MODE=true

FEATURES:
    • Dual deployment: File system and/or FTP
    • Smart vendor skipping: Skip vendor uploads if composer.lock unchanged
    • Connection testing: Validates FTP connection before deployment
    • Selective cleaning: Cleans only relevant files, preserves others
    • Progress tracking: Detailed progress and error reporting
    • Environment support: Separate preview and production configurations

"@ -ForegroundColor White
}

# Check for help request
if ($args -contains "-Help" -or $args -contains "--help" -or $args -contains "/?" -or $args -contains "-h") {
    Show-Help
    exit 0
}

# Validate parameters
if ($FTP -and $FileSystemOnly) {
    Write-Warning "-FTP and -FileSystemOnly specified together. Will deploy to both targets."
}

# Run main function
Main