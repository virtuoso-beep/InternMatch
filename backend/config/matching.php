<?php

return [
    'enabled' => (bool) env('AI_ENABLED', false),
    'url' => env('AI_SERVICE_URL', 'http://ai:8000'),
    'model' => 'intfloat/multilingual-e5-base',
    'revision' => env('AI_MODEL_REVISION', 'd128750597153bb5987e10b1c3493a34e5a4502a'),
    // 'auto': ask the AI service's /recommendations/ml, which reranks with Logistic Regression only when a
    // validated model built from real coordinator judgments is installed, and otherwise returns cosine
    // order. 'off': always use plain cosine similarity.
    'reranker' => env('MATCHING_RERANKER', 'auto'),
    // Local testing only: allow the 'Update recommendations' action to train on synthetic test data. Set in
    // docker-compose.override.yml (never loaded in production); the AI service has the same switch.
    'allow_test_model' => (bool) env('ML_ALLOW_SYNTHETIC_MODEL', false),
];
