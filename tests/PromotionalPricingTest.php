<?php
/**
 * Test promo pricing
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 08.20.2013
 * @package shop_extendedpricing
 */
class PromotionalPricingTest extends SapphireTest
{
	static $fixture_file = 'ExtendedPricingTest.yml';

	function setUpOnce() {
		Config::inst()->remove('HasGroupPricing', 'price_levels');
		Config::inst()->update('HasGroupPricing', 'price_levels', array(
			'customers'  => 'CustomerPrice',
			'wholesale' => 'WholesalePrice',
		));

		$p = singleton('Product');
		if (!$p->hasExtension('HasGroupPricing')) Product::add_extension('HasGroupPricing');
		if (!$p->hasExtension('HasPromotionalPricing')) Product::add_extension('HasPromotionalPricing');

		$pv = singleton('ProductVariation');
		if (!$pv->hasExtension('HasGroupPricing')) ProductVariation::add_extension('HasGroupPricing');
		if (!$pv->hasExtension('HasPromotionalPricing')) ProductVariation::add_extension('HasPromotionalPricing');

		parent::setUpOnce();
	}

	function setUp() {
		parent::setUp();
		PriceCache::inst()->disable();
	}

	function testPromoPricing() {
		/** @var Product $p1 */
		$p1 = $this->objFromFixture('Product', 'p1');

		// When there is no promo price, should return the base price
		$this->assertEquals(27.50, $p1->sellingPrice(), 'When there is no promo price, should return the base price');

		// When we add a promo price, should return it
		$p1->PromoActive    = true;
		$p1->PromoType      = 'Amount';
		$p1->PromoAmount    = 10;
		$this->assertEquals(17.50, $p1->sellingPrice(), 'When we add a promo price, should return it');

		// When promo is not active, should get the base price
		$p1->PromoActive    = false;
		$this->assertEquals(27.50, $p1->sellingPrice(), 'When promo is not active, should get the base price');

		// Test the start and end dates
		$p1->PromoActive    = true;
		$p1->PromoStartDate = date('Y-m-d H:i:s', strtotime('tomorrow'));
		$p1->PromoEndDate   = null;
		$this->assertEquals(27.50, $p1->sellingPrice(), 'Start date in the future and no end date');

		$p1->PromoStartDate = date('Y-m-d H:i:s', strtotime('yesterday'));
		$p1->PromoEndDate   = null;
		$this->assertEquals(17.50, $p1->sellingPrice(), 'Start date in the past and no end date');

		$p1->PromoStartDate = date('Y-m-d H:i:s', strtotime('yesterday'));
		$p1->PromoEndDate   = date('Y-m-d H:i:s', strtotime('tomorrow'));
		$this->assertEquals(17.50, $p1->sellingPrice(), 'Start date in the past, End date in the future (in the window)');

		$p1->PromoStartDate = date('Y-m-d H:i:s', strtotime('tomorrow'));
		$p1->PromoEndDate   = date('Y-m-d H:i:s', strtotime('+2 days'));
		$this->assertEquals(27.50, $p1->sellingPrice(), 'Start date and end date in the future');

		$p1->PromoStartDate = date('Y-m-d H:i:s', strtotime('-2 days'));
		$p1->PromoEndDate   = date('Y-m-d H:i:s', strtotime('yesterday'));
		$this->assertEquals(27.50, $p1->sellingPrice(), 'Start and end date in the past');

		$p1->PromoStartDate = date('Y-m-d H:i:s', strtotime('tomorrow'));
		$p1->PromoEndDate   = date('Y-m-d H:i:s', strtotime('yesterday'));
		$this->assertEquals(27.50, $p1->sellingPrice(), 'Start date in the future, End date in the past (i.e. all messed up)');

		$p1->PromoStartDate = null;
		$p1->PromoEndDate   = date('Y-m-d H:i:s', strtotime('tomorrow'));
		$this->assertEquals(17.50, $p1->sellingPrice(), 'End date in the future and no start date');

		$p1->PromoStartDate = null;
		$p1->PromoEndDate   = date('Y-m-d H:i:s', strtotime('yesterday'));
		$this->assertEquals(27.50, $p1->sellingPrice(), 'End date in the past and no start date');

		// Test percentage
		$p1->PromoEndDate   = null;
		$p1->PromoType      = 'Percent';
		$p1->PromoPercent   = 0.10;
		$this->assertEquals(24.75, $p1->sellingPrice(), 'Percentage discount');

		// Test additional methods
		$this->assertTrue($p1->HasPromotion());
		$this->assertEquals(27.50 - 24.75, $p1->calculatePromoSavings());
	}

	function testPromoOnVariation() {
		/** @var Product $p4 */
		$p4 = $this->objFromFixture('Product', 'p4');
		/** @var ProductVaration $p4v1 */
		$p4v1 = $this->objFromFixture('ProductVariation', 'p4v1');
		/** @var ProductVaration $p4v3 */
		$p4v3 = $this->objFromFixture('ProductVariation', 'p4v3'); // this one doesn't have it's own price

		// When there is no promo price, should return the base price
		$this->assertEquals(20, $p4v1->sellingPrice(), 'When there is no promo, should return the base price');
		$this->assertEquals(25, $p4v3->sellingPrice(), 'When there is no promo, should return the base price even if no price on variation');

		// When we add a promo price to the PRODUCT, it should apply to the variations as well
		$p4->PromoActive    = true;
		$p4->PromoType      = 'Amount';
		$p4->PromoAmount    = 10;
		$p4->write();
		DataObject::flush_and_destroy_cache();
		$this->assertEquals(15, $p4->sellingPrice(), 'When we add a promo to the product, should return it');
		$this->assertEquals(10, $p4v1->sellingPrice(), 'When we add a promo to the product, should return it for variations');
		$this->assertEquals(15, $p4v3->sellingPrice(), 'When we add a promo to the product, should return it for variations without a price');

		// When we add a promo to the variation, and compound_discounts is disabled, it should OVERRIDE any promos on the product
		$p4v1->PromoActive  = true;
		$p4v1->PromoType    = 'Amount';
		$p4v1->PromoAmount  = 1;
		$p4v3->PromoActive  = true;
		$p4v3->PromoType    = 'Amount';
		$p4v3->PromoAmount  = 1;
		Config::inst()->update('HasPromotionalPricing', 'compound_discounts', false);
		$this->assertEquals(19, $p4v1->sellingPrice(), 'When we add a promo to the variation, and compound_discounts is disabled, it should OVERRIDE any promos on the product');
		$this->assertEquals(24, $p4v3->sellingPrice(), 'When we add a promo to the variation, and compound_discounts is disabled, it should OVERRIDE any promos on the product even if the variation has no price');

		// When we add a promo to the variation, and compound_discounts is enabled, it should APPLY BOTH promos
		Config::inst()->update('HasPromotionalPricing', 'compound_discounts', true);
		$this->assertEquals(9, $p4v1->sellingPrice(), 'When we add a promo to the variation, and compound_discounts is disabled, it should APPLY BOTH promos');
		$this->assertEquals(14, $p4v3->sellingPrice(), 'When we add a promo to the variation, and compound_discounts is disabled, it should APPLY BOTH promos even if the variation has no price');

		// When a promo is only on the variation it should apply properly
		$p4->PromoActive    = false;
		$p4->write();
		DataObject::reset();
		$p4v1->flushCache();
		$p4v3->flushCache();
		$this->assertEquals(19, $p4v1->sellingPrice(), 'When we add a promo to the variation only, it should apply');
		$this->assertEquals(24, $p4v3->sellingPrice(), 'When we add a promo to the variation only, it should apply even if the variation has no price');
	}

	function testPromoOnCategory() {
		/** @var Product $p1 */
		$p1 = $this->objFromFixture('Product', 'p1');
		/** @var Product $p3 */
		$p3 = $this->objFromFixture('Product', 'p3');
		/** @var ProductCategory $c1 */
		$c1 = $this->objFromFixture('ProductCategory', 'c1');
		/** @var ProductVaration $p4v1 */
		$p4v1 = $this->objFromFixture('ProductVariation', 'p4v1');

		// Products are initially right
		$this->assertEquals(27.50,  $p1->sellingPrice(),    'Products are initially right');
		$this->assertEquals(25,     $p3->sellingPrice(),    'Products are initially right');
		$this->assertEquals(20,     $p4v1->sellingPrice(),  'Products are initially right');

		// When a promo is added to the parent category, it changes the price on products
		$c1->PromoActive    = true;
		$c1->PromoType      = 'Amount';
		$c1->PromoAmount    = 10;
		$c1->write();
		DataObject::flush_and_destroy_cache();
		$this->assertEquals(17.50,  $p1->sellingPrice(),    'When a promo is added to the parent category, it changes the price on products');

		// Check that it also works if the category is not the main parent category
		$this->assertEquals(15,     $p3->sellingPrice(),    'Check that it also works if the category is not the main parent category');

		// When a promo is added to the parent category, it changes the price on variations
		$this->assertEquals(10,     $p4v1->sellingPrice(),  'When a promo is added to the parent category, it changes the price on variations');
	}

	function testPromoWithGroupPricing() {
		/** @var Product $p1 */
		$p1 = $this->objFromFixture('Product', 'p1');
		/** @var Member $m1 */
		$m1 = $this->objFromFixture('Member', 'm1');

		$this->assertEquals(27.50, $p1->sellingPrice(), 'Check initial price');

		$m1->logIn();
		$this->assertEquals(25, $p1->sellingPrice(), 'Check group price');

		// This depends on the order in which the extensions are applied
		// which, apparently, we cannot control? Seems weird but I don't
		// have time to knock this out at the minute. Hopefully can circle
		// back later.
//		$p1->PromoActive    = true;
//		$p1->PromoType      = 'Amount';
//		$p1->PromoAmount    = 10;
//		$this->assertEquals(15, $p1->sellingPrice(), 'Check group + promo');
	}
}
