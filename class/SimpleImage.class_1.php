<?php
    class SimpleImage {
        public $image           = null;
        public $newImage        = null;
        public $tmpImage        = null;

        public $imagePath       = null;

        public $rotateDegrees   = 0;
        public $rotateBGColor   = 0;

        // Orginal sizes
        public $imageWidth      = 0;
        public $imageHeight     = 0;

        // Resize sizes
        public $resizeWidth     = 0;
        public $resizeHeight    = 0;
        public $resize2Square   = 0;
        
        // Thumbnail sizes
        public $thumbnailWidth  = 0;
        public $thumbnailHeight = 0;

        public $errorCode       = null;
        public $errorsList      = array(
                                    1 => "Plik nie istnieje!",
                                    2 => "Wybrano niedozwolony plik!",
                                    3 => "Katalog nie isteniej!",
                                    4 => "Katalog nie ma praw do zapisu!",
                                    5 => "Nie można zmiejszyć do wybranych wartości!",
                                );

        public $imageDir        = null;
        public $imageName       = null;
        public $imageExt        = null;
        public $imageType       = null;
        public $allowedType     = array(
                                    'image/jpeg',
                                    'image/png',
                                    'image/gif'
                                );

        public $imageQuality    = array(
                                    'image/jpeg'    => 90,
                                    'image/png'     => 90
                                );

        public $sizeRatio       = 0;
        
        public $processed       = 0;
        public $exifData        = array();

        
        public function __construct() {}
        public function __destruct() {}

        public function load($imagePath) {
            if (!file_exists($imagePath)) {
                $this->errorCode = 1;
                return false;
            }

            $this->imagePath    = $imagePath; unset($imagePath);
            $this->imageType    = mime_content_type($this->imagePath);

            if (!$this->_isAllowedFileType()) {
                $this->errorCode = 2;
                return false;
            }

            $fileNameArray      = explode('.', basename($this->imagePath));
            $this->imageName    = $fileNameArray['0'];
            $this->imageExt     = $fileNameArray['1'];

            $strLen             = strlen(basename($this->imagePath));
            $this->imageDir     = substr($this->imagePath, 0, -$strLen);

            switch ($this->imageType) {
                case 'image/jpeg':
                    $this->image = imagecreatefromjpeg($this->imagePath);
                    $this->exifData = exif_read_data($this->imagePath);
                break;
                case 'image/png':
                    $this->image = imagecreatefrompng($this->imagePath);
                break;
                case 'image/gif':
                    $this->image = imagecreatefromgif($this->imagePath);
                break;
            }

            $this->imageWidth   = imagesx($this->image);
            $this->imageHeight  = imagesy($this->image);
            $this->sizeRatio    = ($this->imageWidth / $this->imageHeight);
            return true;
        }

        public function resize($width = false, $height = false) {
            if (empty($width) && empty($height))
                return false;

            if ($width >= $this->imageWidth && $height >= $this->imageHeight)
                return false;
            
            if (is_numeric($width) && $width > 0)
                $this->resizeWidth  = (int) $width;

            if (is_numeric($height) && $height > 0)
                $this->resizeHeight = (int) $height;

            if (($width === $height) && ($this->imageWidth !== $this->imageHeight)) {
                if ($this->sizeRatio > 1) {

                    $this->resizeWidth = ($this->resizeHeight * $this->sizeRatio);
                    $this->resizeHeight = (int) ($this->resizeWidth / $this->sizeRatio);

                } else {

                    $this->resizeWidth = ($this->resizeHeight / $this->sizeRatio);
                    $this->resizeHeight = (int) ($this->resizeWidth * $this->sizeRatio);
                    
                }
                
                $this->resize2Square = 1;

                var_dump("ratio:"+$this->sizeRatio);
                var_dump("w:"+$this->resizeWidth);
                var_dump("h:"+$this->resizeHeight);
                var_dump("wo:"+$this->imageWidth);
                var_dump("ho:"+$this->imageHeight);
            }

            if ($this->resizeWidth == 0)
                if ($this->sizeRatio > 1)
                    $this->resizeWidth = ($this->resizeHeight * $this->sizeRatio);
                else
                    $this->resizeWidth = ($this->resizeHeight / $this->sizeRatio);

            if ($this->resizeHeight == 0)
                if ($this->sizeRatio > 1)
                    $this->resizeHeight = (int) ($this->resizeWidth / $this->sizeRatio);
                else
                    $this->resizeHeight = (int) ($this->resizeWidth * $this->sizeRatio);
            
            return true;
        }

        public function thumbnail($width = false, $height = false, $rotateDegrees = false) {
            if (empty($width) && empty($height))
                return false;
            
            if ($width >= $this->imageWidth && $height >= $this->imageHeight)
                return false;

            if (is_numeric($width) && $width > 0)
                $this->thumbnailWidth  = (int) $width;

            if (is_numeric($height) && $height > 0)
                $this->thumbnailHeight = (int) $height;

            if ($this->thumbnailWidth == 0)
                if ($this->sizeRatio > 1)
                    $this->thumbnailWidth = ($this->thumbnailHeight * $this->sizeRatio);
                else
                    $this->thumbnailWidth = ($this->thumbnailHeight / $this->sizeRatio);

            if ($this->thumbnailHeight == 0)
                if ($this->sizeRatio > 1)
                    $this->thumbnailHeight = (int) ($this->thumbnailWidth / $this->sizeRatio);
                else
                    $this->thumbnailHeight = (int) ($this->thumbnailWidth * $this->sizeRatio);

            if ($rotateDegrees)
                $this->rotateDegrees = (int) $rotateDegrees;

            return true;
        }
            
        public function saveThumbnail($fileName) {
            $resizeImage = $this->_resize($this->thumbnailWidth, $this->thumbnailHeight);
            $imageRes = ($this->processed == 1) ? $this->newImage : $this->image;

            $imageQuality = $this->_getImageQuality();
            switch ($this->imageType) {
                case 'image/jpeg':
                    imagejpeg($imageRes, $fileName, $imageQuality);
                break;
                case 'image/png':
                    imagepng($imageRes, $fileName, $imageQuality);
                break;
                case 'image/gif':
                    imagegif($imageRes, $fileName);
                break;
            }
            return true;
        }

        public function saveResized($fileName = false) {
            $resizeImage = $this->_resize($this->resizeWidth, $this->resizeHeight);
            if ($this->processed === 0)
                return false;

            $fileName = ($fileName) ? $fileName : $this->imagePath;
            $imageQuality = $this->_getImageQuality();
            switch ($this->imageType) {
                case 'image/jpeg':
                    imagejpeg($this->newImage, $fileName, $imageQuality);
                break;
                case 'image/png':
                    imagepng($this->newImage, $fileName, $imageQuality);
                break;
                case 'image/gif':
                    imagegif($this->newImage, $fileName);
                break;
            }
            return true;
        }

        public function error() {
            return ($this->errorCode != 0) ? $this->errorsList[$this->errorCode] : false;
        }

        private function _getImageQuality() {
            return (isset($this->imageQuality[$this->imageType])) ? $this->imageQuality[$this->imageType] : false;
        }

        private function _isAllowedFileType() {
            if (empty($this->allowedType))
                return true;

            $allowed = (in_array($this->imageType, $this->allowedType)) ? true : false;
            if (!$allowed)
                $this->errorCode = 1;

            return $allowed;
        }

        /**
         * Metoda sprawdzająca czy docelowy katalog istnieje
         * @return boolean (true|false)
         */
        private function _isDestDirExists() {
            $dirExists = (is_dir($this->destDir)) ? true : false;

            if ($dirExists === false)
                $this->_errorCode = 3;

            return $dirExists;
        }

        /**
         * Metoda sprawdzająca czy docelowy katalog jest zapisywalny
         * @return boolean (true|false)
         */
        private function _isDestDirIsWritable() {
            $isWritable = (is_writable($this->destDir)) ? true : false;

            if ($isWritable === false)
                $this->_errorCode = 4;

            return $isWritable;
        }

        private function _exifOrientation() {
            $ort = $this->exifData['IFD0']['Orientation'];
            var_dump($ort);
//            switch($ort)
//            {
//                case 1: // nothing
//                break;
//                case 2: // horizontal flip
//                    $image->flipImage($public,1);
//                break;
//
//                case 3: // 180 rotate left
//                    $image->rotateImage($public,180);
//                break;
//
//                case 4: // vertical flip
//                    $image->flipImage($public,2);
//                break;
//
//                case 5: // vertical flip + 90 rotate right
//                    $image->flipImage($public, 2);
//                        $image->rotateImage($public, -90);
//                break;
//
//                case 6: // 90 rotate right
//                    $image->rotateImage($public, -90);
//                break;
//
//                case 7: // horizontal flip + 90 rotate right
//                    $image->flipImage($public,1);
//                    $image->rotateImage($public, -90);
//                break;
//
//                case 8:    // 90 rotate left
//                    $image->rotateImage($public, 90);
//                break;
//            }
        }

        
        private function _resize($width, $height) {
            if (!is_resource($this->image))
                return false;

            if ($width == 0 && $height == 0)
                return false;

            $this->newImage     = imagecreatetruecolor($width, $height);
            $this->processed    = 1;
            imagecopyresized(
                $this->newImage,
                $this->image,
                0,
                0,
                0,
                0,
                $width,
                $height,
                $this->imageWidth,
                $this->imageHeight
            );



//            var_dump($this->_exifOrientation());

            if ($this->rotateDegrees != 0) {
                $this->newImage = imagerotate($this->newImage, $this->rotateDegrees, $this->rotateBGColor);

                $width  = imagesx($this->newImage);
                $height = imagesy($this->newImage);
            }

            if ($this->resize2Square === 1) {
                $squareSize = ($width > $height) ? $width : $height;
                $this->tmpImage = imagecreatetruecolor($squareSize, $squareSize);

                $imgDstX = ($width === $squareSize) ? 0 : round((($squareSize - $width)/2),0);
                $imgDstY = ($height === $squareSize) ? 0 : round((($squareSize - $height)/2),0);

                imagecopy(
                    $this->tmpImage,
                    $this->newImage,
                    $imgDstX,
                    $imgDstY,
                    0,
                    0,
                    $width,
                    $height
                );
                $this->newImage = $this->tmpImage;
            }
            return true;
        }
    }