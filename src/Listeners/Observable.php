<?php

namespace Anonimatrix\PageEditor\Listeners;

use Anonimatrix\PageEditor\Interfaces\ObserverInterface;

trait Observable
{
    protected static $observers = [];

    protected static $callbackObservers = [];

    public static $events = [
        'after' => [
            'update',
            'save',
            'delete'
        ],
        'before' => [
            'update',
            'save',
            'delete'
        ],
    ];

    public function __call($method, $args)
    {
        if (in_array($method, $this->events['after'])) {
            call_user_func_array([$this, $method], $args);
            $this->notifyObservers('after' . ucfirst($method), $args[0]);
        } else if (in_array($method, $this->events['before'])) {
            $this->notifyObservers('before' . ucfirst($method), $args[0]);
        }

        call_user_func_array([$this, $method], $args);
    }

    public static function __callStatic($method, $args)
    {
        $isObserverMethod = collect(static::$events)->first(fn($event, $time) => strpos($method, $time) === 0 && in_array(substr($method, strlen($time)), $event));

        if(!$isObserverMethod) return;

        static::$callbackObservers[$method] = $args[0];
    }

    public function observe(ObserverInterface $observer)
    {
        $this->observers[] = $observer;
    }

    public function notifyObservers($event, $model)
    {
        foreach ($this->observers as $observer) {
            $observer->$event($model);
        }

        if (isset($this->callbackObservers[$event])) {
            call_user_func($this->callbackObservers[$event], $model);
        }
    }
}