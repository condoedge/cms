<?php

namespace Anonimatrix\PageEditor\Models\Contracts;

interface TeamContract
{
    public function emailLogoHtml(): string;

    public function getLinkHtmlToSubscribe(): string;
}