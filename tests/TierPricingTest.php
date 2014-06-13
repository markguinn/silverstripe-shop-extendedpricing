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
		'Product'          => array('HasPriceTiers', 'HasPromotionalPricing'),
		'Product_OrderItem'=> array('HasPriceTiers_OrderItem'),
//		'ProductVariation' => array('HasPriceTiers', 'HasPromotionalPricing'),
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
		$this->assertEquals('1-4', $t1->Label);
		$this->assertEquals(14.99, $t1->Price);

		$t2 = $tiers->offsetGet(1);
		$this->assertEquals('5-9 (indeed)', $t2->Label);
		$this->assertEquals(12, $t2->Price);

		$t3 = $tiers->last();
		$this->assertEquals('10+', $t3->Label);
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
		$this->assertEquals($price * $qty, ShoppingCart::curr()->SubTotal());
	}

	public function testProductMiddleTier() {
		$price = 12;
		$qty   = 5;
		$this->assertEquals($price, $this->p2->getPrices()->offsetGet(1)->Price);
		ShoppingCart::singleton()->add($this->p2, $qty);
		$this->assertEquals($price * $qty, ShoppingCart::curr()->SubTotal());
	}

	public function testProductTopTier() {
		$price = 7.50;
		$qty   = 10;
		$this->assertEquals($price, $this->p2->getPrices()->offsetGet(2)->Price);
		ShoppingCart::singleton()->add($this->p2, $qty);
		$this->assertEquals($price * $qty, ShoppingCart::curr()->SubTotal());
	}

	public function testSetQuantityInCart() {
		$p1 = 12;
		$q1 = 9;
		$p2 = 7.50;
		$q2 = 11;
		ShoppingCart::singleton()->add($this->p2, $q1);
		$this->assertEquals($p1 * $q1, ShoppingCart::curr()->SubTotal());
		ShoppingCart::singleton()->setQuantity($this->p2, $q2);
		$this->assertEquals($p2 * $q2, ShoppingCart::curr()->SubTotal());
	}

	public function testProductVariationWithTiers() {
	}

	public function testTiersWithPromotionalPricing() {
	}

	public function testGlobalTiers() {
	}
}