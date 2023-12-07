<?php

namespace Anonimatrix\PageEditor;

class PageEditor 
{
    protected $beforeSave;
    protected $afterSave;

    public function beforeSave(callable $callback)
    {
        $this->beforeSave = $callback;   
    }

    public function afterSave(callable $callback)
    {
        $this->afterSave = $callback;   
    }
}