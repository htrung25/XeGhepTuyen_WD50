/**
 * Ma trận mã ghế theo từng hàng vật lý, từ đầu xe xuống cuối xe.
 *
 * Đây là quy ước nghiệp vụ theo vehicle_type, không suy luận từ seat_count hay
 * tiền tố mã ghế. Mỗi loại xe vì vậy luôn sở hữu một sơ đồ độc lập.
 */
export const VEHICLE_SEAT_ROWS: Readonly<
    Record<string, readonly (readonly string[])[]>
> = {
    sedan_4: [['A1'], ['B1', 'B2', 'B3']],
    mpv_7: [['A1'], ['B1', 'B2', 'B3'], ['C1', 'C2', 'C3']],
    van_9: [
        ['A1', 'A2'],
        ['B1', 'B2'],
        ['B3', 'B4'],
        ['C1', 'C2', 'C3'],
    ],
    limousine_12: [
        ['A1', 'A2'],
        ['B1', 'B2'],
        ['C1', 'C2'],
        ['D1', 'D2'],
        ['E1', 'E2'],
        ['F1', 'F2'],
    ],
    minibus_16: [
        ['A1'],
        ['B1', 'B2', 'B3'],
        ['C1', 'C2', 'C3'],
        ['D1', 'D2', 'D3'],
        ['E1', 'E2', 'E3'],
        ['F1', 'F2', 'F3'],
    ],
};
