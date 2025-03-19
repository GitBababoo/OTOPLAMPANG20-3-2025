<?php if (isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo sanitize($error_message); ?></div>
<?php endif; ?>

<?php if (empty($cart_items)): ?>
    <p class="alert alert-info">ไม่มีสินค้าในตะกร้า</p>
<?php endif; ?>