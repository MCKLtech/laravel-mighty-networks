<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\DataTransferObjects\Me;
use MCKLtech\MightyNetworks\DataTransferObjects\Network;
use MCKLtech\MightyNetworks\DataTransferObjects\NetworkUser;
use MCKLtech\MightyNetworks\Requests\Admin\Network\GetMeRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Network\GetNetworkRequest;
use MCKLtech\MightyNetworks\Resources\NetworkResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class NetworkResourceTest extends TestCase
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
     * @return array<string, mixed>
     */
    private function userPayload(): array
    {
        return [
            'id' => 42,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'short_bio' => 'Community builder',
            'admin' => true,
            'last_visited_at' => '2024-04-01T08:00:00+00:00',
        ];
    }

    public function test_details_returns_the_network_and_asserts_the_request(): void
    {
        $mock = new MockClient([MockResponse::make($this->networkPayload(), 200)]);

        $resource = new NetworkResource($this->admin($mock), '12345');

        $network = $resource->details();

        $this->assertInstanceOf(Network::class, $network);
        $this->assertSame(12345, $network->id);
        $this->assertSame('paint-pals', $network->subdomain);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetNetworkRequest
                && $request->getMethod() === Method::GET
                && $request->resolveEndpoint() === 'networks/12345/';
        });
    }

    public function test_me_returns_the_authenticated_context_and_asserts_the_request(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'network' => $this->networkPayload(),
                'user' => $this->userPayload(),
            ], 200),
        ]);

        $resource = new NetworkResource($this->admin($mock), '12345');

        $me = $resource->me();

        $this->assertInstanceOf(Me::class, $me);
        $this->assertInstanceOf(Network::class, $me->network);
        $this->assertInstanceOf(NetworkUser::class, $me->user);
        $this->assertSame(12345, $me->network->id);
        $this->assertSame(42, $me->user->id);
        $this->assertSame('Jane Doe', $me->user->name);
        $this->assertTrue($me->user->admin);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetMeRequest
                && $request->getMethod() === Method::GET
                && $request->resolveEndpoint() === 'networks/12345/me';
        });
    }
}
