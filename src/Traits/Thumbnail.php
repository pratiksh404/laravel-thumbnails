<?php

namespace drh2so4\Thumbnail\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image as Image;

trait Thumbnail
{
    public function makeThumbnail($fieldname = 'image', $custom = [])
    {
        if (!empty(request()->$fieldname) && request()->has($fieldname) || $custom['image']) {

            /* ------------------------------------------------------------------- */

            $image_file = $custom['image'] ?? request()->file($fieldname); // Retriving Image File
            $filenamewithextension = $image_file->getClientOriginalName(); //Retriving Full Image Name
            $raw_filename = pathinfo($filenamewithextension, PATHINFO_FILENAME); //Retriving Image Raw Filename only
            $filename = str_replace('-', '', $raw_filename); // Retrive Filename
            $extension = $image_file->getClientOriginalExtension(); //Retriving Image extension
            $imageStoreNameOnly = $filename.'-'.time(); //Making Image Store name
            $imageStoreName = $filename.'-'.time().'.'.$extension; //Making Image Store name

            /* ------------------------------------------------------------------- */

            /* ----------------------------------------Image Upload----------------------------------------- */
            $img = $custom['image'] ?? request()->$fieldname;
            $this->update([
                $fieldname => $img->storeAs($custom['storage'] ?? config('thumbnail.storage_path', 'uploads'), $imageStoreName, 'public'), // Storing Parent Image
            ]);
            /* --------------------------------------------------------------------------------------------- */

            $image = Image::cache(function ($cached_img) use ($image_file, $custom) {
                return $cached_img->make($image_file->getRealPath())->fit($custom['width'] ?? config('thumbnail.img_width', 1000), $custom['height'] ?? config('thumbnail.img_height', 800)); //Parent Image Interventing
            }, config('thumbnail.image_cached_time', 10), true);
            $image->save(public_path('storage/'.$this->$fieldname), $custom['quality'] ?? config('thumbnail.image_quality', 80)); // Parent Image Locating Save

            if (config('thumbnail.thumbnail', true)) {
                $thumbnails = false;
                $thumbnails = $custom['thumbnails'] ?? config('thumbnail.thumbnails', false) ?? false;
                $storage = $custom['storage'] ?? config('thumbnail.storage_path', 'uploads') ?? false;
                if ($thumbnails) {
                    /* -----------------------------------------Custom Thumbnails------------------------------------------------- */
                    $this->makeCustomThumbnails($image_file, $imageStoreNameOnly, $storage, $thumbnails);
                /* -------------------------------------------------------------------------------------------------- */
                } else {
                    /* ---------------------------------------Default Thumbnails--------------------------------------- */
                    $this->makeDefaultThumbnails($image_file, $extension, $imageStoreNameOnly);
                    /* ------------------------------------------------------------------------------------------------ */
                }
            }
        }
    }

    // Make Image
    private function makeImg($image_file, $name, $location, $width, $height, $quality)
    {
        $image = $image_file->storeAs($location, $name, 'public'); // Thumbnail Storage Information
        $img = Image::cache(function ($cached_img) use ($image_file, $width, $height) {
            return $cached_img->make($image_file->getRealPath())->fit($width, $height);
        }, config('thumbnail.image_cached_time', 10), true); //Storing Thumbnail
        $img->save(public_path('storage/'.$image), $quality); //Storing Thumbnail
    }

    // Make Custom Thumbnail
    private function makeCustomThumbnails($image_file, $imageStoreNameOnly, $storage, $thumbnails)
    {
        foreach ($thumbnails as $thumbnail) {
            $customthumbnail = $imageStoreNameOnly.'-'.str_replace('-', '', $thumbnail['thumbnail-name']).'.'.$extension; // Making Thumbnail Name
            $this->makeImg(
                $image_file,
                $customthumbnail,
                $storage,
                (int) $thumbnail['thumbnail-width'],
                (int) $thumbnail['thumbnail-height'],
                (int) $thumbnail['thumbnail-quality']
            );
        }
    }

    // Make Default Thumbnail
    private function makeDefaultThumbnails($image_file, $extension, $imageStoreNameOnly)
    {
        /* --------------------- Thumbnail Info---------------------------------------- */
        //small thumbnail name
        $smallthumbnail = $imageStoreNameOnly.'-small'.'.'.$extension; // Making Thumbnail Name

        //medium thumbnail name
        $mediumthumbnail = $imageStoreNameOnly.'-medium'.'.'.$extension; // Making Thumbnail Name

        // Medium Thumbnail
        $this->makeImg(
            $image_file,
            $mediumthumbnail,
            config('thumbnail.storage_path', 'uploads'),
            config('thumbnail.medium_thumbnail_width', 800),
            config('thumbnail.medium_thumbnail_height', 600),
            config('thumbnail.medium_thumbnail_quality', 60)
        );

        // Small Thumbnail
        $this->makeImg(
            $image_file,
            $smallthumbnail,
            config('thumbnail.storage_path', 'uploads'),
            config('thumbnail.small_thumbnail_width', 800),
            config('thumbnail.small_thumbnail_height', 600),
            config('thumbnail.small_thumbnail_quality', 60)
        );

        /* ------------------------------------------------------------------------------------- */
    }

    // Thumbnail Path
    public function thumbnail($fieldname = 'image', $size = null)
    {
        return $this->imageDetail($fieldname, $size)->path;
    }

    /* Checking Image Existance */
    private function imageExists($image)
    {
        return file_exists($image->getRealPath());
    }

    // Checking Image's Thumbnail Existance
    public function hasThumbnail($fieldname = 'image', $size = null)
    {
        return $this->imageDetail($fieldname, $size)->property->has_thumbnail;
    }

    // Thumbnail Count
    public function thumbnailCount($fieldname = 'image', $size = null)
    {
        return $this->hasThumbnail($fieldname, $size) ? $this->imageDetail($fieldname, $size)->property->thumbnail_count : 0;
    }

    /* Image Details */
    public function imageDetail($fieldname = 'image', $size = null)
    {
        $image = $this->$fieldname;
        $path = explode('/', $image);
        $extension = \File::extension($image);
        $name = basename($image, '.'.$extension);
        $image_fullname = isset($size) ? $name.'-'.(string) $size.'.'.$extension : $name.'.'.$extension;
        array_pop($path);
        $location = implode('/', $path);
        $path = 'storage/'.$location.'/'.$image_fullname;
        $image_files = File::files(public_path('storage/'.$location));
        $images_property = $this->imageProperty($image_files);
        $image_detail = [
            'image'     => $image,
            'name'      => $name,
            'fullname'  => $image_fullname,
            'extension' => $extension,
            'path'      => $path,
            'directory' => public_path('storage/'.$location),
            'location'  => public_path('storage/'.$image),
            'property'  => $images_property,
        ];

        return json_decode(json_encode($image_detail));
    }

    // Image Property
    private function imageProperty($image_files)
    {
        $thumbnail_count = 0;
        $images_property = [];
        $thumbnails_property = [];
        foreach ($image_files as $image) {
            $image_partition = explode('-', basename($image));
            if (isset($image_partition[2])) {
                $thumbnails_property['image'] = $image->getFilename() ?? null;
                $thumbnails_property['real_name'] = $image_partition[0];
                $thumbnails_property['size'] = $image->getSize();
                $thumbnails_property['created_date'] = isset($image_partition[1]) ? Carbon::createFromFormat('Y/m/d H:i:s', date('Y/m/d H:i:s', (int) $image_partition[1])) : null;
                $thumbnails_property['directory'] = $image->getPath();
                $thumbnails_property['location'] = $image->getRealPath();
                $thumbnail_exists = $this->imageExists($image);
                $images_property['has_thumbnail'] = $thumbnail_exists || $this->imageExists($image);
                $images_property['thumbnail_count'] = $thumbnail_count + 1;
            } else {
                $images_property['real_name'] = $image_partition[0];
                $images_property['size'] = $image->getSize();
                $images_property['directory'] = $image->getPath();
                $images_property['location'] = $image->getRealPath();
            }
            if (isset($image_partition[2])) {
                $images_property['thumbnails'] = $thumbnails_property;
            }
        }

        return $images_property;
    }
}
