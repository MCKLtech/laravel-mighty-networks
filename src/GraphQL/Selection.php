<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\GraphQL;

use BackedEnum;
use JsonException;

/**
 * A lightweight, pragmatic GraphQL selection-set builder.
 *
 * This is intentionally **not** a schema-aware DSL: it assembles a field
 * selection (with optional aliases, literal arguments and nested selections)
 * and renders a syntactically valid document. Callers remain responsible for
 * the fields they name. Prefer the typed request classes; reach for this (or a
 * raw query) only for shapes the typed requests do not cover.
 */
final class Selection
{
    /**
     * @var list<array{name: string, alias: string|null, arguments: array<string, mixed>, selection: self|null}>
     */
    private array $fields = [];

    /**
     * @param  string|null  $name  An operation header such as `query Me`, or null for a bare selection set.
     */
    public function __construct(
        private readonly ?string $name = null,
    ) {}

    public static function make(?string $name = null): self
    {
        return new self($name);
    }

    /**
     * Append a field.
     *
     * @param  array<string, mixed>  $arguments  Argument values; use {@see Variable} to reference a query variable.
     */
    public function field(string $name, array $arguments = [], ?self $selection = null, ?string $alias = null): self
    {
        $this->fields[] = [
            'name' => $name,
            'alias' => $alias,
            'arguments' => $arguments,
            'selection' => $selection,
        ];

        return $this;
    }

    /**
     * Render the selection set as a GraphQL document fragment.
     */
    public function render(): string
    {
        $parts = [];

        foreach ($this->fields as $field) {
            $parts[] = $this->renderField($field);
        }

        $body = '{ '.implode(' ', $parts).' }';

        return $this->name === null ? $body : $this->name.' '.$body;
    }

    /**
     * @param  array{name: string, alias: string|null, arguments: array<string, mixed>, selection: self|null}  $field
     */
    private function renderField(array $field): string
    {
        $head = $field['alias'] === null ? $field['name'] : $field['alias'].': '.$field['name'];

        if ($field['arguments'] !== []) {
            $arguments = [];

            foreach ($field['arguments'] as $key => $value) {
                $arguments[] = $key.': '.self::formatValue($value);
            }

            $head .= '('.implode(', ', $arguments).')';
        }

        if ($field['selection'] instanceof self) {
            $head .= ' '.$field['selection']->render();
        }

        return $head;
    }

    /**
     * Render a single argument value as a GraphQL literal.
     *
     * @throws JsonException
     */
    private static function formatValue(mixed $value): string
    {
        return match (true) {
            $value instanceof Variable => (string) $value,
            $value instanceof BackedEnum => (string) $value->value,
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value), is_float($value) => (string) $value,
            is_string($value) => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            is_array($value) => '['.implode(', ', array_map(self::formatValue(...), array_values($value))).']',
            $value === null => 'null',
            default => json_encode($value, JSON_THROW_ON_ERROR),
        };
    }
}
