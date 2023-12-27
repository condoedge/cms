<?php

namespace Anonimatrix\PageEditor\Models\Traits;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

trait HasImageTrait
{
    public function manualUploadImage($uploadedFile, $attribute = 'image', $imageSize = null)
    {
        if(!$uploadedFile) return;
        
        $newExtension = 'jpg';

        $nameWithoutExtension = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $path = 'mysql/models/' . $nameWithoutExtension . '_' . $attribute . '-' . time() . '.' . $newExtension;

        Storage::disk('public')->put($path, (string) $this->resize($uploadedFile, $imageSize, $newExtension));

        $this->attributes[$attribute] = json_encode([
            'mime_type' => 'image/' . $newExtension,
            'path' => $path,
            'name' => $nameWithoutExtension . '.' . $newExtension,
            'size' => Storage::disk('public')->size($path),
        ], JSON_UNESCAPED_SLASHES);
    }

    public function resize($file, $width, $format = 'jpg')
    {
        $image = Image::make($file)->resize($width, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode($format)->__toString();

        return $image;
    }
}