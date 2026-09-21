<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Collections\TagCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\NewTagData;
use MCKLtech\MightyNetworks\DataTransferObjects\Tag;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateTagData;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\AddTagToMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\CreateTagRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\DeleteTagRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\GetMemberTagRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\GetTagRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\ListMemberTagsRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\ListTagsRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\RemoveTagFromMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\ReplaceTagRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\UpdateTagRequest;
use MCKLtech\MightyNetworks\Resources\TagsResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class TagsResourceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function tagPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 42,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'title' => 'VIP Member',
            'description' => 'Premium tier members',
            'color' => '#FF5733',
            'custom_field_id' => 99,
        ], $overrides);
    }

    public function test_find_by_id_returns_a_typed_tag_and_asserts_the_request(): void
    {
        $mock = new MockClient([MockResponse::make($this->tagPayload(), 200)]);

        $resource = new TagsResource($this->admin($mock), '12345');

        $tag = $resource->findById(42);

        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertSame(42, $tag->id);
        $this->assertSame('VIP Member', $tag->title);
        $this->assertSame('#FF5733', $tag->color);
        $this->assertSame(99, $tag->customFieldId);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetTagRequest
                && $request->resolveEndpoint() === 'networks/12345/tags/42/'
                && $request->getMethod() === Method::GET;
        });
    }

    public function test_find_by_id_or_null_returns_null_on_a_404(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Tag not found'], 404)]);

        $resource = new TagsResource($this->admin($mock), '12345');

        $this->assertNull($resource->findByIdOrNull(42));
    }

    public function test_all_returns_a_tag_collection(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->tagPayload(['id' => 1]), $this->tagPayload(['id' => 2])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new TagsResource($this->admin($mock), '12345');

        $tags = $resource->all(perPage: 50);

        $this->assertInstanceOf(TagCollection::class, $tags);
        $this->assertCount(2, $tags);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListTagsRequest
                && $request->resolveEndpoint() === 'networks/12345/tags'
                && $request->query()->get('per_page') === 50;
        });
    }

    public function test_create_maps_the_dto_to_a_snake_case_body_and_omits_nulls(): void
    {
        $mock = new MockClient([MockResponse::make($this->tagPayload(), 201)]);

        $resource = new TagsResource($this->admin($mock), '12345');

        $tag = $resource->create(new NewTagData(title: 'VIP Member', color: '#FF5733'));

        $this->assertSame(42, $tag->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof CreateTagRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/tags'
                && $request->body()->all() === [
                    'title' => 'VIP Member',
                    'color' => '#FF5733',
                ];
        });
    }

    public function test_update_sends_a_patch_with_the_changed_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->tagPayload(), 200)]);

        $resource = new TagsResource($this->admin($mock), '12345');

        $resource->update(42, new UpdateTagData(description: 'Updated'));

        $mock->assertSent(function ($request): bool {
            return $request instanceof UpdateTagRequest
                && $request->getMethod() === Method::PATCH
                && $request->resolveEndpoint() === 'networks/12345/tags/42/'
                && $request->body()->all() === ['description' => 'Updated'];
        });
    }

    public function test_replace_sends_a_put_with_the_supplied_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->tagPayload(), 200)]);

        $resource = new TagsResource($this->admin($mock), '12345');

        $tag = $resource->replace(42, new UpdateTagData(title: 'Replaced'));

        $this->assertSame(42, $tag->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ReplaceTagRequest
                && $request->getMethod() === Method::PUT
                && $request->resolveEndpoint() === 'networks/12345/tags/42/'
                && $request->body()->all() === ['title' => 'Replaced'];
        });
    }

    public function test_delete_sends_a_delete_to_the_tag_endpoint(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new TagsResource($this->admin($mock), '12345');

        $resource->delete(42);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeleteTagRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/tags/42/';
        });
    }

    public function test_member_tags_are_listed_from_the_member_scoped_endpoint(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => [$this->tagPayload(['id' => 3])],
                'meta' => ['current_page' => 1, 'total_pages' => 1],
            ], 200),
        ]);

        $resource = new TagsResource($this->admin($mock), '12345');

        $tags = $resource->tagsForMember(7);

        $this->assertSame([3], $tags->map(static fn (Tag $tag): int => $tag->id)->all());

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListMemberTagsRequest
                && $request->resolveEndpoint() === 'networks/12345/members/7/tags';
        });
    }

    public function test_add_to_member_posts_the_tag_id(): void
    {
        $mock = new MockClient([MockResponse::make($this->tagPayload(), 200)]);

        $resource = new TagsResource($this->admin($mock), '12345');

        $resource->addToMember(7, 42);

        $mock->assertSent(function ($request): bool {
            return $request instanceof AddTagToMemberRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/members/7/tags'
                && $request->body()->all() === ['tag_id' => 42];
        });
    }

    public function test_tag_for_member_uses_the_trailing_slash_path(): void
    {
        $mock = new MockClient([MockResponse::make($this->tagPayload(), 200)]);

        $resource = new TagsResource($this->admin($mock), '12345');

        $resource->tagForMember(7, 42);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetMemberTagRequest
                && $request->resolveEndpoint() === 'networks/12345/members/7/tags/42/';
        });
    }

    public function test_remove_from_member_deletes_the_assignment(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new TagsResource($this->admin($mock), '12345');

        $resource->removeFromMember(7, 42);

        $mock->assertSent(function ($request): bool {
            return $request instanceof RemoveTagFromMemberRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/members/7/tags/42/';
        });
    }

    public function test_a_404_throws_a_not_found_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Tag not found'], 404)]);

        $resource = new TagsResource($this->admin($mock), '12345');

        $this->expectException(NotFoundException::class);

        $resource->findById(42);
    }
}
