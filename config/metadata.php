<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TMDb API Configuration
    |--------------------------------------------------------------------------
    */
    'tmdb' => [
        'api_key' => env('TMDB_API_KEY'),
        'base_url' => 'https://api.themoviedb.org/3',
        'image_base_url' => 'https://image.tmdb.org/t/p',
        'rate_limit' => 3, // requests per second (TMDb allows 40/10s)
    ],

    /*
    |--------------------------------------------------------------------------
    | OMDb API Configuration (Fallback)
    |--------------------------------------------------------------------------
    */
    'omdb' => [
        'api_key' => env('OMDB_API_KEY'),
        'base_url' => 'https://www.omdbapi.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Matching Configuration
    |--------------------------------------------------------------------------
    */
    'matching' => [
        'confidence_threshold' => 80,   // Below this → Admin Review Queue
        'fuzzy_threshold' => 60,   // Minimum similarity % for subtitle linking
    ],

];
