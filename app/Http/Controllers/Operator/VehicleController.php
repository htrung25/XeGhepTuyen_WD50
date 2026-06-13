<?php

namespace App\Http\Controllers\Operator;

use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Operator\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    public function index(): JsonResponse
    {
        $operator = auth('operator')->user()->operator;

        $vehicles = $operator->vehicles()->with('assignedDriver.user')->get();

        return response()->json([
            'success' => true,
            'data'    => VehicleResource::collection($vehicles),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'plate_number'           => ['required', 'string', 'unique:vehicles,plate_number'],
            'vehicle_type'           => ['required', 'in:sedan_4,mpv_7,van_9,minibus_16'],
            'brand'                  => ['required', 'string', 'max:50'],
            'model'                  => ['required', 'string', 'max:50'],
            'year'                   => ['required', 'integer', 'min:2000', 'max:2030'],
            'color'                  => ['required', 'string', 'max:30'],
            'seat_count'             => ['required', 'integer', 'min:4', 'max:50'],
            'registration_expiry'    => ['required', 'date', 'after:today'],
            'amenities'              => ['nullable', 'array'],
            'image'                  => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ], [
            'plate_number.required'        => 'Vui lòng nhập biển số xe',
            'plate_number.unique'          => 'Biển số xe đã tồn tại',
            'vehicle_type.in'              => 'Loại xe không hợp lệ',
            'brand.required'               => 'Vui lòng nhập hãng xe',
            'model.required'               => 'Vui lòng nhập dòng xe',
            'year.required'                => 'Vui lòng nhập năm sản xuất',
            'color.required'               => 'Vui lòng nhập màu xe',
            'seat_count.required'          => 'Vui lòng nhập số chỗ',
            'registration_expiry.required' => 'Vui lòng nhập hạn đăng kiểm',
            'registration_expiry.after'    => 'Hạn đăng kiểm phải sau hôm nay',
        ]);

        $operator = auth('operator')->user()->operator;

        try {
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $imageUrl = Storage::url($request->file('image')->store('vehicles', 'public'));
            }

            $vehicle = Vehicle::create([
                'operator_id'         => $operator->id,
                'plate_number'        => strtoupper($request->plate_number),
                'vehicle_type'        => $request->vehicle_type,
                'brand'               => $request->brand,
                'model'               => $request->model,
                'year'                => $request->year,
                'color'               => $request->color,
                'seat_count'          => $request->seat_count,
                'registration_expiry' => $request->registration_expiry,
                'amenities'           => $request->amenities ?? [],
                'image_url'           => $imageUrl,
                'status'              => VehicleStatus::Active,
            ]);

            return response()->json(['success' => true, 'message' => 'Thêm xe thành công', 'data' => $vehicle], 201);
        } catch (\Exception $e) {
            Log::error('Vehicle create failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $operator = auth('operator')->user()->operator;
        $vehicle  = Vehicle::where('id', $id)->where('operator_id', $operator->id)->first();

        if (!$vehicle) {
            return response()->json(['success' => false, 'message' => 'Xe không tồn tại'], 404);
        }

        return response()->json(['success' => true, 'data' => $vehicle->load('activeDriver.user')]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $operator = auth('operator')->user()->operator;
        $vehicle  = Vehicle::where('id', $id)->where('operator_id', $operator->id)->first();

        if (!$vehicle) {
            return response()->json(['success' => false, 'message' => 'Xe không tồn tại'], 404);
        }

        $vehicle->update($request->only(['brand', 'model', 'color', 'registration_expiry', 'amenities', 'status']));

        return response()->json(['success' => true, 'message' => 'Cập nhật xe thành công', 'data' => $vehicle]);
    }
}
