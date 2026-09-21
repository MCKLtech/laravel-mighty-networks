<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use MCKLtech\MightyNetworks\Collections\MemberCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Member;
use MCKLtech\MightyNetworks\DataTransferObjects\NewMemberData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateMemberData;
use MCKLtech\MightyNetworks\Enums\MembershipRole;
use MCKLtech\MightyNetworks\Enums\MemberType;
use PHPUnit\Framework\TestCase;

final class DataTransferObjectsTest extends TestCase
{
    public function test_new_member_data_emits_snake_case_and_omits_nulls(): void
    {
        $data = new NewMemberData(
            email: 'new@example.com',
            firstName: 'New',
            lastName: 'Member',
            role: MembershipRole::Host,
            memberType: MemberType::Limited,
        );

        $this->assertSame([
            'email' => 'new@example.com',
            'first_name' => 'New',
            'last_name' => 'Member',
            'role' => 'host',
            'member_type' => 'limited',
            'send_welcome_email' => true,
        ], $data->toArray());
    }

    public function test_new_member_data_accepts_a_raw_role_string_and_space_ids(): void
    {
        $data = new NewMemberData(
            email: 'new@example.com',
            firstName: 'New',
            lastName: 'Member',
            role: 'moderator',
            spaceIds: [1, 2],
            sendWelcomeEmail: false,
        );

        $this->assertSame('moderator', $data->toArray()['role']);
        $this->assertSame([1, 2], $data->toArray()['space_ids']);
        $this->assertFalse($data->toArray()['send_welcome_email']);
    }

    public function test_update_member_data_omits_null_fields(): void
    {
        $data = new UpdateMemberData(firstName: 'Janet', role: MembershipRole::Moderator);

        $this->assertSame([
            'first_name' => 'Janet',
            'role' => 'moderator',
        ], $data->toArray());
    }

    public function test_update_member_data_can_be_empty(): void
    {
        $this->assertSame([], (new UpdateMemberData)->toArray());
    }

    public function test_member_collection_is_typed(): void
    {
        $collection = new MemberCollection([$this->member()]);

        $this->assertInstanceOf(MemberCollection::class, $collection->ensure(Member::class));
        $this->assertCount(1, $collection);
    }

    private function member(): Member
    {
        return Member::fromArray([
            'id' => 1,
            'created_at' => '2024-01-01T00:00:00+00:00',
            'updated_at' => '2024-01-01T00:00:00+00:00',
            'email' => 'a@b.com',
            'member_type' => 'full',
            'permalink' => 'https://example.mn.co/members/1',
        ]);
    }
}
