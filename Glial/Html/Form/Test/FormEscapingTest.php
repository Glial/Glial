<?php

declare(strict_types=1);

namespace Glial\Html\Form\Test;

require_once dirname(__DIR__) . '/Form.php';

use DOMDocument;
use DOMElement;
use Glial\Html\Form\Form;
use PHPUnit\Framework\TestCase;

final class FormEscapingTest extends TestCase
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

    public function testInputEscapesConfiguredAndRequestDerivedAttributes(): void
    {
        $value = 'node.example" autofocus data-pmc-injected="yes & <west>';
        $placeholder = 'Server "name" & <address>';
        $_GET['ldap']['host'] = $value;

        $input = $this->firstElement(
            Form::input('ldap', 'host', array(
                'class' => 'pmc-input',
                'placeholder' => $placeholder,
            )),
            'input'
        );

        $this->assertSame($value, $input->getAttribute('value'));
        $this->assertSame($placeholder, $input->getAttribute('placeholder'));
        $this->assertFalse($input->hasAttribute('autofocus'));
        $this->assertFalse($input->hasAttribute('data-pmc-injected'));
    }

    public function testIndexedInputKeepsItsNameAndWholeRequestValue(): void
    {
        $value = 'indexed" data-indexed-injected="yes';
        Form::setIndice(true);
        $_GET['row'][0]['value'] = $value;

        $input = $this->firstElement(Form::input('row', 'value'), 'input');

        $this->assertSame('row-0-value', $input->getAttribute('id'));
        $this->assertSame('row[0][value]', $input->getAttribute('name'));
        $this->assertSame($value, $input->getAttribute('value'));
        $this->assertFalse($input->hasAttribute('data-indexed-injected'));
    }

    public function testSelectEscapesOptionsGroupsLabelsAndDynamicAttributes(): void
    {
        $selectLabel = 'Choose "one" & <two>';
        $groupLabel = 'Ops" data-group-injected="yes';
        $optionId = 'one" data-option-injected="yes';
        $optionLabel = '<b data-label-injected="yes">Label & "Blue"</b>';
        $title = 'Tip" data-extra-injected="yes';
        $preEncodedContent = '&lt;span title=&quot;Blue&quot;&gt;Team&lt;/span&gt;';

        $html = Form::select(
            'sample',
            'choice',
            array(
                array('group' => 1, 'libelle' => $groupLabel),
                array(
                    'id' => $optionId,
                    'libelle' => $optionLabel,
                    'extra' => array(
                        'title' => $title,
                        'data-content' => $preEncodedContent,
                    ),
                ),
            ),
            $optionId,
            array('aria-label' => $selectLabel)
        );

        $document = $this->documentFor($html);
        $select = $document->getElementsByTagName('select')->item(0);
        $group = $document->getElementsByTagName('optgroup')->item(0);
        $option = $document->getElementsByTagName('option')->item(0);

        $this->assertInstanceOf(DOMElement::class, $select);
        $this->assertInstanceOf(DOMElement::class, $group);
        $this->assertInstanceOf(DOMElement::class, $option);
        $this->assertSame($selectLabel, $select->getAttribute('aria-label'));
        $this->assertSame($groupLabel, $group->getAttribute('label'));
        $this->assertFalse($group->hasAttribute('data-group-injected'));
        $this->assertSame($optionId, $option->getAttribute('value'));
        $this->assertFalse($option->hasAttribute('data-option-injected'));
        $this->assertSame($title, $option->getAttribute('title'));
        $this->assertFalse($option->hasAttribute('data-extra-injected'));
        $this->assertSame(
            '<span title="Blue">Team</span>',
            $option->getAttribute('data-content')
        );
        $this->assertSame($optionLabel, $option->textContent);
        $this->assertSame(0, $option->getElementsByTagName('b')->length);
    }

    public function testAutocompleteEscapesVisibleAndHiddenRequestValues(): void
    {
        $_GET['lookup']['user'] = '42" data-hidden-injected="yes';
        $_GET['lookup']['user_auto'] = 'Alice" data-auto-injected="yes';

        $document = $this->documentFor(Form::autocomplete('lookup', 'user'));
        $visible = $document->getElementsByTagName('input')->item(0);
        $hidden = $document->getElementsByTagName('input')->item(1);

        $this->assertInstanceOf(DOMElement::class, $visible);
        $this->assertInstanceOf(DOMElement::class, $hidden);
        $this->assertSame($_GET['lookup']['user_auto'], $visible->getAttribute('value'));
        $this->assertFalse($visible->hasAttribute('data-auto-injected'));
        $this->assertSame($_GET['lookup']['user'], $hidden->getAttribute('value'));
        $this->assertFalse($hidden->hasAttribute('data-hidden-injected'));
    }

    public function testIndexedAutocompleteKeepsNamesAndWholeValues(): void
    {
        Form::setIndice(true);
        $_GET['lookup'][0]['user'] = '42 & "Blue"';
        $_GET['lookup'][0]['user_auto'] = 'Alice <Admin>';

        $document = $this->documentFor(Form::autocomplete('lookup', 'user'));
        $visible = $document->getElementsByTagName('input')->item(0);
        $hidden = $document->getElementsByTagName('input')->item(1);

        $this->assertSame('lookup[0][user_auto]', $visible->getAttribute('name'));
        $this->assertSame($_GET['lookup'][0]['user_auto'], $visible->getAttribute('value'));
        $this->assertSame('lookup[0][user]', $hidden->getAttribute('name'));
        $this->assertSame($_GET['lookup'][0]['user'], $hidden->getAttribute('value'));
    }

    public function testInvalidUtf8IsSubstitutedWithValidUtf8(): void
    {
        $html = Form::input('sample', 'invalid', array('value' => "bad\xC3("));

        $this->assertSame(1, preg_match('//u', $html));

        $input = $this->firstElement($html, 'input');
        $this->assertSame("bad\u{FFFD}(", $input->getAttribute('value'));
    }

    public function testAlreadyEncodedValuesAreNotDoubleEncoded(): void
    {
        $encoded = 'A &quot;B&quot; &amp; C';
        $html = Form::input('sample', 'compat', array(
            'value' => $encoded,
            'placeholder' => $encoded,
        ));

        $this->assertStringContainsString('value="'.$encoded.'"', $html);
        $this->assertStringContainsString('placeholder="'.$encoded.'"', $html);
        $this->assertStringNotContainsString('&amp;quot;', $html);

        $input = $this->firstElement($html, 'input');
        $this->assertSame('A "B" & C', $input->getAttribute('value'));
        $this->assertSame('A "B" & C', $input->getAttribute('placeholder'));
    }

    public function testBrowserSubmissionRoundTripKeepsLogicalValues(): void
    {
        $name = 'A "Blue" & <Green>';
        $role = 'Ops "Lead" & <Admin>';
        $teamId = 'team-"42"&<west>';
        $teamLabel = 'Team "West" & <Ops>';
        $_GET['profile']['name'] = $name;
        $_GET['profile']['role'] = $role;
        $_GET['profile']['team'] = $teamId;
        $_GET['profile']['team_auto'] = $teamLabel;

        $html = '<form>'
            .Form::input('profile', 'name')
            .Form::select(
                'profile',
                'role',
                array(array('id' => $role, 'libelle' => 'Administrator')),
                $role
            )
            .Form::autocomplete('profile', 'team')
            .'</form>';

        $document = $this->documentFor($html);
        $submission = array();

        foreach ($document->getElementsByTagName('input') as $input) {
            $submission[$input->getAttribute('name')] = $input->getAttribute('value');
        }

        $select = $document->getElementsByTagName('select')->item(0);
        foreach ($select->getElementsByTagName('option') as $option) {
            if ($option->hasAttribute('selected')) {
                $submission[$select->getAttribute('name')] = $option->getAttribute('value');
            }
        }

        parse_str(http_build_query($submission), $parsed);

        $this->assertSame($name, $parsed['profile']['name']);
        $this->assertSame($role, $parsed['profile']['role']);
        $this->assertSame($teamId, $parsed['profile']['team']);
        $this->assertSame($teamLabel, $parsed['profile']['team_auto']);
    }

    public function testCheckBoxEscapesValueAndVisibleText(): void
    {
        $value = 'yes" data-checkbox-injected="yes';
        $text = '<b data-text-injected="yes">Enable & "confirm"</b>';
        $document = $this->documentFor(Form::checkBox('settings', 'enabled', $value, $text));
        $input = $document->getElementsByTagName('input')->item(0);
        $label = $document->getElementsByTagName('label')->item(0);

        $this->assertSame($value, $input->getAttribute('value'));
        $this->assertFalse($input->hasAttribute('data-checkbox-injected'));
        $this->assertSame($text, $label->textContent);
        $this->assertSame(0, $label->getElementsByTagName('b')->length);
    }

    private function firstElement(string $html, string $tag): DOMElement
    {
        $element = $this->documentFor($html)->getElementsByTagName($tag)->item(0);
        $this->assertInstanceOf(DOMElement::class, $element);

        return $element;
    }

    private function documentFor(string $html): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<!doctype html><html><head><meta charset="UTF-8"></head><body>'.$html.'</body></html>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $this->assertTrue($loaded);

        return $document;
    }
}
