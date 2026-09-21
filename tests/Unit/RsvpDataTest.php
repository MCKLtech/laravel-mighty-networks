<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\NewRsvpData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateRsvpData;
use MCKLtech\MightyNetworks\Enums\RsvpStatus;
use PHPUnit\Framework\TestCase;

final class RsvpDataTest extends TestCase
{
    public function test_new_rsvp_data_emits_snake_case(): void
    {
        $data = new NewRsvpData(
            memberId: 42,
            status: RsvpStatus::Yes,
            instanceAt: CarbonImmutable::parse('2024-05-01T18:00:00+00:00'),
        );

        $this->assertSame([
            'member_id' => 42,
            'status' => 'yes',
            'instance_at' => '2024-05-01T18:00:00+00:00',
        ], $data->toArray());
    }

    public function test_new_rsvp_data_omits_a_null_instance_at_and_accepts_a_raw_status(): void
    {
        $data = new NewRsvpData(memberId: 42, status: 'no');

        $this->assertSame([
            'member_id' => 42,
            'status' => 'no',
        ], $data->toArray());
    }

    public function test_update_rsvp_data_omits_null_fields(): void
    {
        $data = new UpdateRsvpData(status: RsvpStatus::Maybe);

        $this->assertSame(['status' => 'maybe'], $data->toArray());
    }

    public function test_update_rsvp_data_can_be_empty(): void
    {
        $this->assertSame([], (new UpdateRsvpData)->toArray());
    }
}
