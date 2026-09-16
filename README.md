# English Olympic Exam System - Laravel + MySQL MVP

Bộ mã nguồn MVP cho cuộc thi English Olympic theo tài liệu người dùng cung cấp.

## Nghiệp vụ đã triển khai
- 2 cấp độ KET / PET.
- 3 vòng thi, cấu hình số câu và thời gian riêng từng vòng.
- Đăng nhập thí sinh bằng SBD + PIN.
- Bắt đầu bài thi theo giờ server.
- Tự lưu từng đáp án.
- Nộp bài / tự hết giờ phía server.
- Chấm điểm tự động.
- Xếp hạng: điểm giảm dần, thời gian tăng dần.
- Quota KET/PET vào vòng sau cấu hình trong database, không hard-code.
- Admin mở/đóng vòng thi, xem kết quả, chọn thí sinh vào vòng sau.

## Dữ liệu mẫu từ thể lệ
Seeder tạo:
- English Olympic
- KET, PET
- Vòng 1: 20 câu / 15 phút
- Vòng 2: 30 câu / 30 phút
- Vòng 3: 30 câu / 40 phút
- Các nhóm: Social English, Nature, Everyday English, Vocabulary, Grammar, Listening, Reading

Quota KET/PET không được seed cứng vì tài liệu có chỗ chưa thống nhất và có quota PET lớn hơn số thí sinh PET ban đầu. Admin cần cấu hình trước kỳ thi.

## Yêu cầu
- PHP >= 8.2
- Laravel 13.x khuyến nghị (mã nguồn cũng theo cấu trúc middleware mới dùng `bootstrap/app.php`)
- MySQL 8+

## Cài đặt vào dự án Laravel mới
1. Tạo Laravel project mới.
2. Chép toàn bộ thư mục `app`, `database`, `resources`, `routes` trong gói này vào project.
3. Thêm middleware alias `candidate.session` vào `bootstrap/app.php` theo ví dụ bên dưới.
4. Cấu hình MySQL trong `.env`.
5. Chạy:

```bash
php artisan migrate
php artisan db:seed --class=EnglishOlympicSeeder
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8000
```

## Middleware alias - Laravel 13
Trong `bootstrap/app.php`:

```php
use App\Http\Middleware\CandidateSession;
use App\Http\Middleware\AdminRole;
use Illuminate\Foundation\Configuration\Middleware;

->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'candidate.session' => CandidateSession::class,
        'admin.role' => AdminRole::class,
    ]);
})
```

## Tài khoản Admin
Gói này dùng bảng `users` mặc định của Laravel và cột `role` bổ sung.
Seeder tạo admin từ biến môi trường:

```env
OLYMPIC_ADMIN_EMAIL=admin@example.com
OLYMPIC_ADMIN_PASSWORD=ChangeMe123!
```

Nếu không khai báo, seeder dùng tài khoản dev:
- Email: admin@example.com
- Password: ChangeMe123!

Đổi mật khẩu trước khi dùng thật.

## URL chính
- `/admin/login` - đăng nhập admin
- `/admin` - bảng điều khiển
- `/candidate/login` - đăng nhập thí sinh
- `/candidate` - màn hình chờ / các vòng thi

## Lưu ý triển khai LAN
Nên chạy server Laravel/MySQL trên một máy giáo viên hoặc máy chủ nội bộ. Các máy thí sinh truy cập bằng IP LAN, ví dụ `http://192.168.1.10:8000`.

Không nên phụ thuộc Internet trong ngày thi.


## Chức năng Speaking
Gói có module Speaking Challenge cơ bản: thêm thí sinh, chấm 4 tiêu chí Pronunciation, Fluency, Vocabulary/Grammar, Communication theo từng giám khảo.
