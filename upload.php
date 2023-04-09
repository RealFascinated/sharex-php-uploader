<?php
$before = microtime(true); // Start time of the script
declare(strict_types=1);
error_reporting(E_ERROR); // Hide PHP errors
header('Content-type:application/json;charset=utf-8'); // Set the content type to JSON

/**
 * Configuration
 */
$tokens = array("set me"); // Your secret keys
$uploadDir = "./"; // The upload directory
$useRandomFileNames = false; // Use random file names instead of the original file name
$fileNameLength = 8; // The length of the random file name

/**
 * Check if the token is valid
 */
function checkToken($token): bool {
  global $tokens;
  return isset($token) && in_array($token, $tokens);
}

/**
 * Generate a random string
 */
function generateRandomString($length = 10): string {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[rand(0, $charactersLength - 1)];
  }
  return $randomString;
}

/**
 * Return a JSON response
 */
function returnJson($status, $message, $timeTaken = null) {
  $json = array('status' => $status, 'url' => $message, 'processingTime' => round($timeTaken ?? 0, 2) . "ms");
  echo(json_encode($json));
  die();
}

try {
  $token = $_POST['secret']; // The provided secret key
  $file = $_FILES['sharex']; // The uploaded file

  // Check if the token is valid
  if (!checkToken($token)) {
    $timeTaken = microtime(true) - $before;
    returnJson('ERROR', 'Invalid or missing secret key', $timeTaken);
    die();
  }

  // Check if the file was uploaded
  if (!isset($file)) {
    $timeTaken = microtime(true) - $before;
    returnJson('ERROR', 'No file was uploaded', $timeTaken);
    die();
  }

  $target_file = $_FILES["sharex"]["name"]; // File name
  $fileType = pathinfo($target_file, PATHINFO_EXTENSION); // File extension (e.g. png, jpg, etc.)

  // Check if the file already exists
  if (file_exists($uploadDir . $target_file)) {
    $timeTaken = microtime(true) - $before;
    returnJson('ERROR', 'File already exists', $timeTaken);
    die();
  }

  $shouldSave = true; // Whether or not the file should be saved
  $finalName = $target_file; // The final name of the file
  if ($useRandomFileNames) { // Generate a random file name if enabled
    $finalName = generateRandomString($fileNameLength) . "." . $fileType;
  }

  // Convert the image to webp if applicable
  if (in_array($fileType, array("png", "jpeg", "jpg"))) {
    $image = imagecreatefromstring(file_get_contents($_FILES["sharex"]["tmp_name"]));
    $webp_file = pathinfo($finalName, PATHINFO_FILENAME) . ".webp";
    imagewebp($image, $webp_file, 90);
    imagedestroy($image);
    $finalName = $webp_file;
    $shouldSave = false;
  }

  if ($shouldSave) {
    // Move the file to the uploads folder
    if (move_uploaded_file($_FILES["sharex"]["tmp_name"], $uploadDir . $finalName)) {
      $timeTaken = microtime(true) - $before;
      returnJson('OK', $finalName, $timeTaken);
    } else {
      $timeTaken = microtime(true) - $before;
      returnJson('ERROR', 'File upload failed. Does the upload folder exist and did you CHMOD the folder?', $timeTaken);
    }
    die();
  }
  returnJson('OK', $finalName, $timeTaken);
} catch (Exception $e) { // Handle any errors
  $timeTaken = microtime(true) - $before;
  returnJson('ERROR', $e->getMessage(), $timeTaken);
  die();
}
?>