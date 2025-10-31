<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LinkResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'originalUrl' => $this->original_url,
            'shortCode' => $this->short_code,
            'visitsCount' => $this->visits_count ?? $this->visitsCount,
            'expiresAt' => $this->expires_at,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];

        if ($this->relationLoaded('linkStats')) {
            $data['stats'] = $this->linkStats->map(function ($stat) {
                return [
                    'ip' => $stat->ip_address,
                    'userAgent' => $stat->user_agent,
                    'visitedAt' => $stat->created_at,
                ];
            });
        }

        return $data;
    }
}
