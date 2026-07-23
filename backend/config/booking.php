<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Thời gian tối thiểu trước giờ khởi hành
    |--------------------------------------------------------------------------
    | Khách KHÔNG được đặt (và chuyến KHÔNG hiển thị / KHÔNG được tạo) nếu
    | giờ xuất phát cách hiện tại ít hơn số phút này. Dùng chung cho:
    |  - Trip::scopeAvailable() + Trip::canBook()  (phía khách)
    |  - TripService::create()                      (phía nhà xe — tạo đơn & hàng loạt)
    | Mặc định 30 phút (PRD F-C02 / §10.1) để đủ thời gian giữ ghế + thanh toán.
    */
    'min_lead_minutes' => (int) env('BOOKING_MIN_LEAD_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Cutoff báo nghỉ của tài xế
    |--------------------------------------------------------------------------
    | Tài xế chỉ được tự báo "không chạy được chuyến" khi còn NHIỀU HƠN số phút
    | này trước giờ khởi hành. Sát giờ hơn thì phải gọi trực tiếp nhà xe.
    | (Nhà xe đổi tài xế KHÔNG chịu cutoff này, chỉ cần depart_at > now.)
    */
    'driver_report_cutoff_minutes' => (int) env('DRIVER_REPORT_CUTOFF_MINUTES', 15),
];
