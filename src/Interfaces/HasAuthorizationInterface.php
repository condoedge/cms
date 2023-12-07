<?php

namespace Anonimatrix\PageEditor\Interfaces;

interface HasAuthorizationInterface
{
    public function authorizationGuard(string $action, callable $callback);
    public function authorize(string $action, $model = null);
}