/**
 * Javascript for editing promo pricing.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 08.20.2013
 * @package shop_extendedpricing
 * @subpackage javascript
 */
(function ($, window, document, undefined) {
	'use strict';
	function updatePromoFields(){
		$('#PromoPercent').toggle($('#Form_EditForm_PromoType_Percent')[0].checked);
		$('#PromoAmount').toggle($('#Form_EditForm_PromoType_Amount')[0].checked);
	}

	$(function(){
		updatePromoFields();
		$('#PromoType input').click(updatePromoFields);
	});
}(jQuery, this, this.document));