<?php

namespace Anonimatrix\PageEditor\Services;

use Anonimatrix\PageEditor\Interfaces\PageItemServiceInterface;

class PageItemService implements PageItemServiceInterface
{
    /**
     * @var array
     */
    protected $authorize = [];
    
    /**
     * Register a new authorization callback.
     *
     * @param string $action The action to authorize.
     * @param callable $callback The callback that determines if the action is authorized.
     * @return void
     */
    public function authorizationGuard(string $action, callable $callback)
    {
        $this->authorize[$action] = $callback;
    }

    /**
     * Determine if the given action is authorized.
     *
     * @param string $action The action to authorize.
     * @param mixed $model Optional model instance.
     * @return bool
     */
    public function authorize(string $action, $model = null)
    {
        if(!isset($this->authorize[$action])) return true;

        return call_user_func($this->authorize[$action], $model);
    }
}