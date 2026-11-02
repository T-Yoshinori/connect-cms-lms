<?php
return [
    'demo_reset' => [
        'enabled' => env('YUYU_LEARNING_DEMO_RESET_ENABLED', false),
        'course_id' => env('YUYU_LEARNING_DEMO_COURSE_ID'),
        'user_ids' => env('YUYU_LEARNING_DEMO_USER_IDS', ''),
    ],
];
