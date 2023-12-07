<?php

namespace Anonimatrix\PageEditor\Interfaces;

interface ObserverInterface 
{
    public function beforeUpdate($model);
    public function beforeSave($model);
    public function beforeDelete($model);
    
    public function afterUpdate($model);
    public function afterSave($model);
    public function afterDelete($model);
}