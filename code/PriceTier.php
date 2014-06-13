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
		'Label'     => 'Varchar(255)',
		'Price'     => 'Currency',
		'Percentage'=> 'Percentage',
		'MinQty'    => 'Int',
	);

	private static $has_one = array(
		'Product'   => 'Product',
	);

	private static $casting = array(
		'Price'     => 'Currency',
	);

	private static $default_sort = 'MinQty';
}

