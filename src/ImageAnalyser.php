<?php


namespace MichalSolarz\SmartCrop;


class ImageAnalyser
{
    private $prescaleFactor = 1;
    private $scale;
    private $width;
    private $height;
    private $cropWidth;
    private $cropHeight;

    public function analyseImage(Image $image, $width = 390, $height = 190)
    {
        $this->width = $width;
        $this->height = $height;
        $this->scale = min($image->getWidth() / $width, $image->getHeight() / $height);
        $this->countCropDimensions();
        $outputImage = imagecreatetruecolor($image->getWidth(), $image->getHeight());
        $this->edgeDetect($image, $outputImage);
        $this->skinDetect($image, $outputImage);
        $this->saturationDetect($image, $outputImage);
        $crops = $this->generateCrops($image);
        $scores = array();
        $topScore = -INF;
        $topCrop = null;
        foreach ($crops as $crop) {
            $scores[] = $score = $this->score($outputImage, $crop);
            if ($score->getTotal() > $topScore) {
                $topScore = $score->getTotal();
                $topCrop = $crop;
            }
        }
        $croppedImage = $image->getResource();
        if (!is_null($topCrop)) {
            $croppedImage = imagecrop($image->getResource(), array('x' => $topCrop->getX(), 'y' => $topCrop->getY(), 'width' => $topCrop->getWidth(), 'height' => $topCrop->getHeight()));
        }
        if (!file_exists('runtime')) {
            mkdir('runtime');
        }
        if (!file_exists('runtime/debug')) {
            mkdir('runtime/debug');
        }
        imagepng($croppedImage, 'runtime/debug/' . $image->getName() . '.png');
    }

    /**
     * @param Image $image
     * @param $outputImage
     */
    private function edgeDetect(Image $image, $outputImage)
    {
        $cias = $this->makeCies($image);
        for ($y = 0; $y < $image->getHeight(); $y++) {
            for ($x = 0; $x < $image->getWidth(); $x++) {
                if ($x === 0 || $x >= $image->getWidth() - 1 || $y === 0 || $y >= $image->getHeight() - 1) {
                    $lightess = $this->cie($image, $x, $y);
                } else {
                    $lightess = $cias[$y * $image->getWidth() + $x] * 4
                        - $cias[$x + ($y - 1) * $image->getWidth()]
                        - $cias[$x - 1 + $y * $image->getWidth()]
                        - $cias[$x + 1 + $y * $image->getWidth()]
                        - $cias[$x + ($y + 1) * $image->getWidth()];
                }
                $color = imagecolorallocate($outputImage, 0, (int)$this->bounds($lightess), 0);
                imagesetpixel($outputImage, $x, $y, $color);
            }
        }
    }

    private function makeCies(Image $image)
    {
        $cies = array();
        for ($y = 0; $y < $image->getHeight(); $y++) {
            for ($x = 0; $x < $image->getWidth(); $x++) {
                $cies[] = $this->cie($image, $x, $y);
            }
        }

        return $cies;
    }

    private function cie(Image $image, $x, $y)
    {
        $pixel = new Pixel($image, $x, $y);

        return $pixel->getCie();
    }

    private function bounds($value)
    {
        return min(max($value, 0.0), 255);
    }

    private function skinDetect(Image $image, $outputImage)
    {
        for ($y = 0; $y < $image->getHeight(); $y++) {
            for ($x = 0; $x < $image->getWidth(); $x++) {
                $pixel = new Pixel($image, $x, $y);

                $lightness = $pixel->getCie() / 255;
                $skinColor = $pixel->color();
                $outputPixel = new Pixel($outputImage, $x, $y);
                $color = imagecolorallocate($outputImage, 0, $outputPixel->getGreen(), $outputPixel->getBlue());
                if ($skinColor >= SmartCrop::SKIN_THRESHOLD && $lightness >= SmartCrop::SKIN_BRIGHTNESS_MIN && $lightness <= SmartCrop::SKIN_BRIGHTNESS_MAX) {
                    $r = ($skinColor - SmartCrop::SKIN_THRESHOLD) * (255 / (1 - SmartCrop::SKIN_THRESHOLD));
                    $color = imagecolorallocate($outputImage, $this->bounds($r), $outputPixel->getGreen(), $outputPixel->getBlue());
                }
                imagesetpixel($outputImage, $x, $y, $color);

            }
        }
    }

    private function saturationDetect(Image $image, $outputImage)
    {
        for ($y = 0; $y < $image->getHeight(); $y++) {
            for ($x = 0; $x < $image->getWidth(); $x++) {
                $pixel = new Pixel($image, $x, $y);
                $lightness = $pixel->getCie() / 255;
                $saturation = $pixel->saturation();
                $outputPixel = new Pixel($outputImage, $x, $y);
                $color = imagecolorallocate($outputImage, $outputPixel->getRed(), $outputPixel->getGreen(), 0);
                if ($saturation >= SmartCrop::SATURATION_THRESHOLD && $lightness >= SmartCrop::SKIN_BRIGHTNESS_MIN && $lightness <= SmartCrop::SKIN_BRIGHTNESS_MAX) {
                    $b = ($saturation - SmartCrop::SATURATION_THRESHOLD) * (255 / (1 - SmartCrop::SATURATION_THRESHOLD));
                    $color = imagecolorallocate($outputImage, $outputPixel->getRed(), $outputPixel->getGreen(), $this->bounds($b));
                }
                imagesetpixel($outputImage, $x, $y, $color);
            }
        }
    }

    private function generateCrops(Image $image)
    {
        $result = array();

        $width = $image->getWidth();
        $height = $image->getHeight();

        $minDimension = min($width, $height);

        if ($this->cropWidth != 0) {
            $cropWidth = $this->cropWidth;
        } else {
            $cropWidth = $minDimension;
        }

        if ($this->cropHeight != 0) {
            $cropHeight = $this->cropHeight;
        } else {
            $cropHeight = $minDimension;
        }

        for ($scale = SmartCrop::SCALE_MAX; $scale >= SmartCrop::SCALE_MIN; $scale -= SmartCrop::SCALE_STEP) {
            for ($y = 0; $y + $cropHeight * $scale <= $height; $y += SmartCrop::STEP) {
                for ($x = 0; $x + $cropWidth * $scale <= $width; $x += SmartCrop::STEP) {
                    $result[] = new Crop($x, $y, $cropWidth * $scale, $cropHeight * $scale);
                }
            }
        }

        return $result;
    }

    private function chop($x)
    {
        if ($x < 0) {
            return ceil($x);
        }

        return floor($x);
    }

    private function countCropDimensions()
    {
        $this->cropWidth = $this->width * $this->scale * $this->prescaleFactor;
        $this->cropHeight = $this->height * $this->scale * $this->prescaleFactor;
    }

    private function score($outputImage, Crop $crop)
    {
        $height = imagesx($outputImage);
        $width = imagesy($outputImage);
        $score = new Score();
        for ($y = 0; $y <= $height - SmartCrop::SCORE_DOWN_SAMPLE; $y += SmartCrop::SCORE_DOWN_SAMPLE) {
            for ($x = 0; $x <= $width - SmartCrop::SCORE_DOWN_SAMPLE; $x += SmartCrop::SCORE_DOWN_SAMPLE) {
                $pixel = new Pixel($outputImage, $x, $y);

                $importance = $this->importance($crop, $x, $y);
                $det = $pixel->getGreen() / 255;

                $skinScore = $pixel->getRed() / 255 * ($det + SmartCrop::SKIN_BIAS) * $importance;
                $detailScore = $det * $importance;
                $saturationScore = $pixel->getGreen() / 255 * ($det + SmartCrop::SATURATION_BIAS) * $importance;

                $score->addToSkin($skinScore);
                $score->addToDetail($detailScore);
                $score->addToSaturation($saturationScore);
            }
        }
        return $score;
    }

    private function importance(Crop $crop, $x, $y, $ruleOfThirds = true)
    {
        if ($crop->getX() > $x || $x >= $crop->getX() + $crop->getWidth() || $crop->getY() > $y || $y >= $crop->getY() + $crop->getHeight()) {
            return SmartCrop::OUTSIDE_IMPORTANCE;
        }

        $xf = ($x - $crop->getX()) / $crop->getWidth();
        $yf = ($y - $crop->getY()) / $crop->getHeight();

        $px = abs(0.5 - $xf) * 2;
        $py = abs(0.5 - $yf) * 2;

        $dx = max($px - 1 + SmartCrop::EDGE_RADIUS, 0);
        $dy = max($py - 1 + SmartCrop::EDGE_RADIUS, 0);
        $d = ($dx * $dx + $dy * $dy) * SmartCrop::EDGE_WEIGHT;

        $s = 1.41 - sqrt($px * $px + $py * $py);

        if ($ruleOfThirds) {
            $s += (max(0, $s+$d+0.5)*1.2)*($this->thirds($px)+$this->thirds($py));
        }

        return $s + $d;
    }

    private function thirds($x)
    {
        $x = ((fmod($x - (1 / 3) + 1, 2)) * 0.5 - 0.5) * 16;
        return max(1-$x*$x, 0);
    }
}