<?php
/**
 * Test "multi-price" features.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 08.16.2013
 * @package shop_extendedpricing
 */
class ExtendedPricingTest extends SapphireTest
{
	static $fixture_file = 'ExtendedPricingTest.yml';

	function setUpOnce() {
		Config::inst()->remove('HasGroupPricing', 'price_levels');
		Config::inst()->update('HasGroupPricing', 'price_levels', array(
			'customers'  => 'CustomerPrice',
			'wholesale' => 'WholesalePrice',
		));

		$p = singleton('Product');
		if (!$p->hasExtension('HasGroupPricing')) {
			Product::add_extension('HasGroupPricing');
		}
	}

	function testGroupPricing() {
		/** @var Member $m1 */
		$m1 = $this->objFromFixture('Member', 'm1');
		$m2 = $this->objFromFixture('Member', 'm2');
		/** @var Product $p1 */
		$p1 = $this->objFromFixture('Product', 'p1');
		$p2 = $this->objFromFixture('Product', 'p2');
		$p3 = $this->objFromFixture('Product', 'p3');

		// Additional price fields should be created and populated.
		$this->assertTrue($p1->hasField('WholesalePrice'));
		$this->assertTrue($p1->hasField('CustomerPrice'));
		$this->assertEquals(27.50, $p1->BasePrice);
		$this->assertEquals(20, $p1->WholesalePrice);
		$this->assertEquals(25, $p1->CustomerPrice);

		// Additional price inputs should be added to the cms fields.
		$fields = $p1->getCMSFields();
		$this->assertNotNull($fields->fieldByName('Root.Pricing.WholesalePrice'));
		$this->assertNotNull($fields->fieldByName('Root.Pricing.CustomerPrice'));

		// When not logged in, selling price should reflect base price.
		$this->assertEquals($p1->BasePrice, $p1->sellingPrice());

		// When logged in, selling price should change.
		$m1->logIn();
		$this->assertEquals($p1->CustomerPrice, $p1->sellingPrice());

		// When a user is in more than one group, it should reflect the lowest price.
		$m1->logOut();
		$m2->logIn();
		$this->assertEquals($p1->WholesalePrice, $p1->sellingPrice());

		// If group price is not specified, base price should be assumed.
		$this->assertEquals($p2->BasePrice, $p2->sellingPrice());
		$this->assertEquals($p3->CustomerPrice, $p3->sellingPrice());
	}

	function testCartActivity() {
		/** @var Member $m1 */
		$m1 = $this->objFromFixture('Member', 'm1');
		/** @var Product $p1 */
		$p1 = $this->objFromFixture('Product', 'p1');
		$p1->publish('Stage', 'Live');

		// Given an item in the shopping cart, the price should change when a user logs in
		ShoppingCart::singleton()->add($p1, 1);
		/** @var Order $order */
		$order = ShoppingCart::curr();
		$this->assertEquals($p1->BasePrice, $order->SubTotal());
		$m1->logIn();
		$this->assertEquals($p1->CustomerPrice, $order->SubTotal());

		// TODO: We should also test that the total gets updated at the proper time
		// but I'm not familiar enough with the internals of the cart to know what
		// to test. At this time I'll just test manually.
	}

}
