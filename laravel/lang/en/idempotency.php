<?php

return [
    'page' => [
        'title' => 'BL-08 Idempotency and safe retries',
        'description' => 'Create an order once, detect changed retries, and replay the saved response within a defined retention window.',
        'back' => 'Back to dashboard',
        'eyebrow' => 'Retry safety',
        'level' => 'Intermediate',
        'heading' => 'One client intent, one order result',
    ],
    'practice' => [
        'divider' => 'Practice', 'label' => 'Try the API flow', 'title' => 'Create an order with an idempotency key',
        'description' => 'The browser sends Idempotency-Key as an HTTP header. Retry with the same key and fields to replay the saved response; change a business field without changing the key to get a conflict.',
        'product' => 'Product', 'quantity' => 'Quantity', 'quantity_hint' => 'Choose an integer from 1 to 50. Prices come from the server catalog.',
        'key' => 'Idempotency key', 'key_hint' => 'Keep this key to retry the same intent. It is hashed before storage.',
        'submit' => 'Send order request', 'new_key' => 'Generate a new key', 'response_title' => 'HTTP response',
        'response_hint' => 'The complete JSON response is shown below. A replay keeps the original status and body.',
        'replayed' => 'Replay detected', 'first_response' => 'New result', 'http_status' => 'HTTP status', 'request_error' => 'Request rejected',
        'network_error' => 'The request could not be completed. Retry with the same key and unchanged fields.',
        'noscript' => 'JavaScript is required for this interactive API practice.',
        'orders' => 'Recent orders', 'empty_orders' => 'No orders have been created for this account yet.',
        'order_row' => 'Order #:id · :product · quantity :quantity · :currency :amount · :status',
        'status_created' => 'Created',
    ],
    'products' => [
        'starter-kit' => 'Starter kit — USD 12.99 each',
        'course-pass' => 'Course pass — USD 129.00 each',
    ],
    'validation' => [
        'key_required' => 'Send a non-empty Idempotency-Key header.',
        'key_invalid' => 'The idempotency key must be text no longer than 255 characters.',
        'product' => 'Choose one of the available products.',
        'quantity' => 'Quantity must be an integer from 1 to 50.',
    ],
    'errors' => [
        'key_conflict' => 'This key was already used with a different order request. Use the original fields or generate a new key.',
        'in_progress' => 'A request with this key is still being processed. Retry shortly with the same key.',
    ],
];
