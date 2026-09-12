<?php

use Nreljed\Platform\Models\User;
use Nreljed\Platform\Posts\Models\Post;

return [

    /*
    |--------------------------------------------------------------------------
    | Orphan Media Targets
    |--------------------------------------------------------------------------
    |
    | Folder => model class pairs scanned by the core:cleanup-orphan-media
    | command. Each model class must expose a getReferencedPaths() method that
    | returns the storage paths it references.
    |
    | Consuming apps publish this file (php artisan vendor:publish --tag
    | core-config) and add their own folders/models here.
    |
    */

    'orphan_media' => [
        'posts' => Post::class,
        'avatars' => User::class,
    ],

];