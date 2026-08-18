<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\IndexOperatorHistoryRequest;
use App\Http\Resources\Operator\OperatorHistoryResource;
use App\Models\OperatorHistory;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class HistoryController extends Controller
{
    public function index(IndexOperatorHistoryRequest $request): JsonResponse
    {
        $operatorId = auth('operator')->user()->operator->id;
        $filters = $request->validated();

        $query = OperatorHistory::query()
            ->forOperator($operatorId)
            ->with('actor:id,full_name')
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['severity'] ?? null, fn ($query, $severity) => $query->where('severity', $severity))
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action));

        if (! empty($filters['date_from'])) {
            $query->where('occurred_at', '>=', CarbonImmutable::createFromFormat('Y-m-d', $filters['date_from'])->startOfDay());
        }
        if (! empty($filters['date_to'])) {
            $query->where('occurred_at', '<=', CarbonImmutable::createFromFormat('Y-m-d', $filters['date_to'])->endOfDay());
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(fn ($query) => $query
                ->where('title', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%")
                ->orWhere('metadata', 'LIKE', "%{$search}%"));
        }

        $history = $query->orderByDesc('occurred_at')->paginate((int) ($filters['per_page'] ?? 10));

        return response()->json([
            'success' => true,
            'data' => OperatorHistoryResource::collection($history->items()),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'total' => $history->total(),
                'per_page' => $history->perPage(),
                'from' => $history->firstItem(),
                'to' => $history->lastItem(),
            ],
        ]);
    }
}
