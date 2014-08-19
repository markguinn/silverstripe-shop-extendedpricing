<?php
/**
 * Tests for tier pricing features
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 06.12.2014
 * @package shop_extendedpricing
 * @subpackage tests
 */
class TierPricingTest extends SapphireTest
{
	static $fixture_file = 'ExtendedPricingTest.yml';

	protected $requiredExtensions = array(
		'Product' => array('HasPriceTiers', 'HasPromotionalPricing'),
		'Product_OrderItem' => array('HasPriceTiers_OrderItem'),
		'ProductVariation_OrderItem' => array('HasPriceTiers_OrderItem'),
		'SiteConfig' => array('HasPriceTiers'),
	);

	/** @var Product */
	protected $p1;
	/** @var Product */
	protected $p2;
	/** @var Product */
	protected $p4;
	/** @var ProductVariation */
	protected $v1;

	public function setup() {
		parent::setUp();
		PriceCache::inst()->disable();
		ShoppingCart::singleton()->clear();
		$this->p1 = $this->objFromFixture('Product', 'p1');
		$this->p1->publish('Stage', 'Live');
		$this->p2 = $this->objFromFixture('Product', 'p2');
		$this->p2->publish('Stage', 'Live');
		$this->p4 = $this->objFromFixture('Product', 'p4');
		$this->p4->publish('Stage', 'Live');
		$this->v1 = $this->objFromFixture('ProductVariation', 'p4v1');
	}

	public function testNoTiers() {
		$this->assertEquals(27.50, $this->p1->BasePrice);
		ShoppingCart::singleton()->add($this->p1, 10);
		$this->assertEquals(275, ShoppingCart::curr()->SubTotal());
	}

	public function testGetTiers() {
		$this->assertEquals(1, $this->p1->getPrices()->count());

		$tiers = $this->p2->getPrices();
		$this->assertEquals(3, $tiers->count());

		$t1 = $tiers->first();
		$this->assertEquals('1-4', $t1->Label); // automatically generated
		$this->assertEquals(14.99, $t1->Price);

		$t2 = $tiers->offsetGet(1);
		$this->assertEquals('5-9 (indeed)', $t2->Label); // specified in tier record
		$this->assertEquals(12, $t2->Price);

		$t3 = $tiers->last();
		$this->assertEquals('10+', $t3->Label); // automatically generated
		$this->assertEquals(7.50, $t3->Price);
	}

	public function testGetTierForQuantity() {
		$t1 = $this->p2->getTierForQuantity(0);
		$this->assertEquals(14.99, $t1->Price);
		$t1 = $this->p2->getTierForQuantity(1);
		$this->assertEquals(14.99, $t1->Price);
		$t1 = $this->p2->getTierForQuantity(4);
		$this->assertEquals(14.99, $t1->Price);
		$t1 = $this->p2->getTierForQuantity(5);
		$this->assertEquals(12, $t1->Price);
		$t1 = $this->p2->getTierForQuantity(9);
		$this->assertEquals(12, $t1->Price);
		$t1 = $this->p2->getTierForQuantity(10);
		$this->assertEquals(7.50, $t1->Price);
	}

	public function testProductWithTiersBasePrice() {
		$price = 14.99;
		$qty   = 4;
		$this->assertEquals($price, $this->p2->BasePrice);
		ShoppingCart::singleton()->add($this->p2, $qty);
		ShoppingCart::curr()->calculate();
		$this->assertEquals($price * $qty, ShoppingCart::curr()->SubTotal());
	}

	public function testProductMiddleTier() {
		$price = 12;
		$qty   = 5;
		$this->assertEquals($price, $this->p2->getPrices()->offsetGet(1)->Price);
		ShoppingCart::singleton()->add($this->p2, $qty);
		ShoppingCart::curr()->calculate();
		$this->assertEquals($price * $qty, ShoppingCart::curr()->SubTotal());
	}

	public function testProductTopTier() {
		$price = 7.50;
		$qty   = 10;
		$this->assertEquals($price, $this->p2->getPrices()->offsetGet(2)->Price);
		ShoppingCart::singleton()->add($this->p2, $qty);
		ShoppingCart::curr()->calculate();
		$this->assertEquals($price * $qty, ShoppingCart::curr()->SubTotal());
	}

	public function testSetQuantityInCart() {
		$price1 = 12;
		$qty1   = 9;
		$price2 = 7.50;
		$qty2   = 11;
		ShoppingCart::singleton()->add($this->p2, $qty1);
		$this->assertEquals($price1 * $qty1, ShoppingCart::curr()->SubTotal());
		ShoppingCart::singleton()->setQuantity($this->p2, $qty2);
		$this->assertEquals($price2 * $qty2, ShoppingCart::curr()->SubTotal());
	}

	public function testProductVariationWithTiers() {
		$price1 = 20;
		$qty1   = 3;
		$price2 = 16;
		$qty2   = 4;
		ShoppingCart::singleton()->add($this->v1, $qty1);
		ShoppingCart::curr()->calculate();
		$this->assertEquals($price1 * $qty1, ShoppingCart::curr()->SubTotal());
		ShoppingCart::singleton()->setQuantity($this->v1, $qty2);
		ShoppingCart::curr()->calculate();
		$this->assertEquals($price2 * $qty2, ShoppingCart::curr()->SubTotal());
	}

	public function testParentProductWithTiers() {
		$this->p1->ParentID = $this->p2->ID;
		$this->p1->write();
		$this->p1->publish('Stage', 'Live');

		$price = round(27.50 * 0.5, 2);
		$qty   = 10;

		$tiers = $this->p1->getPrices();
		$this->assertEquals(3, $tiers->count());
		$this->assertEquals($price, $tiers->offsetGet(2)->Price);

		ShoppingCart::singleton()->add($this->p1, $qty);
		ShoppingCart::curr()->calculate();
		$this->assertEquals($price * $qty, ShoppingCart::curr()->SubTotal());
	}

	public function testTiersWithPromotionalPricing() {
		$this->p2->PromoActive = true;
		$this->p2->PromoType = 'Percent';
		$this->p2->PromoPercent = 0.5;
		$this->p2->write();
		$this->p2->publish('Stage', 'Live');

		$price = round(14.99 * 0.25, 2);
		$qty   = 10;

		$tiers = $this->p2->getPrices();
		$this->assertEquals(3, $tiers->count());
		$this->assertEquals($price, $tiers->offsetGet(2)->Price);
		$this->assertEquals(7.50, $tiers->offsetGet(2)->OriginalPrice);

		ShoppingCart::singleton()->add($this->p2, $qty);
		ShoppingCart::curr()->calculate();
		$this->assertEquals($price * $qty, ShoppingCart::curr()->SubTotal());
	}

	public function testGlobalTiers() {
		$t1 = new PriceTier(array(
			'MinQty'        => 20,
			'Percentage'    => 0.5,
			'SiteConfigID'  => SiteConfig::current_site_config()->ID,
		));
		$t1->write();

		$tiers = $this->p1->getPrices();
		$this->assertEquals(2, $tiers->count());

		$price1 = 27.50;
		$qty1   = 19;
		$price2 = round(27.50 * 0.5, 2);
		$qty2   = 20;

		ShoppingCart::singleton()->add($this->p1, $qty1);
		ShoppingCart::curr()->calculate();
		$this->assertEquals($price1 * $qty1, ShoppingCart::curr()->SubTotal());
		ShoppingCart::singleton()->setQuantity($this->p1, $qty2);
		ShoppingCart::curr()->calculate();
		$this->assertEquals($price2 * $qty2, ShoppingCart::curr()->SubTotal());
	}
}