<?php
// Start time of the script
$before = microtime(true); // Start time of the script
header('Content-type:application/json;charset=utf-8'); // Set the content type to JSON
error_reporting(E_ERROR); // Hide PHP errors
$tokens = array("set me"); // Your secret keys

/**
 * Check if the token is valid
 */
function checkToken($token) {
  global $tokens;
  if (in_array($token, $tokens)) {
    return true;
  } else {
    return false;
  }
}

/**
 * Return a JSON response
 */
function returnJson($status, $message, $timeTaken = null) {
  $json = array('status' => $status, 'url' => $message, 'processingTime' => round($timeTaken, 2) . "ms");
  echo(json_encode($json));
  die();
}

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

if (in_array($fileType, array("png", "jpeg", "jpg"))) {
  // Convert to webp
  $image = imagecreatefromstring(file_get_contents($_FILES["sharex"]["tmp_name"]));
  $webp_file = pathinfo($target_file, PATHINFO_FILENAME) . ".webp";
  imagewebp($image, $webp_file, 90);
  imagedestroy($image);
  $target_file = $webp_file;
}

// Upload the file
if (move_uploaded_file($_FILES["sharex"]["tmp_name"], $target_file)) {
  $timeTaken = microtime(true) - $before;
  returnJson('OK', $target_file, $timeTaken);
} else {
  $timeTaken = microtime(true) - $before;
  returnJson('ERROR', 'File upload failed. Does the folder exist and did you CHMOD the folder?', $timeTaken);
}
?>