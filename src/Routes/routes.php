<?php

if ( config('admineshop.routes.enabled.products') === true ) {
    Store::routesForProducts();
}

if ( config('admineshop.routes.enabled.discounts') === true ) {
    Store::routesForDiscounts();
}

if ( config('admineshop.routes.enabled.cart') === true ) {
    Store::routesForCart();
}

if ( config('admineshop.routes.enabled.cart_submit') === true ) {
    Store::routesForCartSubmit();
}

if ( config('admineshop.routes.enabled.cart_payments') === true ) {
    Store::routesForPayments();
}

?>