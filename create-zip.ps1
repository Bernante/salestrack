# SalesTrack Production-Ready ZIP Creator for InfinityFree
# This script creates a clean ZIP file excluding unnecessary files

Add-Type -AssemblyName System.IO.Compression.FileSystem

$source = "C:\inventory"
$destination = "C:\inventory\SalesTrack_Production_Ready.zip"

# List of patterns to exclude
$excludePatterns = @(
    ".git",
    "node_modules",
    ".vscode",
    ".cline",
    ".claude",
    ".idea",
    "*.log",
    "Thumbs.db",
    ".DS_Store",
    "*.swp",
    "*.swo",
    ".env.local",
    "*.tmp",
    "salestrack.zip",
    "SalesTrack_Production.zip",
    "SalesTrack_Production_Clean.zip",
    "create-zip.ps1"
)

# Remove existing ZIP if it exists
if (Test-Path $destination) {
    Remove-Item $destination -Force
    Write-Host "Removed existing ZIP file"
}

Write-Host "Creating production-ready ZIP file..."
Write-Host "Source: $source"
Write-Host "Destination: $destination"
Write-Host ""

# Get all files recursively
$allFiles = @(Get-ChildItem -Path $source -Recurse -File -Force)

# Filter out excluded files
$filesToCompress = @()
foreach ($file in $allFiles) {
    $shouldExclude = $false
    $relativePath = $file.FullName.Substring($source.Length + 1)
    
    foreach ($pattern in $excludePatterns) {
        if ($relativePath -like "*$pattern*" -or $file.Name -like $pattern) {
            $shouldExclude = $true
            break
        }
    }
    
    if (-not $shouldExclude) {
        $filesToCompress += $file
    }
}

Write-Host "Total files found: $($allFiles.Count)"
Write-Host "Files to include: $($filesToCompress.Count)"
Write-Host "Files excluded: $($allFiles.Count - $filesToCompress.Count)"
Write-Host ""

# Create the ZIP file using .NET
$zip = [System.IO.Compression.ZipFile]::Open($destination, "Create")

foreach ($file in $filesToCompress) {
    $relativePath = $file.FullName.Substring($source.Length + 1)
    
    # Create entry in ZIP with proper path
    $entry = $zip.CreateEntry($relativePath)
    
    # Write file content to ZIP entry
    $entryStream = $entry.Open()
    $fileStream = [System.IO.File]::OpenRead($file.FullName)
    $fileStream.CopyTo($entryStream)
    $fileStream.Close()
    $entryStream.Close()
    
    Write-Host "Added: $relativePath"
}

$zip.Dispose()

# Get file size
$zipSize = (Get-Item $destination).Length
$zipSizeMB = [math]::Round($zipSize / 1MB, 2)

Write-Host ""
Write-Host "✅ ZIP file created successfully!"
Write-Host "Location: $destination"
Write-Host "Size: $zipSizeMB MB ($zipSize bytes)"
Write-Host ""
Write-Host "Ready for upload to InfinityFree htdocs directory"
