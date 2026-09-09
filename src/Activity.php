<?php
declare(strict_types=1);

/** Append-only audit trail. Also the source of truth for "last updated". */
final class Activity
{
    public static function log(
        string $entity,
        string $action,
        ?int $entityId = null,
        ?int $practiceId = null,
        ?int $taskId = null,
        ?string $field = null,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?string $summary = null
    ): void {
        try {
            Database::run(
                'INSERT INTO activity_log
                   (practice_id, task_id, actor, entity, entity_id, action, field, old_value, new_value, summary)
                 VALUES (:practice_id, :task_id, :actor, :entity, :entity_id, :action, :field, :old_value, :new_value, :summary)',
                [
                    'practice_id' => $practiceId,
                    'task_id'     => $taskId,
                    'actor'       => Auth::actor(),
                    'entity'      => $entity,
                    'entity_id'   => $entityId,
                    'action'      => $action,
                    'field'       => $field,
                    'old_value'   => self::trim($oldValue),
                    'new_value'   => self::trim($newValue),
                    'summary'     => $summary === null ? null : mb_substr($summary, 0, 400),
                ]
            );
        } catch (Throwable $ex) {
            // Never let logging break the request it is describing.
            error_log('activity_log failed: ' . $ex->getMessage());
        }
    }

    private static function trim(?string $v): ?string
    {
        return $v === null ? null : mb_substr($v, 0, 2000);
    }

    /** Recent entries for one practice. */
    public static function forPractice(int $practiceId, int $limit = 25): array
    {
        $limit = max(1, min(200, $limit));
        return Database::all(
            "SELECT l.*, t.name AS task_name, p.name AS product_name
               FROM activity_log l
               LEFT JOIN tasks t    ON t.id = l.task_id
               LEFT JOIN products p ON p.id = t.product_id
              WHERE l.practice_id = :pid
              ORDER BY l.created_at DESC, l.id DESC
              LIMIT {$limit}",
            ['pid' => $practiceId]
        );
    }
}
