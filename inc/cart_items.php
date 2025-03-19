<ul class="list-group mb-3">
    <?php foreach ($cart_items as $item): ?>
        <li class="list-group-item d-flex justify-content-between lh-condensed">
            <div class="d-flex align-items-center">
                <img src="uploads/สินค้า/<?php echo sanitize($item['product_image']); ?>" alt="<?php echo sanitize($item['product_name']); ?>" class="cart-item-image mr-3">
                <div>
                    <h6 class="my-0"><?php echo sanitize($item['product_name']); ?></h6>
                    <small class="text-muted">จำนวน: <?php echo sanitize($item['quantity']); ?></small>
                </div>
            </div>
            <span class="text-muted"><?php echo number_format(sanitize($item['product_price'] * $item['quantity']),2); ?></span>
        </li>
    <?php endforeach; ?>

    <li class="list-group-item d-flex justify-content-between">
        <span>ราคารวมสินค้าทั้งหมด</span>
        <strong><?php echo number_format(sanitize($total_price),2); ?></strong>
    </li>
    <li class="list-group-item d-flex justify-content-between" id="discount-row" style="display: none;">
        <span>ส่วนลด: <span id="discount-title"></span></span>
        <strong class="text-success" id="discount-amount">- 0.00</strong>
    </li>
    <li class="list-group-item d-flex justify-content-between">
        <span>ราคารวมทั้งหมด<?php if ($discount_amount > 0){ echo "หลังหักส่วนลด" ;}?></span>
        <strong id="total-after-discount"><?php echo number_format(sanitize($total_price_after_discount),2); ?></strong>
    </li>
</ul>