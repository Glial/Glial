<?php

declare(strict_types=1);

namespace Glial\Html\Pagination {
    function microtime(): string
    {
        return '0.532403 0';
    }
}

namespace Glial\Html\Pagination\Test {
    use Glial\Html\Pagination\Pagination;
    use PHPUnit\Framework\TestCase;

    final class PaginationCompatibilityTest extends TestCase
    {
        public function testPaginationUsesAnExplicitIntegerSeed(): void
        {
            $source = file_get_contents(dirname(__DIR__) . '/Pagination.php');

            $this->assertIsString($source);
            $this->assertStringNotContainsString('(double)', $source);
            $this->assertStringContainsString(
                'srand((int) ((float) microtime() * 1000000000));',
                $source
            );
        }

        public function testPaginationRendersImpreciseSeedWithoutDeprecation(): void
        {
            $impreciseSeed = (float) \Glial\Html\Pagination\microtime() * 1000000000;
            $deprecations = array();

            $this->assertNotSame($impreciseSeed, (float) (int) $impreciseSeed);

            set_error_handler(
                static function (int $severity, string $message) use (&$deprecations): bool {
                    if (($severity & E_DEPRECATED) === E_DEPRECATED) {
                        $deprecations[] = $message;
                        return true;
                    }

                    return false;
                }
            );

            try {
                $pagination = new Pagination('/monitoring/query', 1, 25, 10, 5);
                $html = $pagination->print_pagination();
            } finally {
                restore_error_handler();
            }

            $this->assertSame(array(), $deprecations);
            $this->assertStringContainsString('/page:2', $html);
            $this->assertStringContainsString('id="gopage', $html);
        }
    }
}
