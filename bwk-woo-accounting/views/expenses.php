<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;
$expense_accounts = $wpdb->get_results( "SELECT id, code, name FROM {$wpdb->prefix}bwk_acc_accounts WHERE type='expense' ORDER BY code" );

if ( isset( $_POST['bwk_acc_expense_nonce'] ) && wp_verify_nonce( $_POST['bwk_acc_expense_nonce'], 'save_expense' ) && current_user_can( 'bwk_acc_edit' ) ) {
    $date   = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );
    $vendor = sanitize_text_field( wp_unslash( $_POST['vendor'] ?? '' ) );
    $cat    = intval( $_POST['category'] ?? 0 );
    $amount = floatval( $_POST['amount'] ?? 0 );
    $tax    = floatval( $_POST['tax'] ?? 0 );
    $pm     = sanitize_text_field( wp_unslash( $_POST['payment_method'] ?? '' ) );
    $notes  = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );

    $wpdb->insert( $wpdb->prefix . 'bwk_acc_expenses', [
        'date' => $date,
        'vendor' => $vendor,
        'category_account_id' => $cat,
        'amount' => $amount,
        'tax_amount' => $tax,
        'payment_method' => $pm,
        'notes' => $notes,
        'status' => 'posted',
    ], [ '%s', '%s', '%d', '%f', '%f', '%s', '%s', '%s' ] );
    $expense_id = $wpdb->insert_id;

    $opts = get_option( 'bwk_acc_settings', [] );
    $currency = $opts['base_currency'] ?? 'USD';
    $user_id = get_current_user_id();
    $wpdb->insert( $wpdb->prefix . 'bwk_acc_journals', [
        'date' => $date,
        'memo' => $vendor,
        'source' => 'expense',
        'ref' => $expense_id,
        'created_by' => $user_id,
    ], [ '%s', '%s', '%s', '%d', '%d' ] );
    $jid = $wpdb->insert_id;

    $wpdb->insert( $wpdb->prefix . 'bwk_acc_journal_lines', [
        'journal_id' => $jid,
        'account_id' => $cat,
        'debit' => $amount,
        'credit' => 0,
        'currency' => $currency,
        'meta' => wp_json_encode( [] ),
    ], [ '%d', '%d', '%f', '%f', '%s', '%s' ] );

    if ( $tax > 0 ) {
        $tax_acc = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}bwk_acc_accounts WHERE code=%s", '2100' ) );
        $wpdb->insert( $wpdb->prefix . 'bwk_acc_journal_lines', [
            'journal_id' => $jid,
            'account_id' => $tax_acc,
            'debit' => $tax,
            'credit' => 0,
            'currency' => $currency,
            'meta' => wp_json_encode( [] ),
        ], [ '%d', '%d', '%f', '%f', '%s', '%s' ] );
    }

    $cash_acc = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}bwk_acc_accounts WHERE code=%s", '1000' ) );
    $wpdb->insert( $wpdb->prefix . 'bwk_acc_journal_lines', [
        'journal_id' => $jid,
        'account_id' => $cash_acc,
        'debit' => 0,
        'credit' => $amount + $tax,
        'currency' => $currency,
        'meta' => wp_json_encode( [ 'payment_method' => $pm ] ),
    ], [ '%d', '%d', '%f', '%f', '%s', '%s' ] );

    echo '<div class="updated"><p>' . esc_html__( 'Expense saved.', 'bwk-woo-accounting' ) . '</p></div>';
}
?>
<div class="wrap">
<h1><?php echo esc_html__( 'Expenses & Bills', 'bwk-woo-accounting' ); ?></h1>
<form method="post">
<?php wp_nonce_field( 'save_expense', 'bwk_acc_expense_nonce' ); ?>
<table class="form-table">
<tr>
<th scope="row"><label for="bwk_vendor"><?php esc_html_e( 'Vendor', 'bwk-woo-accounting' ); ?></label></th>
<td><input name="vendor" id="bwk_vendor" type="text" class="regular-text" required></td>
</tr>
<tr>
<th scope="row"><label for="bwk_date"><?php esc_html_e( 'Date', 'bwk-woo-accounting' ); ?></label></th>
<td><input name="date" id="bwk_date" type="date" value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>" required></td>
</tr>
<tr>
<th scope="row"><label for="bwk_category"><?php esc_html_e( 'Category', 'bwk-woo-accounting' ); ?></label></th>
<td><select name="category" id="bwk_category"><?php foreach ( $expense_accounts as $a ) { printf( '<option value="%d">%s - %s</option>', $a->id, esc_html( $a->code ), esc_html( $a->name ) ); } ?></select></td>
</tr>
<tr>
<th scope="row"><label for="bwk_amount"><?php esc_html_e( 'Amount', 'bwk-woo-accounting' ); ?></label></th>
<td><input name="amount" id="bwk_amount" type="number" step="0.01" required></td>
</tr>
<tr>
<th scope="row"><label for="bwk_tax"><?php esc_html_e( 'Tax Amount', 'bwk-woo-accounting' ); ?></label></th>
<td><input name="tax" id="bwk_tax" type="number" step="0.01" value="0"></td>
</tr>
<tr>
<th scope="row"><label for="bwk_payment"><?php esc_html_e( 'Payment Method', 'bwk-woo-accounting' ); ?></label></th>
<td><input name="payment_method" id="bwk_payment" type="text"></td>
</tr>
<tr>
<th scope="row"><label for="bwk_notes"><?php esc_html_e( 'Notes', 'bwk-woo-accounting' ); ?></label></th>
<td><textarea name="notes" id="bwk_notes" class="large-text"></textarea></td>
</tr>
</table>
<?php submit_button( __( 'Save Expense', 'bwk-woo-accounting' ) ); ?>
</form>
</div>
