<?php
/** @var WC_Order $order */
/** @var string $redirect_url */
/** @var bool $is_purchase */
/** @var string $public_key */
/** @var string[] $iframe_args */

$url_query = parse_url($redirect_url, PHP_URL_QUERY);
$url_query_parts = $url_query ? explode('&', $url_query) : [];

?>

<script type="text/javascript" src="https://www.simplify.com/commerce/simplify.pay.js"></script>

<iframe name="embedded_pay" width="100%" height="450px" <?php echo implode( ' ', $iframe_args ) ?>></iframe>

<form id="embedded-form" style="display: none" action="<?php echo $redirect_url ?>" method="get">
    <?php foreach ($url_query_parts as $query_part): ?>
        <?php
            $query = explode('=', $query_part);
            if (!isset($query[0], $query[1])) {
                continue;
            }
        ?>
        <input type="text" name="<?php echo $query[0] ?>" value="<?php echo $query[1] ?>">
    <?php endforeach; ?>
    <input type="text" name="reference" value="">
    <input type="text" name="amount" value="">
    <?php if($is_purchase): ?>
        <input type="text" name="paymentId" value="">
        <input type="text" name="signature" value="">
        <input type="text" name="paymentDate" value="">
        <input type="text" name="paymentStatus" value="">
        <input type="text" name="authCode" value="">
    <?php else: ?>
        <input type="text" name="cardToken" value="">
    <?php endif; ?>
</form>

<script>
    var redirectUrl = "<?php echo $redirect_url ?>",
        isPurchase = <?php echo $is_purchase ? 'true' : 'false' ?>,
        publicKey = "<?php echo $public_key ?>";
        $embeddedForm = jQuery('#embedded-form');

    SimplifyCommerce.hostedPayments(
        function(data) {
            if (data.close && data.close === true) {
                return;
            }
            $embeddedForm.find("[name=reference]").val(data.reference);
            $embeddedForm.find("[name=amount]").val(data.amount);

            if (isPurchase) {
                $embeddedForm.find("[name=paymentId]").val(data.paymentId);
                $embeddedForm.find("[name=signature]").val(data.signature);
                $embeddedForm.find("[name=paymentDate]").val(data.paymentDate);
                $embeddedForm.find("[name=paymentStatus]").val(data.paymentStatus);
                $embeddedForm.find("[name=authCode]").val(data.authCode);
            } else {
                $embeddedForm.find("[name=cardToken]").val(data.cardToken);
            }
            $embeddedForm.submit();
        },
        {
            scKey: publicKey
        }
    );
</script>

<a class="button cancel"
   href="<?php echo esc_url( $order->get_cancel_order_url() ) ?>"
><?php echo __( 'Cancel order &amp; restore cart', 'woocommerce' ) ?></a>