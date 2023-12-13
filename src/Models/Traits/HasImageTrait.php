<?php

namespace Anonimatrix\PageEditor\Models\Traits;

use Kompo\Model;
use Kompo\Core\FileHandler;

trait HasImageTrait
{
    public function manualUploadImage($uploadedFile)
    {
        $fileHandler = new FileHandler();

        $fileHandler->setDisk('public');

        $model = new Model();

        foreach ($fileHandler->fileToDB($uploadedFile, $model) as $key => $value) {
            $model->{$key} = $value;
        }

        $this->attributes['image'] = json_encode($model->getAttributes(), JSON_UNESCAPED_SLASHES);
    }    
}