<?php

namespace App\Services;

use Spatie\MediaLibrary\HasMedia;

class MediaService
{
    public function upload(HasMedia $model, $files, string $collection)
    {
        if (! $files) {
            return;
        }

        $files = is_array($files) ? $files : [$files];

        foreach ($files as $file) {
            $model->addMedia($file)
                ->toMediaCollection($collection);
        }
    }
}
