<?php

test('returns a successful response', function () {
    $response = $this->getJson('/api/public/health');

    $response->assertOk()->assertJson([
        'success' => true,
        'message' => 'API is running',
    ]);
});
