<?php

namespace Anonimatrix\PageEditor\Interfaces;

use Anonimatrix\PageEditor\Interfaces\ObserverInterface;

interface ObservableInterface
{
    public function observe(ObserverInterface $observer);
}