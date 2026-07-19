# Dữ liệu ranh giới hành chính

- Nguồn: GADM 4.1 (https://gadm.org — free cho academic/non-commercial)
- Tải: https://geodata.ucdavis.edu/gadm/gadm4.1/json/gadm41_VNM_1.json.zip
- File `gadm41_VNM_1.json` được lưu trong repository để command import có thể chạy trên môi trường deploy mà không cần cấu hình biến môi trường hoặc tải file thủ công.
- Import: `php artisan service-area:import database/data/geo/gadm41_VNM_1.json --province="Hà Nội" --code=HN --dry-run`
- Khi deploy: chạy migration trước, kiểm tra bằng `--dry-run`, sau đó bỏ `--dry-run` và thêm `--backfill-routes` để import và cập nhật các tuyến hiện có.
- Import full-resolution: MySQL 8 không hỗ trợ `ST_Simplify` trên SRID 4326 (error 3618). Nếu cần giảm điểm, đơn giản hóa phía nguồn (mapshaper) TRƯỚC khi import.
