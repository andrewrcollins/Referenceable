<?php

// config for MohamedSaid/Referenceable
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

    /*
    |--------------------------------------------------------------------------
    | Generation Strategy
    |--------------------------------------------------------------------------
    |
    | Available strategies: 'random', 'sequential', 'template'
    | - random: Generates random strings (default behavior)
    | - sequential: Generates sequential numbers with optional prefix/suffix
    | - template: Uses format templates with placeholders
    |
    */
    'strategy' => 'random',

    /*
    |--------------------------------------------------------------------------
    | Random Generation Options
    |--------------------------------------------------------------------------
    */

    // The default length of the random part of the reference
    'length' => 6,

    // The default prefix for references
    'prefix' => '',

    // The default suffix for references
    'suffix' => '',

    // The default separator for parts of the reference
    'separator' => '-',

    // The characters to use for generating references
    'characters' => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',

    // Characters to exclude from generation (to avoid confusion)
    'excluded_characters' => '01IOL',

    // Case options: 'upper', 'lower', 'mixed'
    'case' => 'upper',

    /*
    |--------------------------------------------------------------------------
    | Sequential Generation Options
    |--------------------------------------------------------------------------
    */

    'sequential' => [
        // Starting number for sequential generation
        'start' => 1,

        // Minimum digits (pad with zeros)
        'min_digits' => 6,

        // Reset frequency: 'never', 'daily', 'monthly', 'yearly'
        'reset_frequency' => 'never',

        // Counter table name for storing sequence state
        'counter_table' => 'model_reference_counters',
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Generation Options
    |--------------------------------------------------------------------------
    |
    | Available placeholders:
    | {PREFIX} - Custom prefix
    | {SUFFIX} - Custom suffix
    | {YEAR} - Current year (4 digits)
    | {YEAR2} - Current year (2 digits)
    | {MONTH} - Current month (2 digits)
    | {DAY} - Current day (2 digits)
    | {SEQ} - Sequential number
    | {RANDOM} - Random string
    | {MODEL} - Model class name (short)
    | {TIMESTAMP} - Unix timestamp
    |
    */

    'template' => [
        // Default template format
        'format' => '{PREFIX}{YEAR}{MONTH}{SEQ}',

        // Template-specific options
        'random_length' => 4,
        'sequence_length' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Options
    |--------------------------------------------------------------------------
    */

    'validation' => [
        // Enable reference format validation
        'enabled' => true,

        // Custom validation regex pattern
        'pattern' => null,

        // Minimum reference length
        'min_length' => 3,

        // Maximum reference length
        'max_length' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Uniqueness Scope
    |--------------------------------------------------------------------------
    |
    | Options: 'global', 'model', 'tenant'
    | - global: References must be unique across all models
    | - model: References must be unique per model class
    | - tenant: References must be unique per tenant (requires tenant_column)
    |
    */

    'uniqueness_scope' => 'model',

    // Column name for tenant-based uniqueness
    'tenant_column' => 'tenant_id',

    /*
    |--------------------------------------------------------------------------
    | Collision Handling
    |--------------------------------------------------------------------------
    |
    | How to handle reference collisions:
    | - retry: Try generating again (default)
    | - fail: Throw exception
    | - append: Append increment number
    |
    */

    'collision_strategy' => 'retry',

    // Maximum retry attempts for collision resolution
    'max_retries' => 100,

    /*
    |--------------------------------------------------------------------------
    | Performance Options
    |--------------------------------------------------------------------------
    */

    'performance' => [
        // Cache configuration values
        'cache_config' => true,

        // Cache TTL in minutes
        'cache_ttl' => 60,

        // Use database transactions
        'use_transactions' => true,

        // Batch size for bulk operations
        'batch_size' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */

    'events' => [
        // Dispatch events during reference generation
        'enabled' => true,

        // Event classes to dispatch
        'reference_generating' => \MohamedSaid\Referenceable\Events\ReferenceGenerating::class,
        'reference_generated' => \MohamedSaid\Referenceable\Events\ReferenceGenerated::class,
    ],
];
