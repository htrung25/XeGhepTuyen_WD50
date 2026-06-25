<?php

/**
 * R3: summary() lấy số tiền từ SettlementService (nguồn duy nhất) + lộ cash_collected/settlement
 * → khớp tuyệt đối với trang quyết toán, không còn net_revenue "ảo" lệch payout.
 * Dùng helper makeOperatorWithRevenue() (định nghĩa ở PayoutTest, global của Pest).
 */
it('summary đồng bộ SettlementService + có cash_collected/settlement', function () {
    $operator = makeOperatorWithRevenue(online: 1, cash: 1); // 1 online + 1 cash × 150.000, rate 10%
    $headers = ['Authorization' => 'Bearer '.$operator->user->createToken('operator_token')->plainTextToken];

    $url = '/api/operator/revenue/summary?period=custom'
        .'&from_date='.now()->subDays(2)->toDateString()
        .'&to_date='.now()->toDateString();

    $this->getJson($url, $headers)
        ->assertOk()
        ->assertJsonPath('data.gross_revenue', 300000)   // 2 × 150.000
        ->assertJsonPath('data.commission', 30000)        // 10%
        ->assertJsonPath('data.net_revenue', 270000)      // gross − commission
        ->assertJsonPath('data.cash_collected', 150000)   // tiền mặt nhà xe giữ
        ->assertJsonPath('data.settlement', 120000);      // online_net(135k) − cash_commission(15k)
});
