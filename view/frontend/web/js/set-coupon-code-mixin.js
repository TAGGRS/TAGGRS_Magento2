// define(function () {
//     'use strict';
//
//     var mixin = {
//
//
//         action: function (couponCode, isApplied) {
//             console.log("COUPON CODE APPLIED: " + couponCode);
//             return this._super();
//
//         }
//
//     };
//
//
//     return function (target) { // target == Result that Magento_Ui/.../columns returns.
//
//         return target.extend(mixin); // new result that all other modules receive
//
//     };
//
// });
define([

    'mage/utils/wrapper'

], function (wrapper) {

    'use strict';


    return function (action) {

        return wrapper.wrap(action, function (originalAction, couponCode, isApplied) {

            originalAction(couponCode, isApplied);

            console.log("COUPON CODE APPLIED: " + couponCode);
            // add extended functionality here

        });

    };

});
