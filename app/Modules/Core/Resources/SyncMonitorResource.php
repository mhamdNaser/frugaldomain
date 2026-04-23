<?php

namespace App\Modules\Core\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SyncMonitorResource extends JsonResource
{
    public function toArray($request): array
    {
        $type = $this->additional['type'] ?? null;

        return match ($type) {
            'runs' => $this->runData(),
            'status' => $this->statusData(),
            'errors' => $this->errorData(),
            default => parent::toArray($request),
        };
    }

    private function runData(): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'type' => $this->type,
            'trigger' => $this->trigger,
            'status' => $this->status,
            'batch_id' => $this->batch_id,
            'fetched_count' => $this->fetched_count,
            'synced_count' => $this->synced_count,
            'failed_count' => $this->failed_count,
            'jobs_count' => $this->jobs_count ?? 0,
            'successful_jobs_count' => $this->successful_jobs_count ?? 0,
            'failed_jobs_count' => $this->failed_jobs_count ?? 0,
            'error_message' => $this->error_message,
            'correlation_id' => $this->correlation_id,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'created_at' => $this->created_at,
        ];
    }

    private function statusData(): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'entity_type' => $this->entity_type,
            'sync_status' => $this->sync_status,
            'processed_count' => $this->processed_count,
            'cursor' => $this->cursor,
            'error_message' => $this->error_message,
            'last_synced_at' => $this->last_synced_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function errorData(): array
    {
        return [
            'id' => $this->id,
            'sync_run_id' => $this->sync_run_id,
            'sync_job_id' => $this->sync_job_id,
            'store_id' => $this->store_id,
            'type' => $this->type,
            'message' => $this->message,
            'context' => $this->context,
            'file' => $this->file,
            'line' => $this->line,
            'created_at' => $this->created_at,
        ];
    }

}
