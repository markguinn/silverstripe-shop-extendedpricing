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
	 * Extracts out the field updating since that could happen at a couple
	 * different extension points.
	 * @param FieldList $fields
	 */
	protected function updateFields(FieldList $fields) {
		foreach (self::get_levels() as $code => $fieldName) {
			$newField = new TextField($fieldName, $this->getOwner()->fieldLabel($fieldName), '', 12);
			if ($fields->hasTabSet()) {
				$fields->addFieldToTab('Root.Pricing', $newField);
			} else {
				$fields->push($newField);
			}
		}
	}

	/**
	 * @param FieldList $fields
	 */
	public function updateCMSFields(FieldList $fields) {
		// This is a little bit of a crazy hack to account for a pull request
		// I've issued to the main shop module. Basically, the normal extension
		// point for cms fields is called before any of the product-specific
		// tabs are added, so when we add our fields to the Pricing tab, we
		// have no control over placement - they're always at the top of the tab.
		// I've added another extension point called updateProductCMSFields
		// but there's no way to detect if it's present so we just check this
		// config for now. At some point, this will be ubiquitous and we can
		// just remove it [hopefully].
		if (!Config::inst()->get(get_class($this->getOwner()), 'use_product_cms_extension_point')) {
			$this->updateFields($fields);
		}
	}

	/**
	 * This is another extension point I added that is called AFTER all
	 * the product-specific fields and tabs are in place.
	 * @param FieldList $fields
	 */
	public function updateProductCMSFields(FieldList $fields) {
		$this->updateFields($fields);
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
