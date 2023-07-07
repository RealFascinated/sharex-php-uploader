<?php

/**
 * DO NOT TOUCH!!!!!!!!
 */
$SCRIPT_VERSION = "0.1.0"; // The version of the script
$before = microtime(true); // Start time of the script
$defaultSecretKey = "set me"; // The default secret key
header('Content-type:application/json;charset=utf-8'); // Set the response content type to JSON

/**
 * Configuration
 */

if (getenv('DOCKER')) { // If the script is running in a Docker container
  $uploadSecrets = explode(",", getenv('UPLOAD_SECRETS')); // The upload secrets
  $uploadDir = getenv('UPLOAD_DIR'); // The upload directory
  $useRandomFileNames = getenv('USE_RANDOM_FILE_NAMES'); // Use random file names instead of the original file name
  $fileNameLength = getenv('FILE_NAME_LENGTH'); // The length of the random file name
  $shouldConvertToWebp = getenv('SHOULD_CONVERT_TO_WEBP'); // Should the script convert images to webp?
  $webpQuality =  getenv('WEBP_QUALITY'); // The quality of the webp image (0-100)
  $webpThreadhold = getenv('WEBP_THREADHOLD'); // The minimum file size for converting to webp (in bytes)
} else {
  /**
   * !!!
   * USE THIS IF YOU ARE NOT USING DOCKER
   * !!!
   */
  $uploadSecrets = array("set me"); // The upload secrets
  $uploadDir = "./"; // The upload directory
  $useRandomFileNames = false; // Use random file names instead of the original file name
  $fileNameLength = 8; // The length of the random file name
  $shouldConvertToWebp = true; // Should the script convert images to webp?
  $webpQuality = 95; // The quality of the webp image (0-100)
  $webpThreadhold = 1048576; // 1MB - The minimum file size for converting to webp (in bytes)
}

/**
 * Check if the given secret is valid
 */
function checkSecret($secret): bool
{
  global $uploadSecrets;
  return isset($secret) && in_array($secret, $uploadSecrets);
}

/**
 * Generate a random string
 */
function generateRandomString($length = 10): string
{
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $charactersLength = strlen($characters);
  $randomString = '';

  for ($i = 0; $i < $length; $i++) {
    // Shuffle the characters array
    $shuffledCharacters = str_shuffle($characters);
    $randomIndex = random_int(0, $charactersLength - 1);
    $randomString .= $shuffledCharacters[$randomIndex];
  }

  return $randomString;
}

/**
 * Get the time taken to execute the script
 */
function getTimeTaken()
{
  global $before;
  return round(microtime(true) - $before, 3) . "ms";
}

/**
 * Return a JSON response
 */
function returnJson($data): void
{
  echo (json_encode($data));
  die();
}

/**
 * Log to nginx
 */
function logToNginx($message): void
{
  error_log($message);
}

try {
  $secret = isset($_POST['secret']) ? $_POST['secret'] : null; // The secret key
  $file = isset($_FILES['sharex']) ? $_FILES['sharex'] : null; // The uploaded file

  // Page to show if someone visits the upload script
  if ($secret == null && $file == null) {
    returnJson(array(
      'status' => 'OK',
      'url' => 'Welcome to the ShareX PHP Uploader! v' . $SCRIPT_VERSION,
      // Remove this if you don't want to show the support URL
      'support' => "For support, visit - https://git.fascinated.cc/Fascinated/sharex-php-uploader",
      'timeTaken' => getTimeTaken()
    ));
    die();
  }

  // Check if the token is valid
  if (!checkSecret($secret)) {
    returnJson(array(
      'status' => 'ERROR',
      'url' => 'Invalid or missing upload secret',
      'timeTaken' => getTimeTaken()
    ));
    logToNginx("An upload was attempted with an invalid secret key: " . $secret);
    die();
  }

  // Check if the secret is the default one, and if so, tell the user to change it
  if ($secret == $defaultSecretKey) {
    returnJson(array(
      'status' => 'ERROR',
      'url' => 'You need to set your upload secret in the configuration section of the upload.php file',
      'timeTaken' => getTimeTaken()
    ));
    logToNginx("An upload was attempted with the default secret key");
    die();
  }

  // Check if the file was uploaded
  if (!isset($file)) {
    returnJson(array(
      'status' => 'ERROR',
      'url' => 'No file was uploaded',
      'timeTaken' => getTimeTaken()
    ));
    logToNginx("An upload was attempted without providing a file");
    die();
  }

  $originalFileName = preg_replace("/[^A-Za-z0-9_.]/", '', $_FILES["sharex"]["name"]); // Remove unwanted characters
  $fileType = pathinfo($originalFileName, PATHINFO_EXTENSION); // File extension (e.g. png, jpg, etc.)
  $fileSize = $_FILES["sharex"]["size"]; // File size in bytes

  // Check if the file already exists
  if (file_exists($uploadDir . $originalFileName)) {
    returnJson(array(
      'status' => 'ERROR',
      'url' => 'File already exists',
      'timeTaken' => getTimeTaken()
    ));
    logToNginx("An upload was attempted with a file that already exists: " . $originalFileName);
    die();
  }

  $finalName = $originalFileName; // The final name of the file
  if ($useRandomFileNames) { // Generate a random file name if enabled
    $finalName = generateRandomString($fileNameLength) . "." . $fileType;
  }

  $needsToBeSaved = true; // Whether the file needs to be saved

  // Check the file type and size
  if ($shouldConvertToWebp && in_array($fileType, ["png", "jpeg", "jpg"]) && $_FILES["sharex"]["size"] > $webpThreadhold) {
    $image = imagecreatefromstring(file_get_contents($_FILES["sharex"]["tmp_name"]));
    $webp_file = pathinfo($finalName, PATHINFO_FILENAME) . ".webp";
    imagewebp($image, $webp_file, 90); // Convert the image and save it
    imagedestroy($image); // Free up memory
    $finalName = $webp_file;
    $shouldSave = false;
    $fileSize = filesize($webp_file); // Update the file size
  }

  if ($needsToBeSaved) { // Save the file if it has not been saved yet
    // Move the file to the uploads folder
    $success = move_uploaded_file($_FILES["sharex"]["tmp_name"], $uploadDir . $finalName);
    if (!$success) {
      returnJson(array(
        'status' => 'ERROR',
        'url' => 'Failed to save file. Check the permissions of the upload directory.',
        'timeTaken' => getTimeTaken()
      ));
      logToNginx("An upload was attempted but the file could not be saved: " . $finalName);
      die();
    }
  }
  returnJson(array(
    'status' => 'OK',
    'url' => $finalName,
    'timeTaken' => getTimeTaken()
  ));
  logToNginx("An upload was successful. original id: $originalFileName, final id: $finalName, size: $fileSize");
  die();
} catch (Exception $e) { // Handle any errors
  returnJson(array(
    'status' => 'ERROR',
    'url' => $e->getMessage(),
    'timeTaken' => getTimeTaken()
  ));
  logToNginx("An upload was attempted but an error occurred: " . $e->getMessage());
  die();
}
