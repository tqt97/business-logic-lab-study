# BL-08 Technical Implementation Record

> Cập nhật: 2026-11-14
> Phạm vi: `laravel/`
> Trạng thái: implementation đã thêm; chưa chạy verification, migration hoặc concurrency harness.

## 1. Business boundary

BL-08 minh họa synchronous order API với client đã xác thực. Key được scope theo `(user_id, operation)`; operation hiện tại là `orders.create.v1`. Client gửi `Idempotency-Key` và payload gồm product code cùng quantity. Giá được tính từ enum catalog phía server.

Payload fingerprint được tạo từ product code canonical và quantity integer. Cùng key còn hạn với fingerprint giống nhau nhận đúng response status/body đã lưu; fingerprint khác trả `409`. Key có hiệu lực 24 giờ. Sau expiry, record được dùng lại transactionally cho logical request mới; order cũ vẫn được giữ. `expires_at` hết hạn không tự xóa record hoặc snapshot, nên đây là deduplication window chứ chưa phải bảo đảm purge dữ liệu.

Không có payment provider, queue worker hoặc external side effect. Cleanup scheduler cho record hết hạn là bước vận hành tương lai.

## 2. Data model

- `idempotency_orders`: user, product code, quantity, server-calculated `total_minor`, currency và status.
- `idempotency_records`: user, operation, SHA-256 key hash, canonical request hash, `processing|completed`, nullable order reference, response status/body và expiry.
- Unique `(user_id, operation, key_hash)` arbitrates duplicate claims; unique `order_id` keeps the order link one-to-one. Index `expires_at` hỗ trợ retention cleanup.
- Raw key và request body không được lưu. Response snapshot có `response_version` và không chứa PII.

`processing` được ghi bên trong cùng transaction và không thể quan sát từ request khác khi transaction chưa commit. Nhánh `409 in progress` là defensive behavior cho record chưa hoàn tất ngoài protocol bình thường.

## 3. Write protocol

```text
authenticate + validate header/payload
  → canonicalize product_code + quantity; hash request and key
  → transaction: lock existing scoped record
  → replay/conflict if record is unexpired
  → otherwise insert/reset processing record
  → create order from server catalog
  → save order reference + exact versioned response + completed status
  → commit
```

Unique insert collision is caught after rollback, then the committed record is loaded to return replay or payload conflict. Laravel transaction retries up to three times for deadlock. A crash before commit rolls back both order and idempotency record. No network call occurs inside the transaction.

## 4. HTTP contract and security

- Authenticated `POST /labs/idempotency/orders`; lab availability middleware and per-user rate limit apply.
- Request header: `Idempotency-Key` (required, max 255 characters). Body fields: allowlisted `product_code`, `quantity` from 1 to 50.
- Initial response and replay return the persisted HTTP `201` status and same JSON body. `Idempotency-Replayed` is `false` for initial creation and `true` for replay.
- Same unexpired key with different payload returns `409 idempotency_key_conflict`; a visible processing record returns `409 idempotency_request_in_progress` plus `Retry-After`.
- Expired key is eligible for a new order. The record and response are overwritten atomically while prior order data remains.
- User scope comes from authentication, never request input. Product prices are server-owned; raw key, payload and response are not logged by this feature.

## 5. UI learning contract

Practice sends the header using `fetch`, retains the entered key and payload after network failure, and displays the HTTP status, replay header and JSON body. Learners can resend unchanged fields or alter a field while keeping the same key. EN/VI content uses the shared learning overview and production-learning components.

No operational order or reusable key is seeded. The practice creates rows for the current user.

## 6. Verification

| Check | Result |
|---|---|
| Automated tests | Not run |
| Migration on SQLite/MySQL | Not run |
| Same-key two-connection race | Pending |
| Failure/rollback before commit | Pending |
| EN/VI parity and rendered UI | Not run |

No runtime correctness or concurrency claim is made until migration and focused feature tests run. Required cases include replay response equivalence, changed-payload conflict, actor scope, expired key reuse, transaction rollback, validation, rate limiting and a two-connection MySQL race.

## 7. Next boundary

1. Run migration and focused feature tests on SQLite.
2. Run migration and duplicate-key race on MySQL using independent connections.
3. Add scheduled cleanup and retention monitoring if operational volume warrants it.
4. Add an outbox and provider idempotency propagation only if order creation gains external effects.
