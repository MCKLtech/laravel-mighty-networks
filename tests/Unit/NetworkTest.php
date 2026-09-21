<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\Me;
use MCKLtech\MightyNetworks\DataTransferObjects\Network;
use MCKLtech\MightyNetworks\DataTransferObjects\NetworkUser;
use MCKLtech\MightyNetworks\Tests\TestCase;

final class NetworkTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function networkPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 12345,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'subdomain' => 'paint-pals',
            'title' => 'The Paint Pals Network',
            'subtitle' => 'A network for artists',
            'purpose' => 'Bring artists together',
            'description' => 'A great network',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function userPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 42,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'short_bio' => 'Community builder',
            'admin' => true,
            'last_visited_at' => '2024-04-01T08:00:00+00:00',
        ], $overrides);
    }

    public function test_network_from_array_maps_all_fields(): void
    {
        $network = Network::fromArray($this->networkPayload());

        $this->assertSame(12345, $network->id);
        $this->assertSame('paint-pals', $network->subdomain);
        $this->assertSame('The Paint Pals Network', $network->title);
        $this->assertSame('A network for artists', $network->subtitle);
        $this->assertSame('Bring artists together', $network->purpose);
        $this->assertSame('A great network', $network->description);
        $this->assertInstanceOf(CarbonImmutable::class, $network->createdAt);
        $this->assertSame('2024-01-15T10:30:00+00:00', $network->createdAt->toIso8601String());
    }

    public function test_network_user_from_array_maps_all_fields(): void
    {
        $user = NetworkUser::fromArray($this->userPayload());

        $this->assertSame(42, $user->id);
        $this->assertSame('Jane Doe', $user->name);
        $this->assertSame('jane@example.com', $user->email);
        $this->assertSame('Community builder', $user->shortBio);
        $this->assertTrue($user->admin);
        $this->assertInstanceOf(CarbonImmutable::class, $user->lastVisitedAt);
        $this->assertSame('2024-04-01T08:00:00+00:00', $user->lastVisitedAt->toIso8601String());
    }

    public function test_network_user_from_array_handles_a_missing_last_visited_at(): void
    {
        $user = NetworkUser::fromArray($this->userPayload(['last_visited_at' => null]));

        $this->assertNull($user->lastVisitedAt);
    }

    public function test_me_from_array_nests_the_network_and_user(): void
    {
        $me = Me::fromArray([
            'network' => $this->networkPayload(),
            'user' => $this->userPayload(),
        ]);

        $this->assertInstanceOf(Network::class, $me->network);
        $this->assertInstanceOf(NetworkUser::class, $me->user);
        $this->assertSame(12345, $me->network->id);
        $this->assertSame(42, $me->user->id);
    }

    public function test_me_from_array_tolerates_missing_nested_objects(): void
    {
        $me = Me::fromArray([]);

        $this->assertSame(0, $me->network->id);
        $this->assertSame(0, $me->user->id);
        $this->assertSame('', $me->network->title);
    }
}
