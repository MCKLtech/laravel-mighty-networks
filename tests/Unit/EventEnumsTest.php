<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use MCKLtech\MightyNetworks\Enums\EventFrequency;
use MCKLtech\MightyNetworks\Enums\RsvpStatus;
use PHPUnit\Framework\TestCase;

final class EventEnumsTest extends TestCase
{
    public function test_rsvp_status_has_backed_values_and_friendly_labels(): void
    {
        $this->assertSame('yes', RsvpStatus::Yes->value);
        $this->assertSame('maybe', RsvpStatus::Maybe->value);
        $this->assertSame('no', RsvpStatus::No->value);
        $this->assertSame('Yes', RsvpStatus::Yes->toFriendly());
        $this->assertSame(RsvpStatus::Maybe, RsvpStatus::from('maybe'));
    }

    public function test_event_frequency_has_backed_values_and_friendly_labels(): void
    {
        $this->assertSame('daily', EventFrequency::Daily->value);
        $this->assertSame('weekly', EventFrequency::Weekly->value);
        $this->assertSame('monthly', EventFrequency::Monthly->value);
        $this->assertSame('yearly', EventFrequency::Yearly->value);
        $this->assertSame('Weekly', EventFrequency::Weekly->toFriendly());
        $this->assertSame(EventFrequency::Yearly, EventFrequency::from('yearly'));
    }
}
