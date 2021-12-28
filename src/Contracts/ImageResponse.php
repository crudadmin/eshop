<?php

namespace AdminEshop\Contracts;

class ImageResponse
{
    public $x1;

    public $x2;

    public function __construct($image)
    {
        $this->x1 = $image->url;

        if ( $file = $this->getResizeParams($image) ) {
            $this->x2 = $file->url;
        }
    }

    private function getResizeParams($image)
    {
        $params = @$image->resizeParams ?: [];

        if ( count($params) == 0 ){
            return false;
        }

        $params = array_map(function($value){
            return $value * 2;
        }, $params);

        if ( $image = $image->getOriginalObject() ) {
            return $image->resize(...array_map(function($dim){
                return $dim ?: null;
            }, $params));
        }
    }

    public static function productHeadImage($obj)
    {
        return new static($obj->resize(430));
    }
}