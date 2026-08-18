<?php

namespace App\Http\Resources\Operator;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OperatorHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'action' => $this->action,
            'severity' => $this->severity,
            'title' => $this->title,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'actor' => $this->actor ? [
                'id' => $this->actor->id,
                'full_name' => $this->actor->full_name,
            ] : null,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
        ];
    }
}
