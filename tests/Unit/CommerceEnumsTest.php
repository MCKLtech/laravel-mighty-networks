<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use MCKLtech\MightyNetworks\Enums\CancelTiming;
use MCKLtech\MightyNetworks\Enums\PlanStatus;
use MCKLtech\MightyNetworks\Enums\PricingType;
use PHPUnit\Framework\TestCase;

final class CommerceEnumsTest extends TestCase
{
    public function test_plan_status_has_backed_values_and_friendly_labels(): void
    {
        $this->assertSame('visible', PlanStatus::Visible->value);
        $this->assertSame('archived', PlanStatus::Archived->value);
        $this->assertSame('Visible', PlanStatus::Visible->toFriendly());
        $this->assertSame(PlanStatus::Legacy, PlanStatus::from('legacy'));
    }

    public function test_pricing_type_has_backed_values_and_friendly_labels(): void
    {
        $this->assertSame('free', PricingType::Free->value);
        $this->assertSame('one_time_installment', PricingType::OneTimeInstallment->value);
        $this->assertSame('token_gated', PricingType::TokenGated->value);
        $this->assertSame('Non-paid', PricingType::NonPaid->toFriendly());
        $this->assertSame(PricingType::Subscription, PricingType::from('subscription'));
    }

    public function test_cancel_timing_has_backed_values_and_friendly_labels(): void
    {
        $this->assertSame('now', CancelTiming::Now->value);
        $this->assertSame('end_of_billing_cycle', CancelTiming::EndOfBillingCycle->value);
        $this->assertSame('Immediately', CancelTiming::Now->toFriendly());
        $this->assertSame(CancelTiming::EndOfBillingCycle, CancelTiming::from('end_of_billing_cycle'));
    }
}
