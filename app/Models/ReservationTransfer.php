<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ReservationTransfer
{
    public function create(
        int $reservationId,
        int $fromRoomId,
        int $toRoomId,
        ?int $transferredBy,
        ?string $reason
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO reservation_transfers
                (reservation_id, from_room_id, to_room_id, transferred_by, reason)
             VALUES
                (:reservation_id, :from_room_id, :to_room_id, :transferred_by, :reason)'
        );
        $stmt->execute([
            'reservation_id' => $reservationId,
            'from_room_id' => $fromRoomId,
            'to_room_id' => $toRoomId,
            'transferred_by' => $transferredBy,
            'reason' => $reason,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forReservation(int $reservationId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT t.*,
                    fr.room_number AS from_room_number,
                    tr.room_number AS to_room_number,
                    s.full_name AS transferred_by_name
             FROM reservation_transfers t
             INNER JOIN rooms fr ON fr.id = t.from_room_id
             INNER JOIN rooms tr ON tr.id = t.to_room_id
             LEFT JOIN staff s ON s.id = t.transferred_by
             WHERE t.reservation_id = :reservation_id
             ORDER BY t.transferred_at DESC'
        );
        $stmt->execute(['reservation_id' => $reservationId]);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }
}
