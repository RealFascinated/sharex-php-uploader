<?php

/**
 * DO NOT TOUCH!!!!!!!!
 */
$SCRIPT_VERSION = "0.2.0"; // The version of the script
$defaultUploadKey = "set_me"; // The default uploadKey key
header('Content-type:application/json;charset=utf-8'); // Set the response content type to JSON

/**
 * Configuration
 */

if (getenv('DOCKER')) { // If the script is running in a Docker container
  $uploaduploadKeys = explode(",", getenv('UPLOAD_uploadKeyS')); // The upload uploadKeys
  $uploadDir = getenv('UPLOAD_DIR'); // The upload directory
  $useRandomFileNames = getenv('USE_RANDOM_FILE_NAMES'); // Use random file names instead of the original file name
  $fileNameLength = getenv('FILE_NAME_LENGTH'); // The length of the random file name
} else {
  /**
   * !!!
   * USE THIS IF YOU ARE NOT USING DOCKER
   * !!!
   */
  $uploaduploadKeys = array("set_me"); // The upload uploadKeys
  $uploadDir = "./"; // The upload directory
  $useRandomFileNames = false; // Use random file names instead of the original file name
  $fileNameLength = 8; // The length of the random file name
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
  $uploadKey = isset($_POST['uploadKey']) ? $_POST['uploadKey'] : null; // The uploadKey key
  $file = isset($_FILES['sharex']) ? $_FILES['sharex'] : null; // The uploaded file

  // Page to show if someone visits the upload script
  if ($uploadKey == null && $file == null) {
    respondJson(array(
      'status' => 'OK',
      'url' => 'Welcome to the ShareX PHP Uploader! v' . $SCRIPT_VERSION,
      'support' => "For support, visit - https://github.com/RealFascinated/sharex-php-uploader"
    ));
    die();
  }

  // Check if the upload key is valid
  if (!isset($uploadKey) && in_array($uploadKey, $uploaduploadKeys)) {
    respondJson(array(
      'status' => 'ERROR',
      'url' => 'Invalid or missing upload uploadKey'
    ));
    die();
  }

  // Check if the upload key is the default one, and if so, tell the user to change it
  if ($uploadKey == $defaultUploadKey) {
    respondJson(array(
      'status' => 'ERROR',
      'url' => 'You need to set your upload key in the configuration section of the upload.php file'
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
      'url' => 'The file ' . $fileName . ' already exists'
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
