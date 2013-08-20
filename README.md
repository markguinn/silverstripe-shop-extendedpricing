Extended Pricing Options for Silverstripe Shop
==============================================

Provides several options of extended pricing for Buyables.

GROUP PRICING
-------------
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


PROMOTIONAL PRICING
-------------------
Allows you to set promotional discounts on products, variations, and
categories.

- can be applied to categories as well
- can be limited by start and/or end date
- can be absolute price or percentage discount
- can specify whether to display as a sale (i.e. show old price crossed out)

To use, you must add the 'HasPromotionalPricing' extension at
whatever levels you want like so:

```
Product:
  extensions:
    - HasPromotionalPricing
ProductVariation:
  extensions:
    - HasPromotionalPricing
ProductCategory:
  extensions:
    - HasPromotionalPricing
```

Discounts are then applied on the Pricing tab in the CMS. By default,
discounts do not compound if they are applied at multiple levels (i.e.
a $5 discount on the category and a $4 discount on the product would
only yield $4 discount), but if you wish to change that you can
use the following config setting:

```
HasPromotionalPricing:
  compound_discounts: true
```

See the examples in templates/Includes for display. You'll probably
need to modify your template.


TODO
----
- Add display templates for cart and checkout
- Debug flaky javascript in CMS


DEVELOPERS:
-----------
* Mark Guinn - mark@adaircreative.com

Pull requests always welcome. Follow Silverstripe coding standards.


LICENSE (MIT):
--------------
Copyright (c) 2013 Mark Guinn

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
Software, and to permit persons to whom the Software is furnished to do so, subject
to the following conditions:

The above copyright notice and this permission notice shall be included in all copies
or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE
FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
DEALINGS IN THE SOFTWARE.

