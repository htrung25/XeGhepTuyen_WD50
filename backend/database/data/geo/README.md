# Dữ liệu ranh giới hành chính

- Nguồn: GADM 4.1 (https://gadm.org — free cho academic/non-commercial)
- Tải: https://geodata.ucdavis.edu/gadm/gadm4.1/json/gadm41_VNM_1.json.zip
- File `gadm41_VNM_1.json` được lưu trong repository để command import có thể chạy trên môi trường deploy mà không cần cấu hình biến môi trường hoặc tải file thủ công.
- Import: `php artisan service-area:import database/data/geo/gadm41_VNM_1.json --province="Hà Nội" --code=HN --dry-run`
- Khi deploy: chạy migration trước, kiểm tra bằng `--dry-run`, sau đó bỏ `--dry-run` và thêm `--backfill-routes` để import và cập nhật các tuyến hiện có.
- Import full-resolution: MySQL 8 không hỗ trợ `ST_Simplify` trên SRID 4326 (error 3618). Nếu cần giảm điểm, đơn giản hóa phía nguồn (mapshaper) TRƯỚC khi import.

## Ranh giới cấp HUYỆN (dvhcvn) — dùng cho geofencing theo tuyến

- Nguồn: https://github.com/daohoangson/dvhcvn (`data/gis/{mã tỉnh}.json`), mã hành chính GSO
- File trong repo: `dvhcvn_01.json` (Hà Nội), `dvhcvn_31.json` (Hải Phòng)
- Vì sao đổi nguồn cho tỉnh sang dvhcvn: ranh giới tỉnh và huyện phải CÙNG nguồn,
  nếu tỉnh lấy GADM còn huyện lấy dvhcvn sẽ có điểm nằm trong tỉnh mà không thuộc huyện nào.
- Mã huyện và mã tỉnh trong file trùng khớp `resources/data/vn-provinces.json` mà tuyến đang dùng
  (đã đối chiếu: Hà Nội 30/30, Hải Phòng 15/15).
- Quy ước mã vùng: tỉnh `HN`/`HP`, huyện `HN-001`, `HP-303` (mã tỉnh ngắn + mã huyện GSO).
- TÊN vùng lấy theo catalog vn-provinces.json, KHÔNG lấy tên trong file GIS
  (VD 311: catalog "Thành phố Thuỷ Nguyên" vs GIS "Huyện Thuỷ Nguyên").
- Import:
  `php artisan service-area:import-districts database/data/geo/dvhcvn_01.json database/data/geo/dvhcvn_31.json --prune --backfill-routes --dry-run`
  Bỏ `--dry-run` để ghi thật. Command idempotent: checksum không đổi thì bỏ qua.
