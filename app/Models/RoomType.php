<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class RoomType
{
    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        $stmt = Database::connection()->query(
            'SELECT rt.*,
                    (SELECT COUNT(*) FROM rate_plans rp WHERE rp.room_type_id = rt.id) AS rate_plan_count,
                    (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.id) AS room_count
             FROM room_types rt
             ORDER BY rt.name ASC'
        );

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT rt.*,
                    (SELECT COUNT(*) FROM rate_plans rp WHERE rp.room_type_id = rt.id) AS rate_plan_count,
                    (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.id) AS room_count
             FROM room_types rt
             WHERE rt.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO room_types
                (name, description, base_capacity_adults, base_capacity_children, base_rate, extra_bed_rate, amenities)
             VALUES
                (:name, :description, :base_capacity_adults, :base_capacity_children, :base_rate, :extra_bed_rate, :amenities)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'],
            'base_capacity_adults' => $data['base_capacity_adults'],
            'base_capacity_children' => $data['base_capacity_children'],
            'base_rate' => $data['base_rate'],
            'extra_bed_rate' => $data['extra_bed_rate'],
            'amenities' => $data['amenities'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE room_types SET
                name = :name,
                description = :description,
                base_capacity_adults = :base_capacity_adults,
                base_capacity_children = :base_capacity_children,
                base_rate = :base_rate,
                extra_bed_rate = :extra_bed_rate,
                amenities = :amenities
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'],
            'base_capacity_adults' => $data['base_capacity_adults'],
            'base_capacity_children' => $data['base_capacity_children'],
            'base_rate' => $data['base_rate'],
            'extra_bed_rate' => $data['extra_bed_rate'],
            'amenities' => $data['amenities'],
        ]);
    }

    public function delete(int $id): bool
    {
        $rooms = Database::connection()->prepare(
            'SELECT COUNT(*) FROM rooms WHERE room_type_id = :id'
        );
        $rooms->execute(['id' => $id]);
        if ((int) $rooms->fetchColumn() > 0) {
            return false;
        }

        $stmt = Database::connection()->prepare('DELETE FROM room_types WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
