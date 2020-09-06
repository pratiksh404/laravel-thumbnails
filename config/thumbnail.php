<?php

return [


    /*
    |--------------------------------------------------------------------------
    |  Thumbnail Feature
    |--------------------------------------------------------------------------
    |
    | This option defines whether to use Package's Thumbnail feature or not
    | Default option is true
    | 
    */
    'thumbnail' => true,

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Qualities
    |--------------------------------------------------------------------------
    |
    | These options are default post image and its thumbnail quality
    | 
    | 
    */

    'image_quality' => 80,
    'medium_thumbnail_quality' => 60,
    'small_thumbnail_quality' => 30,

    /*
    |--------------------------------------------------------------------------
    | Default Image Fit Size
    |--------------------------------------------------------------------------
    |
    | These options is default post imahe height and width fit size
    | 
    | 
    */

    'img_width' => 1000,
    'img_height' => 800,

    'medium_thumbnail_width' => 800,
    'medium_thumbnail_height' => 600,

    'small_thumbnail_width' => 400,
    'small_thumbnail_height' => 300,

    /*
    |--------------------------------------------------------------------------
    | Image and Thumbnail Storage Path
    |--------------------------------------------------------------------------
    |
    | Define your default image storage location along with its thumbnail
    | 
    | 
    */

    "storage_path" => "uploads",

    /*
    |--------------------------------------------------------------------------
    | Image Cached Time (Minutes)
    |--------------------------------------------------------------------------
    |
    | Option for setting image cached time
    | 
    | 
    */

    'image_cached_time' => 10,

    /*
    |--------------------------------------------------------------------------
    | Custom Thumbnail Creation
    |-------------------------------------------------------------------------- 
    | Uncomment to create...
    */

    /*     "thumbnails" => [
        [
            "thumbnail-name" => "medium",
            "thumbnail-width" => 800,
            "thumbnail-height" => 600,
            "thumbnail-quality" => 60
        ],
        [
            "thumbnail-name" => "small",
            "thumbnail-width" => 400,
            "thumbnail-height" => 300,
            "thumbnail-quality" => 30
        ],
        "thumbnails_storage" => "uploads",     
        ] */
];
