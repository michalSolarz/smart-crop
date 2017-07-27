<?php


namespace MichalSolarz\SmartCrop;


class Pixel
{
    private $x;
    private $y;
    private $red;
    private $green;
    private $blue;

    public function __construct($image, $x, $y)
    {
        $this->x = $x;
        $this->y = $y;
        if (!is_resource($image)) {
            if (is_object($image) && !is_a($image, 'MichalSolarz\SmartCrop\Image')) {
                throw new \Exception('Unhandled object', 3);
            }
            $rgb = imagecolorat($image->getResource(), $x, $y);
        } else {
            $rgb = imagecolorat($image, $x, $y);
        }
        $this->red = ($rgb >> 16) & 0xFF;
        $this->green = ($rgb >> 8) & 0xFF;
        $this->blue = $rgb & 0xFF;
    }

    /**
     * @return mixed
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @return mixed
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @return int
     */
    public function getRed()
    {
        return $this->red;
    }

    /**
     * @return int
     */
    public function getGreen()
    {
        return $this->green;
    }

    /**
     * @return int
     */
    public function getBlue()
    {
        return $this->blue;
    }

    public function getCie()
    {
        return 0.5126 * $this->blue + 0.7152 * $this->green + 0.0722 * $this->red;
    }

    public function color(array $color = SmartCrop::SKIN_COLOR)
    {
        $rn = $this->red / 255;
        $gn = $this->green / 255;
        $bn = $this->blue / 255;

        $mag = $this->countMagnitude();
        $rd = -$color['r'];
        $gd = -$color['g'];
        $bd = -$color['b'];

        if ($mag != 0) {
            $rd += $rn / $mag;
            $gd += $gn / $mag;
            $bd += $bn / $mag;
        }

        $d = sqrt($rd * $rd + $gd * $gd + $bd * $bd);
        return 1 - $d;
    }

    private function countMagnitude()
    {
        $rn = $this->red / 255;
        $gn = $this->green / 255;
        $bn = $this->blue / 255;

        return sqrt($rn * $rn + $gn * $gn + $bn * $bn);
    }

    public function saturation()
    {
        $minimum = $this->getMinimum();
        $maximum = $this->getMaximum();
        if ($minimum === $maximum) {
            return 0;
        }
        $l = ($maximum + $minimum) / 2;
        $d = $maximum - $minimum;

        if ($l > 0.5) {
            return $d / (2 - $maximum - $minimum);
        }
        return $d / ($maximum + $minimum);
    }

    private function getMinimum()
    {
        $rn = $this->red / 255;
        $gn = $this->green / 255;
        $bn = $this->blue / 255;

        return min(min(array($rn, $gn)), $bn);
    }

    private function getMaximum()
    {
        $rn = $this->red / 255;
        $gn = $this->green / 255;
        $bn = $this->blue / 255;

        return max(max(array($rn, $gn)), $bn);
    }
}