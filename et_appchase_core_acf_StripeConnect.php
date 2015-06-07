<?php
/*
Plugin Name: -ET-AcfStripeConnectGravityPlusBridge
Description: Lets you add a Stripe Connect button using Advanced Custom Fields. Requires Gravity + Stripe + Connect (https://gravityplus.pro).  Forked from ACF Enhanced Message Field, which was totally different in scope, but super helpful to get started..
Version: 1.0
Author: JJ Rohrer
Author URI:
Depends: Advanced Custom Fields Pro, Gravity Forms + Stripe Connect

Instructions:
    Make a page with slug: stripe_connect_success
    Make a page with slug: stripe_connect_failure

    In your config file (be sure this picking test vs. prod correctly)
    define('STRIPE_SECRET_KEY', 'sk_xxx');
    define('STRIPE_PUBLISHABLE_KEY', 'pk_yyy');
    define('STRIPE_CONNECT_CLIENT_ID', 'ca_zzz');

    On stripe.com, Account->Settings->Connect

NiceToDo:
    Make the redirects by admin-only
    Save the current form as part of the going to stripe

*/

// Include field type for ACF5
function include_field_type_etStripeConnect( $version ) {

	require_once('et_appchase_core_acf_StripeConnect.inc.php');
}

add_action('acf/include_field_types', 'include_field_type_etStripeConnect');

