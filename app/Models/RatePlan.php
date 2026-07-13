<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class RatePlan
{
    /** @return list<array<string, mixed>> */
    public function forRoomType(int $roomTypeId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM rate_plans
             WHERE room_type_id = :room_type_id
             ORDER BY is_active DESC, name ASC'
        );
        $stmt->execute(['room_type_id' => $roomTypeId]);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM rate_plans WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO rate_plans
                (room_type_id, name, rate, start_date, end_date, is_active)
             VALUES
                (:room_type_id, :name, :rate, :start_date, :end_date, :is_active)'
        );
        $stmt->execute([
            'room_type_id' => $data['room_type_id'],
            'name' => $data['name'],
            'rate' => $data['rate'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'is_active' => $data['is_active'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE rate_plans SET
                name = :name,
                rate = :rate,
                start_date = :start_date,
                end_date = :end_date,
                is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'rate' => $data['rate'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'is_active' => $data['is_active'],
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM rate_plans WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
