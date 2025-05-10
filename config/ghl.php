<?php

return [
    'response_type' => 'code',
    'scopes' => [
        'contacts.readonly',
        'contacts.write',
        'calendars.readonly',
        'calendars.write',
        'opportunities.readonly',
        'opportunities.write',
        'locations/customFields.readonly',
        'locations/customFields.write',
    ],
    'base_url' => 'https://services.leadconnectorhq.com/',
    'token_url' => 'https://services.leadconnectorhq.com/oauth/token',
    'token_grant_type' => 'authorization_code',
    'token_refresh' => 'refresh_token',
    'accept' => 'application/json',
    'version' => 'v1.1',
    'api_version_header' => '2021-07-28',
    'auth_base_url' => 'https://marketplace.gohighlevel.com/oauth/chooselocation',
    'opportunity_status' => 'open'
];
