<?php
/**
 * For some reason, the database structure doesn't get rebuilt between
 * test suites, so some tests were failing when run under travis or
 * as a module, but only when running in a bare environment where these
 * extensions weren't added globally. This just centralizes the setup
 * so all tests for this module share some global setup.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 06.12.2014
 * @package shop_extendedpricing
 * @subpackage tests
 */
abstract class ExtendedPricingBaseTest extends SapphireTest
{
	function setUpOnce() {
		Config::inst()->remove('HasGroupPricing', 'price_levels');
		Config::inst()->update('HasGroupPricing', 'price_levels', array(
			'customers'  => 'CustomerPrice',
			'wholesale' => 'WholesalePrice',
		));

		// i'm adding them all here because if you run all the tests together, the db doesn't seem to get rebuilt
		$pc = singleton('ProductCategory');
		if (!$pc->hasExtension('HasPromotionalPricing')) ProductCategory::add_extension('HasPromotionalPricing');

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
}