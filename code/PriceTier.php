<?php
/**
 * BasePrice is always the first tier.
 * We only need the minimum side of the range.
 *
 * @property string $Label
 * @property float $Price
 * @property int $MinQty
 * @property int $ProductID
 * @method Product Product()
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 03.21.2014
 * @package apluswhs.com
 * @subpackage models
 */
class PriceTier extends DataObject
{
	private static $db = array(
		'Label'      => 'Varchar(255)',
		'Price'      => 'Currency',
		'Percentage' => 'Percentage',
		'MinQty'     => 'Int',
	);

	private static $has_one = array(
		'Product'    => 'Product',
		'SiteConfig' => 'SiteConfig', // only used for global tiers, if present
	);

	private static $casting = array(
		'Price'         => 'Currency',
		'OriginalPrice' => 'Currency'
	);

	private static $default_sort = 'MinQty';


	/**
	 * Calculate the price for this tier from a given base price
	 * @param float $price [optional]
	 * @return float
	 */
	public function calcPrice($price=0.0) {
		if (!$price) $price = $this->Price;
		return round($price * $this->Percentage, 2);
	}
}

