<?php

declare(strict_types=1);

namespace App\Services;

final class RoomTypeService
{
    /** @var list<string> */
    public const AMENITY_OPTIONS = [
        'High Speed WiFi',
        'Mini Bar',
        'Climate Control',
        'Smart TV',
        'Balcony',
        'Hot Tub',
        'Work Desk',
        'Safe',
        'Coffee Maker',
        'Bathtub',
    ];

    /**
     * @param list<string>|string|null $amenities
     */
    public function encodeAmenities(array|string|null $amenities): ?string
    {
        if (is_string($amenities)) {
            $amenities = array_filter(array_map('trim', explode(',', $amenities)));
        }

        if ($amenities === null || $amenities === []) {
            return null;
        }

        $allowed = array_values(array_intersect(self::AMENITY_OPTIONS, $amenities));

        return $allowed === [] ? null : json_encode($allowed, JSON_UNESCAPED_UNICODE);
    }

    /** @return list<string> */
    public function decodeAmenities(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            /** @var list<string> $list */
            $list = array_values(array_filter($decoded, 'is_string'));

            return $list;
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
