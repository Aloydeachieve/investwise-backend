<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', '*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        'http://localhost:3000',    // Next.js development
        'http://localhost:3001',    // Alternative Next.js port
        'http://127.0.0.1:3000',   // Alternative localhost format
        'https://investwise-frontend-8paj.vercel.app',
        'https://investwise-frontend-8paj-git-main-aloysius-dominics-projects.vercel.app',
        'https://investwise-frontend-8paj-mag8v2ryu-aloysius-dominics-projects.vercel.app',
        'https://investwise-frontend-8paj-git-dev-aloysius-dominics-projects.vercel.app',
        'https://investwise-frontend-8paj-jw01s3za5-aloysius-dominics-projects.vercel.app/',
    ],

'allowed_origins_patterns' => [
    '/^https:\/\/investwise-frontend-8paj-git-[a-z0-9-]+-aloysius-dominics-projects\.vercel\.app$/',
],

    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
        'Accept',
        'Origin',
    ],

    'exposed_headers' => [
        'X-Total-Count',
        'X-Page-Count',
        'X-Per-Page',
    ],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => true,

];
