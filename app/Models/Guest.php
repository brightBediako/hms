<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Guest
{
    public const ID_TYPES = [
        'passport',
        'national_id',
        'drivers_license',
        'other',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function search(?string $q = null, int $limit = 100): array
    {
        $sql = 'SELECT g.*,
                       (SELECT COUNT(*) FROM reservations r WHERE r.guest_id = g.id) AS stay_count
                FROM guests g
                WHERE 1=1';
        $params = [];

        if ($q !== null && trim($q) !== '') {
            $sql .= ' AND (
                g.full_name LIKE :q
                OR g.email LIKE :q
                OR g.phone LIKE :q
                OR g.id_number LIKE :q
            )';
            $params['q'] = '%' . trim($q) . '%';
        }

        $sql .= ' ORDER BY g.full_name ASC LIMIT ' . max(1, min(500, $limit));

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM guests WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO guests (full_name, email, phone, id_type, id_number, nationality, address, notes)
             VALUES (:full_name, :email, :phone, :id_type, :id_number, :nationality, :address, :notes)'
        );
        $stmt->execute([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'id_type' => $data['id_type'],
            'id_number' => $data['id_number'],
            'nationality' => $data['nationality'],
            'address' => $data['address'],
            'notes' => $data['notes'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE guests SET
                full_name = :full_name,
                email = :email,
                phone = :phone,
                id_type = :id_type,
                id_number = :id_number,
                nationality = :nationality,
                address = :address,
                notes = :notes
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'id_type' => $data['id_type'],
            'id_number' => $data['id_number'],
            'nationality' => $data['nationality'],
            'address' => $data['address'],
            'notes' => $data['notes'],
        ]);
    }

    /**
     * Stay history once reservations exist (feature 08+).
     *
     * @return list<array<string, mixed>>
     */
    public function stayHistory(int $guestId, int $limit = 20): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.id, r.booking_reference, r.check_in_date, r.check_out_date,
                    r.status, r.agreed_rate, rm.room_number, rt.name AS room_type_name
             FROM reservations r
             INNER JOIN rooms rm ON rm.id = r.room_id
             INNER JOIN room_types rt ON rt.id = rm.room_type_id
             WHERE r.guest_id = :guest_id
             ORDER BY r.check_in_date DESC
             LIMIT ' . max(1, min(100, $limit))
        );
        $stmt->execute(['guest_id' => $guestId]);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }
}
