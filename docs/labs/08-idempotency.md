# BL-08 — Idempotency và retry an toàn

> **Domain minh họa:** Tạo đơn hàng qua API  
> **Nhóm:** idempotency  
> **Trạng thái:** implemented locally; chưa chạy verification cho BL-08
> **Độ khó:** nền tảng đến trung cấp  
> **Mục tiêu:** cùng key + payload tạo một order logic, retry replay kết quả đã commit, còn payload khác bị conflict.
>
## 1. Tóm tắt và phạm vi

**Bài toán:** Nếu client timeout rồi gửi lại yêu cầu, hệ thống trả lại kết quả cũ hay tạo side effect mới?

**Phạm vi:** một use case cốt lõi và các trường hợp biên dưới đây. **Ngoài phạm vi:** UI sản phẩm hoàn chỉnh, tích hợp provider thật, multi-tenant phức tạp hoặc abstraction tổng quát trừ khi lab cần để chứng minh rule. Domain chỉ là phương tiện học; không dùng entity của lab khác nếu không có quan hệ nghiệp vụ thật.

**Actor:** API client đã xác thực; order service; database. Lab không gọi payment provider và không chạy worker.

## 2. Quy tắc nghiệp vụ và invariant

| ID | Quy tắc | Cách hiểu/điểm cần chốt |
| --- | --- | --- |
| BR-08-01 | Key scope theo user đã xác thực và operation version. | Tenant chưa có trong ứng dụng; scope là `(user_id, operation)`. |
| BR-08-02 | Cùng key + canonical payload trả cùng status/body đã lưu. | Payload gồm product code allowlist và quantity integer đã validate. |
| BR-08-03 | Cùng key còn hạn + payload khác trả `409`. | Không tạo order thứ hai. |
| BR-08-04 | Claim đang xử lý không tạo order song song. | `processing` và `completed` nằm trong cùng transaction; claim chưa commit không lộ cho request khác. |
| BR-08-05 | Idempotency record và order commit nguyên tử. | Unique index phân xử key; transaction retry tối đa ba lần cho deadlock. |
| BR-08-06 | Key hết hạn sau 24 giờ có thể dùng cho logical request mới. | Record được tái sử dụng nguyên tử; order cũ không bị xóa. |

### Invariants

- **INV-08-01:** Unique scope(key) trong thời hạn retention.
- **INV-08-02:** Payload fingerprint ổn định/canonical; không lưu raw sensitive payload không cần thiết.
- **INV-08-03:** Một logical request tạo tối đa một order.

## 3. Scenarios cần mô hình hóa

- **SC-08-00:** Same key/payload sequential retry yields one order.
- **SC-08-01:** Same key different payload rejected.
- **SC-08-02:** Concurrent same key yields one side effect.
- **SC-08-03:** Crash before commit allows safe retry; crash after external effect handled by outbox/dedup.
- **SC-08-04:** Expired key behavior explicitly defined.

| Điều kiện | Kết quả | Side effect |
| --- | --- | --- |
| Không có record cùng scope/key còn hạn | `201`; tạo order | Order + response snapshot + record completed cùng transaction |
| Cùng key/payload còn hạn | Replay `201` và JSON body | Không tạo order mới; header `Idempotency-Replayed: true` |
| Cùng key nhưng payload khác | `409 idempotency_key_conflict` | Không đổi dữ liệu |
| Record cùng key đang processing | `409 idempotency_request_in_progress` + `Retry-After` | Không tạo order; nhánh phòng vệ cho record chưa hoàn tất |
| Record hết hạn | Xem như request logic mới | Tái sử dụng record và tạo order mới atomically |

## 4. Mô hình dữ liệu tối thiểu

| Bảng | Field gợi ý | Ý nghĩa và bảo vệ rule |
| --- | --- | --- |
| `idempotency_records` | `user_id, operation, key_hash, request_hash, status, order_id, response_code, response_body, expires_at` | Unique `(user_id, operation, key_hash)` và `order_id`; chỉ lưu SHA-256 của key/payload; có expiry index. |
| `idempotency_orders` | `user_id, product_code, quantity, total_minor, currency, status` | Order demo; giá lấy từ catalog server, không nhận total từ client. |

### Relationship và source of truth

Mô hình phải giữ rõ aggregate sở hữu invariant. Với lab này, bảng gốc cần chịu trách nhiệm cho: Unique scope(key) trong thời hạn retention. Field nào không phục vụ rule, quyền, history hoặc query có căn cứ thì chưa thêm. Chỉ snapshot dữ liệu khi cần giữ ý nghĩa tại thời điểm phát sinh; không sao chép field vì tiện render.

## 5. Migration plan (Laravel + MySQL định hướng)

Unique composite index `(user_id,operation,key_hash)`; key header tối đa 255 ký tự và chỉ lưu SHA-256; request fingerprint SHA-256 trên payload canonical; index `expires_at`. Response JSON snapshot có version và không chứa PII. Cleanup job chưa thuộc implementation.

**Thứ tự migration:** tạo bảng gốc trước, child/history sau; FK và unique constraint được thêm cùng schema ban đầu nếu dữ liệu mới. Với bảng đã có dữ liệu, tách expand → backfill → enforce constraint khi deployment cần zero/low downtime. `down()` cần được cân nhắc theo khả năng mất dữ liệu; rollback schema không đồng nghĩa rollback business event. Viết migration/integration test trên MySQL cho constraint phụ thuộc engine; không suy luận từ SQLite.

## 6. Model, Factory và Seeder

### Model và ranh giới logic

`IdempotencyRecord`; `CreateIdempotentOrder` điều phối transaction và duplicate-key recovery. FormRequest đọc `Idempotency-Key` header và validate payload. Response JSON snapshot có version để replay.

### Factory

`IdempotencyRecordFactory` tạo processing/completed/expired record; `OrderFactory` tạo order hợp lệ. Conflict là kết quả so request hash, không phải trạng thái record.

### Seeder

Không seed order hoặc operational key. Practice tự tạo dữ liệu bằng request của user hiện tại; không có key demo dùng chung trong database.

Factory phục vụ fixture test, Seeder phục vụ demo/khởi tạo dữ liệu phát triển; không trộn hai mục đích. Factory phải tạo trạng thái nhất quán, còn scenario bất hợp lệ nên được dựng có chủ đích trong test để không vô tình dùng như dữ liệu hợp lệ.

## 7. Cấu trúc code gợi ý

```text
app/
├── Http/Requests/Idempotency/...
├── Http/Controllers/Idempotency/...
├── Models/...
├── Policies/...                 # nếu action cần authorization
├── Actions/Idempotency/...
├── Domain/Idempotency/ # pure rules/value objects nếu giúp rõ
├── Jobs/                         # chỉ khi có async work
└── Events/                       # chỉ khi event có consumer/use case
```

| Thành phần | Trách nhiệm | Tránh làm |
| --- | --- | --- |
| Form Request | Shape, required/format, authorize request cơ bản | Quyết định rule cần transaction hoặc state mới nhất |
| Controller | HTTP mapping, gọi use case, response | Tính toán nghiệp vụ dài, xử lý lock |
| Action/use case | Điều phối một hành động, transaction boundary | Trở thành class một dòng cho mọi CRUD |
| Domain function/value object | Quy tắc thuần, xác định, test nhanh | Query Eloquent hoặc gửi email |
| Eloquent model | Persistence, relation, casts | Cho phép mass assignment bypass invariant |
| Policy | Quyền actor/resource/action | Kiểm tra feature enabled thay cho business authorization |

**Không bắt buộc** tạo Repository nếu Eloquent query đơn giản và chưa cần thay persistence. Nếu query phức tạp hoặc có nhiều adapter, ghi lý do trước khi thêm abstraction.

## 8. Luồng xử lý và thuật toán

`HTTP input/header → authenticate → validate shape → canonicalize/hash → scoped DB claim → create order + response snapshot → commit → HTTP response/replay`

### Thuật toán tham chiếu

Canonicalize product code và quantity integer; hash key/payload; trong transaction khóa record scoped nếu đã có. Record còn hạn được so hash để replay/conflict; record hết hạn được reset. Record mới bắt đầu `processing`, action tạo order từ catalog server, lưu resource và exact JSON response có version, chuyển `completed`, rồi commit. Unique violation được đọc lại ngoài transaction để xử lý request cạnh tranh. Rollback trước commit xóa cả claim và order. Lab không có external side effect.

Input cần normalize một lần; thời gian/clock, rounding hoặc key generation nên inject khi cần determinism. Mọi trạng thái có thể thay đổi giữa validation và write cần được đọc lại/khóa/compare trong boundary persistence.

## 9. Phương án triển khai và trade-off

Unique constraint + transaction là nền đơn giản; lock wait/duplicate handling phải rõ. Cache/Redis key nhanh nhưng durability/eviction semantics phức tạp. Lưu response snapshot giúp replay chính xác, đổi lại cần quản lý schema version, storage và cleanup. Retention là 24 giờ, vượt retry window ngắn của demo.

| Lựa chọn | Ưu điểm | Chi phí/rủi ro | Khi chọn |
| --- | --- | --- | --- |
| Logic thuần + use case rõ | Dễ đọc, test nhanh, rule tách framework | Cần mapping lỗi/DTO | Rule có decision table hoặc nhiều entry point |
| CRUD/controller trực tiếp | Ít lớp, nhanh cho thao tác tầm thường | Rule/transaction dễ rải ra UI/controller | Chỉ khi không có invariant/race/side effect đáng kể |
| Thêm domain abstraction | Có boundary/useful language | Tăng code và onboarding | Khi nhiều rule/actor/use case thực sự dùng chung concept |

Chọn giải pháp đơn giản nhất đáp ứng rule và chứng cứ hiện tại; nâng cấp theo giới hạn đo được, không theo tên pattern.

## 10. Transaction, concurrency và failure modes

- **Concurrency:** Scope quá rộng làm lộ kết quả; fingerprint bỏ sót field; key hết hạn trước retry; duplicate insert bị map thành 500; processing record bị kẹt.
- **Transaction boundary:** xác định ghi nào phải commit cùng nhau; không giữ DB transaction qua network call.
- **Retry/idempotency:** nếu client, queue hoặc provider có thể retry, xác định logical request key/dedup/compensation.
- **Deadlock:** khóa records theo thứ tự ổn định; retry chỉ khi toàn bộ use case retry-safe.
- **Observability:** log correlation id, outcome/error code/latency; redact payload/secret. Thêm audit/metric chỉ khi cần vận hành hoặc giải thích hành vi.

## 11. Security và quyền

- Enforce authorization ở backend; kiểm tra scope khi load resource và mỗi action.
- Không tin identifier, price, status, scope hoặc expiry do client quyết định nếu server là nguồn sự thật. Product price được tra từ enum catalog; client chỉ gửi product code và quantity.
- Validate input theo allowlist và giới hạn kích thước; tránh mass assignment.
- PII/secret trong logs, histories, queues và exports phải có purpose, access, retention.
- Key scope too broad leaks result between users; fingerprint ignores meaningful field; key expires too early; duplicate inserts mapped as generic 500; in-progress stuck forever.

## 12. Test plan và bằng chứng cần thu

| Tầng | Kiểm tra | Evidence cần ghi |
| --- | --- | --- |
| Unit | Pure rule/decision table/boundaries | Case, expected result, clock/input cố định |
| Feature | Route, validation, policy, error mapping | Actor/request/response/DB assertions |
| Integration | Migration, constraints, transaction, query semantics | DB engine + version + config đã chạy |
| Concurrency | Hai connection/process nếu invariant có race | Harness, barriers, số lần lặp, outcome |
| Async/external | Queue/provider failure/retry nếu áp dụng | Fake/real service, retry configuration, limits |

- [ ] Same key/payload sequential retry yields one order — pending.
- [ ] Same key different payload rejected — pending.
- [ ] Concurrent same key yields one order — pending MySQL two-connection evidence.
- [ ] Crash before commit allows safe retry — pending.
- [ ] Expired key behavior explicitly defined — reuse sau 24 giờ; verification pending.

Đây là **kế hoạch test**, không phải khẳng định tests đã chạy. Xem [technical record](../technical/BL-08-implementation.md) để biết trạng thái verification và giới hạn. Unit test không chứng minh locking, collation, transaction isolation hay queue driver behavior.

## 13. Failure modes, edge cases và mở rộng

**Các lỗi đáng diễn tập:** Key scope too broad leaks result between users; fingerprint ignores meaningful field; key expires too early; duplicate inserts mapped as generic 500; in-progress stuck forever.

**Mở rộng sau MVP:** Async request status, provider idempotency key propagation, retention cleanup, response schema versioning.

Mỗi extension phải thêm rule và acceptance test riêng; không thêm field/bảng chỉ vì extension có thể xuất hiện.

## 14. Demo script và câu hỏi phỏng vấn

1. Nêu actor, problem và 2–3 rule chính.
2. Demo happy path.
3. Tạo boundary/failure path, quan sát lỗi người dùng có thể xử lý.
4. Mở migration/schema và chỉ invariant được DB bảo vệ.
5. Mở test chứng minh hành vi; nêu runtime/giới hạn.
6. Giải thích phương án đã bỏ và lý do.

**Câu hỏi phản biện:** Gửi request, giả lập timeout response rồi retry; đổi payload giữ key. Hỏi: disable nút gửi có xử lý case client retry không?

## 15. Chia chặng triển khai

1. Chốt actor scope, canonical payload, retention 24 giờ và decision table — **hoàn tất**.
2. Thêm migration, models, factories và synchronous atomic action — **code đã thêm; chưa chạy**.
3. Thêm authenticated API practice, rate limit và UI EN/VI — **code đã thêm; chưa chạy**.
4. Chạy migration và feature tests trên SQLite — **pending**.
5. Chạy migration MySQL, duplicate-key race hai connection và rollback — **pending**.
6. Thêm outbox/provider key/cleanup scheduler khi thuộc scope vận hành — **ngoài scope hiện tại**.

**DoD:** không đánh dấu complete khi thiếu rule, test evidence, trade-off hoặc giới hạn quan trọng.
