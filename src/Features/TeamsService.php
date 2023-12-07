<?php

namespace Anonimatrix\PageEditor\Features;

class TeamsService
{
    protected $teamClass = [];

    public function setTeamClass($class = null)
    {
        $this->teamClass = $class ?? config('page-editor.teams.class');
    }

    public function getTeamClass()
    {
        return $this->teamClass;
    }
}