<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\GraphQL;

/**
 * A GraphQL variable reference, rendered as `$name`.
 *
 * Use this for argument values that should be supplied through the request's
 * `variables` map rather than inlined into the query document.
 */
final readonly class Variable
{
    public function __construct(
        public string $name,
    ) {}

    public function __toString(): string
    {
        return '$'.$this->name;
    }
}
