<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Collections\AbuseReportCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\AbuseReport;
use MCKLtech\MightyNetworks\Enums\AbuseReportContextType;
use MCKLtech\MightyNetworks\Enums\AbuseReportType;
use MCKLtech\MightyNetworks\Requests\Admin\AbuseReports\ListAbuseReportsRequest;
use MCKLtech\MightyNetworks\Resources\AbuseReportsResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class AbuseReportsResourceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function abuseReportPayload(array $overrides = []): array
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

    public function test_all_returns_a_typed_collection_and_asserts_the_request(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->abuseReportPayload(['id' => 1]), $this->abuseReportPayload(['id' => 2])],
                'links' => ['self' => 'https://api.mn.co/...', 'next' => null],
            ], 200),
        ]);

        $resource = new AbuseReportsResource($this->admin($mock), '12345');

        $reports = $resource->all(perPage: 50);

        $this->assertInstanceOf(AbuseReportCollection::class, $reports);
        $this->assertCount(2, $reports);
        $this->assertSame([1, 2], $reports->map(static fn (AbuseReport $report): int => $report->id)->all());

        $first = $reports->first();
        $this->assertInstanceOf(AbuseReport::class, $first);
        $this->assertSame(AbuseReportType::Spam, $first->reportType);
        $this->assertSame(AbuseReportContextType::Comment, $first->contextType);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListAbuseReportsRequest
                && $request->getMethod() === Method::GET
                && $request->resolveEndpoint() === 'networks/12345/abuse_reports'
                && $request->query()->get('per_page') === 50;
        });
    }

    public function test_all_maps_every_documented_field(): void
    {
        $mock = new MockClient([
            MockResponse::make(['items' => [$this->abuseReportPayload()], 'links' => ['next' => null]], 200),
        ]);

        $resource = new AbuseReportsResource($this->admin($mock), '12345');

        $report = $resource->all()->first();

        $this->assertInstanceOf(AbuseReport::class, $report);
        $this->assertSame(11, $report->id);
        $this->assertSame(42, $report->memberId);
        $this->assertSame(77, $report->targetableId);
        $this->assertSame('Post', $report->targetableType);
        $this->assertSame('Spammy link', $report->reason);
        $this->assertSame(5, $report->contextId);
        $this->assertSame(7, $report->spaceId);
        $this->assertSame(12345, $report->networkId);
        $this->assertSame(3, $report->ignoredById);
    }

    public function test_it_paginates_the_items_links_envelope_and_terminates(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->abuseReportPayload(['id' => 1])],
                'links' => ['self' => 'https://api.mn.co/...?page=1', 'next' => 'https://api.mn.co/...?page=2'],
            ], 200),
            MockResponse::make([
                'items' => [$this->abuseReportPayload(['id' => 2])],
                'links' => ['self' => 'https://api.mn.co/...?page=2', 'next' => null],
            ], 200),
        ]);

        $resource = new AbuseReportsResource($this->admin($mock), '12345');

        $ids = [];

        foreach ($resource->paginate(perPage: 50)->items() as $report) {
            $this->assertInstanceOf(AbuseReport::class, $report);
            $ids[] = $report->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
    }

    public function test_it_paginates_the_data_meta_envelope_and_terminates(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => [$this->abuseReportPayload(['id' => 1])],
                'meta' => ['current_page' => 1, 'total_pages' => 2, 'total_count' => 2, 'per_page' => 1],
            ], 200),
            MockResponse::make([
                'data' => [$this->abuseReportPayload(['id' => 2])],
                'meta' => ['current_page' => 2, 'total_pages' => 2, 'total_count' => 2, 'per_page' => 1],
            ], 200),
        ]);

        $resource = new AbuseReportsResource($this->admin($mock), '12345');

        $ids = [];

        foreach ($resource->paginate(perPage: 1)->items() as $report) {
            $this->assertInstanceOf(AbuseReport::class, $report);
            $ids[] = $report->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
    }

    public function test_each_runs_a_callback_over_every_abuse_report(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->abuseReportPayload(['id' => 1])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new AbuseReportsResource($this->admin($mock), '12345');

        $ids = [];

        $resource->each(static function (AbuseReport $report) use (&$ids): void {
            $ids[] = $report->id;
        });

        $this->assertSame([1], $ids);
    }
}
