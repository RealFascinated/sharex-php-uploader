<?php

/**
 * DO NOT TOUCH!!!!!!!!
 */
$SCRIPT_VERSION = "1.0.0"; // The version of the script
$defaultUploadKey = "set_me"; // The default upload key
header('Content-type:application/json;charset=utf-8'); // Set the response content type to JSON

/**
 * Configuration
 */
if (getenv('DOCKER')) { // If the script is running in a Docker container
  $uploadKeys = explode(",", getenv('UPLOAD_SECRETS')); // The upload keys
  $uploadDir = getenv('UPLOAD_DIR'); // The upload directory
  $useRandomFileNames = getenv('USE_RANDOM_FILE_NAMES'); // Use random file names instead of the original file name
  $fileNameLength = getenv('FILE_NAME_LENGTH'); // The length of the random file name
} else {
  /**
   * !!!
   * USE THIS IF YOU ARE NOT USING DOCKER
   * !!!
   */
  $uploadKeys = array("set_me"); // The upload keys
  $uploadDir = "./"; // The upload directory
  $useRandomFileNames = false; // Use random file names instead of the original file name
  $fileNameLength = 8; // The length of the random file name
}

/**
 * Generate a random string
 */
function generateRandomString(int $length = 10): string
{
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $charactersLength = strlen($characters);
  $randomString = '';

  for ($i = 0; $i < $length; $i++) {
    $randomIndex = random_int(0, $charactersLength - 1);
    $randomString .= $characters[$randomIndex];
  }

  return $randomString;
}

/**
 * Sanitize filename to prevent security issues
 */
function sanitizeFileName(string $fileName): string
{
  // Remove null bytes (security risk)
  $fileName = str_replace("\0", '', $fileName);
  
  // Remove path traversal attempts
  $fileName = str_replace(['../', '..\\', './', '.\\'], '', $fileName);
  
  // Get just the basename (remove any directory components)
  $fileName = basename($fileName);
  
  // Remove Windows reserved characters: < > : " | ? * \
  $fileName = preg_replace('/[<>:"|?*\\\\]/', '', $fileName);
  
  // Replace spaces and multiple dots with underscores
  $fileName = preg_replace('/\s+/', '_', $fileName);
  $fileName = preg_replace('/\.{2,}/', '.', $fileName);
  
  // Remove leading/trailing dots, spaces, and underscores
  $fileName = trim($fileName, '._ ');
  
  // Limit filename length (255 is max on most filesystems, leave room for extension)
  $maxLength = 200;
  if (strlen($fileName) > $maxLength) {
    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
    $name = pathinfo($fileName, PATHINFO_FILENAME);
    $fileName = substr($name, 0, $maxLength - strlen($ext) - 1) . '.' . $ext;
  }
  
  // If filename is empty or only dots, generate a fallback
  if (empty($fileName) || preg_match('/^\.+$/', $fileName)) {
    $fileName = 'upload_' . time();
  }
  
  return $fileName;
}

/**
 * Return a JSON response
 */
function respondJson(array $data): void
{
  echo (json_encode($data));
  die();
}

try {
  $uploadKey = isset($_POST['secret']) ? $_POST['secret'] : null; // The upload key
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
  if ($uploadKey === null || !in_array($uploadKey, $uploadKeys)) {
    respondJson(array(
      'status' => 'ERROR',
      'url' => 'Invalid or missing upload key'
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

  $originalFileName = sanitizeFileName($_FILES["sharex"]["name"]);
  $fileType = pathinfo($originalFileName, PATHINFO_EXTENSION);
  $fileName = $useRandomFileNames ? generateRandomString($fileNameLength) . "." . $fileType : $originalFileName;
  $fileSize = $_FILES["sharex"]["size"]; // File size in bytes

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
