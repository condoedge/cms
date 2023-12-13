<?php

namespace Anonimatrix\PageEditor\Features;

class TeamsService
{
    protected $teamClass = [];

    public function setTeamClass($class = null)
    {
        $this->teamClass = $class ?? config('page-editor.teams.model');

        if (!in_array(\Anonimatrix\PageEditor\Models\Contracts\TeamContract::class, class_implements($this->teamClass))) {
            throw new \Exception("Class {$this->teamClass} must implement the TeamContract interface");
        }
    }

    public function getTeamClass()
    {
        return $this->teamClass;
    }
}