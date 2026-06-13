<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\StoreRouteRequest;
use App\Models\Route;
use App\Models\RouteStop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RouteController extends Controller
{
    public function index(): JsonResponse
    {
        $operator = auth('operator')->user()->operator;

        $routes = Route::with('stops')->get();

        return response()->json(['success' => true, 'data' => $routes]);
    }

    public function store(StoreRouteRequest $request): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, &$route) {
                $route = Route::create([
                    'route_code'      => $request->route_code,
                    'origin_city'     => $request->origin_city,
                    'dest_city'       => $request->dest_city,
                    'distance_km'     => $request->distance_km,
                    'duration_hours'  => $request->duration_hours,
                    'description'     => $request->description,
                ]);

                foreach ($request->stops as $stop) {
                    RouteStop::create([
                        'route_id'       => $route->id,
                        'stop_name'      => $stop['stop_name'],
                        'stop_address'   => $stop['stop_address'],
                        'stop_order'     => $stop['stop_order'],
                        'lat'            => $stop['lat'] ?? null,
                        'lng'            => $stop['lng'] ?? null,
                        'is_pickup'      => $stop['is_pickup'] ?? true,
                        'is_dropoff'     => $stop['is_dropoff'] ?? true,
                    ]);
                }
            });

            return response()->json(['success' => true, 'message' => 'Tạo tuyến đường thành công', 'data' => $route->load('stops')], 201);
        } catch (\Exception $e) {
            Log::error('Route create failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $route = Route::with('stops')->find($id);

        if (!$route) {
            return response()->json(['success' => false, 'message' => 'Tuyến đường không tồn tại'], 404);
        }

        return response()->json(['success' => true, 'data' => $route]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $route = Route::find($id);

        if (!$route) {
            return response()->json(['success' => false, 'message' => 'Tuyến đường không tồn tại'], 404);
        }

        if (!$route->canBeDeleted()) {
            return response()->json(['success' => false, 'message' => 'Không thể cập nhật tuyến đang có chuyến lịch'], 422);
        }

        $route->update($request->only(['origin_city', 'dest_city', 'distance_km', 'duration_hours', 'description', 'is_active']));

        return response()->json(['success' => true, 'message' => 'Cập nhật thành công', 'data' => $route]);
    }
}
