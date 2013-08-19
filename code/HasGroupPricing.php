<?php
/**
 * Extension to add multiple levels of pricing to a product, based on
 * the logged in group.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 08.16.2013
 * @package shop_extendedpricing
 */
class HasGroupPricing extends DataExtension
{
	private static $price_levels = array(
		//'GroupCode' => 'FieldName'
	);

	/**
	 * Shortcut to for config
	 * @return array
	 */
	public static function get_levels() {
		$price_levels = Config::inst()->get('HasGroupPricing', 'price_levels');
		if (!is_array($price_levels)) $price_levels = array();
		return $price_levels;
	}

	/**
	 * @param $class
	 * @param $extension
	 * @param $args
	 * @return mixed
	 */
	public static function get_extra_config($class, $extension, $args) {
		$statics = parent::get_extra_config($class, $extension, $args);
		$price_levels = self::get_levels();

		if (count($price_levels) > 0) {
			if (!isset($statics['db'])) $statics['db'] = array();

			// add a field for each price level
			foreach ($price_levels as $code => $field) {
				$statics['db'][$field] = 'Currency';
			}
		}

		return $statics;
	}

	/**
	 * @param FieldList $fields
	 */
	public function updateCMSFields(FieldList $fields) {
		foreach (self::get_levels() as $code => $field) {
			$fields->addFieldToTab('Root.Pricing', new TextField($field, $this->getOwner()->fieldLabel($field), '', 12));
		}
	}

	/**
	 * TODO: this probably needs to be cached
	 * @param $price
	 */
	public function updateSellingPrice(&$price) {
		$levels = self::get_levels();
		$member = Member::currentUser();
		if (count($levels) > 0 && $member) {
			// if there is a logged in member and multiple levels, check them uot
			$groups = $member->Groups()->column('Code');
			foreach ($groups as $code) {
				// if the group we're looking at has it's own price field,
				// and the price is lower than the current price, update it
				if (isset($levels[$code])) {
					$field = $levels[$code];
					$altPrice = $this->getOwner()->getField($field);
					if ($altPrice > 0 && $altPrice < $price) $price = $altPrice;
				}
			}
		}
	}
}
