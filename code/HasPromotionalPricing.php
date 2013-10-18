<?php
/**
 * Extension for Buyable models to allow promotional pricing
 * to be applied.
 *  - can be applied to categories as well
 *  - can be limited by start and/or end date
 *  - can be absolute price or percentage discount
 *  - can specify whether to display as a sale (i.e. show old price crossed out)
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 08.19.2013
 * @package shop_extendedpricing
 */
class HasPromotionalPricing extends DataExtension
{
	// Config value - if there is a promo on a parent (category or product) and a child (product or variation),
	// do both discounts apply (true) or just the most specific one on the child (false)
	private static $compound_discounts = false;

	// Config value - globally disable all discounts
	private static $disable_discounts = false;

	private static $db = array(
		"PromoActive"    => "Boolean",
		"PromoDisplay"   => "Enum('ShowDiscount,HideDiscount','ShowDiscount')", // Display old price as well?
		"PromoType"      => "Enum('Percent,Amount','Percent')",
		"PromoAmount"    => "Currency",
		"PromoPercent"   => "Percentage",
		"PromoStartDate" => "Datetime",
		"PromoEndDate"   => "Datetime",
	);

	/** @var bool - used by sellingPriceBeforePromotions */
	protected static $bypass = false;

	/** @var double - used for savings calculations */
	protected $_cachedPrice;
	protected $_cachedOriginal;

	/**
	 * Extracts out the field updating since that could happen at a couple
	 * different extension points.
	 * @param FieldList $fields
	 */
	protected function updateFields(FieldList $fields) {
		// This seemed to cause problems. Moved to config.yml.
		//Requirements::javascript(SHOP_EXTENDEDPRICING_FOLDER . '/javascript/ExtendedPricingAdmin.js');

		$newFields = array(
			new CheckboxField("PromoActive", "Promotional pricing active?"),
			new OptionsetField("PromoDisplay", "Display Settings",
				array(
					"ShowDiscount"  => "Show base price crossed out",
					"HideDiscount"  => "Hide base price",
				)
			),
			new OptionsetField("PromoType", "Type of discount",
				array(
					"Percent"   => "Percentage of subtotal (eg 25%)",
					"Amount"    => "Fixed amount (eg $25.00)"
				)
			),
			new PercentageField("PromoPercent", "Percent discount"),
			new NumericField("PromoAmount", "Fixed discount (e.g. 5 = $5 off)"),
			new FieldGroup("Valid date range (optional):", array(
				PromoDatetimeField::create("PromoStartDate", "Start Date / Time"),
				PromoDatetimeField::create("PromoEndDate", "End Date / Time (you should set the end time to 23:59:59, if you want to include the entire end day)"),
			))
		);

		$f = new ToggleCompositeField('PromoFields', 'Promotional Pricing', $newFields);
		$f->setStartClosed(!$this->getOwner()->PromoActive);

		if ($fields->hasTabSet()) {
			$fields->addFieldToTab('Root.Pricing', $f);
		} else {
			$fields->push($f);
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
	 * Returns the selling price without any discounts added in.
	 * This may be a slightly tedious way to do it, but it does
	 * gaurantee that any extensions before or after this one
	 * get to run.
	 *
	 * @return double
	 */
	public function sellingPriceBeforePromotion() {
		self::$bypass = true;
		$price = $this->getOwner()->sellingPrice();
		self::$bypass = false;
		return $price;
	}

	/**
	 * Original price for template usage
	 * @return Money
	 */
	function getOriginalPrice() {
		$currency = Payment::site_currency();
		$field = new Money("OriginalPrice");
		$field->setAmount($this->sellingPriceBeforePromotion());
		$field->setCurrency($currency);
		return $field;
	}
	function OriginalPrice(){ return $this->getOriginalPrice(); }


	/**
	 * TODO: make sure these calculations only happen once
	 * @return float
	 */
	function calculatePromoSavings() {
		return $this->sellingPriceBeforePromotion() - $this->getOwner()->sellingPrice();
	}

	/**
	 * @return Money
	 */
	function PromoSavings() {
		$currency = Payment::site_currency();
		$field = new Money("PromoSavings");
		$field->setAmount($this->calculatePromoSavings());
		$field->setCurrency($currency);
		return $field;
	}

	/**
	 * TODO: check category discounts
	 * @param $price
	 */
	public function updateSellingPrice(&$price) {
		if (self::$bypass || Config::inst()->get('HasPromotionalPricing', 'disable_discounts')) return;

		// Special case: if this is a variation without it's own price
		// AND the parent product has a promo, the price we inherited
		// is already discounted, so we need to reset that.
		if ($this->getOwner() instanceof ProductVariation && !$this->getOwner()->Price)
		{
			$p = $this->getOwner()->Product();
			if ($p && $p->exists() && $p->hasExtension('HasPromotionalPricing') && $p->hasValidPromotion()) {
				$price = $p->sellingPriceBeforePromotion();
			}
		}

		// Apply the most local discount first
		$compoundDiscounts = Config::inst()->get('HasPromotionalPricing', 'compound_discounts');
		$applied = $this->applyPromoFrom($this->getOwner(), $price);
		if ($applied && !$compoundDiscounts) return;

		// For each level of parent discounts do the same
		$parents = $this->collectParentPromoSources();
		if (is_array($parents) && count($parents) > 0) {
			$processed = array();
			foreach ($parents as $parent) {
				// It's very possible to have a category in there
				// more than once, so just do a quick uniqueness check
				$key = $parent->ClassName . ':' . $parent->ID;
				if (isset($processed[$key])) continue;
				$processed[$key] = true;

				// Apply the promo and stop if needed
				$applied = $this->applyPromoFrom($parent, $price);
				if ($applied && !$compoundDiscounts) return;
			}
		}
	}

	/**
	 * Collects any other sources of applicable discounts, leaving
	 * room for extension from other sources
	 * @param $obj [optional]
	 * @return array
	 */
	protected function collectParentPromoSources($obj = null) {
		if (!$obj) $obj = $this->getOwner();
		if ($obj->hasMethod('getParentPromoSources')) {
			return $obj->getParentPromoSources();
		} else {
			$sources = array();

			if ($obj instanceof ProductVariation) {
				$p = $obj->Product();
				if ($p && $p->exists() && $p->hasExtension('HasPromotionalPricing')) $sources[] = $p;
				$sources = array_merge($sources, $this->collectParentPromoSources($p));
			}

			if ($obj instanceof Product) {
				$cats = $obj->ProductCategories()->toArray();
				if ($obj->ParentID) $cats[] = $obj->Parent();
				foreach ($cats as $cat) {
					if ($cat && $cat->exists() && $cat->hasExtension('HasPromotionalPricing')) $sources[] = $cat;
					$sources = array_merge($sources, $this->collectParentPromoSources($cat));
				}
			}

			if ($obj instanceof ProductCategory) {
				if ($obj->ParentID) {
					$p = $obj->Parent();
					if ($p && $p->exists() && $p->hasExtension('HasPromotionalPricing')) $sources[] = $p;
					$sources = array_merge($sources, $this->collectParentPromoSources($p));
				}
			}

			return $sources;
		}
	}

	/**
	 * Apply the discount from this or any parent object to
	 * a given price.
	 *
	 * @param ProductCategory|Buyable $obj
	 * @param $price
	 * @return bool - was any discount applied?
	 */
	protected function applyPromoFrom($obj, &$price) {
		if (!$obj->hasValidPromotion($obj)) return false;

		// Apply the price
		if ($obj->PromoType == 'Percent') {
			$price -= $price * $obj->PromoPercent;
		} else {
			$price -= $obj->PromoAmount;
		}

		if ($price < 0) $price = 0;
		return true;
	}

	/**
	 * Does this object have an applicable promo?
	 * @param ProductCategory|Buyable $obj [optional]
	 * @return bool
	 */
	public function hasValidPromotion($obj=null) {
		if (!$obj) $obj = $this->getOwner();

		// Check that it's not disabled
		if (!$obj->PromoActive) return false;
		// We actually want to be able to specify a 0 discount on a child item to cancel the discount on the parent
		//if (!$obj->PromoPercent && !$obj->PromoAmount) return false;

		// Check time frame
		$t      = time();
		$start  = strtotime($obj->PromoStartDate);
		$end    = strtotime($obj->PromoEndDate);
		if ($start > 0 && $t < $start) return false;
		if ($end > 0 && $t > $end) return false;

		// All clear
		return true;
	}

	/**
	 * For template
	 * @return bool
	 */
	public function HasPromotion() {
		return $this->hasValidPromotion();
	}
}