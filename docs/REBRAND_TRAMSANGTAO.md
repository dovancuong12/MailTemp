# Rebrand sang TramSangTao Mail

Tài liệu này ghi lại quá trình rebrand dự án từ **TempFastMail** (`tempfastmail.com`) sang **TramSangTao Mail** (`tramsangtao.com`), cùng các bước setup cần thực hiện sau khi rebrand.

---

## 1. Mục tiêu

- Đổi tên thương hiệu hiển thị: `Temp Fast Mail` / `Temporary Fast Mail` / `TempFastMail` → **TramSangTao Mail**
- Đổi domain trong privacy policy & các text public: `tempfastmail.com` → **tramsangtao.com**
- Cấu hình hệ thống để các địa chỉ email tạm sinh ra có dạng `<random>.<4 số>@tramsangtao.com`

---

## 2. Các file đã chỉnh sửa

| File | Thay đổi |
|---|---|
| `templates/base.html.twig` | Title mặc định: `Temp Fast Mail` → `TramSangTao Mail` |
| `templates/navbar.html.twig` | Brand top: `Temporary Fast Mail` → `TramSangTao Mail` |
| `templates/footer.html.twig` | Tên thương hiệu + copyright (replace tất cả `Temporary Fast Mail`) |
| `templates/home/index.html.twig` | Title trang chủ |
| `templates/blog/index.html.twig` | Title trang blog list |
| `templates/blog/view_single.html.twig` | Title trang chi tiết blog |
| `templates/security/login.html.twig` | Title trang login |
| `templates/static_pages/privacy_policy.html.twig` | Toàn bộ `TempFastMail` → `TramSangTao Mail`, `TEMPFASTMAIL` → `TRAMSANGTAO MAIL`, `tempfastmail.com` → `tramsangtao.com`, đổi mailto + tên Ltd |
| `templates/components/faq.html.twig` | Heading "What is Temporary Fast Email Box?" → "What is TramSangTao Mail?" |
| `assets/react/components/email/generator.tsx` | Text dưới hero: `Temp Fast Mail` → `TramSangTao Mail` |
| `src/Controller/Admin/DashboardController.php` | Admin dashboard title |

## 3. Các file KHÔNG chỉnh sửa (cố ý)

| File | Lý do |
|---|---|
| `README.md` | Tài liệu của dự án upstream, không hiển thị cho user cuối |
| `Makefile` | Chỉ chứa lệnh dev/ops, không user-facing |
| `docker-compose.yaml`, `docker-compose.demo.yaml` | `container_name: tempfastmail_*` chỉ là tên container Docker, không ảnh hưởng domain |
| Link GitHub `kasteckis/tempfastmail` trong `footer.html.twig` & `faq.html.twig` | Giữ ghi nhận tác giả gốc theo MIT license. Có thể bỏ nếu muốn (xem mục **Optional**) |
| `docs/CLOUDFLARE_EMAIL_WORKER_SETUP.md` | Hướng dẫn upstream giữ nguyên |
| `src/Entity/*`, migrations, schema | Domain dùng để tạo email **không hardcode trong code** — nó được lưu trong bảng `domain` của DB và admin add qua giao diện `/admin` |

---

## 4. Bước setup sau khi rebrand

### Bước 1 — Build lại frontend

Vì đã sửa file `.tsx`, cần rebuild webpack bundle.

**Cách 1: Build trực tiếp trong container đang chạy (dev hoặc prod đang sống)**
```bash
docker exec -it tempfastmail_php npm run build
```

**Cách 2: Rebuild prod image (khi deploy)**
```bash
docker compose -f docker-compose.demo.yaml build --no-cache php
docker compose -f docker-compose.demo.yaml up -d php
```

Sau khi build xong, refresh trình duyệt (Ctrl+F5) để load asset mới.

### Bước 2 — Clear cache Symfony (production)

Nếu chạy `APP_ENV=prod`, Twig templates được cache. Cần clear:
```bash
docker exec -it tempfastmail_php php bin/console cache:clear --env=prod
```

### Bước 3 — Add domain `tramsangtao.com` vào DB

1. Đăng nhập `https://<your-deployed-url>/admin`
   - Default: `admin@admin.dev` / `admin` (tạo bởi `MakeUserCommand` lúc khởi động)
2. Menu trái → **Domains** → nút **+ Add Domain** (góc trên phải)
3. Điền:
   - **domain**: `tramsangtao.com` (không có `@`, không có `https://`, không có path)
   - **activeUntil**: để mặc định (+1 năm) hoặc chọn ngày xa hơn
4. **Save**

Sau đó các email sinh ra sẽ có dạng:
```
john.doe.1234@tramsangtao.com
mary.smith.5678@tramsangtao.com
```

### Bước 4 — Vô hiệu các domain cũ (nếu cần)

Generator chọn **random** giữa các domain còn `activeUntil >= now`. Nếu trong DB còn domain cũ (vd `kasteckis-test.com`), một số user sẽ nhận email ở domain đó.

Cách xử lý:
- **Vào `/admin` → Domains**
- Với mỗi domain cũ: bấm **Edit** → đổi `activeUntil` về 1 ngày trong quá khứ → Save  
  HOẶC bấm **Delete** để xóa hẳn
- Chỉ giữ `tramsangtao.com` đang active

### Bước 5 — Cấu hình Cloudflare Email Worker

Đây là bước quan trọng nhất để **email thật sự đến được hệ thống của bạn**. Nếu bỏ qua bước này, người dùng vẫn nhận được địa chỉ `@tramsangtao.com` nhưng không có email nào tới được inbox.

#### 5.1. Trỏ domain về Cloudflare

1. Tạo tài khoản Cloudflare miễn phí: https://cloudflare.com/
2. **Add Site** → nhập `tramsangtao.com` → chọn plan **Free**
3. Cloudflare sẽ cung cấp 2 nameserver (vd: `ned.ns.cloudflare.com`, `kate.ns.cloudflare.com`)
4. Vào trang quản lý domain ở registrar (nơi mua domain) → đổi nameserver về 2 NS trên
5. Đợi DNS propagation (vài phút đến 24h, thường < 30 phút)

#### 5.2. Bật Email Routing

1. Cloudflare dashboard → chọn `tramsangtao.com`
2. Sidebar trái → **Email** → **Email Routing**
3. Bấm **Enable Email Routing** → **Add records and enable**
4. Cloudflare sẽ tự thêm các MX + TXT records

#### 5.3. Tạo Email Worker

Worker này nhận email từ Cloudflare và POST sang API `/api/email` của TramSangTao Mail.

1. Sidebar trái → **Workers & Pages** → **Create Worker**
2. Đặt tên (vd: `tramsangtao-email-forwarder`) → **Deploy**
3. Vào worker vừa tạo → **Edit code** → paste code từ repo:
   - https://github.com/kasteckis/cloudflare-email-worker-html-parser
4. Trong worker code, sửa 2 hằng số:
   ```js
   const API_URL = 'https://tramsangtao.com/api/email';
   const AUTHORIZATION_KEY = '<giá trị từ .env CREATE_RECEIVED_EMAIL_API_AUTHORIZATION_KEY>';
   ```
   `AUTHORIZATION_KEY` phải khớp với biến `CREATE_RECEIVED_EMAIL_API_AUTHORIZATION_KEY` trong file `.env` của server.
5. **Save and Deploy**

#### 5.4. Routing rule: catch-all → Worker

1. Quay lại **Email** → **Email Routing** → tab **Routing rules**
2. Tìm **Catch-all address** → Edit
3. **Action**: chọn **Send to a Worker**
4. **Destination**: chọn worker `tramsangtao-email-forwarder`
5. **Save** + **Enable**

#### 5.5. Test

Gửi 1 email từ Gmail/Outlook đến địa chỉ vừa generate ở trang chủ (vd: `john.doe.1234@tramsangtao.com`). Trong vòng 5-10 giây, email sẽ xuất hiện ở inbox web.

---

## 5. Bảo mật quan trọng

### Đổi `APP_SECRET` và `CREATE_RECEIVED_EMAIL_API_AUTHORIZATION_KEY`

Trong file `.env`, **bắt buộc đổi** 2 giá trị này trước khi deploy production:

```env
APP_SECRET=<random 32+ chars>
CREATE_RECEIVED_EMAIL_API_AUTHORIZATION_KEY=<random 32+ chars>
```

Sinh giá trị ngẫu nhiên:
```bash
# Linux/Mac
openssl rand -hex 32

# PowerShell (Windows)
-join ((48..57) + (97..122) | Get-Random -Count 64 | ForEach-Object {[char]$_})
```

`CREATE_RECEIVED_EMAIL_API_AUTHORIZATION_KEY` phải đồng bộ với hằng số `AUTHORIZATION_KEY` trong Cloudflare Worker.

### Đổi mật khẩu admin mặc định

Sau lần đăng nhập đầu, vào **Users** trong admin → edit user `admin@admin.dev` → đổi `newPassword` thành mật khẩu mạnh.

---

## 6. Optional — Bỏ link GitHub upstream

Nếu không muốn hiện link đến repo gốc `kasteckis/tempfastmail` trong footer và FAQ, sửa các file:

### `templates/footer.html.twig`
Xóa hoặc comment dòng:
```twig
<li class="mx-2"><a class="has-text-white" target="_blank" href="https://github.com/kasteckis/tempfastmail">Open Source (GitHub)</a></li>
<li class="is-hidden-mobile has-text-white">•</li>
```

### `templates/components/faq.html.twig`
Xóa các đoạn:
```twig
Our source code is open for review on <a href="https://github.com/kasteckis/tempfastmail" target="_blank">GitHub</a> for audit.
```
và:
```twig
<li>Should be open source. You can view code of this website <a href="https://github.com/kasteckis/tempfastmail" target="_blank">here</a></li>
```

**Lưu ý license:** Dự án upstream dùng giấy phép MIT. Theo MIT, bạn được phép bỏ link công khai nhưng **phải giữ file LICENSE và copyright notice** trong source code. File `LICENSE` ở thư mục gốc nên giữ nguyên.

---

## 7. Checklist hoàn tất

- [ ] Đã build lại frontend (`npm run build`)
- [ ] Đã clear cache Symfony (prod)
- [ ] Đã đăng nhập `/admin` đổi mật khẩu admin mặc định
- [ ] Đã add `tramsangtao.com` vào bảng Domains
- [ ] Đã vô hiệu/xóa các domain cũ
- [ ] `APP_SECRET` và `CREATE_RECEIVED_EMAIL_API_AUTHORIZATION_KEY` đã được đổi sang giá trị ngẫu nhiên
- [ ] Domain `tramsangtao.com` đã trỏ về Cloudflare nameserver
- [ ] Cloudflare Email Routing đã enabled (MX/TXT records)
- [ ] Cloudflare Worker đã deploy với đúng `API_URL` và `AUTHORIZATION_KEY`
- [ ] Catch-all rule đã trỏ về Worker
- [ ] Đã test gửi 1 email thật và nhận được trong inbox web

---

## 8. Troubleshooting

### Trang chủ vẫn hiện "Temp Fast Mail"
- Chưa rebuild frontend → chạy `npm run build` trong container
- Browser cache → Ctrl+F5 / clear cache

### Trang `/` báo "There are no domains added"
- Bảng `domain` trống hoặc tất cả domain đã hết hạn (`activeUntil < now`)
- Vào `/admin/domains` add `tramsangtao.com`

### Email gửi đến không xuất hiện
1. Kiểm tra Cloudflare Email Routing logs: Dashboard → Email → Email Routing → tab **Activity**
2. Kiểm tra Worker logs: Workers & Pages → worker của bạn → **Logs**
3. Kiểm tra response từ API:
   ```bash
   curl -X POST https://tramsangtao.com/api/email \
     -H "Authorization: <YOUR_KEY>" \
     -H "Content-Type: application/json" \
     -d '{"real_from":"test@example.com","real_to":"john.doe.1234@tramsangtao.com","subject":"Test","html":"<p>Hi</p>"}'
   ```
   Phản hồi `"OK"` (HTTP 201) = OK. `401 Unauthorized` = sai `AUTHORIZATION_KEY`.

### Domain mới generate nhưng vẫn @tempfastmail.com hoặc domain khác
- Bảng `domain` còn lưu domain cũ → vào `/admin/domains` xóa hoặc set `activeUntil` về quá khứ
- Random selection: `DomainRepository::findOneActiveRandomDomain()` chỉ chọn domain còn active

---

## 9. Files tham khảo

- Hướng dẫn Cloudflare gốc: [`docs/CLOUDFLARE_EMAIL_WORKER_SETUP.md`](CLOUDFLARE_EMAIL_WORKER_SETUP.md)
- Worker code: https://github.com/kasteckis/cloudflare-email-worker-html-parser
- Repo upstream: https://github.com/kasteckis/tempfastmail
