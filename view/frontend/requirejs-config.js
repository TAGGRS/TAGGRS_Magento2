var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/sidebar': {
                'Taggrs_DataLayer/js/checkout-sidebar-mixin': true
            },
            'Magento_Checkout/js/model/shipping-rate-processor/new-address': {
                'Taggrs_DataLayer/js/new-address-mixin': true
            },
            'Magento_Checkout/js/action/set-payment-information-extended': {
                'Taggrs_DataLayer/js/set-payment-information-mixin': true

            }
        }
    }
};
