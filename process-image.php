<?php
    require_once 'class/SimpleImage.class.php';

    $sampleImage = 'image/sample3.jpg';

    if (file_exists($sampleImage)) {
        $sImage = new SimpleImage();

        $sImage->load($sampleImage);
        $sImage->rotateDegrees = (int) $_GET['r'];
//        $sImage->resize(350);  //  800px width
//        $sImage->resize(null, 350);  //  800px width
//        $sImage->resize(350, 350);  //  800px width

//        $sImage->thumbnail(120);
//        $sImage->thumbnail(null,120);
        $sImage->thumbnail(120, 120);
        $sR = $sImage->saveThumbnail('image/resized.jpg');

        $error = $sImage->error();
        var_dump($error);
    }