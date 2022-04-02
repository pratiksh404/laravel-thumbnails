<?php

namespace drh2so4\Thumbnail\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;

trait Thumbnail
{
    public function makeThumbnail($fieldname = 'image', $custom = [])
    {
        if (! empty(request()->$fieldname) || $custom['image']) {
            /* ------------------------------------------------------------------- */

            $image_file = $custom['image'] ?? request()->file($fieldname); // Retriving Image File
            $extension = $this->image_info($image_file)->extension; //Retriving Image extension
            $imageStoreNameOnly = $this->image_info($image_file)->imageStoreNameOnly; //Making Image Store name

            /* ------------------------------------------------------------------- */

            /* ----------------------------------------Parent Image Upload----------------------------------------- */
            $this->uploadImage($fieldname, $custom); // Upload Parent Image
            /* --------------------------------------------------------------------------------------------- */
            if (config('thumbnail.thumbnail', true)) {
                $thumbnails = false;
                $thumbnails = $custom['thumbnails'] ?? config('thumbnail.thumbnails') ?? false; // Grab Thumbnails
                $storage = $custom['storage'] ?? config('thumbnail.storage_path', 'uploads') ?? false; // Grab Storage Info
                if ($thumbnails) {
                    /* -----------------------------------------Custom Thumbnails------------------------------------------------- */
                    $this->makeCustomThumbnails($image_file, $imageStoreNameOnly, $extension, $storage, $thumbnails);
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
    private function makeCustomThumbnails($image_file, $imageStoreNameOnly, $extension, $storage, $thumbnails)
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

    /* Image Upload Process Info */
    private function image_info($image_file)
    {
        $filenamewithextension = $image_file->getClientOriginalName(); //Retriving Full Image Name
        $raw_filename = pathinfo($filenamewithextension, PATHINFO_FILENAME); //Retriving Image Raw Filename only
        $filename = $this->validImageName($raw_filename); // Retrive Filename
        $extension = $image_file->getClientOriginalExtension(); //Retriving Image extension
        $imageStoreNameOnly = $filename.'-'.time(); //Making Image Store name
        $imageStoreName = $filename.'-'.time().'.'.$extension; //Making Image Store name

        $image_info['filenamewithextension'] = $filenamewithextension;
        $image_info['raw_filename'] = $raw_filename;
        $image_info['filename'] = $filename;
        $image_info['extension'] = $extension;
        $image_info['imageStoreNameOnly'] = $imageStoreNameOnly;
        $image_info['imageStoreName'] = $imageStoreName;

        return json_decode(json_encode($image_info));
    }

    // Upload Parent Image
    public function uploadImage($fieldname = 'image', $custom = [])
    {
        $image_file = $custom['image'] ?? request()->file($fieldname); // Retriving Image File
        $img = $custom['image'] ?? request()->$fieldname;
        $imageStoreName = $this->image_info($image_file)->imageStoreName;
        $this->update([
            $fieldname => $img->storeAs($custom['storage'] ?? config('thumbnail.storage_path', 'uploads'), $imageStoreName, 'public'), // Storing Parent Image
        ]);

        $image = Image::cache(function ($cached_img) use ($image_file, $custom) {
            return $cached_img->make($image_file->getRealPath())->fit($custom['width'] ?? config('thumbnail.img_width', 1000), $custom['height'] ?? config('thumbnail.img_height', 800)); //Parent Image Interventing
        }, config('thumbnail.image_cached_time', 10), true);
        $image->save(public_path('storage/'.$this->getRawOriginal($fieldname)), $custom['quality'] ?? config('thumbnail.image_quality', 80)); // Parent Image Locating Save
    }

    // Thumbnail Path
    public function thumbnail($fieldname = 'image', $size = null, $byLocation = false)
    {
        return $this->imageDetail($fieldname, $size, $byLocation)->path;
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
    public function imageDetail($fieldname = 'image', $size = null, $byLocation = false)
    {
        $location = $byLocation ? $fieldname : null; // Search By Location Condition
        $image = $byLocation ? ($location ?? $this->getRawOriginal($fieldname)) : $this->getRawOriginal($fieldname); // Search By Field or Location
        $path = explode('/', $image);
        $extension = \File::extension($image);
        $name = basename($image, '.'.$extension);
        $image_fullname = isset($size) ? $name.'-'.(string) $size.'.'.$extension : $name.'.'.$extension;
        array_pop($path);
        $location = implode('/', $path);
        $path = 'storage/'.$location.'/'.$image_fullname;
        $image_files = File::files(public_path('storage/'.$location));
        $images_property = $this->imageProperty($image_files, $name);
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
    private function imageProperty($image_files, $parent_name)
    {
        $images_property = [];
        $thumbnails_property = [];
        $thumbnail_count = 0;
        foreach ($image_files as $image) {
            if (strpos(basename($image), '-') === false) {
                continue;
            }

            $image_partition = explode('-', basename($image));
            if (isset($image_partition[0]) && isset($image_partition[1])) {
                $parent_thumbnail_name = $image_partition[0].'-'.$image_partition[1];
                if ($parent_name == $parent_thumbnail_name) {
                    $thumbnail_count++;
                    $thumbnail_exists = $this->imageExists($image);
                    if (isset($image_partition[2])) {
                        $thumbnails_property['image'] = $image->getFilename() ?? null;
                        $thumbnails_property['real_name'] = $image_partition[0];
                        $thumbnails_property['size'] = $image->getSize();
                        $thumbnails_property['created_date'] = isset($image_partition[1]) ? Carbon::createFromFormat('Y/m/d H:i:s', date('Y/m/d H:i:s', (int) $image_partition[1])) : null;
                        $thumbnails_property['directory'] = $image->getPath();
                        $thumbnails_property['location'] = $image->getRealPath();
                        $images_property['has_thumbnail'] = $thumbnail_exists || $this->imageExists($image);
                        $images_property['thumbnail_count'] = $thumbnail_count;
                        $thumbnails[] = $thumbnails_property;
                        $images_property['thumbnails'] = $thumbnails;
                    }
                } elseif ($image->getFileNameWithoutExtension() == $parent_name) {
                    $images_property['has_thumbnail'] = ($thumbnail_exists ?? false);
                    $images_property['real_name'] = $image_partition[0];
                    $images_property['size'] = $image->getSize();
                    $images_property['directory'] = $image->getPath();
                    $images_property['location'] = $image->getRealPath();
                } else {
                }
            }
        }

        return $images_property;
    }

    // Hard Delete
    public function hardDelete($fieldname = 'image'): void
    {
        if (File::exists($this->imageDetail($fieldname)->location)) {
            if ($this->imageDetail($fieldname)->property->has_thumbnail) {
                foreach ($this->imageDetail($fieldname)->property->thumbnails as $thumbnail) {
                    File::exists($thumbnail->location) ? File::delete($thumbnail->location) : '';
                }
            }
            File::exists($this->imageDetail($fieldname)->location) ? File::delete($this->imageDetail($fieldname)->location) : false;
        }
    }

    // Hard Delete with Parent
    public function hardDeleteWithParent($fieldname = 'image'): void
    {
        $this->hardDelete($fieldname);
        $this->delete();
    }

    // Valid Image Name
    private function validImageName($name)
    {
        return strtolower(str_replace([' ', '-', '$', '<', '>', '&', '{', '}', '*', '\\', '/', ':'.';', ',', "'", '"'], '_', trim($name)));
    }
}
