<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

class ActivityLogService
{
    /** @var list<string> */
    public const HEAVY_JSON_PATHS = [
        '$.attributes.body_html',
        '$.old.body_html',
        '$.attributes.body_text',
        '$.old.body_text',
        '$.attributes.credentials',
        '$.old.credentials',
        '$.attributes.to',
        '$.old.to',
        '$.attributes.cc',
        '$.old.cc',
        '$.attributes.bcc',
        '$.old.bcc',
    ];

    /** @param array<string, mixed> $filters */
    public function paginated(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $table = (new Activity)->getTable();
        $removePaths = implode(', ', array_map(
            static fn (string $path) => "'{$path}'",
            self::HEAVY_JSON_PATHS
        ));

        $prunedProperties = "JSON_REMOVE({$table}.properties, {$removePaths})";

        return Activity::query()
            ->with(['causer' => fn ($q) => $q->select('id', 'name')])
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('causer_id', $id))
            ->when($filters['event'] ?? null, fn ($q, $event) => $q->where('event', $event))
            ->when($filters['subject_type'] ?? null, fn ($q, $type) => $q->where('subject_type', 'like', '%' . class_basename($type) . '%'))
            ->when($filters['search'] ?? null, function ($q, $search) use ($table) {
                $q->where(function ($inner) use ($search, $table) {
                    $inner->where("{$table}.description", 'like', "%{$search}%")
                        ->orWhere("{$table}.properties", 'like', "%{$search}%");
                });
            })
            ->select([
                "{$table}.id",
                "{$table}.description",
                "{$table}.event",
                "{$table}.subject_type",
                "{$table}.subject_id",
                "{$table}.causer_type",
                "{$table}.causer_id",
                "{$table}.created_at",
            ])
            ->selectRaw("
                CASE
                    WHEN JSON_VALID({$table}.properties)
                    THEN LEFT(CAST({$prunedProperties} AS CHAR), 65535)
                    ELSE LEFT(CAST({$table}.properties AS CHAR), 8192)
                END AS properties
            ")
            ->selectRaw("
                JSON_CONTAINS_PATH({$table}.properties, 'one', '$.attributes.body_html', '$.old.body_html') AS changed_body_html
            ")
            ->selectRaw("
                JSON_CONTAINS_PATH({$table}.properties, 'one', '$.attributes.body_text', '$.old.body_text') AS changed_body_text
            ")
            ->selectRaw("
                JSON_CONTAINS_PATH({$table}.properties, 'one', '$.attributes.credentials', '$.old.credentials') AS changed_credentials
            ")
            ->latest("{$table}.created_at")
            ->paginate($perPage);
    }
}
