# BCCD Wireframe - PC Gear Store

## 1. UI direction

- Tone: hiện đại, sạch, dễ nhìn
- Màu gợi ý:
  - `Primary`: `#0F172A`
  - `Accent`: `#2563EB`
  - `Highlight`: `#F97316`
  - `Surface`: `#F8FAFC`
- Font:
  - heading: `Poppins`
  - body: `Inter` hoặc `Roboto`

## 2. Home page

```text
+----------------------------------------------------------------------------------+
| LOGO | Search bar                               | Account | Cart | Build PC      |
+----------------------------------------------------------------------------------+
| Menu: CPU | Mainboard | RAM | VGA | SSD | PSU | Case | Cooler | Sale           |
+----------------------------------------------------------------------------------+
| Hero banner                         | Promo 1               | Promo 2           |
+----------------------------------------------------------------------------------+
| Section: Danh muc noi bat                                                      |
| [CPU] [Mainboard] [RAM] [VGA] [SSD] [Laptop Gear]                              |
+----------------------------------------------------------------------------------+
| Section: San pham noi bat                                                      |
| [Card] [Card] [Card] [Card]                                                    |
| [Card] [Card] [Card] [Card]                                                    |
+----------------------------------------------------------------------------------+
| Section: Cau hinh PC goi y                                                     |
| [Starter Build] [Gaming Build] [Streaming Build]                               |
+----------------------------------------------------------------------------------+
| Section: Tin cong nghe / huong dan build PC                                    |
+----------------------------------------------------------------------------------+
| Footer: about | contact | social | support                                     |
+----------------------------------------------------------------------------------+
```

## 3. Product listing page

```text
+----------------------------------------------------------------------------------+
| Breadcrumb: Home / CPU                                                         |
+----------------------------------------------------------------------------------+
| Sidebar filter         | Toolbar: Sort | Price range | Brand | Socket           |
|------------------------+---------------------------------------------------------|
| Category               | [Card] [Card] [Card]                                   |
| Brand                  | [Card] [Card] [Card]                                   |
| Price                  | [Card] [Card] [Card]                                   |
| Socket                 | [Card] [Card] [Card]                                   |
| RAM type               |                                                         |
| Wattage                |                                                         |
+----------------------------------------------------------------------------------+
| Pagination                                                                     |
+----------------------------------------------------------------------------------+
```

## 4. Product detail page

```text
+----------------------------------------------------------------------------------+
| Breadcrumb: Home / Category / Product                                          |
+----------------------------------------------------------------------------------+
| Gallery images            | Product title                                       |
| [Main image]              | Price / sale price                                  |
| [thumb] [thumb] [thumb]   | Stock status                                        |
|                            | Short specs                                         |
|                            | Quantity                                            |
|                            | [Add to cart] [Buy now] [Add to build]              |
+----------------------------------------------------------------------------------+
| Tabs: Description | Specifications | Reviews                                    |
+----------------------------------------------------------------------------------+
| Related products: [Card] [Card] [Card] [Card]                                  |
+----------------------------------------------------------------------------------+
```

## 5. PC Builder page

```text
+----------------------------------------------------------------------------------+
| Header: Build PC                                                                |
+----------------------------------------------------------------------------------+
| Component list                     | Build summary                              |
|------------------------------------+--------------------------------------------|
| CPU        [Select product]        | Selected parts                             |
| Mainboard  [Select product]        | - CPU                                      |
| RAM        [Select product]        | - Mainboard                                |
| GPU        [Select product]        | - RAM                                      |
| SSD        [Select product]        | - GPU                                      |
| PSU        [Select product]        | - SSD                                      |
| Case       [Select product]        | - PSU                                      |
| Cooler     [Select product]        | - Case                                     |
|                                    | Compatibility status                       |
|                                    | Estimated power                            |
|                                    | Total price                                |
|                                    | [Save build] [Add all to cart]             |
+----------------------------------------------------------------------------------+
| Validation panel                                                              |
| - CPU va mainboard hop socket                                                  |
| - RAM dung loai DDR5                                                           |
| - GPU vua case                                                                 |
+----------------------------------------------------------------------------------+
```

## 6. Cart and checkout

```text
+----------------------------------------------------------------------------------+
| Cart items                                                                      |
| Product | Price | Quantity | Total | Remove                                     |
+----------------------------------------------------------------------------------+
| Coupon box                         | Order summary                              |
|                                    | Subtotal                                   |
|                                    | Shipping                                   |
|                                    | Total                                      |
|                                    | [Proceed to checkout]                      |
+----------------------------------------------------------------------------------+

+----------------------------------------------------------------------------------+
| Checkout                                                                          |
| Billing info | Shipping info | Payment method | Order review                     |
+----------------------------------------------------------------------------------+
```

## 7. Admin pages

```text
+----------------------------------------------------------------------------------+
| Admin sidebar: Dashboard | Products | Orders | PC Builder | Rules               |
+----------------------------------------------------------------------------------+
| Dashboard cards: Revenue | Orders | Products | Saved Builds                     |
+----------------------------------------------------------------------------------+
| Product spec form                                                               |
| Product: [select]                                                               |
| Component type: [select]                                                        |
| Spec rows: key | value | numeric value | unit                                   |
+----------------------------------------------------------------------------------+
| Compatibility rules form                                                        |
| Source type | Target type | Source key | Target key | Operator | Message        |
+----------------------------------------------------------------------------------+
```

## 8. Skin customization ideas

- Header nền tối, nút CTA màu cam
- Card sản phẩm bo góc vừa, shadow nhẹ
- Hover card: ảnh zoom nhẹ, hiện nút `Xem chi tiết`
- Badge:
  - `Sale`
  - `Hot`
  - `New`
- Banner dùng hình case PC, VGA, gaming setup

## 9. Demo presentation flow

1. Vào trang chủ và giới thiệu giao diện.
2. Vào danh mục sản phẩm, demo filter.
3. Vào chi tiết sản phẩm, demo thông số kỹ thuật.
4. Vào `PC Builder`, chọn linh kiện và kiểm tra tương thích.
5. Lưu build, thêm vào giỏ hàng.
6. Vào admin và demo form quản lý rule/spec.
