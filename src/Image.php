<?php


namespace MichalSolarz\SmartCrop;


class Image
{
    private $width;
    private $height;

    private $imagePath;
    private $resource;

    public function __construct($imagePath)
    {
        $this->imagePath = $imagePath;
        $this->imageExists();
        switch (exif_imagetype($imagePath)) {
            case IMAGETYPE_PNG:
                $this->createFromPng();
                break;
            case IMAGETYPE_JPEG:
                $this->createFromJpeg();
                break;
            default:
                throw new \Exception('Unsupported image type', 2);
                break;
        }
        $this->readDimensions();
    }

    private function imageExists()
    {
        if (!file_exists($this->imagePath)) {
            throw new \Exception('File doesn\'t exist', 1);
        }
    }

    private function createFromPng()
    {
        $this->resource = imagecreatefrompng($this->imagePath);
    }

    private function createFromJpeg()
    {
        $this->resource = imagecreatefromjpeg($this->imagePath);
    }

    private function readDimensions()
    {
        $dimensions = getimagesize($this->imagePath);
        $this->width = $dimensions[0];
        $this->height = $dimensions[1];
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getName()
    {
        return explode('.', array_values(array_slice(explode('/', $this->imagePath), -1))[0])[0];
    }
}