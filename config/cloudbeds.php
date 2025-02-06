<?php

return [

    'response_type' => 'code',
    'scopes' => [
        'read:guest',
        'write:guest',
        'read:reservation',
        'write:reservation',
    ],
    'base_url' => 'https://hotels.cloudbeds.com/api/v1.1/',
    'accept' => 'application/json',
    'version' => 'v1.1',
    'api_version_header' => '2021-07-28',
];
