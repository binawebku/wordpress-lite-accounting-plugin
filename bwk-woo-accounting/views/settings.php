<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap">
<h1><?php echo esc_html__( 'Settings', 'bwk-woo-accounting' ); ?></h1>
<form method="post" action="options.php">
<?php
settings_fields( 'bwk_acc_settings' );
 do_settings_sections( 'bwk_acc_settings' );
 submit_button();
?>
</form>
</div>
