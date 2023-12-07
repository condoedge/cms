<?php

namespace Anonimatrix\PageEditor\Features;

class TeamsService
{
    protected $teamClass = [];

    public function setTeamClass($class = null)
    {
        $this->teamClass = $class ?? config('page-editor.teams.model');
    }

    public function getTeamClass()
    {
        return $this->teamClass;
    }
}