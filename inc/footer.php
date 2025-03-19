<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('#discount_id').change(function() {
            var discount_id = $(this).val();
            var total_price = <?php echo $total_price; ?>;
            if (discount_id === "") {
                $('#discount-row').hide();
                $('#total-after-discount').text(<?php echo number_format(sanitize($total_price), 2) ?>);
                return;
            }

            $.ajax({
                url: 'calculate_discount.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    discount_id: discount_id,
                    total_price: total_price
                },
                success: function(response) {
                    if (response.error) {
                        $('#discount-amount').text('0.00');
                        $('#discount-row').hide();
                        alert(response.error);
                        $('#total-after-discount').text(<?php echo number_format(sanitize($total_price), 2) ?>);
                    }
                    else
                    {
                        $('#discount-row').show();
                        $('#discount-amount').text('- ' + response.discount_amount);
                        $('#total-after-discount').text(response.total_price_after_discount);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: " + status + " - " + error);
                }
            });
        });
    });

    function openEditProfilePopup() {
        document.getElementById('editProfilePopup').style.display = 'block';
        document.getElementById('overlay').style.display = 'block';
    }

    function closeEditProfilePopup() {
        document.getElementById('editProfilePopup').style.display = 'none';
        document.getElementById('overlay').style.display = 'none';
    }
</script>
</body>
</html>