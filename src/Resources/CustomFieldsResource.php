<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use MCKLtech\MightyNetworks\Collections\CustomFieldAnswerCollection;
use MCKLtech\MightyNetworks\Collections\CustomFieldCollection;
use MCKLtech\MightyNetworks\Collections\CustomFieldOptionCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\CustomField;
use MCKLtech\MightyNetworks\DataTransferObjects\CustomFieldAnswer;
use MCKLtech\MightyNetworks\DataTransferObjects\CustomFieldOption;
use MCKLtech\MightyNetworks\DataTransferObjects\NewCustomFieldData;
use MCKLtech\MightyNetworks\DataTransferObjects\NewCustomFieldOptionData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateCustomFieldData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateCustomFieldOptionData;
use MCKLtech\MightyNetworks\Enums\CustomFieldResponseType;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Pagination\AdminPagedPaginator;
use MCKLtech\MightyNetworks\Requests\Admin\CustomFields\CreateCustomFieldAnswerRequest;
use MCKLtech\MightyNetworks\Requests\Admin\CustomFields\CreateCustomFieldOptionRequest;
use MCKLtech\MightyNetworks\Requests\Admin\CustomFields\CreateCustomFieldRequest;
use MCKLtech\MightyNetworks\Requests\Admin\CustomFields\DeleteCustomFieldAnswerRequest;
use MCKLtech\MightyNetworks\Requests\Admin\CustomFields\DeleteCustomFieldOptionRequest;
use MCKLtech\MightyNetworks\Requests\Admin\CustomFields\DeleteCustomFieldRequest;
use MCKLtech\MightyNetworks\Requests\Admin\CustomFields\GetCustomFieldAnswerRequest;
use MCKLtech\MightyNetworks\Requests\Admin\CustomFields\GetCustomFieldOptionRequest;
use MCKLtech\MightyNetworks\Requests\Admin\CustomFields\GetCustomFieldRequest;
use MCKLtech\MightyNetworks\Requests\Admin\CustomFields\ListCustomFieldAnswersRequest;
use MCKLtech\MightyNetworks\Requests\Admin\CustomFields\ListCustomFieldOptionsRequest;
use MCKLtech\MightyNetworks\Requests\Admin\CustomFields\ListCustomFieldsRequest;
use MCKLtech\MightyNetworks\Requests\Admin\CustomFields\ReplaceCustomFieldOptionRequest;
use MCKLtech\MightyNetworks\Requests\Admin\CustomFields\ReplaceCustomFieldRequest;
use MCKLtech\MightyNetworks\Requests\Admin\CustomFields\UpdateCustomFieldOptionRequest;
use MCKLtech\MightyNetworks\Requests\Admin\CustomFields\UpdateCustomFieldRequest;
use Saloon\Http\Response;

/**
 * The public custom fields API, including their dropdown options and each
 * member's answers.
 */
final class CustomFieldsResource extends Resource
{
    /**
     * Find a custom field by its numeric ID.
     *
     * @throws NotFoundException
     */
    public function findById(int $id): CustomField
    {
        return $this->customFieldFrom(
            $this->connector()->send(new GetCustomFieldRequest($this->networkId(), $id)),
        );
    }

    /**
     * Like {@see findById()} but returns null instead of throwing a 404.
     */
    public function findByIdOrNull(int $id): ?CustomField
    {
        try {
            return $this->findById($id);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Fetch the first page of custom fields, optionally filtered by response type.
     */
    public function all(
        CustomFieldResponseType|string|null $responseType = null,
        int $perPage = 25,
    ): CustomFieldCollection {
        return $this->collectionFrom(
            $this->connector()->send(new ListCustomFieldsRequest(
                networkId: $this->networkId(),
                responseType: $responseType,
                perPage: $perPage,
            )),
        );
    }

    /**
     * Lazily paginate through every custom field, optionally filtered by response type.
     */
    public function paginate(
        CustomFieldResponseType|string|null $responseType = null,
        int $perPage = 25,
    ): AdminPagedPaginator {
        return $this->connector()
            ->paginate(new ListCustomFieldsRequest($this->networkId(), $responseType))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every custom field, fetching pages lazily.
     *
     * @param  callable(CustomField): void  $callback
     */
    public function each(callable $callback, int $perPage = 25): void
    {
        foreach ($this->paginate(perPage: $perPage)->items() as $item) {
            $callback($this->ensureCustomField($item));
        }
    }

    /**
     * Create a new custom field.
     */
    public function create(NewCustomFieldData $data): CustomField
    {
        return $this->customFieldFrom(
            $this->connector()->send(new CreateCustomFieldRequest($this->networkId(), $data)),
        );
    }

    /**
     * Partially update an existing custom field (HTTP `PATCH`).
     */
    public function update(int $id, UpdateCustomFieldData $data): CustomField
    {
        return $this->customFieldFrom(
            $this->connector()->send(new UpdateCustomFieldRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Replace an existing custom field with the supplied representation (HTTP `PUT`).
     */
    public function replace(int $id, UpdateCustomFieldData $data): CustomField
    {
        return $this->customFieldFrom(
            $this->connector()->send(new ReplaceCustomFieldRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Permanently delete a custom field.
     */
    public function delete(int $id): void
    {
        $this->connector()->send(new DeleteCustomFieldRequest($this->networkId(), $id));
    }

    /**
     * Fetch the first page of a custom field's options.
     */
    public function options(int $customFieldId, ?string $term = null, int $perPage = 25): CustomFieldOptionCollection
    {
        return $this->optionCollectionFrom(
            $this->connector()->send(new ListCustomFieldOptionsRequest(
                networkId: $this->networkId(),
                customFieldId: $customFieldId,
                term: $term,
                perPage: $perPage,
            )),
        );
    }

    /**
     * Lazily paginate through every option of a custom field.
     */
    public function paginateOptions(
        int $customFieldId,
        ?string $term = null,
        int $perPage = 25,
    ): AdminPagedPaginator {
        return $this->connector()
            ->paginate(new ListCustomFieldOptionsRequest($this->networkId(), $customFieldId, $term))
            ->setPerPageLimit($perPage);
    }

    /**
     * Find a single option of a custom field.
     *
     * @throws NotFoundException
     */
    public function findOption(int $customFieldId, int $optionId): CustomFieldOption
    {
        return $this->optionFrom(
            $this->connector()->send(new GetCustomFieldOptionRequest($this->networkId(), $customFieldId, $optionId)),
        );
    }

    /**
     * Create a new option for a custom field.
     */
    public function createOption(int $customFieldId, NewCustomFieldOptionData $data): CustomFieldOption
    {
        return $this->optionFrom(
            $this->connector()->send(new CreateCustomFieldOptionRequest(
                $this->networkId(),
                $customFieldId,
                $data,
            )),
        );
    }

    /**
     * Partially update an existing option of a custom field (HTTP `PATCH`).
     */
    public function updateOption(
        int $customFieldId,
        int $optionId,
        UpdateCustomFieldOptionData $data,
    ): CustomFieldOption {
        return $this->optionFrom(
            $this->connector()->send(new UpdateCustomFieldOptionRequest(
                $this->networkId(),
                $customFieldId,
                $optionId,
                $data,
            )),
        );
    }

    /**
     * Replace an existing option of a custom field (HTTP `PUT`).
     */
    public function replaceOption(
        int $customFieldId,
        int $optionId,
        UpdateCustomFieldOptionData $data,
    ): CustomFieldOption {
        return $this->optionFrom(
            $this->connector()->send(new ReplaceCustomFieldOptionRequest(
                $this->networkId(),
                $customFieldId,
                $optionId,
                $data,
            )),
        );
    }

    /**
     * Permanently delete an option from a custom field.
     */
    public function deleteOption(int $customFieldId, int $optionId): void
    {
        $this->connector()->send(new DeleteCustomFieldOptionRequest(
            $this->networkId(),
            $customFieldId,
            $optionId,
        ));
    }

    /**
     * Fetch the first page of a member's answers to a custom field.
     */
    public function answers(int $customFieldId, int $memberId, int $perPage = 25): CustomFieldAnswerCollection
    {
        return $this->answerCollectionFrom(
            $this->connector()->send(new ListCustomFieldAnswersRequest(
                networkId: $this->networkId(),
                customFieldId: $customFieldId,
                memberId: $memberId,
                perPage: $perPage,
            )),
        );
    }

    /**
     * Lazily paginate through every answer a member gave to a custom field.
     */
    public function paginateAnswers(
        int $customFieldId,
        int $memberId,
        int $perPage = 25,
    ): AdminPagedPaginator {
        return $this->connector()
            ->paginate(new ListCustomFieldAnswersRequest($this->networkId(), $customFieldId, $memberId))
            ->setPerPageLimit($perPage);
    }

    /**
     * Find a single answer by ID.
     *
     * @throws NotFoundException
     */
    public function findAnswer(int $customFieldId, int $memberId, int $answerId): CustomFieldAnswer
    {
        return $this->answerFrom(
            $this->connector()->send(new GetCustomFieldAnswerRequest(
                $this->networkId(),
                $customFieldId,
                $memberId,
                $answerId,
            )),
        );
    }

    /**
     * Create or replace a member's answer to a custom field.
     *
     * Supply only the value fields relevant to the field's response type. For
     * location fields pass `latitude`/`longitude` (and `text` as the label);
     * for multi-location fields pass `locations`. A `204` response means the
     * answer was cleared.
     *
     * @param  array<int, array{label: string, latitude: float|int, longitude: float|int}>|null  $locations
     * @param  array<int, int>|null  $segmentIds
     */
    public function createAnswer(
        int $customFieldId,
        int $memberId,
        ?string $text = null,
        ?int $number = null,
        ?bool $booleanValue = null,
        ?string $date = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $month = null,
        ?int $day = null,
        ?string $url = null,
        ?string $phoneNumber = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?array $locations = null,
        ?array $segmentIds = null,
    ): CustomFieldAnswer {
        return $this->answerFrom(
            $this->connector()->send(new CreateCustomFieldAnswerRequest(
                networkId: $this->networkId(),
                customFieldId: $customFieldId,
                memberId: $memberId,
                text: $text,
                number: $number,
                booleanValue: $booleanValue,
                date: $date,
                startDate: $startDate,
                endDate: $endDate,
                month: $month,
                day: $day,
                url: $url,
                phoneNumber: $phoneNumber,
                latitude: $latitude,
                longitude: $longitude,
                locations: $locations,
                segmentIds: $segmentIds,
            )),
        );
    }

    /**
     * Delete a member's answer to a custom field.
     */
    public function deleteAnswer(int $customFieldId, int $memberId): void
    {
        $this->connector()->send(new DeleteCustomFieldAnswerRequest(
            $this->networkId(),
            $customFieldId,
            $memberId,
        ));
    }

    private function customFieldFrom(Response $response): CustomField
    {
        return $this->ensureCustomField($response->dto());
    }

    private function optionFrom(Response $response): CustomFieldOption
    {
        return $this->ensureOption($response->dto());
    }

    private function answerFrom(Response $response): CustomFieldAnswer
    {
        return $this->ensureAnswer($response->dto());
    }

    private function collectionFrom(Response $response): CustomFieldCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof CustomFieldCollection) {
            throw new MightyNetworksException('Expected a CustomFieldCollection from the custom fields endpoint.');
        }

        return $dto;
    }

    private function optionCollectionFrom(Response $response): CustomFieldOptionCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof CustomFieldOptionCollection) {
            throw new MightyNetworksException('Expected a CustomFieldOptionCollection from the options endpoint.');
        }

        return $dto;
    }

    private function answerCollectionFrom(Response $response): CustomFieldAnswerCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof CustomFieldAnswerCollection) {
            throw new MightyNetworksException('Expected a CustomFieldAnswerCollection from the answers endpoint.');
        }

        return $dto;
    }

    private function ensureCustomField(mixed $value): CustomField
    {
        if (! $value instanceof CustomField) {
            throw new MightyNetworksException('Expected a CustomField from the custom fields endpoint.');
        }

        return $value;
    }

    private function ensureOption(mixed $value): CustomFieldOption
    {
        if (! $value instanceof CustomFieldOption) {
            throw new MightyNetworksException('Expected a CustomFieldOption from the options endpoint.');
        }

        return $value;
    }

    private function ensureAnswer(mixed $value): CustomFieldAnswer
    {
        if (! $value instanceof CustomFieldAnswer) {
            throw new MightyNetworksException('Expected a CustomFieldAnswer from the answers endpoint.');
        }

        return $value;
    }
}
