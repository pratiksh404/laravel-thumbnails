## Laravel Thumbnail Generator

Package for uploading the image and saving that image along with it's thumbnail.

## What does it do ?

- Uploads Image
- Make its thumbnail i.e low quality, resized version of its parent image

## Why use thubnails ?

The small file size of thumbnails makes it possible for website designers to offer visitors a lot of content immediately without increasing the loading time of the page.
Also why use full glory of that image if you just have to crunched it up to tiny space... Use thumbnail.

### Installation

Run Composer Require Command

```sh
$ composer require drh2so4/thumbnail
```

Use thumbnail trait to your model

```sh
<?php

namespace App;

use drh2so4\Thumbnail\Traits\thumbnail;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use thumbnail;

    protected $fillable = ['image'];
}

```

This model initially consists of two methods :-

- makeThumbnail
- thumbnail

### Usages

Package utilizes it's trait method, let us guide you to use that

## makeThumbnail

This method is responsible for actually uploading the image and making its thumbnail.
It takes one parameter i.e image fieldname (default = "image").

```sh
    public function store(Request $request)
    {
        $image = Image::create($this->validateData());
        $image->makeThumbnail('image'); //This handles uploading image and storing it's thumbnail
        return redirect('/imageUpload');
    }
```

Same can go with update method.

## thumbnail

Well, we created our thumbnail but how to use it, let me guide through that
When we uploaded image with name suppose "batman".
Image is upload with name batman-current_time_instant i.e (batman-1521549.jpg).

What about thumbnail...
well thumbnail uses it's parent image name followed by -size
i.e batman-1521549-medium-jpg, batman-1521549-small.jpg

## How to use thumbnail ?

Just call as following

```sh
    @foreach ($images as $image)
        <img src="{{asset($image->thumbnail('small'))}}"> // For small thumbnail
    <img src="{{asset($image->thumbnail('medium'))}}"> // For medium thumbnail
    @endforeach
```

Thumbnail's image property is predefined but if you wish to change that publish it's config file thumbnail.php

```sh
php artisan vendor:publish --tag=thumbnail-config
```

Our config file looks like follows :-

```sh
<?php

return [


    /*
    |--------------------------------------------------------------------------
    |  Thumbnail Feature
    |--------------------------------------------------------------------------
    |
    | This option defines whether to use Package's Thumbnail Featured or not
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

    "storage_path" => "uploads"

];

```

Feel free to change the values

## Thumbnail

| Thumbnail        | Width | Height | Quality |
| ---------------- | ----- | ------ | ------- |
| Uploaded Image   | 1000  | 800    | 80      |
| Medium Thumbnail | 800   | 600    | 60      |
| Small Thumbnail  | 400   | 300    | 30      |

Don't worry..working on making these thumbnail dynamic.. Stay tuned xoxo.

### Todos

- Dyanmic Thumbnail Categories
- More Functionality
- Maintainabilty

## Package Used

- http://image.intervention.io/

## License

MIT

**DOCTYPE NEPAL ||DR.H2SO4**
