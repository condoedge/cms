<?php

namespace Anonimatrix\PageEditor\Traits;

use Illuminate\Database\Eloquent\Model;
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