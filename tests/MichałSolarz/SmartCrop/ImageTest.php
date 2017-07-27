<?php


namespace MichałSolarz\SmartCrop;


use MichalSolarz\SmartCrop\Image;

class ImageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Exception
     */
    public function testMissingImage()
    {
        new Image('missing-image.jpg');
    }
}