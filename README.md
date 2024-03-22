# Taggrs GTM DataLayer for Magento 2
This extension collect eccommerce data from Magento 2 and pushes it to the Google Tag Manager DataLayer.

## Configuration
The configuration for this extension is located at 

**Stores > Configuration > TAGGRS > Data Layer**

### Google Tag Manager Settings
This tab is to configure GTM. 
1. **Google Tag Manager Code** enter your GTM- tracking code.
2. **Measurements API secret** enter your Google Measurements API secret.
3. **Subdomain for Enhanced Tracking Script** enter your subdomain for enhanced tracking.

### Events
Per-event settings to enable or disable measuring

## Events

The events that are currently captured are:

- view_item_list
- view_item
- add_to_cart
- remove_from_cart
- view_cart
- select_promotion
- begin_checkout
- purchase

### view_item_list
This event is triggered on the Catalog Category pages and the Catalog Search Result page. In both cases the DataLayer is rendered in the backend.

Because of the Full Page Cache (FPC) it is necessary to retrieve the user_data part via AJAX before pushing it to the DataLayer global. 

The block for rendering the Catalog Category Datalayer is [Taggrs\DataLayer\Block\Event\CategoryViewItemList](Block/Event/CategoryViewItemList.php). 

The block for rendering the Catalog Search Results DataLayer is [Taggrs\DataLayer\Block\Event\SearchResultsViewItemList](Block/Event/SearchResultsViewItemList.php).

### view_item
This event is triggered on the Catalog Product Page. This DataLayer is rendered in the backend. 

Because of the FPC it is necessary to retrieve the user_data part via AJAX before pushing it to the DataLayer global. 

The block for rendering the DataLayer is [Taggrs\DataLayer\Block\Event\ViewItem](Block/Event/ViewItem.php).

### add_to_cart
This event is triggered when a product is added to the cart. Because adding a product to the cart is an AJAX event in Magento 2, it is necessary to render the DataLayer via an AJAX event.

The DataLayer is rendered in the following frontend controller: [Taggrs\DataLayer\Controller\AddToCart\Index](Controller/AddToCart/Index.php).

### remove_from_cart
This event is triggered when a product is removed from the cart. It can happen from the Minicart, or from the Checkout Cart page.

#### Minicart
Because removing a product from the cart is an AJAX event in Magento 2, it is necessary to render the DataLayer via AJAX.

Because it's impossible to retrieve information about a product that is removed from the cart, it is necessary to have the information about the cart readily available in the frontend.

This data is rendered in [Taggrs\DataLayer\Controller\GetQuoteData\Index](Controller/GetQuoteData/Index.php).

The DataLayer is built and pushed to the DataLayer global in [Taggrs/DataLayer/view/frontend/web/js/checkout-sidebar-mixin.js](view/frontend/web/js/checkout-sidebar-mixin.js).

#### Checkout Cart
Because there's a redirect after removing a product from the cart it's needed to render the DataLayer in the backend and store it in the session to show it in the frontend. 

The DataLayer is built in [Taggrs\DataLayer\Plugin\RemoveFromCart](Plugin/RemoveFromCart.php). Which is registered as a plugin for `Magento\Checkout\Controller\Cart\Delete::beforeExecute()`.

It's then stored in the session and after the redirect it's retrieved from the session and rendered by [Taggrs\DataLayer\Block\SessionDataLayer](Block/SessionDataLayer.php). 

### view_cart
This event is triggered on the Checkout Cart page. Because the checkout pages aren't cached in the FPC it's not necessary to load the user data via AJAX.

The DataLayer is rendered in [Taggrs\DataLayer\Block\Event\ViewCart](Block/Event/ViewCart.php).

### select_promotion
This event is triggered when a customer applies a discount code. This can be on the Checkout Cart page or on the Checkout Payment page.

#### Checkout Cart page
Because there's a redirect after applying a coupon, it's needed to render the DataLayer in the backend and store it in the session to show it in the frontend, just like the `remove_from_cart` event on the Checkout Cart page.

The DataLayer is built in [Taggrs\DataLayer\Plugin\SelectPromotion](Plugin/SelectPromotion.php).

#### Checkout Payment page
Because applying a coupon on the Checkout Payment page is an AJAX event, the DataLayer needs to be rendered via AJAX too.

The DataLayer is built in [Taggrs\DataLayer\Controller\SelectPromotion\Index](Controller/SelectPromotion/Index.php).

### begin_checkout
This event is triggered on the Checkout Index page. This DataLayer is rendered in the backend in [Taggrs\DataLayer\Block\Event\BeginCheckout](Block/Event/BeginCheckout.php).

### purchase
This event is triggered on the Checkout Thank You page. Because in some cases customers will not see the Checkout Thank You page, it's also needed to push the DataLayer from the backend using the Google Measurements API.

#### Checkout Thank You page
This DataLayer is rendered in the backend using [Taggrs\DataLayer\Block\Event\Purchase](Block/Event/Purchase.php).
