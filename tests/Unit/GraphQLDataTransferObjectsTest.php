<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLMember;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLNetwork;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLNode;
use PHPUnit\Framework\TestCase;

final class GraphQLDataTransferObjectsTest extends TestCase
{
    public function test_graphql_member_from_array_maps_every_selected_field(): void
    {
        $payload = [
            'id' => 'gid://mighty/Member/42',
            'resourceId' => '42',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'avatarUrl' => 'https://cdn.mn.co/avatars/42.jpg',
            'shortBio' => 'Founder',
            'timeZone' => 'America/Los_Angeles',
            'memberType' => 'FULL_MEMBER',
            'networkRole' => 'HOST',
            'isLimitedMember' => true,
            'referralCount' => 3,
            'followedMemberCount' => 4,
            'followerCount' => 5,
            'joinedAt' => '2024-01-15T10:30:00+00:00',
            'lastActiveAt' => '2024-04-01T08:00:00+00:00',
            'updatedAt' => '2024-04-02T08:00:00+00:00',
            'url' => 'https://test-network.mn.co/members/42',
            'ambassadorLevel' => 'gold',
            'ambassadorLevelId' => 9,
            'primaryProfileFieldLabel' => 'Company',
            'privateChatEnabled' => false,
            'gamificationStreaksPublicCalendarEnabled' => true,
            'hasPushEnabled' => true,
            'hasConfirmedInstallation' => false,
        ];

        $member = GraphQLMember::fromArray($payload);

        $this->assertSame('gid://mighty/Member/42', $member->id);
        $this->assertSame('42', $member->resourceId);
        $this->assertSame('Jane Doe', $member->name);
        $this->assertSame('FULL_MEMBER', $member->memberType);
        $this->assertSame('HOST', $member->networkRole);
        $this->assertTrue($member->isLimitedMember);
        $this->assertSame(3, $member->referralCount);
        $this->assertSame(4, $member->followedMemberCount);
        $this->assertSame(5, $member->followerCount);
        $this->assertSame('2024-01-15T10:30:00+00:00', $member->joinedAt?->toIso8601String());
        $this->assertSame('2024-04-01T08:00:00+00:00', $member->lastActiveAt?->toIso8601String());
        $this->assertSame('2024-04-02T08:00:00+00:00', $member->updatedAt?->toIso8601String());
        $this->assertSame('gold', $member->ambassadorLevel);
        $this->assertSame(9, $member->ambassadorLevelId);
        $this->assertFalse($member->privateChatEnabled);
        $this->assertTrue($member->gamificationStreaksPublicCalendarEnabled);
        $this->assertTrue($member->hasPushEnabled);
        $this->assertFalse($member->hasConfirmedInstallation);
        $this->assertSame($payload, $member->raw);
    }

    public function test_graphql_member_from_array_defaults_missing_fields(): void
    {
        $member = GraphQLMember::fromArray([]);

        $this->assertSame('', $member->id);
        $this->assertSame('', $member->resourceId);
        $this->assertNull($member->name);
        $this->assertNull($member->email);
        $this->assertNull($member->joinedAt);
        $this->assertNull($member->referralCount);
        $this->assertFalse($member->isLimitedMember);
        $this->assertSame([], $member->raw);
    }

    public function test_graphql_member_from_array_treats_empty_and_non_scalar_values_as_null(): void
    {
        $member = GraphQLMember::fromArray([
            'name' => '',
            'email' => '',
            'firstName' => 123,
            'referralCount' => 'not-a-number',
            'privateChatEnabled' => 'yes',
            'joinedAt' => '',
        ]);

        $this->assertNull($member->name);
        $this->assertNull($member->email);
        $this->assertNull($member->firstName);
        $this->assertNull($member->referralCount);
        $this->assertNull($member->privateChatEnabled);
        $this->assertNull($member->joinedAt);
    }

    public function test_graphql_member_from_array_casts_numeric_strings(): void
    {
        $member = GraphQLMember::fromArray([
            'referralCount' => '7',
            'followerCount' => '11',
        ]);

        $this->assertSame(7, $member->referralCount);
        $this->assertSame(11, $member->followerCount);
    }

    public function test_graphql_network_from_array_maps_every_selected_field(): void
    {
        $payload = [
            'id' => 'gid://mighty/Network/1',
            'resourceId' => '1',
            'title' => 'Test Network',
            'subtitle' => 'A community',
            'description' => 'Welcome',
            'slug' => 'test-network',
            'url' => 'https://test-network.mn.co',
            'avatarUrl' => 'https://cdn.mn.co/avatar.png',
            'headerUrl' => 'https://cdn.mn.co/header.png',
            'hostHeroImageUrl' => 'https://cdn.mn.co/hero.png',
            'defaultLocale' => 'en',
            'purpose' => 'Community',
            'discoverable' => true,
            'explorable' => true,
            'joinable' => true,
            'createdAt' => '2024-01-01T00:00:00+00:00',
            'updatedAt' => '2024-02-01T00:00:00+00:00',
        ];

        $network = GraphQLNetwork::fromArray($payload);

        $this->assertSame('gid://mighty/Network/1', $network->id);
        $this->assertSame('Test Network', $network->title);
        $this->assertSame('A community', $network->subtitle);
        $this->assertSame('en', $network->defaultLocale);
        $this->assertTrue($network->discoverable);
        $this->assertTrue($network->explorable);
        $this->assertTrue($network->joinable);
        $this->assertSame('2024-01-01T00:00:00+00:00', $network->createdAt?->toIso8601String());
        $this->assertSame($payload, $network->raw);
    }

    public function test_graphql_network_from_array_defaults_missing_fields(): void
    {
        $network = GraphQLNetwork::fromArray([]);

        $this->assertSame('', $network->id);
        $this->assertNull($network->title);
        $this->assertNull($network->createdAt);
        $this->assertFalse($network->discoverable);
        $this->assertFalse($network->explorable);
        $this->assertFalse($network->joinable);
    }

    public function test_graphql_node_from_array_maps_the_typename(): void
    {
        $payload = ['__typename' => 'Member', 'id' => 'gid://mighty/Member/42'];

        $node = GraphQLNode::fromArray($payload);

        $this->assertSame('gid://mighty/Member/42', $node->id);
        $this->assertSame('Member', $node->typename);
        $this->assertSame($payload, $node->raw);
    }

    public function test_graphql_node_from_array_defaults_missing_values(): void
    {
        $node = GraphQLNode::fromArray([]);

        $this->assertSame('', $node->id);
        $this->assertNull($node->typename);
        $this->assertSame([], $node->raw);
    }

    public function test_graphql_member_joined_at_is_an_immutable_carbon_instance(): void
    {
        $member = GraphQLMember::fromArray(['joinedAt' => '2024-01-15T10:30:00+00:00']);

        $this->assertInstanceOf(CarbonImmutable::class, $member->joinedAt);
    }
}
