<?php

namespace Anonimatrix\PageEditor\Models\Traits;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

trait HasImageTrait
{
    public function manualUploadImage($uploadedFile, $attribute = 'image', $imageSize = null)
    {
        if(!$uploadedFile) return;

        $newExtension = $this->resolveImageExtension($uploadedFile);

        $nameWithoutExtension = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $path = 'mysql/models/' . $nameWithoutExtension . '_' . $attribute . '-' . time() . '.' . $newExtension;

        Storage::disk('public')->put($path, (string) $this->resize($uploadedFile, $imageSize, $newExtension));

        $dimensions = $this->getImageDimensions($uploadedFile);

        $this->attributes[$attribute] = json_encode([
            'mime_type' => 'image/' . $newExtension,
            'path' => $path,
            'name' => $nameWithoutExtension . '.' . $newExtension,
            'size' => Storage::disk('public')->size($path),
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'ratio' => $dimensions['ratio'],
        ], JSON_UNESCAPED_SLASHES);
    }

    public function resize($file, $width, $format = 'jpg')
    {
        $image = Image::make($file)->resize($width, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->orientate()->encode($format)->__toString();

        return $image;
    }

    /**
     * Get the original dimensions and aspect ratio of an uploaded image.
     */
    public function getImageDimensions($uploadedFile): array
    {
        $sizes = getimagesize($uploadedFile->getRealPath());

        $width = $sizes[0] ?? 0;
        $height = $sizes[1] ?? 0;
        $ratio = $height > 0 ? round($width / $height, 4) : 0;

        return [
            'width' => $width,
            'height' => $height,
            'ratio' => $ratio,
        ];
    }

    /**
     * Resolve the best output extension for the uploaded image.
     * Preserves PNG for transparency, uses jpg otherwise.
     */
    protected function resolveImageExtension($uploadedFile): string
    {
        $originalExtension = strtolower($uploadedFile->getClientOriginalExtension());

        $transparentFormats = ['png', 'webp', 'gif'];

        if (in_array($originalExtension, $transparentFormats)) {
            return $originalExtension === 'gif' ? 'png' : $originalExtension;
        }

        return 'jpg';
    }
}