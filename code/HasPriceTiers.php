<?php
/**
 * Adds tiered pricing to a product (or theoretically any buyable)
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 03.21.2014
 * @package shop_extendedpricing
 */
class HasPriceTiers extends DataExtension
{
	private static $db = array(
		'BasePriceLabel'    => 'HTMLVarchar(100)',  // applied only to the baseprice (equivalent of PriceTier->Label)
	);

	private static $has_many = array(
		'PriceTiers' => 'PriceTier',
	);

	/** @var ArrayList - cache of getPrices */
	protected $_prices;

	/**
	 * Grabs all prices into one place.
	 * @return ArrayList
	 */
	public function getPrices() {
		if (!isset($this->_prices)) {
			$this->_prices = new ArrayList();

			// Create a base tier
			$base = new PriceTier();
			$base->Label = $this->owner->BasePriceLabel;
			$base->Price = $this->owner->sellingPrice();
			$base->Percentage = 1;
			$base->MinQty = 1;
			$this->_prices->push($base);

			// Integrate with promo pricing
			if ($this->owner->hasExtension('HasPromotionalPricing') && $base->Price != $this->owner->BasePrice) {
				$base->OriginalPrice = $this->owner->BasePrice;
			}

			// If this product has tiers, use those
			$tiers = $this->owner->PriceTiers();

			// If not, see if the parent has tiers
			if ((!$tiers || !$tiers->exists()) && $this->owner->hasMethod('Parent')) {
				$parent = $this->owner->Parent();
				if ($parent && $parent->exists() && $parent->hasExtension('HasPriceTiers')) {
					$tiers = $parent->PriceTiers();
					if ($tiers && empty($base->Label) && !empty($parent->BasePriceLabel)) $base->Label = $parent->BasePriceLabel;
				}
			}

			// If not, see if there are global tiers
			if ((!$tiers || !$tiers->exists()) && SiteConfig::has_extension('HasPriceTiers')) {
				$global = SiteConfig::current_site_config();
				$tiers  = $global->PriceTiers();
				if ($tiers && empty($base->Label) && !empty($global->BasePriceLabel)) $base->Label = $global->BasePriceLabel;
			}

			// Fill in the additional tiers
			foreach ($tiers as $tier) {
				/** @var PriceTier $tier */
				// calculate a price if needed
				if ($tier->Price == 0 && $tier->Percentage > 0) {
					$tier->Price = $tier->calcPrice($base->Price);
				} elseif ($tier->Price > 0 && $tier->Percentage == 0 && $base->Price > 0) {
					$price = $tier->Price;
					$this->owner->extend('updateSellingPrice', $price); // make sure discounts still apply
					$price = $price < 0 ? 0 : $price;
					$tier->Price = $price;
					$tier->Percentage = $price / $base->Price;
				}

				// integrate with promo pricing
				if ($this->owner->hasExtension('HasPromotionalPricing') && !empty($base->OriginalPrice)) {
					$tier->OriginalPrice = $tier->calcPrice($base->OriginalPrice);
				}

				// add it to the stack
				$this->_prices->push($tier);
			}

			// now make one more pass through and generate missing labels
			$num = $this->_prices->count();
			if ($num > 1) {
				for ($i = 0; $i < $num; $i++) {
					if (empty($this->_prices[$i]->Label)) {
						$this->_prices[$i]->Label = (string)$this->_prices[$i]->MinQty;
						if ($i == $num-1) {
							$this->_prices[$i]->Label .= '+';
						} else {
							$this->_prices[$i]->Label .= '-' . ($this->_prices[$i+1]->MinQty-1);
						}
					}
				}
			}
		}

		return $this->_prices;
	}


	/**
	 * @param $qty
	 * @return PriceTier
	 */
	public function getTierForQuantity($qty) {
		$tiers = $this->getPrices();
		if (!$tiers || $tiers->count() == 0) return null;

		$returnTier = $tiers->first();

		foreach ($tiers as $testTier) {
			//echo "Testing $qty against {$testTier->MinQty"
			if ($qty < $testTier->MinQty) break;
			else $returnTier = $testTier;
		}

		return $returnTier;
	}


	/**
	 * @param FieldList $fields
	 */
	public function updateCMSFields(FieldList $fields) {
		$baseLabel = TextField::create('BasePriceLabel', 'Label for Base Tier');
		$bp = $fields->fieldByName('Root.Pricing.BasePrice');
		if ($bp) {
			$fields->addFieldToTab('Root.Pricing', $baseLabel, 'BasePrice');
		}
	}

}


class HasPriceTiers_OrderItem extends DataExtension
{
	/**
	 * @param float $unitPrice
	 */
	public function updateUnitPrice(&$unitPrice) {
		$buyable = $this->owner->Buyable();
		if (!$buyable) return;
		$tier = null;

		// Easiest case: the buyable has it's own tiers
		if ($buyable->hasExtension('HasPriceTiers')) {
			$tier = $buyable->getTierForQuantity($this->owner->Quantity);
		}

		// Usually, you'd have one set of tiers on the parent product
		// which apply to all variations
		if (!$tier && $buyable instanceof ProductVariation) {
			$prod = $buyable->Product();
			if ($prod && $prod->exists() && $prod->hasExtension('HasPriceTiers')) {
				$tier = $prod->getTierForQuantity($this->owner->Quantity);
			}
		}

		// Finally, in some cases (grouped products, primarily) we
		// would want to get the tiers from a parent
		if (!$tier && $buyable->hasMethod('Parent')) {
			$parent = $buyable->Parent();
			if ($parent && $parent->exists() && $parent->hasExtension('HasPriceTiers')) {
//				echo "{$buyable->ID} parent with tiers\n";
				$tier = $parent->getTierForQuantity($this->owner->Quantity);
			}
		}

		// Finally, if we got a tier and it's not the base tier, change the price
		if ($tier && $tier->MinQty > 1) $unitPrice = $tier->calcPrice($unitPrice);
	}


	/**
	 * The shop module won't recalculate the unitprice twice in one request (a good thing)
	 * But currently the ->add method first adds with a quantity of 1 and then sets the quantity,
	 * however the unitprice ends up getting calculated in there while the qty=1 and never
	 * recalculated. That's a safe assumption generally but it breaks tier pricing so we
	 * have to check here and force it to recalculate.
	 */
//	public function onBeforeWrite() {
//		if (
//			ShoppingCart::curr() &&
//			$this->owner->OrderID == ShoppingCart::curr()->ID &&
//			$this->owner->isChanged('Quantity') &&
//			$this->owner->Quantity != 1
//		) {
//			// force unitprice to be recalculated
//			$this->owner->setUnitPrice(0);
//			$this->owner->UnitPrice();
//		}
//	}
}


