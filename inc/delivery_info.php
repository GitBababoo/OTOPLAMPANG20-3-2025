<?php if (empty($phone_number)): ?>
    <div class="alert alert-warning">
        กรุณาเพิ่มเบอร์โทรศัพท์ <span class="profile-link" onclick="openEditProfilePopup()">แก้ไขข้อมูลส่วนตัว</span>
    </div>
<?php else: ?>
    <p><strong>ชื่อ:</strong> <?php echo $first_name . ' ' . $last_name; ?></p>
    <p><strong>ที่อยู่:</strong> <?php echo $address; ?></p>
    <p><strong>เบอร์โทรศัพท์:</strong> <?php echo $phone_number; ?></p>
<?php endif; ?>