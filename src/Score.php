<?php


namespace MichalSolarz\SmartCrop;


class Score
{
    private $detail;
    private $saturation;
    private $skin;

    public function addToDetail($value)
    {
        $this->detail += $value;
    }

    public function addToSaturation($value)
    {
        $this->saturation += $value;
    }

    public function addToSkin($value)
    {
        $this->skin += $value;
    }

    /**
     * @return mixed
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * @return mixed
     */
    public function getSaturation()
    {
        return $this->saturation;
    }

    /**
     * @return mixed
     */
    public function getSkin()
    {
        return $this->skin;
    }

    /**
     * @return mixed
     */
    public function getTotal()
    {
        return $this->detail + $this->saturation + $this->skin;
    }
}