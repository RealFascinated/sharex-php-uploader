#!/bin/bash

set -e

# Get upload directory from environment or use default
UPLOAD_DIR="${UPLOAD_DIR:-./}"
HASH_FILE="${UPLOAD_DIR}/.file_hashes.json"

# Ensure upload directory exists
if [ ! -d "$UPLOAD_DIR" ]; then
  echo "Error: Upload directory does not exist: $UPLOAD_DIR"
  exit 1
fi

echo "Scanning upload directory: $UPLOAD_DIR"
echo "Generating file hashes..."

# Create temporary file for JSON entries
TEMP_FILE=$(mktemp)
trap "rm -f $TEMP_FILE" EXIT

# Scan directory for files (excluding the hash file itself)
file_count=0
while IFS= read -r -d '' file; do
  # Get relative filename from upload directory
  filename=$(basename "$file")
  
  # Skip the hash file itself
  if [ "$filename" = ".file_hashes.json" ]; then
    continue
  fi
  
  # Calculate SHA256 hash
  hash=$(sha256sum "$file" | cut -d' ' -f1)
  
  # Escape filename for JSON (handle quotes and backslashes)
  filename_escaped=$(printf '%s' "$filename" | sed 's/\\/\\\\/g' | sed 's/"/\\"/g')
  
  # Write JSON entry to temp file
  echo "  \"$hash\": \"$filename_escaped\"" >> "$TEMP_FILE"
  
  echo "Calculated hash for $filename"
  ((file_count++))
done < <(find "$UPLOAD_DIR" -maxdepth 1 -type f -print0 2>/dev/null || true)

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
