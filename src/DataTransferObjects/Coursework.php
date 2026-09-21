<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Enums\CompletionCriteria;
use MCKLtech\MightyNetworks\Enums\CourseworkStatus;
use MCKLtech\MightyNetworks\Enums\CourseworkType;
use MCKLtech\MightyNetworks\Enums\UnlockingCriteria;

/**
 * A coursework item (lesson, quiz, section, or overview) within a course Space.
 */
final readonly class Coursework
{
    public function __construct(
        public int $id,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public int $spaceId,
        public CourseworkType $type,
        public string $title,
        public CourseworkStatus $status,
        public int $position,
        public CompletionCriteria $completionCriteria,
        public UnlockingCriteria $unlockingCriteria,
        public int $childrenCount,
        public string $permalink,
        public ?int $parentId = null,
        public ?string $parentType = null,
        public ?string $description = null,
    ) {}

    /**
     * Create a Coursework from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            createdAt: self::date((string) ($data['created_at'] ?? '')),
            updatedAt: self::date((string) ($data['updated_at'] ?? '')),
            spaceId: (int) ($data['space_id'] ?? 0),
            type: CourseworkType::tryFrom((string) ($data['type'] ?? '')) ?? CourseworkType::Lesson,
            title: (string) ($data['title'] ?? ''),
            status: CourseworkStatus::tryFrom((string) ($data['status'] ?? '')) ?? CourseworkStatus::Hidden,
            position: (int) ($data['position'] ?? 0),
            completionCriteria: CompletionCriteria::tryFrom((string) ($data['completion_criteria'] ?? '')) ?? CompletionCriteria::None,
            unlockingCriteria: UnlockingCriteria::tryFrom((string) ($data['unlocking_criteria'] ?? '')) ?? UnlockingCriteria::None,
            childrenCount: (int) ($data['children_count'] ?? 0),
            permalink: (string) ($data['permalink'] ?? ''),
            parentId: self::intOrNull($data, 'parent_id'),
            parentType: self::stringOrNull($data, 'parent_type'),
            description: self::stringOrNull($data, 'description'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function stringOrNull(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function intOrNull(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    private static function date(string $value): CarbonImmutable
    {
        return $value === ''
            ? CarbonImmutable::now()
            : CarbonImmutable::parse($value);
    }
}
