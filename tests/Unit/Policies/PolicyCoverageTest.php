<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class PolicyCoverageTest extends TestCase
{
    #[Test]
    public function every_ability_is_proven_permitted_and_refused(): void
    {
        $uncovered = [];

        foreach ($this->abilities() as $policy => $abilities) {
            $tests = @file_get_contents(base_path("tests/Unit/Policies/{$policy}Test.php")) ?: '';

            foreach ($abilities as $ability) {
                if (! str_contains($tests, "'{$ability}'")) {
                    $uncovered[] = "{$policy}::{$ability}";
                }
            }

            if (! str_contains($tests, 'nobody_is_signed_in')) {
                $uncovered[] = "{$policy}: no test for the answer with nobody signed in";
            }
        }

        $this->assertSame([], $uncovered, 'Every policy method needs a permitted and a refused case, see FR-009 and FR-010.');
    }

    /** @return array<string, list<string>> */
    private function abilities(): array
    {
        $found = [];

        foreach (glob(app_path('Policies/*.php')) ?: [] as $file) {
            $class = 'App\\Policies\\'.basename($file, '.php');

            $found[basename($file, '.php')] = array_values(array_map(
                fn (\ReflectionMethod $method): string => $method->getName(),
                array_filter(
                    new ReflectionClass($class)->getMethods(\ReflectionMethod::IS_PUBLIC),
                    fn (\ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === $class,
                ),
            ));
        }

        return $found;
    }
}
