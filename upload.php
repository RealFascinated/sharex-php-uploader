<?php

/**
 * DO NOT TOUCH!!!!!!!!
 */
$SCRIPT_VERSION = "0.2.0"; // The version of the script
$defaultSecretKey = "set_me"; // The default secret key
header('Content-type:application/json;charset=utf-8'); // Set the response content type to JSON

/**
 * Configuration
 */

if (getenv('DOCKER')) { // If the script is running in a Docker container
  $uploadSecrets = explode(",", getenv('UPLOAD_SECRETS')); // The upload secrets
  $uploadDir = getenv('UPLOAD_DIR'); // The upload directory
  $useRandomFileNames = getenv('USE_RANDOM_FILE_NAMES'); // Use random file names instead of the original file name
  $fileNameLength = getenv('FILE_NAME_LENGTH'); // The length of the random file name
} else {
  /**
   * !!!
   * USE THIS IF YOU ARE NOT USING DOCKER
   * !!!
   */
  $uploadSecrets = array("set_me"); // The upload secrets
  $uploadDir = "./"; // The upload directory
  $useRandomFileNames = false; // Use random file names instead of the original file name
  $fileNameLength = 8; // The length of the random file name
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
 * Return a JSON response
 */
function respondJson($data): void
{
  echo (json_encode($data));
  die();
}

try {
  $secret = isset($_POST['secret']) ? $_POST['secret'] : null; // The secret key
  $file = isset($_FILES['sharex']) ? $_FILES['sharex'] : null; // The uploaded file

  // Page to show if someone visits the upload script
  if ($secret == null && $file == null) {
    respondJson(array(
      'status' => 'OK',
      'url' => 'Welcome to the ShareX PHP Uploader! v' . $SCRIPT_VERSION,
      // Remove this if you don't want to show the support URL
      'support' => "For support, visit - https://github.com/RealFascinated/sharex-php-uploader"
    ));
    die();
  }

  // Check if the token is valid
  if (!checkSecret($secret)) {
    respondJson(array(
      'status' => 'ERROR',
      'url' => 'Invalid or missing upload secret'
    ));
    die();
  }

  // Check if the secret is the default one, and if so, tell the user to change it
  if ($secret == $defaultSecretKey) {
    respondJson(array(
      'status' => 'ERROR',
      'url' => 'You need to set your upload secret in the configuration section of the upload.php file'
    ));
    die();
  }

  // Check if the file was uploaded
  if (!isset($file)) {
    respondJson(array(
      'status' => 'ERROR',
      'url' => 'No file was uploaded'
    ));
    die();
  }

  $originalFileName = preg_replace("/[^A-Za-z0-9_.]/", '', $_FILES["sharex"]["name"]); // Remove unwanted characters (e.g. spaces, special characters, etc.)
  $fileType = pathinfo($originalFileName, PATHINFO_EXTENSION); // File extension (e.g. png, jpg, etc.)
  $fileSize = $_FILES["sharex"]["size"]; // File size in bytes

  $fileName = $originalFileName; // The final name of the file
  if ($useRandomFileNames) { // Generate a random file name if enabled
    $fileName = generateRandomString($fileNameLength) . "." . $fileType;
  }

  // Check if the file already exists
  if (file_exists($uploadDir . $fileName)) {
    respondJson(array(
      'status' => 'ERROR',
      'url' => 'File already exists'
    ));
    die();
  }

  // Move the file to the uploads folder
  $success = move_uploaded_file($_FILES["sharex"]["tmp_name"], $uploadDir . $fileName);
  if (!$success) {
    respondJson(array(
      'status' => 'ERROR',
      'url' => 'Failed to save file. Check the permissions of the upload directory.'
    ));
    die();
  }

  respondJson(array(
    'status' => 'OK',
    'url' => $fileName
  ));
  die();
} catch (Exception $e) { // Handle any errors
  respondJson(array(
    'status' => 'ERROR',
    'url' => $e->getMessage()
  ));
  die();
}
