<?php

namespace drh2so4\Thumbnail;

use Illuminate\Support\ServiceProvider;

class ThumbnailServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }
    }

    public function register()
    {
        //
    }

    public function registerPublishing()
    {
        /* Config File Publishing */
        $this->publishes([
            __DIR__ . '/../config/thumbnail.php' => config_path('thumbnail.php')
        ], 'thumbnail-config');
    }
}
