<?php

return [

    // enable or disable SSE
    'enabled' => env('SSE_ENABLED', true),

    // polling interval in seconds between requests
    'interval' => env('SSE_INTERVAL', 2),

    // append logged user id in SSE response
    'append_user_id' => env('SSE_APPEND_USER_ID', true),

    // middleware type (web  , auth  , guest)
    'middleware_type' => env('MIDDLEWARE_TYPE', 'web'),

    // keep events log in database by created at < now
    'keep_events_logs' => env('SSE_KEEP_EVENTS_LOGS', false),

    // keep events log in database by deleiverd is true
    'keep_delivered_logs' => env('SSE_KEEP_DELIVERED_LOGS', false),

    // notification settings
    'position' => 'bottomLeft', // top, topLeft, topCenter, topRight, center, centerLeft, centerRight, bottom, bottomLeft, bottomCenter, bottomRight

    //notification timeout
    'timeout' => false, // false, 1000, 3000, 3500, etc. Delay for closing event in milliseconds (ms). Set 'false' for sticky notifications.
];
