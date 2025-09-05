<?php
namespace BWK\Accounting\Woo;

class Order_Sync {
    private $logger;

    public function __construct() {
        $this->logger = function_exists( 'wc_get_logger' ) ? wc_get_logger() : null;
        add_action( 'woocommerce_order_status_completed', [ $this, 'create_journal' ] );
        add_action( 'woocommerce_order_refunded', [ $this, 'refund_journal' ], 10, 2 );
    }

    private function log( string $message ) : void {
        if ( $this->logger ) {
            $this->logger->info( $message, [ 'source' => 'bwk-acc' ] );
        }
    }

    private function get_account_id( string $code ) : int {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}bwk_acc_accounts WHERE code=%s", $code ) );
    }

    public function create_journal( int $order_id ) : void {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return;
        }
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }
        $total    = (float) $order->get_total();
        $tax      = (float) $order->get_total_tax();
        $shipping = (float) $order->get_shipping_total();
        $currency = $order->get_currency();

        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'bwk_acc_journals', [
            'date'       => gmdate( 'Y-m-d', $order->get_date_paid() ? $order->get_date_paid()->getTimestamp() : time() ),
            'memo'       => 'Order ' . $order->get_order_number(),
            'source'     => 'order',
            'ref'        => $order->get_id(),
            'created_by' => get_current_user_id(),
        ], [ '%s', '%s', '%s', '%d', '%d' ] );
        $jid = $wpdb->insert_id;

        $cash_acc = $this->get_account_id( '1000' );
        $sales_acc = $this->get_account_id( '4000' );
        $tax_acc = $this->get_account_id( '2000' );
        $ship_acc = $this->get_account_id( '4010' );

        $wpdb->insert( $wpdb->prefix . 'bwk_acc_journal_lines', [
            'journal_id' => $jid,
            'account_id' => $cash_acc,
            'debit'      => $total,
            'credit'     => 0,
            'currency'   => $currency,
            'meta'       => wp_json_encode( [ 'order' => $order_id ] ),
        ], [ '%d', '%d', '%f', '%f', '%s', '%s' ] );

        $wpdb->insert( $wpdb->prefix . 'bwk_acc_journal_lines', [
            'journal_id' => $jid,
            'account_id' => $sales_acc,
            'debit'      => 0,
            'credit'     => $total - $tax - $shipping,
            'currency'   => $currency,
            'meta'       => wp_json_encode( [ 'order' => $order_id ] ),
        ], [ '%d', '%d', '%f', '%f', '%s', '%s' ] );

        if ( $tax > 0 ) {
            $wpdb->insert( $wpdb->prefix . 'bwk_acc_journal_lines', [
                'journal_id' => $jid,
                'account_id' => $tax_acc,
                'debit'      => 0,
                'credit'     => $tax,
                'currency'   => $currency,
                'meta'       => wp_json_encode( [ 'order' => $order_id ] ),
            ], [ '%d', '%d', '%f', '%f', '%s', '%s' ] );
        }

        if ( $shipping > 0 ) {
            $wpdb->insert( $wpdb->prefix . 'bwk_acc_journal_lines', [
                'journal_id' => $jid,
                'account_id' => $ship_acc,
                'debit'      => 0,
                'credit'     => $shipping,
                'currency'   => $currency,
                'meta'       => wp_json_encode( [ 'order' => $order_id ] ),
            ], [ '%d', '%d', '%f', '%f', '%s', '%s' ] );
        }

        $this->log( 'Created journal ' . $jid . ' for order ' . $order_id );
    }

    public function refund_journal( int $order_id, int $refund_id ) : void {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return;
        }
        $refund = wc_get_order( $refund_id );
        if ( ! $refund ) {
            return;
        }
        $total    = (float) $refund->get_total();
        $tax      = (float) $refund->get_total_tax();
        $shipping = (float) $refund->get_shipping_total();
        $currency = $refund->get_currency();

        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'bwk_acc_journals', [
            'date'       => gmdate( 'Y-m-d', time() ),
            'memo'       => 'Refund ' . $order_id,
            'source'     => 'refund',
            'ref'        => $refund_id,
            'created_by' => get_current_user_id(),
        ], [ '%s', '%s', '%s', '%d', '%d' ] );
        $jid = $wpdb->insert_id;

        $cash_acc = $this->get_account_id( '1000' );
        $sales_acc = $this->get_account_id( '4000' );
        $tax_acc = $this->get_account_id( '2000' );
        $ship_acc = $this->get_account_id( '4010' );

        $wpdb->insert( $wpdb->prefix . 'bwk_acc_journal_lines', [
            'journal_id' => $jid,
            'account_id' => $cash_acc,
            'debit'      => 0,
            'credit'     => abs( $total ),
            'currency'   => $currency,
            'meta'       => wp_json_encode( [ 'order' => $order_id ] ),
        ], [ '%d', '%d', '%f', '%f', '%s', '%s' ] );

        $wpdb->insert( $wpdb->prefix . 'bwk_acc_journal_lines', [
            'journal_id' => $jid,
            'account_id' => $sales_acc,
            'debit'      => abs( $total ) - abs( $tax ) - abs( $shipping ),
            'credit'     => 0,
            'currency'   => $currency,
            'meta'       => wp_json_encode( [ 'order' => $order_id ] ),
        ], [ '%d', '%d', '%f', '%f', '%s', '%s' ] );

        if ( $tax > 0 ) {
            $wpdb->insert( $wpdb->prefix . 'bwk_acc_journal_lines', [
                'journal_id' => $jid,
                'account_id' => $tax_acc,
                'debit'      => abs( $tax ),
                'credit'     => 0,
                'currency'   => $currency,
                'meta'       => wp_json_encode( [ 'order' => $order_id ] ),
            ], [ '%d', '%d', '%f', '%f', '%s', '%s' ] );
        }

        if ( $shipping > 0 ) {
            $wpdb->insert( $wpdb->prefix . 'bwk_acc_journal_lines', [
                'journal_id' => $jid,
                'account_id' => $ship_acc,
                'debit'      => abs( $shipping ),
                'credit'     => 0,
                'currency'   => $currency,
                'meta'       => wp_json_encode( [ 'order' => $order_id ] ),
            ], [ '%d', '%d', '%f', '%f', '%s', '%s' ] );
        }

        $this->log( 'Created refund journal ' . $jid . ' for order ' . $order_id );
    }
}
