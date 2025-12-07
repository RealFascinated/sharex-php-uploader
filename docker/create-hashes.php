#!/usr/bin/env php
<?php

$UPLOAD_DIR = getenv('UPLOAD_DIR') ?: '/var/www/html';
// Normalize path - if it's relative, use /var/www/html
if ($UPLOAD_DIR === './' || $UPLOAD_DIR === '.') {
    $UPLOAD_DIR = '/var/www/html';
}

$HASH_FILE = $UPLOAD_DIR . '/.file_hashes.json';

if (file_exists($HASH_FILE)) {
    echo "Hash file already exists: $HASH_FILE. Skipping hash generation...\n";
    exit(0);
}

// Ensure upload directory exists
if (!is_dir($UPLOAD_DIR)) {
    echo "Error: Upload directory does not exist: $UPLOAD_DIR\n";
    exit(1);
}

echo "Scanning upload directory: $UPLOAD_DIR\n";
echo "Generating file hashes...\n";

$fileHashes = [];

// Scan directory for files (excluding hidden files and hash file)
$files = glob($UPLOAD_DIR . '/*');
$totalFiles = 0;
foreach ($files as $file) {
    // Skip if not a regular file
    if (!is_file($file)) {
        continue;
    }
    
    $filename = basename($file);
    
    // Skip hidden files (starting with .) and the hash file itself
    if ($filename[0] === '.' || $filename === '.file_hashes.json') {
        continue;
    }
    
    $totalFiles++;
}

echo "Found $totalFiles files to process\n";

$processed = 0;
foreach ($files as $file) {
    // Skip if not a regular file
    if (!is_file($file)) {
        continue;
    }
    
    $filename = basename($file);
    
    // Skip hidden files
    if ($filename[0] === '.' ) {
        continue;
    }
    
    // Calculate SHA256 hash
    $hash = hash_file('sha256', $file);
    
    if (empty($hash)) {
        echo "Warning: Failed to hash $filename, skipping\n";
        continue;
    }
    
    // Store hash -> filename mapping
    $fileHashes[$hash] = $filename;
    
    $processed++;
    // Show progress every 100 files
    if ($processed % 100 === 0) {
        echo "Processed $processed/$totalFiles files...\n";
    }
}

// Write JSON file
$json = json_encode($fileHashes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents($HASH_FILE, $json);
chmod($HASH_FILE, 0644);

echo "Hash file created: $HASH_FILE\n";
echo "Total files processed: $processed\n";

