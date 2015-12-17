<?php
/**
 * Quick unit tests for percentage field
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 08.20.2013
 * @package shop_extendedpricing
 * @subpackage tests
 */
class PercentageFieldTest extends SapphireTest
{
    public function testField()
    {
        $locale = i18n::get_locale();
        i18n::set_locale('en_US');

        // Should start at 0
        $field = new PercentageField('Test', 'TestField');
        $this->assertEquals(0, $field->dataValue());
        $this->assertEquals('0%', $field->Value());

        // Entering 50 should yield 0.5
        $field->setValue('50');
        $this->assertEquals(0.5, $field->dataValue());
        $this->assertEquals('50%', $field->Value());

        // Entering 50% should yield 0.5
        $field->setValue('50%');
        $this->assertEquals(0.5, $field->dataValue());
        $this->assertEquals('50%', $field->Value());

        // Entering -50% should yield -0.5
        $field->setValue('-50%');
        $this->assertEquals(-0.5, $field->dataValue());
        $this->assertEquals('-50%', $field->Value());

        // Entering 0.5 should yield 0.5
        $field->setValue('0.5');
        $this->assertEquals(0.5, $field->dataValue());
        $this->assertEquals('50%', $field->Value());

        // Entering 0.5% should yield 0.005
        $field->setValue('0.5%');
        $this->assertEquals(0.005, $field->dataValue());
        $this->assertEquals('0.5%', $field->Value());

        i18n::set_locale($locale);
    }

    public function testFieldInDenmark()
    {
        $locale = i18n::get_locale();
        i18n::set_locale('da_DK');

        // Should start at 0
        $field = new PercentageField('Test', 'TestField');
        $this->assertEquals(0, $field->dataValue());
        $this->assertEquals('0%', $field->Value());

        // Entering 50 should yield 0.5
        $field->setValue('50');
        $this->assertEquals(0.5, $field->dataValue());
        $this->assertEquals('50%', $field->Value());

        // Entering 50% should yield 0.5
        $field->setValue('50%');
        $this->assertEquals(0.5, $field->dataValue());
        $this->assertEquals('50%', $field->Value());

        // Entering -50% should yield -0.5
        $field->setValue('-50%');
        $this->assertEquals(-0.5, $field->dataValue());
        $this->assertEquals('-50%', $field->Value());

        // Entering 0.5 should yield 0.5
        $field->setValue('0,5');
        $this->assertEquals(0.5, $field->dataValue());
        $this->assertEquals('50%', $field->Value());

        // Entering 0.5% should yield 0.005
        $field->setValue('0,5%');
        $this->assertEquals(0.005, $field->dataValue());
        $this->assertEquals('0,5%', $field->Value());

        i18n::set_locale($locale);
    }
}
