<?php

namespace Anonimatrix\PageEditor\Models\Traits;

use Kompo\Model;
use Kompo\Core\ImageHandler;

trait HasImageTrait
{
    public function manualUploadImage($uploadedFile)
    {
        $fileHandler = new ImageHandler();

        $fileHandler->setDisk('public');

        $model = new Model();

        foreach ($fileHandler->fileToDB($uploadedFile, $model) as $key => $value) {
            $model->{$key} = $value;
        }

        $this->attributes['image'] = json_encode($model->getAttributes(), JSON_UNESCAPED_SLASHES);
    }    
}