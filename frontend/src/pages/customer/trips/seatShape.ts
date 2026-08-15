/**
 * Hình dáng ghế xe khách (tựa lưng bo tròn + 2 tay vịn) vẽ bằng MỘT path SVG
 * liền mạch duy nhất — tránh kiểu ghép 3 khối rời rạc (lưng + tay trái + tay
 * phải) nhìn như 3 hộp cạnh nhau. viewBox 48x56, vẽ theo chiều kim đồng hồ.
 */
export const SEAT_PATH =
    'M 10,16 Q 10,2 24,2 Q 38,2 38,16 L 38,28 Q 46,28 46,34 L 46,40 Q 46,46 38,46 Q 38,52 32,52 L 16,52 Q 10,52 10,46 Q 2,46 2,40 L 2,34 Q 2,28 10,28 L 10,16 Z';
