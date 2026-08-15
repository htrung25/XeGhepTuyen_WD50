<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\VietnamAdministrative;
use Illuminate\Http\JsonResponse;

class ProvinceController extends Controller
{
    /** Danh mục tỉnh + huyện cho dropdown (dữ liệu tĩnh — cache dài ở client) */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => VietnamAdministrative::provinces(),
        ])->header('Cache-Control', 'public, max-age=86400');
    }
}
