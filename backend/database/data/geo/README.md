# Dữ liệu ranh giới hành chính

- Nguồn: GADM 4.1 (https://gadm.org — free cho academic/non-commercial)
- Tải: https://geodata.ucdavis.edu/gadm/gadm4.1/json/gadm41_VNM_1.json.zip
- Giải nén `gadm41_VNM_1.json` vào thư mục này (file lớn — KHÔNG commit, đã gitignore)
- Import: `php artisan service-area:import database/data/geo/gadm41_VNM_1.json --province="Hà Nội" --code=HN --dry-run`
- Import full-resolution: MySQL 8 không hỗ trợ `ST_Simplify` trên SRID 4326 (error 3618). Nếu cần giảm điểm, đơn giản hóa phía nguồn (mapshaper) TRƯỚC khi import.
