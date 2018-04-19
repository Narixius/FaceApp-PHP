<?php
require('../FaceApp/faceapp.php');
try {
    $FaceApp = new FaceApp(__DIR__.'/image.jpg');

    $photoCode = $FaceApp->getPhotoCode();

    $filter = $FaceApp->getFilters()[array_rand($FaceApp->getFilters())];

    $FaceApp->applyFilter($photoCode, $filter, false);

    if ($FaceApp->savePhoto('newImage.jpg')) {
        //Done !
        //image saved to "__DIR__/newImagee.jpg" and now we wanna show it
        header('Content-Type: image/jpeg');
        readfile('newImage.jpg');
    }
} catch (Exception $e) {
    exit($e->getMessage());
} 