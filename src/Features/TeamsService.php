<?php

namespace Anonimatrix\PageEditor\Features;

class TeamsService
{
    protected $teamClass = [];

    public function setTeamClass($class = null)
    {
        $this->teamClass = $class ?? config('page-editor.teams.model');

        if (class_implements($class, \Anonimatrix\PageEditor\Models\Contracts\TeamContract::class)) {
            throw new \Exception("Class $class must implement the TeamContract interface");
        }
    }

    public function getTeamClass()
    {
        return $this->teamClass;
    }
}