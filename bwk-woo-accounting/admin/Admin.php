<?php
namespace BWK\Accounting\Admin;

class Admin {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
        add_action( 'admin_bar_menu', [ $this, 'toolbar' ], 100 );
        add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function register_menus() : void {
        add_menu_page( __( 'BWK Accounting', 'bwk-woo-accounting' ), __( 'BWK Accounting', 'bwk-woo-accounting' ), 'bwk_acc_view', 'bwk-acc', [ $this, 'dashboard_page' ], 'dashicons-calculator', 56 );
        add_submenu_page( 'bwk-acc', __( 'Dashboard', 'bwk-woo-accounting' ), __( 'Dashboard', 'bwk-woo-accounting' ), 'bwk_acc_view', 'bwk-acc', [ $this, 'dashboard_page' ] );
        add_submenu_page( 'bwk-acc', __( 'Sales & Invoices', 'bwk-woo-accounting' ), __( 'Sales & Invoices', 'bwk-woo-accounting' ), 'bwk_acc_edit', 'bwk-acc-sales', [ $this, 'sales_page' ] );
        add_submenu_page( 'bwk-acc', __( 'Expenses & Bills', 'bwk-woo-accounting' ), __( 'Expenses & Bills', 'bwk-woo-accounting' ), 'bwk_acc_edit', 'bwk-acc-expenses', [ $this, 'expenses_page' ] );
        add_submenu_page( 'bwk-acc', __( 'Chart of Accounts', 'bwk-woo-accounting' ), __( 'Chart of Accounts', 'bwk-woo-accounting' ), 'bwk_acc_manage', 'bwk-acc-accounts', [ $this, 'accounts_page' ] );
        add_submenu_page( 'bwk-acc', __( 'Journal Entries', 'bwk-woo-accounting' ), __( 'Journal Entries', 'bwk-woo-accounting' ), 'bwk_acc_manage', 'bwk-acc-journals', [ $this, 'journals_page' ] );
        add_submenu_page( 'bwk-acc', __( 'Ledgers', 'bwk-woo-accounting' ), __( 'Ledgers', 'bwk-woo-accounting' ), 'bwk_acc_view', 'bwk-acc-ledgers', [ $this, 'ledgers_page' ] );
        add_submenu_page( 'bwk-acc', __( 'Reports', 'bwk-woo-accounting' ), __( 'Reports', 'bwk-woo-accounting' ), 'bwk_acc_view', 'bwk-acc-reports', [ $this, 'reports_page' ] );
        add_submenu_page( 'bwk-acc', __( 'Taxes & Zakat', 'bwk-woo-accounting' ), __( 'Taxes & Zakat', 'bwk-woo-accounting' ), 'bwk_acc_manage', 'bwk-acc-tax', [ $this, 'tax_page' ] );
        add_submenu_page( 'bwk-acc', __( 'Reconciliation', 'bwk-woo-accounting' ), __( 'Reconciliation', 'bwk-woo-accounting' ), 'bwk_acc_manage', 'bwk-acc-recon', [ $this, 'recon_page' ] );
        add_submenu_page( 'bwk-acc', __( 'Import/Export', 'bwk-woo-accounting' ), __( 'Import/Export', 'bwk-woo-accounting' ), 'bwk_acc_export', 'bwk-acc-import', [ $this, 'import_page' ] );
        add_submenu_page( 'bwk-acc', __( 'Settings', 'bwk-woo-accounting' ), __( 'Settings', 'bwk-woo-accounting' ), 'bwk_acc_manage', 'bwk-acc-settings', [ $this, 'settings_page' ] );
    }

    public function assets( $hook ) : void {
        if ( false !== strpos( $hook, 'bwk-acc' ) ) {
            wp_enqueue_style( 'bwk-acc-admin', BWK_ACC_URL . 'assets/css/admin.css', [], BWK_ACC_VERSION );
            wp_enqueue_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.4.0', true );
            wp_enqueue_script( 'bwk-acc-admin', BWK_ACC_URL . 'assets/js/admin.js', [ 'jquery', 'chartjs' ], BWK_ACC_VERSION, true );
        }
    }

    public function toolbar( \WP_Admin_Bar $bar ) : void {
        if ( ! current_user_can( 'bwk_acc_view' ) ) {
            return;
        }
        $bar->add_node( [
            'id'    => 'bwk-acc',
            'title' => __( 'Accounting', 'bwk-woo-accounting' ),
            'href'  => admin_url( 'admin.php?page=bwk-acc' ),
        ] );
        $bar->add_node( [
            'id'     => 'bwk-acc-reports',
            'parent' => 'bwk-acc',
            'title'  => __( 'Reports', 'bwk-woo-accounting' ),
            'href'   => admin_url( 'admin.php?page=bwk-acc-reports' ),
        ] );
        if ( current_user_can( 'bwk_acc_manage' ) ) {
            $bar->add_node( [
                'id'     => 'bwk-acc-settings',
                'parent' => 'bwk-acc',
                'title'  => __( 'Settings', 'bwk-woo-accounting' ),
                'href'   => admin_url( 'admin.php?page=bwk-acc-settings' ),
            ] );
        }
    }

    public function register_settings() : void {
        register_setting( 'bwk_acc_settings', 'bwk_acc_settings' );
        add_settings_section( 'bwk_acc_general', __( 'General', 'bwk-woo-accounting' ), '__return_false', 'bwk_acc_settings' );
        add_settings_field( 'bwk_acc_base_currency', __( 'Base Currency', 'bwk-woo-accounting' ), [ $this, 'field_base_currency' ], 'bwk_acc_settings', 'bwk_acc_general' );
    }

    public function field_base_currency() : void {
        $opts = get_option( 'bwk_acc_settings', [] );
        $val  = $opts['base_currency'] ?? 'USD';
        echo '<input type="text" name="bwk_acc_settings[base_currency]" value="' . esc_attr( $val ) . '" class="regular-text" />';
    }

    private function render( string $view, string $cap = 'bwk_acc_view' ) : void {
        if ( ! current_user_can( $cap ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'bwk-woo-accounting' ) );
        }
        include BWK_ACC_PATH . 'views/' . $view . '.php';
    }

    public function dashboard_page() : void { $this->render( 'dashboard', 'bwk_acc_view' ); }
    public function sales_page() : void { $this->render( 'sales', 'bwk_acc_edit' ); }
    public function expenses_page() : void { $this->render( 'expenses', 'bwk_acc_edit' ); }
    public function accounts_page() : void { $this->render( 'accounts', 'bwk_acc_manage' ); }
    public function journals_page() : void { $this->render( 'journals', 'bwk_acc_manage' ); }
    public function ledgers_page() : void { $this->render( 'ledgers', 'bwk_acc_view' ); }
    public function reports_page() : void { $this->render( 'reports', 'bwk_acc_view' ); }
    public function tax_page() : void { $this->render( 'tax', 'bwk_acc_manage' ); }
    public function recon_page() : void { $this->render( 'recon', 'bwk_acc_manage' ); }
    public function import_page() : void { $this->render( 'import', 'bwk_acc_export' ); }
    public function settings_page() : void { $this->render( 'settings', 'bwk_acc_manage' ); }
}
