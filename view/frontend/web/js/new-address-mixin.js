define([

    'mage/utils/wrapper'

], function (wrapper) {

    'use strict';


    return function (newAddress) {

        newAddress.getRates = wrapper.wrapSuper(newAddress.getRates, function (address) {

            this._super(address);

            if (address.postcode !== null) {
                taggrsAjaxEvent('addshippinginfo', () => {}, 1);
            }


        });


        return newAddress;

    };

});
