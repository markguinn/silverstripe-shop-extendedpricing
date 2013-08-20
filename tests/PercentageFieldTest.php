<?php
/**
 * Quick unit tests for percentage field
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 08.20.2013
 * @package shop_extendedpricing
 * @subpackage forms
 */
class PercentageFieldTest extends SapphireTest
{
	function testField() {
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
	}
}