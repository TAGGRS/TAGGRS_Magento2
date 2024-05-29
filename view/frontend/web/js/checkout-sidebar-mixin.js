define([
    'jquery',
    'uiComponent'
], function ($, Component) {
    'use strict';
    return function (target) {
        return $.widget('mage.sidebar', $.mage.sidebar, {
            _removeItem: function (elem) {

                if (window.taggersEventsConfig.remove_from_cart !== true) {
                    return this._super(elem);
                }

                const quoteItemId = $(elem).data('cart-item');
                console.log(window.taggrsQuoteData);
                if (window.taggrsQuoteData.hasOwnProperty(quoteItemId)) {
                    const quoteItem = window.taggrsQuoteData[quoteItemId];
                    const dataLayer = {event: 'remove_from_cart'};
                    dataLayer.ecommerce = {
                        currency: window.taggrsCurrency,
                        value: quoteItem.price,
                        items: [quoteItem]
                    };
                    taggrsPush(dataLayer, true);
                }

                return this._super(elem);
            },

        });
    }
});
