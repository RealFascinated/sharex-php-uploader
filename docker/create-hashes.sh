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

# Create temporary file for JSON entries
TEMP_FILE=$(mktemp)
trap "rm -f $TEMP_FILE" EXIT

# Scan directory for files using find (handles many files better than glob)
file_count=0
find "$UPLOAD_DIR" -maxdepth 1 -type f ! -name ".file_hashes.json" | while IFS= read -r file; do
  # Get relative filename from upload directory
  filename=$(basename "$file")
  
  # Calculate SHA256 hash
  hash=$(sha256sum "$file" | cut -d' ' -f1)
  
  # Escape filename for JSON (handle quotes and backslashes)
  filename_escaped=$(printf '%s' "$filename" | sed 's/\\/\\\\/g' | sed 's/"/\\"/g')
  
  # Write JSON entry to temp file
  echo "  \"$hash\": \"$filename_escaped\"" >> "$TEMP_FILE"
  
  echo "Calculated hash for $filename"
  file_count=$((file_count + 1))
done

# Re-count files since the while loop runs in a subshell
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
