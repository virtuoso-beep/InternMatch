<?php

return [
    'enabled' => (bool) env('AI_ENABLED', false),
    'url' => env('AI_SERVICE_URL', 'http://ai:8000'),
    'model' => 'intfloat/multilingual-e5-base',
    'revision' => env('AI_MODEL_REVISION', 'd128750597153bb5987e10b1c3493a34e5a4502a'),
];
