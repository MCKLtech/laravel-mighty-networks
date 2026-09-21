<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\Invite;
use MCKLtech\MightyNetworks\DataTransferObjects\NewInviteData;
use MCKLtech\MightyNetworks\DataTransferObjects\Plan;
use MCKLtech\MightyNetworks\DataTransferObjects\PlanPrice;
use MCKLtech\MightyNetworks\DataTransferObjects\Purchase;
use MCKLtech\MightyNetworks\DataTransferObjects\PurchaseDetail;
use MCKLtech\MightyNetworks\DataTransferObjects\Subscription;
use MCKLtech\MightyNetworks\DataTransferObjects\SubscriptionDetail;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateInviteData;
use MCKLtech\MightyNetworks\Enums\PlanStatus;
use MCKLtech\MightyNetworks\Enums\PricingType;
use PHPUnit\Framework\TestCase;

final class CommerceDataTransferObjectsTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function planPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 7,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'name' => 'Founders Circle',
            'description' => 'High-touch membership',
            'status' => 'visible',
            'pricing_type' => 'subscription',
            'visible_to_members' => true,
            'external' => false,
            'multiple' => true,
            'permalink' => 'https://example.mn.co/plans/founders',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function invitePayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 11,
            'created_at' => '2024-02-01T09:00:00+00:00',
            'updated_at' => '2024-02-02T09:30:00+00:00',
            'recipient_email' => 'claude@example.com',
            'recipient_first_name' => 'Claude',
            'recipient_last_name' => 'Monet',
            'sender_id' => 99,
            'user_id' => 5,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function planPricePayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 7,
            'name' => 'Founders Circle',
            'amount' => 4900,
            'currency' => 'usd',
            'interval' => 'month',
            'type' => 'subscription',
            'has_free_trial' => true,
        ], $overrides);
    }

    public function test_plan_maps_every_documented_field_and_enums(): void
    {
        $plan = Plan::fromArray($this->planPayload());

        $this->assertSame(7, $plan->id);
        $this->assertSame('Founders Circle', $plan->name);
        $this->assertSame('High-touch membership', $plan->description);
        $this->assertSame(PlanStatus::Visible, $plan->status);
        $this->assertSame(PricingType::Subscription, $plan->pricingType);
        $this->assertTrue($plan->visibleToMembers);
        $this->assertFalse($plan->external);
        $this->assertTrue($plan->multiple);
        $this->assertSame('https://example.mn.co/plans/founders', $plan->permalink);
        $this->assertInstanceOf(CarbonImmutable::class, $plan->createdAt);
        $this->assertSame('2024-01-15T10:30:00+00:00', $plan->createdAt->toIso8601String());
    }

    public function test_plan_maps_unrecognised_status_and_pricing_to_null(): void
    {
        $plan = Plan::fromArray($this->planPayload(['status' => 'brand_new', 'pricing_type' => 'crypto']));

        $this->assertNull($plan->status);
        $this->assertNull($plan->pricingType);
    }

    public function test_plan_treats_missing_optionals_as_null(): void
    {
        $plan = Plan::fromArray([
            'id' => 1,
            'created_at' => '2024-01-01T00:00:00+00:00',
            'updated_at' => '2024-01-01T00:00:00+00:00',
            'name' => 'Free',
            'permalink' => 'https://example.mn.co/plans/free',
        ]);

        $this->assertNull($plan->description);
        $this->assertNull($plan->status);
        $this->assertNull($plan->pricingType);
        $this->assertNull($plan->visibleToMembers);
        $this->assertNull($plan->external);
        $this->assertNull($plan->multiple);
    }

    public function test_plan_price_preserves_amount_in_cents(): void
    {
        $price = PlanPrice::fromArray($this->planPricePayload());

        $this->assertSame(4900, $price->amount);
        $this->assertSame('usd', $price->currency);
        $this->assertSame('month', $price->interval);
        $this->assertSame('subscription', $price->type);
        $this->assertTrue($price->hasFreeTrial);
        $this->assertSame(7, $price->id);
        $this->assertSame('Founders Circle', $price->name);
    }

    public function test_plan_price_allows_deleted_bundle_id_and_name(): void
    {
        $price = PlanPrice::fromArray($this->planPricePayload(['id' => null, 'name' => null]));

        $this->assertNull($price->id);
        $this->assertNull($price->name);
    }

    public function test_subscription_maps_member_and_nested_billing_objects(): void
    {
        $subscription = Subscription::fromArray([
            'member_id' => 42,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'email' => 'jane@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'time_zone' => 'America/Los_Angeles',
            'location' => 'San Francisco, CA',
            'referral_count' => 3,
            'avatar' => 'https://cdn.mn.co/avatars/42.jpg',
            'categories' => [['id' => 1, 'title' => 'Founders']],
            'permalink' => 'https://example.mn.co/members/42',
            'ambassador_level' => 'gold',
            'plan' => $this->planPricePayload(),
            'subscription' => [
                'id' => 555,
                'current_period_start' => '2024-03-01T00:00:00+00:00',
                'current_period_end' => '2024-04-01T00:00:00+00:00',
                'payment_platform' => 'stripe',
                'canceled_at' => null,
                'trial_length' => 14,
                'trial_start' => '2024-02-15T00:00:00+00:00',
                'trial_end' => '2024-02-29T00:00:00+00:00',
                'purchased_at' => '2024-02-15T00:00:00+00:00',
            ],
        ]);

        $this->assertSame(42, $subscription->memberId);
        $this->assertSame('jane@example.com', $subscription->email);
        $this->assertSame(3, $subscription->referralCount);
        $this->assertSame([['id' => 1, 'title' => 'Founders']], $subscription->categories);
        $this->assertSame('gold', $subscription->ambassadorLevel);
        $this->assertSame(4900, $subscription->plan->amount);
        $this->assertSame(555, $subscription->subscription->id);
        $this->assertSame('stripe', $subscription->subscription->paymentPlatform);
        $this->assertSame(14, $subscription->subscription->trialLength);
        $this->assertNull($subscription->subscription->canceledAt);
        $this->assertSame(
            '2024-04-01T00:00:00+00:00',
            $subscription->subscription->currentPeriodEnd?->toIso8601String(),
        );
    }

    public function test_subscription_detail_parses_immutable_dates(): void
    {
        $detail = SubscriptionDetail::fromArray([
            'id' => 1,
            'purchased_at' => '2024-02-15T00:00:00+00:00',
        ]);

        $this->assertInstanceOf(CarbonImmutable::class, $detail->purchasedAt);
        $this->assertNull($detail->currentPeriodEnd);
    }

    public function test_purchase_maps_member_plan_and_nested_purchase(): void
    {
        $purchase = Purchase::fromArray([
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
            'plan' => $this->planPricePayload(),
            'purchase' => [
                'id' => 777,
                'tax_percent' => 8,
                'payment_platform' => 'web3',
                'purchased_at' => '2024-02-15T00:00:00+00:00',
                'created_at' => '2024-02-15T00:00:00+00:00',
                'updated_at' => '2024-02-16T00:00:00+00:00',
            ],
        ]);

        $this->assertSame(42, $purchase->memberId);
        $this->assertSame('jane@example.com', $purchase->memberEmail);
        $this->assertSame('Jane', $purchase->memberFirstName);
        $this->assertSame(3, $purchase->memberReferralCount);
        $this->assertSame('[{"id":1,"title":"Founders"}]', $purchase->memberCategories);
        $this->assertSame(4900, $purchase->plan->amount);
        $this->assertSame(777, $purchase->purchase->id);
        $this->assertSame(8, $purchase->purchase->taxPercent);
        $this->assertSame('web3', $purchase->purchase->paymentPlatform);
    }

    public function test_purchase_detail_parses_immutable_dates(): void
    {
        $detail = PurchaseDetail::fromArray([
            'id' => 1,
            'purchased_at' => '2024-02-15T00:00:00+00:00',
            'created_at' => '2024-02-15T00:00:00+00:00',
            'updated_at' => '2024-02-16T00:00:00+00:00',
        ]);

        $this->assertInstanceOf(CarbonImmutable::class, $detail->createdAt);
        $this->assertInstanceOf(CarbonImmutable::class, $detail->updatedAt);
        $this->assertNull($detail->taxPercent);
    }

    public function test_invite_maps_every_documented_field(): void
    {
        $invite = Invite::fromArray($this->invitePayload());

        $this->assertSame(11, $invite->id);
        $this->assertSame('claude@example.com', $invite->recipientEmail);
        $this->assertSame('Claude', $invite->recipientFirstName);
        $this->assertSame('Monet', $invite->recipientLastName);
        $this->assertSame(99, $invite->senderId);
        $this->assertSame(5, $invite->userId);
        $this->assertInstanceOf(CarbonImmutable::class, $invite->updatedAt);
    }

    public function test_invite_treats_absent_user_id_as_null(): void
    {
        $invite = Invite::fromArray($this->invitePayload(['user_id' => null]));

        $this->assertNull($invite->userId);
    }

    public function test_new_invite_data_emits_snake_case_and_omits_nulls(): void
    {
        $data = new NewInviteData(recipientEmail: 'new@example.com');

        $this->assertSame(['recipient_email' => 'new@example.com'], $data->toArray());

        $data = new NewInviteData(
            recipientEmail: 'new@example.com',
            recipientFirstName: 'New',
            recipientLastName: 'Person',
            userId: 5,
        );

        $this->assertSame([
            'recipient_email' => 'new@example.com',
            'recipient_first_name' => 'New',
            'recipient_last_name' => 'Person',
            'user_id' => 5,
        ], $data->toArray());
    }

    public function test_update_invite_data_can_be_empty(): void
    {
        $this->assertSame([], (new UpdateInviteData)->toArray());

        $this->assertSame(
            ['recipient_last_name' => 'Monet'],
            (new UpdateInviteData(recipientLastName: 'Monet'))->toArray(),
        );
    }
}
