# Dữ liệu ranh giới hành chính

- Nguồn: GADM 4.1 (https://gadm.org — free cho academic/non-commercial)
- Tải: https://geodata.ucdavis.edu/gadm/gadm4.1/json/gadm41_VNM_1.json.zip
- Giải nén `gadm41_VNM_1.json` vào thư mục này (file lớn — KHÔNG commit, đã gitignore)
- Import: `php artisan service-area:import database/data/geo/gadm41_VNM_1.json --province="Hà Nội" --code=HN --simplify=0.003 --dry-run`
