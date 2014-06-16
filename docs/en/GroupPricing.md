GROUP PRICING
=============

Allows you to define one or more additional levels of pricing
that take effect based on the group of the logged in user. The
primary use case is wholesale or corporate pricing.

Price levels are defined via yml config like so:

```
Product:
  extensions:
    - HasGroupPricing
HasGroupPricing:
  price_levels:
    wholesale: WholesalePrice
    supercheap: SuperCheapPrice
  field_labels:
    WholesalePrice: 'Price for wholesale customers'
    SuperCheapPrice: 'Another level of price'
```

This will create additional fields in the CMS and on the Product
record. Product->sellingPrice() will then return the lowest
applicable price for the current member.