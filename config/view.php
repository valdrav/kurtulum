<?php

return [

    'paths' => [
        resource_path('views'),
    ],

    /*
    | realpath() klasör yoksa false döner ve Blade "valid cache path" hatası verir.
    | Plesk/Git deploy'da storage/framework/views Git'te olmayabilir.
    */
    'compiled' => env(
        'VIEW_COMPILED_PATH',
        storage_path('framework/views'),
    ),

];
