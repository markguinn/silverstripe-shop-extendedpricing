<div class="pricetag">
	<% if $HasPromotion %>
		<del class="original"><strong class="price">$OriginalPrice.Nice</strong></del>
		<span class="discounted">
			<strong class="price">$Price.Nice</strong>
			<span class="savings">(You Save: $PromoSavings.Nice)</span>
		</span>
	<% else %>
		<span class="original"><strong class="price">$Price.Nice</strong></span>
	<% end_if %>
</div>