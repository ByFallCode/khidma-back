<?php

return [
    'secret' => env('JWT_SECRET', 'secretjwt'),
    'ttl' => (int) env('JWT_TTL', 36000),
];
