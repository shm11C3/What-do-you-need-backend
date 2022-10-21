<?php

return [
    'domain' => env('AUTH0_DOMAIN'),
    'clientId' => env('AUTH0_CLIENT_ID'),
    'clientSecret' => env('AUTH0_CLIENT_SECRET'),
    'managementId' => env('AUTH0_MANAGEMENT_ID'),
    'managementSecret' => env('AUTH0_MANAGEMENT_SECRET'),
    'testUsername' => env('AUTH0_TEST_USERNAME'),
    'testUserPass' => env('AUTH0_TEST_USER_PASS'),
    'cookieSecret' => env('AUTH0_COOKIE_SECRET'),
];
