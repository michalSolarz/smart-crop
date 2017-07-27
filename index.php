<?php
require 'vendor/autoload.php';

$smartCrop = new \MichalSolarz\SmartCrop\SmartCrop();
foreach (scandir('img') as $image) {
    if ($image == '.' || $image == '..' || is_dir($image)) {
        continue;
    }
    echo $image . PHP_EOL;
    $smartCrop->loadImage('img/'.$image);
    $smartCrop->analyseImage();
}
