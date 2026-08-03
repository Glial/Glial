<?php

declare(strict_types=1);

namespace Glial\Html\Pagination\Test;

use Glial\Html\Pagination\Pagination;
use PHPUnit\Framework\TestCase;

final class PaginationCompatibilityTest extends TestCase
{
    public function testPaginationUsesTheCanonicalFloatCast(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/Pagination.php');

        $this->assertIsString($source);
        $this->assertStringNotContainsString('(double)', $source);
        $this->assertStringContainsString('(float) microtime()', $source);
    }

    public function testPaginationLoadsWithoutDeprecation(): void
    {
        $deprecations = array();

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
            require_once dirname(__DIR__) . '/Pagination.php';
        } finally {
            restore_error_handler();
        }

        $this->assertSame(array(), $deprecations);
        $this->assertTrue(class_exists(Pagination::class, false));
    }
}
