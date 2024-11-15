# **edu2 - 開發者 README（中文版）**

## **概述**
**edu2** 是一個專為 **卓越游泳會 (swim.hk)** 開發的獨立 PHP 應用程式，用於管理游泳班的課程、教練及學生資訊。它能與 **WordPress** 和 **WooCommerce** 數據庫進行互動，為游泳班管理提供高效的自動化功能與統計分析工具。

---

## **功能與檔案位置**

### **主要功能列表**
| **功能描述**                            | **檔案位置**                      |
|-----------------------------------------|-----------------------------------|
| 學員出席與點名管理                      | `attendance.php`                 |
| 新增及管理學員 BMI 資料                 | `account_mybmi.php`, `add_bmi.php` |
| 學員管理功能                            | `student.php`                    |
| 教練資料與出勤記錄管理                  | `coach.php`                      |
| 分析與統計圖表                          | `analysis.php`, `chart2.php`     |
| 課堂時間及日程管理                      | `classes.php`, `class_time.php`  |
| 續費與退款管理                          | `order_renew.php`, `order_refund.php` |
| 學生成績查詢與測試結果管理              | `history_score.php`, `account_test_result.php` |
| 管理員操作日誌                          | `admin_log.php`                  |
| 檔案上傳與管理                          | `uploader.php`                   |
| 影片播放功能                            | `video_player.php`               |
| 自訂工具與實用功能                      | `tools.php`                      |

---

## **數據庫結構**

### 表格：students
| **字段名**      | **類型**      | **描述**                  |
|-----------------|--------------|--------------------------|
| `id`           | INT (PK)     | 學員 ID                  |
| `name`         | VARCHAR(50)  | 學員姓名                |
| `age`          | INT          | 學員年齡                |
| `gender`       | ENUM         | 性別（男/女）            |
| `created_at`   | TIMESTAMP    | 註冊時間                |

### 表格：coaches
| **字段名**      | **類型**      | **描述**                  |
|-----------------|--------------|--------------------------|
| `id`           | INT (PK)     | 教練 ID                  |
| `name`         | VARCHAR(50)  | 教練姓名                |
| `specialty`    | TEXT         | 教練專業特長            |
| `hired_at`     | TIMESTAMP    | 聘用日期                |

### 表格：classes
| **字段名**      | **類型**      | **描述**                  |
|-----------------|--------------|--------------------------|
| `id`           | INT (PK)     | 課程 ID                  |
| `name`         | VARCHAR(50)  | 課程名稱                |
| `coach_id`     | INT (FK)     | 負責教練 ID             |
| `start_time`   | DATETIME     | 課程開始時間            |
| `end_time`     | DATETIME     | 課程結束時間            |

### 表格：attendance
| **字段名**      | **類型**      | **描述**                  |
|-----------------|--------------|--------------------------|
| `id`           | INT (PK)     | 點名記錄 ID              |
| `student_id`   | INT (FK)     | 學員 ID                  |
| `class_id`     | INT (FK)     | 課程 ID                  |
| `status`       | ENUM         | 狀態（出席/缺席/請假）    |
| `recorded_at`  | TIMESTAMP    | 記錄時間                |

### 表格：payments
| **字段名**      | **類型**      | **描述**                  |
|-----------------|--------------|--------------------------|
| `id`           | INT (PK)     | 付款記錄 ID              |
| `student_id`   | INT (FK)     | 學員 ID                  |
| `amount`       | DECIMAL(10,2)| 金額                     |
| `payment_date` | TIMESTAMP    | 付款日期                |

---

## **系統需求**
- **PHP**: 8.2.25 (256M 記憶體限制)
- **MySQL**: mysqlnd 8.2.25
- **Composer**: 2.8.2
- **Nginx**: 1.26.1

---

## **聯絡方式**
如有疑問，請聯絡 **support@swim.hk** 或訪問 [swim.hk](https://swim.hk) 了解更多資訊。
