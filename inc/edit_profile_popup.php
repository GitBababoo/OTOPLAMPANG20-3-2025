<div class="overlay" id="overlay"></div>
<div class="edit-profile-popup" id="editProfilePopup">
    <span class="close-popup" onclick="closeEditProfilePopup()">×</span>
    <h3>แก้ไขข้อมูลส่วนตัว</h3>

    <form id="editProfileForm" action="save_profile.php" method="post">
        <div class="form-group">
            <label for="first_name">ชื่อ</label>
            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>">
        </div>
        <div class="form-group">
            <label for="last_name">นามสกุล</label>
            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>">
        </div>
        <div class="form-group">
            <label for="phone_number">เบอร์โทรศัพท์</label>
            <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>" required>
        </div>
        <div class="form-group">
            <label for="address">ที่อยู่</label>
            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">บันทึก</button>
    </form>
</div>