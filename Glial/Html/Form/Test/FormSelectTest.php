<?php

declare(strict_types=1);

namespace Glial\Html\Form\Test;

require_once dirname(__DIR__) . '/Form.php';

use Glial\Html\Form\Form;
use PHPUnit\Framework\TestCase;

final class FormSelectTest extends TestCase
{
    protected function setUp(): void
    {
        $_GET = array();
        $_SESSION = array();
        Form::$select_display_msg = false;
        Form::$select_multiple = false;
        Form::$data = array();
        Form::setAjax(false);
        Form::setIndice(false);
    }

    protected function tearDown(): void
    {
        $_GET = array();
        $_SESSION = array();
        Form::$select_display_msg = true;
        Form::$select_multiple = false;
        Form::$data = array();
        Form::setAjax(false);
        Form::setIndice(false);
    }

    public function testMultipleSelectUsesArrayDefaultValues(): void
    {
        $html = Form::select(
            'client',
            'libelle',
            $this->options(),
            array(1, 2),
            array('multiple' => 'multiple')
        );

        $this->assertSelected($html, '1');
        $this->assertSelected($html, '2');
        $this->assertNotSelected($html, '3');
    }

    public function testMultipleSelectNormalizesJsonRequestValues(): void
    {
        $_GET['client']['libelle'] = '["1","2"]';

        $html = Form::select(
            'client',
            'libelle',
            $this->options(),
            array(3),
            array('multiple' => 'multiple')
        );

        $this->assertSelected($html, '1');
        $this->assertSelected($html, '2');
        $this->assertNotSelected($html, '3');
    }

    public function testMultipleSelectNormalizesRepeatedJsonAndLegacyCommaValues(): void
    {
        foreach (array('"[\\"1\\",\\"2\\"]"', '1,2', '[\'"1"\',\'"2"\']') as $selection) {
            $html = Form::select(
                'client',
                'libelle',
                $this->options(),
                $selection,
                array('multiple' => 'multiple')
            );

            $this->assertSelected($html, '1');
            $this->assertSelected($html, '2');
            $this->assertNotSelected($html, '3');
        }
    }

    public function testEmptyRequestArrayOverridesPersistedDefaults(): void
    {
        $_GET['client']['libelle'] = array();

        $html = Form::select(
            'client',
            'libelle',
            $this->options(),
            array(1, 2),
            array('multiple' => 'multiple')
        );

        $this->assertNotSelected($html, '1');
        $this->assertNotSelected($html, '2');
        $this->assertNotSelected($html, '3');
    }

    public function testIndexedMultipleSelectUsesItsIndexedRequestValues(): void
    {
        $_GET['client'][0]['libelle'] = array('2');
        Form::setIndice(true);

        $html = Form::select(
            'client',
            'libelle',
            $this->options(),
            array(1),
            array('multiple' => 'multiple')
        );

        $this->assertStringContainsString('id="client-0-libelle"', $html);
        $this->assertNotSelected($html, '1');
        $this->assertSelected($html, '2');
    }

    public function testJsonBigIntegerIdKeepsItsExactDigits(): void
    {
        $id = '18446744073709551615';
        $html = Form::select(
            'client',
            'libelle',
            array(array('id' => $id, 'libelle' => 'Large identifier')),
            '[' . $id . ']',
            array('multiple' => 'multiple')
        );

        $this->assertSelected($html, $id);
    }

    /**
     * @return array<int,array{id:int,libelle:string}>
     */
    private function options(): array
    {
        return array(
            array('id' => 1, 'libelle' => 'One'),
            array('id' => 2, 'libelle' => 'Two'),
            array('id' => 3, 'libelle' => 'Three'),
        );
    }

    private function assertSelected(string $html, string $id): void
    {
        $this->assertMatchesRegularExpression(
            '/<option value="' . preg_quote($id, '/') . '" selected="selected"/',
            $html
        );
    }

    private function assertNotSelected(string $html, string $id): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/<option value="' . preg_quote($id, '/') . '" selected="selected"/',
            $html
        );
    }
}
