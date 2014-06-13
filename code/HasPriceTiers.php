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

			$base = new PriceTier();
			$base->Label  = $this->owner->BasePriceLabel;
			$base->Price  = $this->owner->sellingPrice();
			$base->Percentage = 1;
			$base->MinQty = 1;
			$this->_prices->push($base);

			foreach ($this->owner->PriceTiers() as $tier) {
				if (empty($tier->Price) && !empty($tier->Percentage)) $tier->Price = round($base->Price * $tier->Percentage, 2);
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


	// TODO: updateCMSFields

}


class HasPriceTiers_OrderItem extends DataExtension
{
	/**
	 * @param float $unitPrice
	 */
	public function updateUnitPrice(&$unitPrice) {
		$buyable = $this->owner->Buyable();
		if ($buyable && $buyable->hasExtension('HasPriceTiers')) {
			$qty = $this->owner->Quantity;
			$tier = $buyable->getTierForQuantity($qty);
			if ($tier && $tier->MinQty > 1) $unitPrice = $tier->Price;
		}
	}
}


