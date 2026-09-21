<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLBillingPlan;
use MCKLtech\MightyNetworks\GraphQL\GraphQLClient;
use MCKLtech\MightyNetworks\Requests\GraphQL\BillingPlanQuery;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class GraphQLBillingPlanTest extends TestCase
{
    private function client(MockClient $mock): GraphQLClient
    {
        return new GraphQLClient($this->graphql($mock), '12345');
    }

    public function test_billing_plan_returns_a_typed_plan_and_passes_the_canonical_name(): void
    {
        $mock = new MockClient([MockResponse::make([
            'data' => ['billingPlan' => [
                'canonicalName' => 'creator',
                'displayName' => 'Creator',
                'group' => 'standard',
                'level' => 2,
                'features' => [
                    ['key' => 'courses', 'label' => 'Courses', 'state' => 'ENABLED', 'value' => 10],
                ],
            ]],
        ], 200)]);

        $plan = $this->client($mock)->billingPlan('creator');

        $this->assertInstanceOf(GraphQLBillingPlan::class, $plan);
        $this->assertSame('creator', $plan->canonicalName);
        $this->assertSame('Creator', $plan->displayName);
        $this->assertSame(2, $plan->level);
        $this->assertCount(1, $plan->features);
        $this->assertSame('courses', $plan->features[0]->key);
        $this->assertSame(10, $plan->features[0]->value);

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof BillingPlanQuery) {
                return false;
            }

            $body = $request->body()->all();

            return $body['variables'] === ['canonicalName' => 'creator']
                && str_contains($body['query'], 'billingPlan(canonicalName: $canonicalName)');
        });
    }

    public function test_billing_plan_returns_null_when_not_found(): void
    {
        $mock = new MockClient([MockResponse::make(['data' => ['billingPlan' => null]], 200)]);

        $this->assertNull($this->client($mock)->billingPlan('missing'));
    }
}
