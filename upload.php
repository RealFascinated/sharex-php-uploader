<?php
$before = microtime(true); // Start time of the script
error_reporting(E_ERROR); // Hide PHP errors
header('Content-type:application/json;charset=utf-8'); // Set the content type to JSON

/**
 * Configuration
 */
$uploadSecrets = array("set me"); // Your secret keys
$uploadDir = "./"; // The upload directory
$useRandomFileNames = false; // Use random file names instead of the original file name
$shouldConvertToWebp = true; // Should the script convert images to webp?
$webpQuality = 90; // The quality of the webp image (0-100)
$fileNameLength = 8; // The length of the random file name
$webpThreadhold = 1048576; // 1MB - The minimum file size for converting to webp (in bytes)

/**
 * Check if the given secret is valid
 */
function checkSecret($token): bool
{
  global $uploadSecrets;
  return isset($token) && in_array($token, $uploadSecrets);
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
    $randomString .= $characters[rand(0, $charactersLength - 1)];
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

try {
  $token = $_POST['secret']; // The provided secret key
  $file = $_FILES['sharex']; // The uploaded file

  // Check if the token is valid
  if (!checkSecret($token)) {
    returnJson(array(
      'status' => 'ERROR',
      'url' => 'Invalid or missing upload secret',
      // Remove this if you don't want to show the support URL
      'support' => "For support, visit - https://git.fascinated.cc/Fascinated/sharex-php-uploader",
      'timeTaken' => getTimeTaken()
    ));
    die();
  }

  // Check if the file was uploaded
  if (!isset($file)) {
    returnJson(array(
      'status' => 'ERROR',
      'url' => 'No file was uploaded',
      'timeTaken' => getTimeTaken()
    ));
    die();
  }

  $target_file = preg_replace("/[^A-Za-z0-9_.]/", '', $_FILES["sharex"]["name"]); // Remove unwanted characters
  $fileType = pathinfo($target_file, PATHINFO_EXTENSION); // File extension (e.g. png, jpg, etc.)

  // Check if the file already exists
  if (file_exists($uploadDir . $target_file)) {
    returnJson(array(
      'status' => 'ERROR',
      'url' => 'File already exists',
      'timeTaken' => getTimeTaken()
    ));
    die();
  }

  $shouldSave = true; // Whether or not the file should be saved
  $finalName = $target_file; // The final name of the file
  if ($useRandomFileNames) { // Generate a random file name if enabled
    $finalName = generateRandomString($fileNameLength) . "." . $fileType;
  }

  // Convert the image to webp if applicable
  if (in_array($fileType, array("png", "jpeg", "jpg")) && $_FILES["sharex"]["size"] > $webpThreadhold && $shouldConvertToWebp) {
    $image = imagecreatefromstring(file_get_contents($_FILES["sharex"]["tmp_name"]));
    $webp_file = pathinfo($finalName, PATHINFO_FILENAME) . ".webp";
    imagewebp($image, $webp_file, $webpQuality); // Convert the image and save it
    imagedestroy($image); // Free up memory
    $finalName = $webp_file;
    $shouldSave = false;
  }

  if ($shouldSave) {
    // Move the file to the uploads folder
    if (move_uploaded_file($_FILES["sharex"]["tmp_name"], $uploadDir . $finalName)) {
      returnJson(array(
        'status' => 'OK',
        'url' => $finalName,
        'timeTaken' => getTimeTaken()
      ));
      die();
    }
    returnJson(array(
      'status' => 'ERROR',
      'url' => 'Failed to save file. Check the permissions of the upload directory.',
      'timeTaken' => getTimeTaken()
    ));
    die();
  }
  returnJson(array(
    'status' => 'OK',
    'url' => $finalName,
    'timeTaken' => getTimeTaken()
  ));
  die();
} catch (Exception $e) { // Handle any errors
  returnJson(array(
    'status' => 'ERROR',
    'url' => $e->getMessage(),
    'timeTaken' => getTimeTaken()
  ));
  die();
}
