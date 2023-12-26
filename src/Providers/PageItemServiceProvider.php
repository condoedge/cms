<?php

namespace Anonimatrix\PageEditor\Providers;

class PageItemServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('page-item-types', function () {
            return collect(config('page-editor.types'));
        });
    }

    public function boot()
    {
        \Kompo\Image::macro('pasteListener', function($id) {
            return $this->onLoad->run('() => {
                document.addEventListener("paste", function(event) {
                    const input = document.getElementById("' . $id . '");
                    input.files = event.clipboardData.files;

                    const changeImgEvent = new Event("change");
                    input.dispatchEvent(changeImgEvent);
                });
            }');
        });
    }
}