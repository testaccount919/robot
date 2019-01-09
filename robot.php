<?php

require 'robot_cleaner.php';

if (isset($_SERVER['SERVER_ADDR'])) {
  echo 'Use robot_post.php for POST request!';

  return;
}

// file reading
$filename = $argv[1];
$handle = fopen($filename, "r");
$contents = fread($handle, filesize($filename));
fclose($handle);

// input parsing
$parsedInput = json_decode($contents, true);

// new Robot instance and run
$robot =
  new Robot_Cleaner($parsedInput['map'], $parsedInput['start'], $parsedInput['commands'], $parsedInput['battery']);
try {
  $result = $robot->run();
  file_put_contents($argv[2], $result);

} catch (Exception $e) {
  echo 'Exception was thrown: ' . $e->getMessage();
}

