<?php
namespace Anonimatrix\PageEditor\Http;

use Anonimatrix\PageEditor\Items\ItemTypes\ImgItem;
use Kompo\Form;

class ImageMethods extends Form
{
    public function getDefaultMaxWidth()
    {
        return ImgItem::getDefaultMaxWidth();
    }

    public function getFullView()
    {
        return ImgItem::getFullView();
    }
}