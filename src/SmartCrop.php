<?php


namespace MichalSolarz\SmartCrop;


class SmartCrop
{
    const SKIN_COLOR = array('r' => 0.78, 'g' => 0.57, 'b' => 0.44);
    const SKIN_THRESHOLD = 0.8;
    const SKIN_BRIGHTNESS_MIN = 0.2;
    const SKIN_BRIGHTNESS_MAX = 1.0;
    const SKIN_BIAS = 0.9;

    const SATURATION_THRESHOLD = 0.4;
    const SATURATION_BRIGHTNESS_MIN = 0.05;
    const SATURATION_BRIGHTNESS_MAX = 0.9;
    const SATURATION_BIAS = 0.2;

    const SCALE_MAX = 1;
    const SCALE_MIN = 1;
    const SCALE_STEP = 0.1;

    const STEP = 8;

    const SCORE_DOWN_SAMPLE = 8;

    const OUTSIDE_IMPORTANCE = -0.5;
    CONST EDGE_RADIUS = 0.4;
    CONST EDGE_WEIGHT = -20;

    private $image;
    private $imageTransformer;

    public function __construct()
    {
        $this->imageTransformer = new ImageAnalyser();
    }

    public function loadImage($imagePath)
    {
        $this->image = new Image($imagePath);
    }

    public function getImage()
    {
        return $this->image;
    }

    public function analyseImage()
    {
        $this->imageTransformer->analyseImage($this->image);
    }
}