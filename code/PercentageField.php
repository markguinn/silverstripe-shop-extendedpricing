<?php
/**
 * Simple form element that makes saving to Percentage
 * DBField easier. Accepts the following kinds of input:
 *  -> 50    = 50%
 *  -> 0.5   = 50%
 *  -> 0.5%  = 0.5%
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 08.20.2013
 * @package shop_extendedpricing
 */
class PercentageField extends NumericField
{
	public function Type() {
		return 'percentage numeric text';
	}

	public function setValue($val) {
		if (strpos($val, '%') !== false || (double)$val > 1) {
			$val = (double)$val / 100.0;
		}

		return parent::setValue($val);
	}

	public function dataValue() {
		return (is_numeric($this->value)) ? $this->value : 0;
	}

	public function Value() {
		return ($this->dataValue() * 100.0) . '%';
	}
}