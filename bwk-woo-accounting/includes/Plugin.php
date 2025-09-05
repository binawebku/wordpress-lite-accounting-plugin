<?php
namespace BWK\Accounting;

use BWK\Accounting\Admin\Admin;
use BWK\Accounting\Woo\Order_Sync;

class Plugin {
    public function __construct() {
        add_action( 'init', [ $this, 'init' ] );

        if ( is_admin() ) {
            new Admin();
        }

        if ( class_exists( 'WooCommerce' ) ) {
            new Order_Sync();
        }
    }

    public function init() : void {
        load_plugin_textdomain( 'bwk-woo-accounting', false, dirname( BWK_ACC_BASENAME ) . '/languages' );
    }
}
