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


    /**
     * Fields update on GHL
     */

    'fields' => [
        'guest_id',
        'guest_status',
        'guest_adults',
        'guest_children',
        'property_id',
        'reservation_id',
        'reservation_status',
        'sub_reservation_id',
        'room_id',
        'room_name',
        'room_type_id',
        'room_type_name',
        'room_type_name_short',
        'room_total',
        'url',
        'start_date',
        'end_date',
        'total_nights',
        'assigned_daily_rates',
        'daily_rate_date',
        'balance',
        'card_type',
        'card_number',
        'card_id',
    ]
    // 'eta',                  // Estimated Time Arrival
    // 'tags',                 // Flienders, New - Reservation
    // 'mark_as_lead',         // false
    // 'source',
    // 'source_id'
];
