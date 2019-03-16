<?php

namespace App\Services;

class PictureService
{
    public function getPictureExtensionFromBinary(string $picture) : string
    {
        $mime = getimagesizefromstring($picture)['mime'];

        $segments = explode('/', $mime);

        return $segments[1];
    }
}
