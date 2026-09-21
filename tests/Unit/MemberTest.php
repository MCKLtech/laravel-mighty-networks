<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\Member;
use MCKLtech\MightyNetworks\Enums\MemberType;
use PHPUnit\Framework\TestCase;

final class MemberTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'id' => 42,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'email' => 'jane@example.com',
            'member_type' => 'limited',
            'permalink' => 'https://example.mn.co/members/42',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'time_zone' => 'America/Los_Angeles',
            'location' => 'San Francisco, CA',
            'bio' => 'Community builder',
            'avatar' => 'https://cdn.mn.co/avatars/42.jpg',
            'referral_count' => 3,
            'categories' => [['id' => 1, 'title' => 'Founders']],
            'ambassador_level' => 'gold',
            'last_visited_at' => '2024-04-01T08:00:00+00:00',
        ], $overrides);
    }

    public function test_it_maps_every_documented_field(): void
    {
        $member = Member::fromArray($this->payload());

        $this->assertSame(42, $member->id);
        $this->assertSame('jane@example.com', $member->email);
        $this->assertSame(MemberType::Limited, $member->memberType);
        $this->assertSame('https://example.mn.co/members/42', $member->permalink);
        $this->assertSame('Jane', $member->firstName);
        $this->assertSame('Doe', $member->lastName);
        $this->assertSame('America/Los_Angeles', $member->timeZone);
        $this->assertSame('San Francisco, CA', $member->location);
        $this->assertSame('Community builder', $member->bio);
        $this->assertSame('https://cdn.mn.co/avatars/42.jpg', $member->avatar);
        $this->assertSame(3, $member->referralCount);
        $this->assertSame([['id' => 1, 'title' => 'Founders']], $member->categories);
        $this->assertSame('gold', $member->ambassadorLevel);
    }

    public function test_it_parses_dates_as_immutable_carbon_instances(): void
    {
        $member = Member::fromArray($this->payload());

        $this->assertInstanceOf(CarbonImmutable::class, $member->createdAt);
        $this->assertSame('2024-01-15T10:30:00+00:00', $member->createdAt->toIso8601String());
        $this->assertInstanceOf(CarbonImmutable::class, $member->lastVisitedAt);
        $this->assertSame('2024-04-01T08:00:00+00:00', $member->lastVisitedAt->toIso8601String());
    }

    public function test_it_preserves_a_masked_email_without_unmasking_it(): void
    {
        $member = Member::fromArray($this->payload(['email' => 'a***@***.***', 'last_visited_at' => null]));

        $this->assertSame('a***@***.***', $member->email);
        $this->assertNull($member->lastVisitedAt);
    }

    public function test_it_treats_missing_optional_fields_as_null(): void
    {
        $member = Member::fromArray([
            'id' => 1,
            'created_at' => '2024-01-01T00:00:00+00:00',
            'updated_at' => '2024-01-01T00:00:00+00:00',
            'email' => '',
            'member_type' => 'full',
            'permalink' => 'https://example.mn.co/members/1',
        ]);

        $this->assertNull($member->firstName);
        $this->assertNull($member->referralCount);
        $this->assertSame([], $member->categories);
        $this->assertSame('', $member->email);
    }

    public function test_it_falls_back_to_full_for_an_unknown_member_type(): void
    {
        $member = Member::fromArray($this->payload(['member_type' => 'guest']));

        $this->assertSame(MemberType::Full, $member->memberType);
    }

    public function test_it_falls_back_to_full_when_the_member_type_is_missing_or_not_a_string(): void
    {
        $this->assertSame(MemberType::Full, Member::fromArray($this->payload(['member_type' => null]))->memberType);
        $this->assertSame(MemberType::Full, Member::fromArray($this->payload(['member_type' => 7]))->memberType);
    }
}
