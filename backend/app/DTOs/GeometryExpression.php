<?php

namespace App\DTOs;

/**
 * Cặp SQL fragment + bindings cho một biểu thức geometry — sản phẩm của
 * GeometryFactory, dùng trong whereRaw/selectOne/DB::update với parameter binding.
 */
final readonly class GeometryExpression
{
    public function __construct(
        public string $sql,
        public array $bindings,
    ) {}
}
