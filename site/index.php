<?php
  DEFINE("DEBUG", false);

  $file = fopen('/dev/urandom', 'rb');

  if ($file) {
    $randomData = fread($file, rand(1, 10000));
    echo $randomData;
    fclose($file);
  } else {
    if (DEBUG) {
      echo "Failed to open urandom.";
    }
  }
?>
