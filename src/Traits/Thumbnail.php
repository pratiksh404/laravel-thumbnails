<?php

namespace drh2so4\Thumbnail\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image as Image;

trait Thumbnail
{
    public function makeThumbnail($fieldname = "image", $custom = [])
    {

        if (!empty(request()->$fieldname) && request()->has($fieldname) || $custom['image']) {

            /* ------------------------------------------------------------------- */

            $image_file = $custom['image'] ?? request()->file($fieldname); // Retriving Image File
            $filenamewithextension  = $image_file->getClientOriginalName(); //Retriving Full Image Name
            $raw_filename = pathinfo($filenamewithextension, PATHINFO_FILENAME); //Retriving Image Raw Filename only
            $filename = str_replace('-', '', $raw_filename); // Retrive Filename
            $extension = $image_file->getClientOriginalExtension(); //Retriving Image extension
            $imageStoreNameOnly = $filename . "-" . time(); //Making Image Store name
            $imageStoreName = $filename . "-" . time() . "." . $extension; //Making Image Store name

            /* ------------------------------------------------------------------- */

            /* ----------------------------------------Image Upload----------------------------------------- */
            $img = $custom['image'] ?? request()->$fieldname;
            $this->update([
                $fieldname => $img->storeAs($custom['storage'] ?? config("thumbnail.storage_path", "uploads"), $imageStoreName, 'public')
            ]);
            /* --------------------------------------------------------------------------------------------- */

            $image = Image::cache(function ($cached_img) use ($image_file, $custom) {
                return $cached_img->make($image_file->getRealPath())->fit($custom['width'] ?? config('thumbnail.img_width', 1000), $custom['height'] ?? config('thumbnail.img_height', 800));
            }, config('thumbnail.image_cached_time', 10), true);
            $image->save(public_path('storage/' . $this->$fieldname), $custom['quality'] ?? config('thumbnail.image_quality', 80));

            if (config('thumbnail.thumbnail', true)) {
                $thumbnails = false;
                $thumbnails = $custom['thumbnails'] ?? config('thumbnail.thumbnails', false) ?? false;
                $storage = $custom['storage'] ?? config('thumbnail.thumbnails_storage', false) ?? false;
                if ($thumbnails) {
                    /* --------------------------------Custom Thumbnails------------------------------------------------- */
                    foreach ($thumbnails as $thumbnail) {
                        $customthumbnail = $imageStoreNameOnly  . '-' . $thumbnail['thumbnail-name'] . '.' . $extension; // Making Thumbnail Name
                        $custom_thumbnail = $image_file->storeAs($storage ?? config("thumbnail.storage_path", "uploads"), $customthumbnail, 'public'); // Thumbnail Storage Information
                        $make_custom_thumbnail = Image::cache(function ($cached_img) use ($image_file, $thumbnail) {
                            return $cached_img->make($image_file->getRealPath())->fit($thumbnail['thumbnail-width'], $thumbnail['thumbnail-height']);
                        }, config('thumbnail.image_cached_time', 10), true); //Storing Thumbnail
                        $make_custom_thumbnail->save(public_path('storage/' . $custom_thumbnail), $thumbnail['thumbnail-quality']); //Storing Thumbnail 
                    }
                    /* -------------------------------------------------------------------------------------------------- */
                } else {
                    /* --------------------- Thumbnail Info--------------------------------- */
                    //small thumbnail name
                    $smallthumbnail =  $imageStoreNameOnly  . '-small' . '.' . $extension; // Making Thumbnail Name

                    //medium thumbnail name
                    $mediumthumbnail =  $imageStoreNameOnly  . '-medium' . '.' . $extension; // Making Thumbnail Name

                    $small_thumbnail = $image_file->storeAs(config("thumbnail.storage_path", "uploads"), $smallthumbnail, 'public'); // Thumbnail Storage Information
                    $medium_thumbnail = $image_file->storeAs(config("thumbnail.storage_path", "uploads"), $mediumthumbnail, 'public'); // Thumbnail Storage Information

                    /* --------------------------------- Saving Thumbnail------------------------------------ */

                    $medium_img = Image::cache(function ($cached_img) use ($image_file) {
                        return $cached_img->make($image_file->getRealPath())->fit(config('thumbnail.medium_thumbnail_width', 800), config('thumbnail.medium_thumbnail_height', 600)); //Storing Thumbnail
                    }, config('thumbnail.image_cached_time', 10), true);

                    $medium_img->save(public_path('storage/' . $medium_thumbnail), config('thumbnail.medium_thumbnail_quality', 60)); //Storing Thumbnail

                    $small_img = Image::cache(function ($cached_img) use ($image_file) {
                        return $cached_img->make($image_file->getRealPath())->fit(config('thumbnail.small_thumbnail_width', 400), config('thumbnail.small_thumbnail_height', 300)); //Storing Thumbnail
                    }, config('thumbnail.image_cached_time', 10), true);

                    $small_img->save(public_path('storage/' . $small_thumbnail), config('thumbnail.small_thumbnail_quality', 30)); //Storing Thumbnail

                    /* ------------------------------------------------------------------------------------- */
                }
            }
        }
    }

    // Thumbnail Return
    public function thumbnail($fieldname = "image", $size)
    {
        return $this->imageDetail($fieldname = "image", $size)->path;
    }

    /* Image Details */
    public function imageDetail($fieldname = "image", $size = null)
    {
        $image = $this->$fieldname;
        $path = explode("/", $image);
        $extension = \File::extension($image);
        $name = basename($image, "." . $extension);
        $image_fullname = isset($size) ? $name . "-" . (string) $size . "." . $extension : $name . "." . $extension;
        array_pop($path);
        $location = implode("/", $path);
        $path = "storage/" . $location . "/" . $image_fullname;
        $image_files = File::files(public_path('storage/' . $location));
        $images_property = $this->imageProperty($image_files);
        $image_detail = array(
            'image' => $image,
            'name' => $name,
            'fullname' => $image_fullname,
            'extension' => $extension,
            'path' => $path,
            'directory' => public_path('storage/' . $location),
            'location' => public_path('storage/' . $image),
            'property' => $images_property
        );

        return json_decode(json_encode($image_detail));
    }


    /* Checking Image Existance */
    public function imageExists($image)
    {
        return file_exists(public_path('storage/' . $image)) ? true : false;
    }

    // Checking Image's Thumbnail Existance
    public function hasThumbnail($image)
    {
        //
    }

    // Image Property
    private function imageProperty($image_files)
    {
        $images_property = array();
        $thumbnails_property = array();
        foreach ($image_files as $image) {
            $image_partition = explode('-', basename($image));
            if (isset($image_partition[2])) {
                $thumbnails_property['image'] = $image->getFilename() ?? null;
                $thumbnails_property['real_name'] = isset($image_partition[0]) ? $image_partition[0] : null;
                $thumbnails_property['size'] = isset($image_partition[0]) ? $image->getSize() : null;
                $thumbnails_property['created_date'] = isset($image_partition[1]) ? Carbon::createFromFormat('Y/m/d H:i:s', date('Y/m/d H:i:s', $image_partition[1])) : null;
                $thumbnails_property['directory'] = isset($image_partition[0]) ? $image->getPath() : null;
                $thumbnails_property['location'] = isset($image_partition[0]) ? $image->getRealPath() : null;
            } else {
                $images_property['real_name'] = isset($image_partition[0]) ? $image_partition[0] : null;
                $images_property['size'] = isset($image_partition[0]) ? $image->getSize() : null;
                $images_property['directory'] = isset($image_partition[0]) ? $image->getPath() : null;
                $images_property['location'] = isset($image_partition[0]) ? $image->getRealPath() : null;
            }
            if (isset($image_partition[2])) {
                $images_property['thumbnails'] = $thumbnails_property;
            }
        }
        return $images_property;
    }
}
