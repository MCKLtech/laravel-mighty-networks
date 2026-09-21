<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Collections\PurchaseCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Purchase;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Requests\Admin\Purchases\DeletePurchaseRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Purchases\GetPurchaseRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Purchases\ListPurchasesRequest;
use MCKLtech\MightyNetworks\Resources\PurchasesResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class PurchasesResourceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function purchasePayload(array $overrides = []): array
    {
        return array_merge([
            'member_id' => 42,
            'member_email' => 'jane@example.com',
            'member_first_name' => 'Jane',
            'member_last_name' => 'Doe',
            'member_time_zone' => 'America/Los_Angeles',
            'member_location' => 'San Francisco, CA',
            'member_referral_count' => 3,
            'member_avatar' => 'https://cdn.mn.co/avatars/42.jpg',
            'member_categories' => '[{"id":1,"title":"Founders"}]',
            'member_permalink' => 'https://example.mn.co/members/42',
            'member_ambassador_level' => 'gold',
            'plan' => [
                'id' => 7,
                'name' => 'Founders Circle',
                'amount' => 9900,
                'currency' => 'usd',
                'interval' => 'one_time',
                'type' => 'one-time-payment',
                'has_free_trial' => false,
            ],
            'purchase' => [
                'id' => 777,
                'tax_percent' => 8,
                'payment_platform' => 'stripe',
                'purchased_at' => '2024-02-15T00:00:00+00:00',
                'created_at' => '2024-02-15T00:00:00+00:00',
                'updated_at' => '2024-02-16T00:00:00+00:00',
            ],
        ], $overrides);
    }

    public function test_all_returns_a_purchase_collection_and_sends_filters(): void
    {
        $mock = new MockClient([
            MockResponse::make(['items' => [$this->purchasePayload()], 'links' => ['next' => null]], 200),
        ]);

        $resource = new PurchasesResource($this->admin($mock), '12345');

        $purchases = $resource->all(planId: 7, memberId: 42, perPage: 10);

        $this->assertInstanceOf(PurchaseCollection::class, $purchases);
        $this->assertCount(1, $purchases);
        $this->assertSame(9900, $purchases->first()?->plan->amount);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListPurchasesRequest
                && $request->resolveEndpoint() === 'networks/12345/purchases'
                && $request->query()->get('plan_id') === 7
                && $request->query()->get('member_id') === 42
                && $request->query()->get('per_page') === 10;
        });
    }

    public function test_find_by_id_returns_a_typed_purchase_with_a_trailing_slash(): void
    {
        $mock = new MockClient([MockResponse::make($this->purchasePayload(), 200)]);

        $resource = new PurchasesResource($this->admin($mock), '12345');

        $purchase = $resource->findById(777);

        $this->assertInstanceOf(Purchase::class, $purchase);
        $this->assertSame(777, $purchase->purchase->id);
        $this->assertSame('jane@example.com', $purchase->memberEmail);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetPurchaseRequest
                && $request->getMethod() === Method::GET
                && $request->resolveEndpoint() === 'networks/12345/purchases/777/';
        });
    }

    public function test_find_by_id_or_null_returns_null_on_a_404(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Not found'], 404)]);

        $resource = new PurchasesResource($this->admin($mock), '12345');

        $this->assertNull($resource->findByIdOrNull(777));
    }

    public function test_revoke_sends_the_immediate_query_parameter(): void
    {
        $mock = new MockClient([MockResponse::make($this->purchasePayload(), 200)]);

        $resource = new PurchasesResource($this->admin($mock), '12345');

        $purchase = $resource->revoke(777, true);

        $this->assertInstanceOf(Purchase::class, $purchase);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeletePurchaseRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/purchases/777/'
                && $request->query()->get('immediate') === true;
        });
    }

    public function test_revoke_omits_immediate_when_not_provided(): void
    {
        $mock = new MockClient([MockResponse::make($this->purchasePayload(), 200)]);

        $resource = new PurchasesResource($this->admin($mock), '12345');

        $resource->revoke(777);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeletePurchaseRequest
                && $request->query()->get('immediate') === null;
        });
    }

    public function test_delete_is_an_alias_for_revoke(): void
    {
        $mock = new MockClient([MockResponse::make($this->purchasePayload(), 200)]);

        $resource = new PurchasesResource($this->admin($mock), '12345');

        $purchase = $resource->delete(777, false);

        $this->assertSame(777, $purchase->purchase->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeletePurchaseRequest
                && $request->query()->get('immediate') === false;
        });
    }

    public function test_a_404_throws_a_not_found_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Not found'], 404)]);

        $resource = new PurchasesResource($this->admin($mock), '12345');

        $this->expectException(NotFoundException::class);

        $resource->findById(777);
    }

    public function test_it_paginates_purchases_and_terminates(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->purchasePayload(['purchase' => ['id' => 1, 'purchased_at' => '2024-01-01T00:00:00+00:00', 'created_at' => '2024-01-01T00:00:00+00:00', 'updated_at' => '2024-01-01T00:00:00+00:00']])],
                'links' => ['next' => 'https://api.mn.co/...?page=2'],
            ], 200),
            MockResponse::make([
                'items' => [$this->purchasePayload(['purchase' => ['id' => 2, 'purchased_at' => '2024-01-01T00:00:00+00:00', 'created_at' => '2024-01-01T00:00:00+00:00', 'updated_at' => '2024-01-01T00:00:00+00:00']])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new PurchasesResource($this->admin($mock), '12345');

        $ids = [];

        foreach ($resource->paginate(perPage: 1)->items() as $purchase) {
            $this->assertInstanceOf(Purchase::class, $purchase);
            $ids[] = $purchase->purchase->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
    }
}
