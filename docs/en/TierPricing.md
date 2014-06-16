TIER PRICING
============

Allows you to see pricing tiers based on how many items a customer purchases.
For example, a shirt could cost $10 normally, but only $8 if one buys 20 or
more.

- Can apply a fixed price or percentage
- Can have global tiers attached to the SiteConfig
- Can have tiers on a parent product which apply to variations

Usage:

```
Product:
  extensions:
    - HasPriceTiers
Product_OrderItem:
  extensions:
    - HasPriceTiers_OrderItem
ProductVariation_OrderItem:
  extensions:
    - HasPriceTiers_OrderItem
```

If you want to have the option of defining global price tiers, add:

```
SiteConfig:
  extensions:
    - HasPriceTiers
```
