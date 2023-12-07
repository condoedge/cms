<?php

namespace Anonimatrix\PageEditor;

use Anonimatrix\PageEditor\Interfaces\PageItemServiceInterface;

class PageItemService implements PageItemServiceInterface
{
    protected $authorize = [];

    public function authorizationGuard(string $action, callable $callback)
    {
        $this->authorize[$action] = $callback;
    }

    public function authorize(string $action, $model = null)
    {
        if(!isset($this->authorize[$action])) return true;

        return call_user_func($this->authorize[$action], $model);
    }
}