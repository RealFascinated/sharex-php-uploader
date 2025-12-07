#!/bin/sh

set -e

# Get upload directory from environment or use default
# In Docker, the actual path is /var/www/html regardless of env var
UPLOAD_DIR="${UPLOAD_DIR:-/var/www/html}"
# Normalize path - if it's relative, use /var/www/html
if [ "$UPLOAD_DIR" = "./" ] || [ "$UPLOAD_DIR" = "." ]; then
  UPLOAD_DIR="/var/www/html"
fi
HASH_FILE="${UPLOAD_DIR}/.file_hashes.json"

# Ensure upload directory exists
if [ ! -d "$UPLOAD_DIR" ]; then
  echo "Error: Upload directory does not exist: $UPLOAD_DIR"
  exit 1
fi

echo "Scanning upload directory: $UPLOAD_DIR"
echo "Generating file hashes..."

# Test if we can list files
echo "Testing directory access..."
ls -la "$UPLOAD_DIR" | head -5

# Create temporary file for JSON entries
TEMP_FILE=$(mktemp)
trap "rm -f $TEMP_FILE" EXIT

# Count total files first for progress
echo "Counting files..."
total_files=$(find "$UPLOAD_DIR" -maxdepth 1 -type f ! -name ".file_hashes.json" 2>/dev/null | wc -l)
echo "Found $total_files files to process"

# Scan directory for files using find (handles many files better than glob)
processed=0
find "$UPLOAD_DIR" -maxdepth 1 -type f ! -name ".file_hashes.json" 2>/dev/null | while IFS= read -r file; do
  # Get relative filename from upload directory
  filename=$(basename "$file")
  
  echo "Processing: $filename"
  
  # Calculate SHA256 hash
  hash=$(sha256sum "$file" 2>/dev/null | cut -d' ' -f1)
  
  if [ -z "$hash" ]; then
    echo "Warning: Failed to hash $filename, skipping"
    continue
  fi
  
  # Escape filename for JSON (handle quotes and backslashes)
  filename_escaped=$(printf '%s' "$filename" | sed 's/\\/\\\\/g' | sed 's/"/\\"/g')
  
  # Write JSON entry to temp file
  echo "  \"$hash\": \"$filename_escaped\"" >> "$TEMP_FILE"
  
  processed=$((processed + 1))
  # Show progress every 100 files
  if [ $((processed % 100)) -eq 0 ]; then
    echo "Processed $processed/$total_files files..."
  fi
done

# Count processed files from temp file
file_count=$(wc -l < "$TEMP_FILE" 2>/dev/null || echo "0")

# Build final JSON with proper formatting
{
  echo "{"
  if [ -s "$TEMP_FILE" ]; then
    # Add entries with commas except for the last one
    head -n -1 "$TEMP_FILE" | sed 's/$/,/'
    tail -n 1 "$TEMP_FILE"
  fi
  echo "}"
} > "$HASH_FILE"

chmod 0644 "$HASH_FILE"

echo "Hash file created: $HASH_FILE"
echo "Total files processed: $file_count"
