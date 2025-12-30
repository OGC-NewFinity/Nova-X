<?php
/**
 * Auth Modal Partial
 * 
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="nova-x-auth-modal" class="nova-x-modal hidden">
    <div class="nova-x-modal-backdrop"></div>
    <div class="nova-x-modal-container">
        <button class="nova-x-modal-close" aria-label="Close">&times;</button>
        <div class="nova-x-modal-body">
            <div class="nova-x-auth-section">
                <?php include NOVA_X_PATH . 'admin/partials/nova-x-auth-login.php'; ?>
                <?php include NOVA_X_PATH . 'admin/partials/nova-x-auth-register.php'; ?>
            </div>
        </div>
    </div>
</div>

