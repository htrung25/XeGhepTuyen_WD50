<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\StoreRouteRequest;
use App\Http\Requests\Operator\UpdateRouteRequest;
use App\Models\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RouteController extends Controller
{
    public function index(): JsonResponse
    {
        $operator = auth('operator')->user()->operator;

        $routes = $operator->routes()
            ->with(['stops', 'pickupServiceArea', 'dropoffServiceArea'])
            ->get();

        return response()->json(['success' => true, 'data' => $routes]);
    }

    public function store(StoreRouteRequest $request): JsonResponse
    {
        try {
            $operator = auth('operator')->user()->operator;
            $validated = $request->validated();

            $route = DB::transaction(function () use ($operator, $validated): Route {
                $stops = $validated['stops'];
                unset($validated['stops']);

                $route = $operator->routes()->create($validated);

                foreach ($stops as $stop) {
                    $route->stops()->create($stop);
                }

                return $route;
            });

            return response()->json([
                'success' => true,
                'message' => 'Tạo tuyến đường thành công',
                'data' => $route->load(['stops', 'pickupServiceArea', 'dropoffServiceArea']),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Route create failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $route = $this->findOwnedRoute($id, ['stops', 'pickupServiceArea', 'dropoffServiceArea']);

        if (! $route) {
            return response()->json(['success' => false, 'message' => 'Tuyến đường không tồn tại'], 404);
        }

        return response()->json(['success' => true, 'data' => $route]);
    }

    public function update(UpdateRouteRequest $request, string $id): JsonResponse
    {
        $route = $this->findOwnedRoute($id);

        if (! $route) {
            return response()->json(['success' => false, 'message' => 'Tuyến đường không tồn tại'], 404);
        }

        if (! $route->canBeDeleted()) {
            return response()->json(['success' => false, 'message' => 'Không thể cập nhật tuyến đang có chuyến lịch'], 422);
        }

        $route->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công',
            'data' => $route->load(['stops', 'pickupServiceArea', 'dropoffServiceArea']),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $route = $this->findOwnedRoute($id);

        if (! $route) {
            return response()->json(['success' => false, 'message' => 'Tuyến đường không tồn tại'], 404);
        }

        if (! $route->canBeDeleted()) {
            return response()->json(['success' => false, 'message' => 'Không thể xoá tuyến đang có chuyến lịch'], 422);
        }

        DB::transaction(function () use ($route) {
            $route->stops()->delete();
            $route->delete();
        });

        return response()->json(['success' => true, 'message' => 'Đã xoá tuyến đường']);
    }

    private function findOwnedRoute(string $id, array $with = []): ?Route
    {
        return auth('operator')->user()->operator
            ->routes()
            ->with($with)
            ->whereKey($id)
            ->first();
    }
}
