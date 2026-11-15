<?php

return [
    'page' => [
        'title' => 'BL-08 Idempotency và retry an toàn',
        'description' => 'Tạo order một lần, nhận diện retry đã đổi input và replay response đã lưu trong thời hạn xác định.',
        'back' => 'Về dashboard',
        'eyebrow' => 'Retry an toàn',
        'level' => 'Trung cấp',
        'heading' => 'Một ý định từ client, một kết quả order',
    ],
    'practice' => [
        'divider' => 'Thực hành', 'label' => 'Thử API flow', 'title' => 'Tạo order với idempotency key',
        'description' => 'Browser gửi Idempotency-Key trong HTTP header. Retry cùng key và field để replay response đã lưu; đổi field nghiệp vụ mà giữ key sẽ nhận conflict.',
        'product' => 'Sản phẩm', 'quantity' => 'Số lượng', 'quantity_hint' => 'Nhập số nguyên từ 1 đến 50. Giá lấy từ catalog phía server.',
        'key' => 'Idempotency key', 'key_hint' => 'Giữ key này để retry cùng ý định. Server hash key trước khi lưu.',
        'submit' => 'Gửi request tạo order', 'new_key' => 'Tạo key mới', 'response_title' => 'HTTP response',
        'response_hint' => 'JSON response đầy đủ hiển thị bên dưới. Replay giữ nguyên status và body ban đầu.',
        'replayed' => 'Đã nhận diện replay', 'first_response' => 'Kết quả mới', 'http_status' => 'HTTP status', 'request_error' => 'Request bị từ chối',
        'network_error' => 'Không hoàn tất được request. Hãy retry cùng key và giữ nguyên các field.',
        'noscript' => 'Cần bật JavaScript để thực hành API tương tác này.',
        'orders' => 'Order gần đây', 'empty_orders' => 'Tài khoản chưa tạo order nào.',
        'order_row' => 'Order #:id · :product · số lượng :quantity · :currency :amount · :status',
        'status_created' => 'Đã tạo',
    ],
    'products' => [
        'starter-kit' => 'Bộ khởi đầu — USD 12.99 mỗi sản phẩm',
        'course-pass' => 'Vé khóa học — USD 129.00 mỗi vé',
    ],
    'validation' => [
        'key_required' => 'Hãy gửi Idempotency-Key header không rỗng.',
        'key_invalid' => 'Idempotency key phải là văn bản không quá 255 ký tự.',
        'product' => 'Hãy chọn một sản phẩm có trong danh sách.',
        'quantity' => 'Số lượng phải là số nguyên từ 1 đến 50.',
    ],
    'errors' => [
        'key_conflict' => 'Key này đã dùng với order request khác. Hãy gửi lại field cũ hoặc tạo key mới.',
        'in_progress' => 'Request dùng key này vẫn đang xử lý. Hãy retry sau ít giây với cùng key.',
    ],
];
