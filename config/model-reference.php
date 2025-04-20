<?php

// config for MoSaid/ModelReference
return [
    /*
    |--------------------------------------------------------------------------
    | Reference Options
    |--------------------------------------------------------------------------
    |
    | This file is for setting the default options for model references.
    | These values will be used if not specified in the model.
    |
    */

    // The default column name to store the reference
    'column_name' => 'reference',

    // The default length of the random part of the reference
    'length' => 6,

    // The default prefix for references
    'prefix' => '',

    // The default suffix for references
    'suffix' => '',

    // The default separator for parts of the reference
    'separator' => '-',

    // The characters to use for generating references
    'characters' => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'
];
