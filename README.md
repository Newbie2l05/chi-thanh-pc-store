# 🛒 Chí Thành PC Store (BCCĐ Project)

![Project Status](https://img.shields.io/badge/Status-Completed-success)
![WordPress](https://img.shields.io/badge/WordPress-6.x-blue)
![WooCommerce](https://img.shields.io/badge/WooCommerce-8.x-purple)
![PHP](https://img.shields.io/badge/PHP-7.4+-777bb3)

**ChiThanhh PC Store** là một website Thương mại điện tử chuyên bán linh kiện máy tính và Gaming Gear, được xây dựng trên nền tảng WordPress & WooCommerce. Đây là dự án phục vụ cho Báo Cáo Chuyên Đề (BCCĐ).

Điểm nổi bật của dự án là một **Plugin Tự Build PC (PC Builder)** do sinh viên tự phát triển, cho phép người dùng tự do lựa chọn linh kiện, kiểm tra độ tương thích giữa các linh kiện (Ví dụ: CPU và Mainboard) và tự động thêm toàn bộ cấu hình vào giỏ hàng chỉ với một thao tác.

## ✨ Tính năng nổi bật

1. **Giao diện Premium Dark Mode**: Thiết kế hiện đại, mượt mà lấy cảm hứng từ các thương hiệu công nghệ lớn (như Corsair), mang lại trải nghiệm cao cấp cho người dùng.
2. **PC Builder Plugin (Custom Plugin)**:
   - Cho phép người dùng chọn từng linh kiện để tự lắp ráp PC.
   - **Hệ thống kiểm tra tương thích**: Cảnh báo ngay lập tức nếu CPU không khớp Socket với Mainboard, hoặc các quy tắc do Admin thiết lập bị vi phạm.
   - Thêm toàn bộ cấu hình (Build) vào giỏ hàng WooCommerce nhanh chóng.
3. **Quản lý linh kiện & Luật tương thích (Admin)**: Trang quản trị riêng cho phép Admin tự do thiết lập các loại linh kiện (Component Types) và các quy tắc tương thích (Compatibility Rules).
4. **Tối ưu UI/UX**: Tích hợp các hiệu ứng cuộn trang (Scroll Reveal), Sticky Header với hiệu ứng Glassmorphism (Backdrop blur) và Ambient Glow khi tương tác với sản phẩm.

## 🛠️ Công nghệ sử dụng

- **Core**: WordPress (CMS) & WooCommerce (E-commerce Engine).
- **Backend**: PHP, MySQL.
- **Frontend**: HTML5, Vanilla CSS (Custom Design System), JavaScript (IntersectionObserver API, AJAX).
- **Architecture**: Custom WordPress Theme (`pcgear-store`) & Custom WordPress Plugin (`pc-builder`).

## 📂 Cấu trúc dự án

Dự án này chỉ đẩy lên GitHub các mã nguồn tùy chỉnh (Custom Code) để đảm bảo kho lưu trữ gọn nhẹ và chuyên nghiệp:

```text
/
├── wp-content/
│   ├── plugins/
│   │   └── pc-builder/          # Plugin tự viết (Xử lý logic Build PC, check tương thích)
│   └── themes/
│       └── pcgear-store/        # Giao diện tự viết (Dark theme, responsive, animations)
├── .gitignore                   # Loại bỏ các file WP Core, node_modules...
├── README.md                    # Tài liệu dự án
└── ...                          # Các file tài liệu thiết kế (ERD, Schema)
```

## 🚀 Hướng dẫn cài đặt (Dành cho Giảng viên/Dev)

### Yêu cầu hệ thống
- Môi trường: XAMPP, Laragon, Docker... (PHP >= 7.4, MySQL/MariaDB).
- WordPress: Đã cài đặt sẵn một bản WordPress Core mới.
- Plugins bắt buộc: WooCommerce.

### Các bước triển khai
1. Clone repository này về thư mục gốc của WordPress:
   ```bash
   git clone https://github.com/Newbie2l05/chi-thanh-pc-store.git .
   ```
2. Truy cập **WP Admin**:
   - Vào **Appearance -> Themes**: Kích hoạt giao diện **PCGear Store**.
   - Vào **Plugins**: Kích hoạt plugin **PC Builder**.
3. Cập nhật Permalinks: Vào Settings -> Permalinks, chọn Post name và Save.
4. Thêm sản phẩm mẫu qua WooCommerce và thiết lập thông số kỹ thuật trong box "PC Builder Specs".
5. Vào menu **PC Builder** ở sidebar Admin để cấu hình "Component Types" (CPU, Main, RAM...) và "Compatibility Rules" (Quy tắc tương thích).

## 🎓 Về tác giả

Dự án được thiết kế và lập trình bởi:
- **Lâm Chí Thành**
- Đề tài: Thương mại điện tử / Hệ thống thông tin (Xây dựng Website PC Store).
