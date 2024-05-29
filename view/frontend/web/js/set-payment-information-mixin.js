define([
    'jquery',
    'mage/utils/wrapper'
], function ($, wrapper) {
    'use strict';

    return function (setPaymentInformationAction) {

        /** Override default place order action */
        return wrapper.wrap(setPaymentInformationAction, function (originalAction, messageContainer, paymentData, skipBilling) {
            //Your extras logic here
            //Add extended functionality here
            taggrsAjaxEvent('addpaymentinfo');

            return originalAction(messageContainer, paymentData, skipBilling);
        });
    };
});
