<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use MCKLtech\MightyNetworks\Enums\MemberSort;
use MCKLtech\MightyNetworks\Enums\SortOrder;
use MCKLtech\MightyNetworks\GraphQL\Selection;
use MCKLtech\MightyNetworks\GraphQL\Selections;
use MCKLtech\MightyNetworks\GraphQL\Variable;
use PHPUnit\Framework\TestCase;

final class GraphQLSelectionTest extends TestCase
{
    public function test_it_renders_an_operation_with_nested_fields(): void
    {
        $document = Selection::make('query Me')
            ->field('me', selection: Selections::member())
            ->render();

        $this->assertStringStartsWith('query Me { me { id resourceId name email', $document);
        $this->assertStringEndsWith('} }', $document);
    }

    public function test_it_renders_aliases_arguments_and_variables(): void
    {
        $document = Selection::make('query Members($first: Int, $after: String)')
            ->field('network', selection: Selection::make()->field(
                name: 'members',
                arguments: ['first' => new Variable('first'), 'after' => new Variable('after')],
                selection: Selection::make()->field('totalCount'),
            ))
            ->render();

        $this->assertSame(
            'query Members($first: Int, $after: String) { network { members(first: $first, after: $after) { totalCount } } }',
            $document,
        );
    }

    public function test_it_renders_aliases_enums_lists_and_escaped_strings(): void
    {
        $document = Selection::make()
            ->field('members', arguments: [
                'sort' => MemberSort::ResourceId,
                'sortOrder' => SortOrder::Asc,
                'ids' => ['a', 'b'],
                'term' => 'a "quote"',
                'flag' => true,
                'limit' => 10,
                'nothing' => null,
            ], alias: 'roster')
            ->render();

        $this->assertSame(
            '{ roster: members(sort: RESOURCE_ID, sortOrder: ASC, ids: ["a", "b"], term: "a \"quote\"", flag: true, limit: 10, nothing: null) }',
            $document,
        );
    }

    public function test_member_selection_contains_the_core_identity_fields(): void
    {
        $document = Selections::member()->render();

        $this->assertStringContainsString('resourceId', $document);
        $this->assertStringContainsString('memberType', $document);
        $this->assertStringContainsString('networkRole', $document);
    }
}
