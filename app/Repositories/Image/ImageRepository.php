<?php

namespace App\Repositories\Image;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as ImageIntervention;

class ImageRepository
{

    protected $width = 512;

    protected $rootDirectory = 'uploads/images';

    protected $disk = 'public';

    protected $extension;

    protected $quality = 50;


    public function upload($image)
    {
        if (!isset($image) || !is_file($image))
            throw  new \Exception(__('lang.invalid_image'));

        $this->extension = $image->getClientOriginalExtension();

        $filename = $this->createUniqueFilename($this->extension);

        $response = Storage::disk($this->disk)->put("$this->rootDirectory/$filename", $this->resizeImage($image));
        if (!$response)
            throw  new \Exception(__('lang.error_with_uploading'));

        return $filename;
    }


    private function createUniqueFilename($extension)
    {
        return 'image_' . time() . mt_rand() . '.' . $extension;
    }


    public function setWidth(int $width)
    {
        $this->width = $width;
        return $this;
    }

    public function getWidth()
    {
        return $this->width;
    }


    public function resizeImage($image)
    {
        return ImageIntervention::make($image)->resize($this->getWidth(), null, function ($constraint) {
            $constraint->aspectRatio();
        })->encode($this->extension, $this->quality)->getEncoded();

    }
}
