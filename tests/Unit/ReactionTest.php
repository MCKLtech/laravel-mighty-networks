<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\Reaction;
use PHPUnit\Framework\TestCase;

final class ReactionTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'id' => 99,
            'created_at' => '2024-02-01T09:00:00+00:00',
            'updated_at' => '2024-02-01T09:00:00+00:00',
            'space_id' => 9,
            'network_id' => 12345,
            'targetable_id' => 7,
            'targetable_type' => 'Post',
            'targetable_space_id' => 9,
            'emoji' => '👍🏽',
            'base_emoji' => '👍',
            'member_id' => 42,
        ], $overrides);
    }

    public function test_it_maps_every_documented_field(): void
    {
        $reaction = Reaction::fromArray($this->payload());

        $this->assertSame(99, $reaction->id);
        $this->assertSame(9, $reaction->spaceId);
        $this->assertSame(12345, $reaction->networkId);
        $this->assertSame(7, $reaction->targetableId);
        $this->assertSame('Post', $reaction->targetableType);
        $this->assertSame(9, $reaction->targetableSpaceId);
        $this->assertSame('👍🏽', $reaction->emoji);
        $this->assertSame('👍', $reaction->baseEmoji);
        $this->assertSame(42, $reaction->memberId);
        $this->assertInstanceOf(CarbonImmutable::class, $reaction->createdAt);
    }
}
