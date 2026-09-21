<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * Guards Admin REST API coverage.
 *
 * `tests/Fixtures/admin-rest-operations.json` records every operation in the
 * public OpenAPI spec. This test asserts each one is backed by a request class,
 * so dropping or renaming an endpoint fails CI instead of going unnoticed.
 *
 * Two guard tests keep this honest: one fails if the fixture itself is empty or
 * truncated (a vacuous pass would be worse than no test), and one fails if the
 * source scan finds implausibly few implementations.
 */
final class ApiCoverageTest extends TestCase
{
    private const SPEC_PATH_PREFIX = '/admin/v1/networks/{network_id}/';

    private const EXPECTED_OPERATIONS = 137;

    #[DataProvider('specOperations')]
    public function test_every_documented_operation_has_a_request_class(string $method, string $path): void
    {
        $implemented = self::implementedOperations();

        $this->assertArrayHasKey(
            $method.' '.$path,
            $implemented,
            sprintf(
                'The Admin REST operation [%s %s] is documented in the OpenAPI spec, but no request '
                .'class implements it. Either add the request class or, if the endpoint was genuinely '
                .'removed upstream, regenerate the fixture.',
                $method,
                $path,
            ),
        );
    }

    public function test_the_fixture_lists_the_complete_documented_surface(): void
    {
        $this->assertCount(
            self::EXPECTED_OPERATIONS,
            self::specOperations(),
            'The coverage fixture changed size — regenerate it from https://api.mn.co/admin/v1/spec.json '
            .'and update EXPECTED_OPERATIONS deliberately.',
        );
    }

    public function test_the_source_scan_actually_finds_implementations(): void
    {
        $this->assertGreaterThanOrEqual(
            self::EXPECTED_OPERATIONS,
            count(self::implementedOperations()),
            'The request-class scan found implausibly few implementations, which means the scan itself '
            .'is broken rather than the coverage being genuinely incomplete.',
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function specOperations(): array
    {
        $cases = [];

        foreach (self::fixtureOperations() as $operation) {
            $method = $operation['method'];
            $path = self::normaliseSpecPath($operation['path']);

            $cases[$method.' '.$path] = [$method, $path];
        }

        return $cases;
    }

    /**
     * @return list<array{method: string, path: string}>
     */
    private static function fixtureOperations(): array
    {
        $path = dirname(__DIR__).'/Fixtures/admin-rest-operations.json';

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded) || ! isset($decoded['operations']) || ! is_array($decoded['operations'])) {
            throw new RuntimeException('Unable to decode the Admin REST coverage fixture.');
        }

        $operations = [];

        foreach ($decoded['operations'] as $operation) {
            if (! is_array($operation)) {
                continue;
            }

            $method = $operation['method'] ?? null;
            $specPath = $operation['path'] ?? null;

            if (is_string($method) && is_string($specPath)) {
                $operations[] = ['method' => $method, 'path' => $specPath];
            }
        }

        return $operations;
    }

    /**
     * Map every implemented Admin request class onto a normalised operation key.
     *
     * @return array<string, string>
     */
    private static function implementedOperations(): array
    {
        $directory = dirname(__DIR__, 2).'/src/Requests/Admin';

        if (! is_dir($directory)) {
            throw new RuntimeException('Unable to locate the Admin request classes.');
        }

        $found = [];

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            if ($file->getFilename() === 'AdminRequest.php') {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());

            if (preg_match('/Method::(\w+)/', $source, $methodMatch) !== 1) {
                continue;
            }

            if (preg_match("/networkEndpoint\(\s*(?:sprintf\(\s*)?'([^']*)'/", $source, $endpointMatch) !== 1) {
                continue;
            }

            $key = strtoupper($methodMatch[1]).' '.self::normaliseCodePath($endpointMatch[1]);

            $found[$key] = $file->getFilename();
        }

        return $found;
    }

    /**
     * `/admin/v1/networks/{network_id}/members/{id}/` → `members/*\/`.
     */
    private static function normaliseSpecPath(string $path): string
    {
        return self::normaliseCodePath(str_replace(self::SPEC_PATH_PREFIX, '', $path));
    }

    /**
     * `members/%d/` and `members/{id}/` both become `members/*\/`.
     */
    private static function normaliseCodePath(string $path): string
    {
        return (string) preg_replace(['/%[ds]/', '/\{[^}]+\}/'], '*', $path);
    }
}
