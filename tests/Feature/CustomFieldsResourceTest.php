<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

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
use MCKLtech\MightyNetworks\Enums\CustomFieldLocationGranularity;
use MCKLtech\MightyNetworks\Enums\CustomFieldPrivacy;
use MCKLtech\MightyNetworks\Enums\CustomFieldResponseType;
use MCKLtech\MightyNetworks\Enums\CustomFieldStatus;
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
use MCKLtech\MightyNetworks\Resources\CustomFieldsResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class CustomFieldsResourceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function fieldPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 3,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'title' => 'Favourite colour',
            'response_type' => 'dropdown_single_select',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function optionPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 4,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'title' => 'Blue',
            'description' => 'A calming colour',
            'custom_field_id' => 3,
            'member_count' => 12,
            'is_ad_hoc' => false,
            'creator_id' => 1,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function answerPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 5,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'last_edited_at' => '2024-03-21T09:00:00+00:00',
            'user_id' => 42,
            'custom_field_id' => 3,
            'text' => 'Blue',
        ], $overrides);
    }

    public function test_find_by_id_returns_a_typed_custom_field_with_enum(): void
    {
        $mock = new MockClient([MockResponse::make($this->fieldPayload(), 200)]);

        $resource = new CustomFieldsResource($this->admin($mock), '12345');

        $field = $resource->findById(3);

        $this->assertInstanceOf(CustomField::class, $field);
        $this->assertSame(CustomFieldResponseType::DropdownSingleSelect, $field->responseType);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetCustomFieldRequest
                && $request->resolveEndpoint() === 'networks/12345/custom_fields/3/';
        });
    }

    public function test_all_filters_by_response_type_and_paginates(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => [$this->fieldPayload()],
                'meta' => ['current_page' => 1, 'total_pages' => 1],
            ], 200),
        ]);

        $resource = new CustomFieldsResource($this->admin($mock), '12345');

        $fields = $resource->all(CustomFieldResponseType::DropdownSingleSelect, perPage: 50);

        $this->assertInstanceOf(CustomFieldCollection::class, $fields);
        $this->assertCount(1, $fields);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListCustomFieldsRequest
                && $request->resolveEndpoint() === 'networks/12345/custom_fields'
                && $request->query()->get('response_type') === 'dropdown_single_select'
                && $request->query()->get('per_page') === 50;
        });
    }

    public function test_create_maps_enums_to_a_snake_case_body(): void
    {
        $mock = new MockClient([MockResponse::make($this->fieldPayload(), 201)]);

        $resource = new CustomFieldsResource($this->admin($mock), '12345');

        $resource->create(new NewCustomFieldData(
            title: 'Location',
            responseType: CustomFieldResponseType::Location,
            privacy: CustomFieldPrivacy::Public,
            locationGranularity: CustomFieldLocationGranularity::City,
        ));

        $mock->assertSent(function ($request): bool {
            return $request instanceof CreateCustomFieldRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/custom_fields'
                && $request->body()->all() === [
                    'title' => 'Location',
                    'response_type' => 'location',
                    'privacy' => 'public',
                    'response_by' => 'individual_member',
                    'location_granularity' => 'city',
                ];
        });
    }

    public function test_update_sends_the_status_and_flags(): void
    {
        $mock = new MockClient([MockResponse::make($this->fieldPayload(), 200)]);

        $resource = new CustomFieldsResource($this->admin($mock), '12345');

        $resource->update(3, new UpdateCustomFieldData(status: CustomFieldStatus::Hidden, primary: true));

        $mock->assertSent(function ($request): bool {
            return $request instanceof UpdateCustomFieldRequest
                && $request->getMethod() === Method::PATCH
                && $request->resolveEndpoint() === 'networks/12345/custom_fields/3/'
                && $request->body()->all() === [
                    'status' => 'hidden',
                    'primary' => true,
                ];
        });
    }

    public function test_replace_sends_a_put_with_the_supplied_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->fieldPayload(), 200)]);

        $resource = new CustomFieldsResource($this->admin($mock), '12345');

        $field = $resource->replace(3, new UpdateCustomFieldData(title: 'Replaced', status: CustomFieldStatus::Visible));

        $this->assertInstanceOf(CustomField::class, $field);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ReplaceCustomFieldRequest
                && $request->getMethod() === Method::PUT
                && $request->resolveEndpoint() === 'networks/12345/custom_fields/3/'
                && $request->body()->all() === [
                    'title' => 'Replaced',
                    'status' => 'visible',
                ];
        });
    }

    public function test_replace_option_sends_a_put_with_the_supplied_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->optionPayload(['title' => 'Navy']), 200)]);

        $resource = new CustomFieldsResource($this->admin($mock), '12345');

        $option = $resource->replaceOption(3, 4, new UpdateCustomFieldOptionData(title: 'Navy'));

        $this->assertInstanceOf(CustomFieldOption::class, $option);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ReplaceCustomFieldOptionRequest
                && $request->getMethod() === Method::PUT
                && $request->resolveEndpoint() === 'networks/12345/custom_fields/3/options/4/'
                && $request->body()->all() === ['title' => 'Navy'];
        });
    }

    public function test_delete_sends_a_delete_to_the_custom_field_endpoint(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new CustomFieldsResource($this->admin($mock), '12345');

        $resource->delete(3);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeleteCustomFieldRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/custom_fields/3/';
        });
    }

    public function test_options_can_be_listed_created_updated_and_deleted(): void
    {
        $mock = new MockClient([
            MockResponse::make(['items' => [$this->optionPayload()], 'links' => ['next' => null]], 200),
            MockResponse::make($this->optionPayload(), 201),
            MockResponse::make($this->optionPayload(['title' => 'Navy']), 200),
            MockResponse::make([], 204),
        ]);

        $resource = new CustomFieldsResource($this->admin($mock), '12345');

        $options = $resource->options(3, term: 'blu');
        $this->assertInstanceOf(CustomFieldOptionCollection::class, $options);

        $resource->createOption(3, new NewCustomFieldOptionData(title: 'Blue', description: 'A calming colour'));
        $resource->updateOption(3, 4, new UpdateCustomFieldOptionData(title: 'Navy'));
        $resource->deleteOption(3, 4);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListCustomFieldOptionsRequest
                && $request->resolveEndpoint() === 'networks/12345/custom_fields/3/options'
                && $request->query()->get('term') === 'blu';
        });

        $mock->assertSent(function ($request): bool {
            return $request instanceof CreateCustomFieldOptionRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/custom_fields/3/options'
                && $request->body()->all() === [
                    'title' => 'Blue',
                    'description' => 'A calming colour',
                ];
        });

        $mock->assertSent(function ($request): bool {
            return $request instanceof UpdateCustomFieldOptionRequest
                && $request->getMethod() === Method::PATCH
                && $request->resolveEndpoint() === 'networks/12345/custom_fields/3/options/4/'
                && $request->body()->all() === ['title' => 'Navy'];
        });

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeleteCustomFieldOptionRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/custom_fields/3/options/4/';
        });
    }

    public function test_find_option_uses_the_trailing_slash_path(): void
    {
        $mock = new MockClient([MockResponse::make($this->optionPayload(), 200)]);

        $resource = new CustomFieldsResource($this->admin($mock), '12345');

        $option = $resource->findOption(3, 4);

        $this->assertInstanceOf(CustomFieldOption::class, $option);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetCustomFieldOptionRequest
                && $request->resolveEndpoint() === 'networks/12345/custom_fields/3/options/4/';
        });
    }

    public function test_answers_can_be_listed_and_created(): void
    {
        $mock = new MockClient([
            MockResponse::make(['items' => [$this->answerPayload()], 'links' => ['next' => null]], 200),
            MockResponse::make($this->answerPayload(), 200),
        ]);

        $resource = new CustomFieldsResource($this->admin($mock), '12345');

        $answers = $resource->answers(3, 7);
        $this->assertInstanceOf(CustomFieldAnswerCollection::class, $answers);

        $resource->createAnswer(
            customFieldId: 3,
            memberId: 7,
            text: 'Blue',
            locations: [['label' => 'San Francisco, CA', 'latitude' => 37.7749, 'longitude' => -122.4194]],
        );

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListCustomFieldAnswersRequest
                && $request->resolveEndpoint() === 'networks/12345/custom_fields/3/members/7/answers';
        });

        $mock->assertSent(function ($request): bool {
            return $request instanceof CreateCustomFieldAnswerRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/custom_fields/3/members/7/answers'
                && $request->body()->all() === [
                    'text' => 'Blue',
                    'locations' => [['label' => 'San Francisco, CA', 'latitude' => 37.7749, 'longitude' => -122.4194]],
                ];
        });
    }

    public function test_find_and_delete_answer_use_the_documented_paths(): void
    {
        $mock = new MockClient([
            MockResponse::make($this->answerPayload(), 200),
            MockResponse::make([], 204),
        ]);

        $resource = new CustomFieldsResource($this->admin($mock), '12345');

        $answer = $resource->findAnswer(3, 7, 5);
        $this->assertInstanceOf(CustomFieldAnswer::class, $answer);

        $resource->deleteAnswer(3, 7);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetCustomFieldAnswerRequest
                && $request->resolveEndpoint() === 'networks/12345/custom_fields/3/members/7/answers/5/';
        });

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeleteCustomFieldAnswerRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/custom_fields/3/members/7/answers';
        });
    }
}
