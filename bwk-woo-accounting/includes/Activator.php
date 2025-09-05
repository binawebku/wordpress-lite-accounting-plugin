<?php
namespace BWK\Accounting;

class Activator {
    public static function activate() : void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        $sql = [];
        $sql[] = "CREATE TABLE {$wpdb->prefix}bwk_acc_accounts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            name varchar(190) NOT NULL,
            type varchar(20) NOT NULL,
            parent_id mediumint(9) DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code)
        ) $charset;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}bwk_acc_journals (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            memo text,
            source varchar(20),
            ref varchar(100),
            created_by bigint(20) unsigned DEFAULT 0,
            PRIMARY KEY  (id),
            KEY date (date)
        ) $charset;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}bwk_acc_journal_lines (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            journal_id bigint(20) unsigned NOT NULL,
            account_id mediumint(9) NOT NULL,
            debit decimal(18,2) DEFAULT 0,
            credit decimal(18,2) DEFAULT 0,
            currency varchar(10) DEFAULT '',
            meta longtext,
            PRIMARY KEY  (id),
            KEY journal_id (journal_id),
            KEY account_id (account_id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}bwk_acc_expenses (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            vendor varchar(190) DEFAULT '',
            category_account_id mediumint(9) NOT NULL,
            amount decimal(18,2) NOT NULL DEFAULT 0,
            tax_amount decimal(18,2) NOT NULL DEFAULT 0,
            payment_method varchar(50) DEFAULT '',
            attachment_id bigint(20) unsigned DEFAULT 0,
            notes text,
            status varchar(20) DEFAULT 'draft',
            PRIMARY KEY  (id),
            KEY date (date),
            KEY category_account_id (category_account_id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}bwk_acc_recon (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            account_id mediumint(9) NOT NULL,
            statement_start date NOT NULL,
            statement_end date NOT NULL,
            opening decimal(18,2) NOT NULL DEFAULT 0,
            closing decimal(18,2) NOT NULL DEFAULT 0,
            status varchar(20) DEFAULT 'open',
            PRIMARY KEY  (id),
            KEY account_id (account_id)
        ) $charset;";

        foreach ( $sql as $query ) {
            dbDelta( $query );
        }

        // Seed chart of accounts if empty.
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}bwk_acc_accounts" );
        if ( ! $count ) {
            $accounts = [
                ['1000','Cash','asset',0],
                ['1010','Bank','asset',0],
                ['1100','Accounts Receivable','asset',0],
                ['2000','Tax Payable','liability',0],
                ['2100','Tax Input','asset',0],
                ['4000','Sales Income','income',0],
                ['4010','Shipping Income','income',0],
                ['5000','COGS','expense',0],
                ['6000','Expenses','expense',0],
                ['6100','Zakat Expense','expense',0],
            ];
            foreach ( $accounts as $acc ) {
                $wpdb->insert( $wpdb->prefix . 'bwk_acc_accounts', [
                    'code'      => $acc[0],
                    'name'      => $acc[1],
                    'type'      => $acc[2],
                    'parent_id' => $acc[3],
                    'active'    => 1,
                ], [ '%s', '%s', '%s', '%d', '%d' ] );
            }
        }

        // Capabilities.
        $caps = [ 'bwk_acc_view', 'bwk_acc_edit', 'bwk_acc_manage', 'bwk_acc_export' ];
        $admin = get_role( 'administrator' );
        foreach ( $caps as $cap ) {
            $admin->add_cap( $cap );
        }
        $manager = get_role( 'shop_manager' );
        if ( $manager ) {
            $manager->add_cap( 'bwk_acc_view' );
            $manager->add_cap( 'bwk_acc_edit' );
            $manager->add_cap( 'bwk_acc_export' );
        }

        if ( ! get_option( 'bwk_acc_settings' ) ) {
            add_option( 'bwk_acc_settings', [
                'base_currency' => get_option( 'woocommerce_currency', 'USD' ),
            ] );
        }
    }

    public static function deactivate() : void {
        // placeholder for future cleanup
    }
}
