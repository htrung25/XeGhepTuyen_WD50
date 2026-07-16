<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Resources\Operator\DriverResource;
use App\Models\Driver;
use App\Services\DriverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    public function __construct(private readonly DriverService $driverService) {}

    public function index(Request $request): JsonResponse
    {
        $operator = auth('operator')->user()->operator;

        $drivers = Driver::where('operator_id', $operator->id)
            ->with(['user', 'currentVehicle'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => DriverResource::collection($drivers->items()),
            'meta' => ['current_page' => $drivers->currentPage(), 'total' => $drivers->total()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:100', 'unique:users,email'],
            'license_number' => ['required', 'string', 'max:20', 'unique:drivers,license_number'],
            'license_class' => ['required', 'in:B2,C,D,E'],
            'license_expiry' => ['required', 'date', 'after:today'],
            'id_card_number' => ['required', 'string', 'max:20'],
            'id_card_front' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'id_card_back' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'license_front' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ], [
            'full_name.required' => 'Vui lòng nhập họ tên tài xế',
            'phone.required' => 'Vui lòng nhập số điện thoại',
            'phone.regex' => 'Số điện thoại không hợp lệ',
            'phone.unique' => 'Số điện thoại đã có tài khoản',
            'email.unique' => 'Email đã được sử dụng',
            'license_number.required' => 'Vui lòng nhập số GPLX',
            'license_number.unique' => 'Số GPLX đã tồn tại',
            'license_class.in' => 'Hạng GPLX không hợp lệ (B2/C/D/E)',
            'license_expiry.required' => 'Vui lòng nhập ngày hết hạn GPLX',
            'license_expiry.after' => 'GPLX đã hết hạn',
            'id_card_number.required' => 'Vui lòng nhập số CMND/CCCD',
        ]);

        $operator = auth('operator')->user()->operator;

        try {
            foreach (['id_card_front', 'id_card_back', 'license_front'] as $field) {
                if ($request->hasFile($field)) {
                    $validated[$field.'_url'] = Storage::url($request->file($field)->store('drivers', 'public'));
                }
            }

            $driver = $this->driverService->createByOperator($validated, $operator);

            return response()->json([
                'success' => true,
                'message' => 'Đã thêm tài xế. Hồ sơ đang chờ admin duyệt GPLX — mật khẩu đăng nhập sẽ được cấp khi duyệt.',
                'data' => [
                    'driver' => new DriverResource($driver->load('user', 'currentVehicle')),
                    'phone' => $driver->user->phone,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Operator add driver failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại'], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $operator = auth('operator')->user()->operator;
        $driver = Driver::where('id', $id)->where('operator_id', $operator->id)->with('user', 'currentVehicle')->first();

        if (! $driver) {
            return response()->json(['success' => false, 'message' => 'Tài xế không tồn tại'], 404);
        }

        return response()->json(['success' => true, 'data' => $driver]);
    }

    public function assignVehicle(Request $request, string $id): JsonResponse
    {
        $request->validate(['vehicle_id' => ['required', 'uuid', 'exists:vehicles,id']]);

        $operator = auth('operator')->user()->operator;
        $driver = Driver::where('id', $id)->where('operator_id', $operator->id)->first();

        if (! $driver) {
            return response()->json(['success' => false, 'message' => 'Tài xế không tồn tại'], 404);
        }

        $driver->update(['current_vehicle_id' => $request->vehicle_id]);

        return response()->json(['success' => true, 'message' => 'Đã phân công xe cho tài xế']);
    }

    /**
     * Cấp lại mật khẩu đăng nhập cho tài xế của nhà xe (khi SMS không tới / tài xế quên).
     */
    public function resetPassword(string $id): JsonResponse
    {
        $operator = auth('operator')->user()->operator;
        $driver = Driver::where('id', $id)->where('operator_id', $operator->id)->with('user', 'operator')->first();

        if (! $driver) {
            return response()->json(['success' => false, 'message' => 'Tài xế không tồn tại'], 404);
        }

        $tempPassword = $this->driverService->resetPassword($driver);

        return response()->json([
            'success' => true,
            'message' => 'Đã cấp lại mật khẩu và gửi SMS cho tài xế',
            'data' => ['phone' => $driver->user->phone, 'temp_password' => $tempPassword],
        ]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate(['status' => ['required', 'in:verified,suspended']]);

        $operator = auth('operator')->user()->operator;
        $driver = Driver::where('id', $id)->where('operator_id', $operator->id)->first();

        if (! $driver) {
            return response()->json(['success' => false, 'message' => 'Tài xế không tồn tại'], 404);
        }

        $driver->update(['status' => $request->status]);

        $msg = $request->status === 'suspended' ? 'Đã tạm đình chỉ tài xế' : 'Đã kích hoạt tài xế';

        return response()->json(['success' => true, 'message' => $msg]);
    }
}
