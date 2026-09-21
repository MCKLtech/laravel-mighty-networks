<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\CustomFields;

use MCKLtech\MightyNetworks\DataTransferObjects\CustomFieldAnswer;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `POST networks/{network_id}/custom_fields/{custom_field_id}/members/{member_id}/answers`
 *
 * Creates or replaces a member's answer. Only the fields relevant to the
 * custom field's `response_type` should be supplied; the rest are omitted.
 *
 * A `204` response means the answer was cleared and there is nothing to return.
 */
final class CreateCustomFieldAnswerRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<int, array{label: string, latitude: float|int, longitude: float|int}>|null  $locations
     * @param  array<int, int>|null  $segmentIds
     */
    public function __construct(
        int|string $networkId,
        protected readonly int $customFieldId,
        protected readonly int $memberId,
        protected readonly ?string $text = null,
        protected readonly ?int $number = null,
        protected readonly ?bool $booleanValue = null,
        protected readonly ?string $date = null,
        protected readonly ?string $startDate = null,
        protected readonly ?string $endDate = null,
        protected readonly ?int $month = null,
        protected readonly ?int $day = null,
        protected readonly ?string $url = null,
        protected readonly ?string $phoneNumber = null,
        protected readonly ?float $latitude = null,
        protected readonly ?float $longitude = null,
        protected readonly ?array $locations = null,
        protected readonly ?array $segmentIds = null,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf(
            'custom_fields/%d/members/%d/answers',
            $this->customFieldId,
            $this->memberId,
        ));
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return array_filter(
            [
                'text' => $this->text,
                'number' => $this->number,
                'boolean_value' => $this->booleanValue,
                'date' => $this->date,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'month' => $this->month,
                'day' => $this->day,
                'url' => $this->url,
                'phone_number' => $this->phoneNumber,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'locations' => $this->locations,
                'segment_ids' => $this->segmentIds,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): CustomFieldAnswer
    {
        $data = $response->json();

        return CustomFieldAnswer::fromArray(is_array($data) ? $data : []);
    }
}
