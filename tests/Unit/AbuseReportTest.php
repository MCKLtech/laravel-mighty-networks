<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\AbuseReport;
use MCKLtech\MightyNetworks\Enums\AbuseReportContextType;
use MCKLtech\MightyNetworks\Enums\AbuseReportType;
use PHPUnit\Framework\TestCase;

final class AbuseReportTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'id' => 11,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'member_id' => 42,
            'targetable_id' => 77,
            'targetable_type' => 'Post',
            'report_type' => 'spam',
            'reason' => 'Spammy link',
            'context_id' => 5,
            'context_type' => 'Comment',
            'space_id' => 7,
            'network_id' => 12345,
            'ignored_at' => '2024-04-01T08:00:00+00:00',
            'ignored_by_id' => 3,
        ], $overrides);
    }

    public function test_from_array_maps_all_fields(): void
    {
        $report = AbuseReport::fromArray($this->payload());

        $this->assertSame(11, $report->id);
        $this->assertSame(42, $report->memberId);
        $this->assertSame(77, $report->targetableId);
        $this->assertSame('Post', $report->targetableType);
        $this->assertSame(AbuseReportType::Spam, $report->reportType);
        $this->assertSame('Spammy link', $report->reason);
        $this->assertSame(5, $report->contextId);
        $this->assertSame(AbuseReportContextType::Comment, $report->contextType);
        $this->assertSame(7, $report->spaceId);
        $this->assertSame(12345, $report->networkId);
        $this->assertSame(3, $report->ignoredById);
        $this->assertInstanceOf(CarbonImmutable::class, $report->createdAt);
        $this->assertInstanceOf(CarbonImmutable::class, $report->ignoredAt);
        $this->assertSame('2024-04-01T08:00:00+00:00', $report->ignoredAt->toIso8601String());
    }

    public function test_from_array_handles_the_optional_fields_being_absent(): void
    {
        $report = AbuseReport::fromArray([
            'id' => 11,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'member_id' => 42,
            'targetable_id' => 77,
            'targetable_type' => 'Post',
            'report_type' => 'offensive',
            'network_id' => 12345,
        ]);

        $this->assertSame(AbuseReportType::Offensive, $report->reportType);
        $this->assertNull($report->reason);
        $this->assertNull($report->contextId);
        $this->assertNull($report->contextType);
        $this->assertNull($report->spaceId);
        $this->assertNull($report->ignoredAt);
        $this->assertNull($report->ignoredById);
    }

    public function test_from_array_maps_unknown_enum_values_to_null(): void
    {
        $report = AbuseReport::fromArray($this->payload([
            'report_type' => 'something_new',
            'context_type' => 'SomethingNew',
        ]));

        $this->assertNull($report->reportType);
        $this->assertNull($report->contextType);
    }

    public function test_abuse_report_type_has_backed_values(): void
    {
        $this->assertSame('spam', AbuseReportType::Spam->value);
        $this->assertSame('offensive', AbuseReportType::Offensive->value);
        $this->assertSame('impersonation', AbuseReportType::Impersonation->value);
        $this->assertSame('other', AbuseReportType::Other->value);
        $this->assertSame('Impersonation', AbuseReportType::Impersonation->toFriendly());
    }

    public function test_abuse_report_context_type_has_backed_values(): void
    {
        $this->assertSame('Post', AbuseReportContextType::Post->value);
        $this->assertSame('Comment', AbuseReportContextType::Comment->value);
        $this->assertSame('Space', AbuseReportContextType::Space->value);
        $this->assertSame('Space', AbuseReportContextType::Space->toFriendly());
    }
}
