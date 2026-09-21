<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use MCKLtech\MightyNetworks\Enums\GraphQLMutation;
use PHPUnit\Framework\TestCase;

/**
 * Proves the {@see GraphQLMutation} registry matches the GraphQL SDL exactly,
 * in both directions, and that its derived type names are correct.
 */
final class GraphQLMutationTest extends TestCase
{
    private const SDL_PATH = __DIR__.'/../Fixtures/mn-schema.graphql';

    public function test_it_covers_every_mutation_field_in_the_sdl_exactly_once(): void
    {
        $enumValues = array_map(
            static fn (GraphQLMutation $mutation): string => $mutation->value,
            GraphQLMutation::cases(),
        );
        sort($enumValues);

        $sdlValues = self::sdlFieldNames('Mutation');
        sort($sdlValues);

        // Bidirectional, exact match: no missing and no extra operations.
        $this->assertSame($sdlValues, $enumValues);

        // The SDL currently documents 185 mutation fields. If this changes, the
        // registry must be regenerated; the assertion above is the real guard.
        $this->assertCount(185, $enumValues);
    }

    public function test_every_mutation_declares_the_expected_input_and_payload_type(): void
    {
        $sdl = self::strippedSdl();

        foreach (GraphQLMutation::cases() as $mutation) {
            $pattern = '/^  '.preg_quote($mutation->value, '/')
                .'\(\s*input:\s*([A-Za-z0-9_]+!)\s*\)\s*:\s*([A-Za-z0-9_]+)/m';

            $matched = preg_match($pattern, $sdl, $matches);

            $this->assertSame(1, $matched, sprintf('SDL declaration not found for [%s].', $mutation->value));
            $this->assertSame($mutation->inputType().'!', $matches[1]);
            $this->assertSame($mutation->payloadType(), $matches[2]);
            $this->assertSame(ucfirst($mutation->value), $mutation->operationName());
        }
    }

    public function test_case_values_are_unique(): void
    {
        $values = array_map(
            static fn (GraphQLMutation $mutation): string => $mutation->value,
            GraphQLMutation::cases(),
        );

        $this->assertSame($values, array_values(array_unique($values)));
    }

    public function test_from_operation_name_accepts_camel_pascal_and_snake_spellings(): void
    {
        $this->assertSame(GraphQLMutation::CreateMember, GraphQLMutation::fromOperationName('createMember'));
        $this->assertSame(GraphQLMutation::CreateMember, GraphQLMutation::fromOperationName('CreateMember'));
        $this->assertSame(GraphQLMutation::CreateMember, GraphQLMutation::fromOperationName('CREATE_MEMBER'));
        $this->assertSame(GraphQLMutation::CreateMember, GraphQLMutation::fromOperationName('  createMember  '));
        $this->assertSame(
            GraphQLMutation::CreateSpaceMemberships,
            GraphQLMutation::fromOperationName('CreateSpaceMemberships'),
        );
    }

    public function test_from_operation_name_returns_null_for_unknown_or_empty_names(): void
    {
        $this->assertNull(GraphQLMutation::fromOperationName('totallyMadeUp'));
        $this->assertNull(GraphQLMutation::fromOperationName(''));
        $this->assertNull(GraphQLMutation::fromOperationName('   '));
    }

    public function test_to_friendly_produces_a_readable_label(): void
    {
        $this->assertSame('Create Member', GraphQLMutation::CreateMember->toFriendly());
        $this->assertSame('Update Webhook Callback', GraphQLMutation::UpdateWebhookCallback->toFriendly());
    }

    /**
     * The decoded SDL with all descriptions removed.
     */
    private static function strippedSdl(): string
    {
        $contents = file_get_contents(self::SDL_PATH);

        if ($contents === false) {
            self::fail('Unable to read the GraphQL SDL fixture.');
        }

        $stripped = preg_replace('/"""[\s\S]*?"""/', '', $contents);

        if (! is_string($stripped)) {
            self::fail('Unable to strip SDL descriptions.');
        }

        return $stripped;
    }

    /**
     * The top-level field names of a `type` block, in declaration order.
     *
     * @return list<string>
     */
    private static function sdlFieldNames(string $typeName): array
    {
        $sdl = self::strippedSdl();
        $marker = 'type '.$typeName.' {';
        $position = strpos($sdl, $marker);

        if ($position === false) {
            self::fail(sprintf('SDL type [%s] not found.', $typeName));
        }

        $start = $position + strlen($marker);
        $depth = 1;
        $index = $start;
        $length = strlen($sdl);

        while ($depth > 0 && $index < $length) {
            $character = $sdl[$index];

            if ($character === '{') {
                $depth++;
            } elseif ($character === '}') {
                $depth--;
            }

            $index++;
        }

        $block = substr($sdl, $start, $index - $start - 1);

        preg_match_all('/^  ([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/m', $block, $matches);

        return array_values(array_map(
            static fn (string $name): string => $name,
            $matches[1],
        ));
    }
}
