Extended Pricing Options for Silverstripe Shop
==============================================

[![Build Status](https://secure.travis-ci.org/markguinn/silverstripe-shop-extendedpricing.png)](http://travis-ci.org/markguinn/silverstripe-shop-extendedpricing)

Provides several options of extended pricing for Buyables.


GROUP PRICING
-------------
Allows you to define one or more additional levels of pricing
that take effect based on the group of the logged in user. The
primary use case is wholesale or corporate pricing.

See docs/en/GroupPricing.md for more infomation.


PROMOTIONAL PRICING
-------------------
Allows you to set promotional discounts on products, variations, and
categories.

- can be applied to categories as well
- can be limited by start and/or end date
- can be absolute price or percentage discount
- can specify whether to display as a sale (i.e. show old price crossed out)

See docs/en/PromotionalPricing.md for more information.


TIER PRICING
------------
Allows you to see pricing tiers based on how many items a customer purchases.
For example, a shirt could cost $10 normally, but only $8 if one buys 20 or
more.

- Can apply a fixed price or percentage
- Can have global tiers attached to the SiteConfig
- Can have tiers on a parent product which apply to variations

See docs/en/TierPricing.md for more information.


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

