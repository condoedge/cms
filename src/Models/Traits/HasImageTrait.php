<?php

namespace Anonimatrix\PageEditor\Models\Traits;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

trait HasImageTrait
{
    // Hard ceiling on the encoded image bytes we keep around. Anything bigger
    // than this lands on every recipient's inbox on every newsletter open, so
    // it's worth one extra encode pass to stay reasonable.
    public const MAX_STORED_IMAGE_BYTES = 1_000_000;

    /**
     * Target storage width for a newsletter's full-size image. Twice the page's
     * content max-width to stay sharp on retina/2× displays, capped at 1600px so
     * very wide newsletter configs don't generate oversized files.
     */
    public function getNewsletterMaxImageWidth(): int
    {
        $maxWidth = $this->page?->getContentMaxWidth() ?: 700;

        return min(1600, $maxWidth * 2);
    }

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
        $img = Image::make($file)->resize($width, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->orientate();

        return $this->encodeUnderTargetSize($img, $format, static::MAX_STORED_IMAGE_BYTES);
    }

    /**
     * Encode the image under a byte budget. For lossy formats we step the
     * quality down; PNG is lossless so we shrink the canvas instead.
     */
    protected function encodeUnderTargetSize($img, string $format, int $maxBytes): string
    {
        if (in_array($format, ['jpg', 'jpeg', 'webp'], true)) {
            $encoded = '';
            foreach ([85, 75, 65, 55, 45] as $quality) {
                $encoded = (string) $img->encode($format, $quality);
                if (strlen($encoded) <= $maxBytes) return $encoded;
            }
            return $encoded;
        }

        // PNG / other lossless: highest compression level (9). If still too big,
        // shrink the image 20% per pass — usually one or two passes is enough.
        $encoded = (string) $img->encode($format, 9);
        for ($attempt = 0; $attempt < 4 && strlen($encoded) > $maxBytes; $attempt++) {
            $img = $img->resize((int) ($img->width() * 0.8), null, function ($c) {
                $c->aspectRatio();
            });
            $encoded = (string) $img->encode($format, 9);
        }
        return $encoded;
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