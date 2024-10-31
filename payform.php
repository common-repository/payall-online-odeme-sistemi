<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 
$payall_plugin_url = plugin_dir_url('/payall/js',__FILE__);

$payall_settings = get_option("woocommerce_payall_settings");
$isInstallment = $payall_settings["installmentenabled"];

$mode = $payall_settings["mode"];

$shop_page_url = get_bloginfo('wpurl');

?>

<section>	
    <?php 
    if($error_message) { ?>
		<div class="row">
            <ul class="woocommerce-error" id="errDiv">
                <li><?php echo __('Your payment could not be completed. Issuer bank response:','payall')?> <br/> 
                <b><?php echo $error_message; ?></b><br/>
                <?php echo __('Please check your card information and try again.','payall')?>
				</li>
            </ul>
        </div>
    <?php } ?>
	
	<h2 align="center"><?php echo __('Payment by Credit Card','payall')?> </h2>		
	<small> <?php echo __('You can make your payment with your credit card on this page securely.','payall')?> </small><br/>        
	<hr/>
</section>

<form novalidate autocomplete="on" method="POST" id="cc_form" action="<?php echo $payall_paywithcard_url; ?>" class="payall_input-form" >
    <div class="payall_row">	
    <?php if($mode == 'form' || $mode == 'form3d') : ?>
            <div class="payall-form-container">
            
            <input type="hidden" id="cc_transactionid" autocomplete="off" value="<?php echo $payall_id; ?>" name="TransactionId" required>

                        <div class="payall_card_holder">
                            <div class="payall_items">
                                <label class="label" for="cc_name"><?php echo __('Card holder name','payall')?></label>
                                <input type="text" class="payall_input cc_input" id="cc_name" placeholder=<?php echo __('NameSurname','payall')?> autocomplete="off" name="cardHolderName" required>
                            </div>
                        </div>

                        <div class="payall_card_number">
                            <div class="payall_cardnumber_items">
                                <label class="label" for="cc_number"><?php echo __('Card number','payall')?></label>
                                <input type="text" name="payall_cardnumber" class="payall_input cc_input" id="cc_number"
                                placeholder="1111 1111 1111 1111"  data-mask="0000 0000 0000 0000"
                                autocomplete="off" required />

                            <input type="hidden" id="id_CardNumber" autocomplete="off" value="" name="CardNumber" required>

                            <div class="card card-pay">
                            
                            </div>
                        </div>

                        <div class="payall_card_details">
                            <div class="payall_items">
                                <label class="label" for="cc_expiry"><?php echo __('Expiration date','payall')?></label>
                                <input type="text" name="payall-card-expiry" class="payall_input cc_input" id="cc_expiry" data-mask="00/00" placeholder="MM / YY" required />
                                <input type="hidden" id="cc_expiremonth" autocomplete="off" value="" name="ExpireMonth" required>
                                <input type="hidden" id="cc_expireyear" autocomplete="off" value="" name="ExpireYear" required>
                            </div>

                            <div class="payall_items">
                                <div class="payall_cvc">
                                    <label class="label" for="payall_cvc-img">CVC</label>
                                    <div class="payall_tooltip">
                                        <span>?</span>
                                        <div class="payall_cvc-img"><img src="<?php echo $payall_plugin_url ?>/img/credit-card-cvv.png" alt=""></div>
                                    </div>
                                </div>
                                <input type="text" name="Cvv" class="payall_input cc_input" id="cc_cvc" data-mask="0000" placeholder="0000" required data-type="cvc">
                            </div>
                        </div>
                        <div>
                        <select id='slc_instalment' name="InstallmentCount">
                                <option value="0"><?php echo __('Single pay','payall')?></option>			
							</select>
                        </div>
                    </div>
            </div>
                     
     <?php endif; ?>

		<div align="center">
			<input type="hidden" name="cc_form_key" value="<?php echo $cc_form_key; ?>"/>
			<button type="submit" id="cc_form_submit" class="btn btn-lg btn-primary"><span><?php echo __('Pay by Credit Card','payall')?></span></button>
        </div>
        <div class="clear clearfix">
        </div>
        <div class="payall_security_logo">
            <img src="<?php echo $payall_plugin_url ?>/img/visa_logo.png" alt="" srcset="">
            <img src="<?php echo $payall_plugin_url ?>/img/mastercard_logo.png" alt="" srcset="">
            <img src="<?php echo $payall_plugin_url ?>/img/bddk.jpg" alt="">
            <img src="<?php echo $payall_plugin_url ?>/img/pci-dss_logo.png" alt="">
        </div>
        <hr/>
		<?php if($mode == 'form' || $mode == 'form3d') : ?>
        <div align="center">
            <div class="" id="cc_validation"><?php echo __('Please check your card information','payall')?></div>
        </div>
		<?php endif; ?>
    </div>	 
</form>

<?php if($mode == 'form' || $mode == 'form3d') : ?>
<script>

var $payallId = '<?php echo $payall_id; ?>';

<?php if($isInstallment == 1) : ?>

var $getInstalmentInProgress = false;

var $cardNumberReplaced = '';

function payall_getinstalments($cardnumber){

    if($getInstalmentInProgress)
    {
        return;
    }

    var $cardNumberActual = $cardnumber.replace(' ','').replace(' ','').replace(' ','').replace(' ','').replace(' ','');

    if($cardNumberReplaced == $cardNumberActual)
    {
        return;
    }

    $cardNumberReplaced = $cardNumberActual;

    if($cardNumberReplaced.length == 6 || $cardNumberReplaced.length == 16) 
    {
        $getInstalmentInProgress = true;
        $cardNumberSubs = $cardNumberReplaced.substring(0, 6);

        jQuery.ajax({
                url: '<?php echo $shop_page_url ?>/?wc-api=payall',
                type: 'POST',
                dataType: 'json',
                data: { 'requesttype' : 'getinstalments', 'binnumber' : $cardNumberSubs, 'payall_id' : $payallId },
                success: function (data) {                    
                    var $installments = '';                	
                    for (var i = 0; i < data.length; i++) {
					    $installments += '<option value="' + data[i]['InstallmentCount'] + '">' + data[i]['SummaryText'] + '</option>';
                    }

                    var $slcInstalment = jQuery('#slc_instalment');
                    $slcInstalment.html($installments);
                    $slcInstalment.removeAttr('disabled');

                    $getInstalmentInProgress = false;                    
                },
                error: function (err) {
                    $getInstalmentInProgress = false;
                    console.log(err);
                }
        });
    }    
}

<?php endif; ?>


 
jQuery(function () {

        jQuery('input#cc_number').payment('formatCardNumber');
        jQuery('input#cc_expiry').payment('formatCardExpiry');
        jQuery('input#cc_cvc').payment('formatCardCVC');
        jQuery("#cc_form_submit").attr("disabled", true);

        jQuery('.cc_input').bind('keypress keyup keydown focus', function (e) {                        
            jQuery(this).removeClass('error');
            jQuery("#cc_form_submit").attr("disabled", true);
            var hasError = false;
            var cardNumber = jQuery('input#cc_number').val();
            var cardType = jQuery.payment.cardType(cardNumber);
            					
            if (!jQuery.payment.validateCardNumber(cardNumber)) {
                jQuery('input#cc_number').addClass('error');
                hasError = 'number';
            }
            
            <?php if($isInstallment == 1) : ?>
                payall_getinstalments(cardNumber);
            // if(e.type == 'keydown' || e.type == 'focus')
            // {
            //     getinstalments(cardNumber);       
            // }
            <?php endif; ?>

            if (!jQuery.payment.validateCardExpiry(jQuery('input#cc_expiry').payment('cardExpiryVal'))) {
                jQuery('input#cc_expiry').addClass('error');
                hasError = 'expiry';
            }
            if (!jQuery.payment.validateCardCVC(jQuery('input#cc_cvc').val(), cardType)) {
                jQuery('input#cc_cvc').addClass('error');
                hasError = 'cvc';
            }
            if (jQuery('input#cc_name').val().length < 3) {
                jQuery('input#cc_name').addClass('error');
                hasError = 'name';
            }

            if (hasError === false) {
                jQuery("#cc_form_submit").removeAttr("disabled");
                jQuery("#cc_validation").hide();
            }
            else {
                jQuery("#cc_validation").show();
                jQuery("#cc_form_submit").attr("disabled", true);
                jQuery('table#cc_form_table').addClass('error');
            }
        });

		jQuery('.cc_input').keypress();
    });

    jQuery('#cc_form_submit').on('click',function() {

    var cardNumber = jQuery('input#cc_number').val();
    jQuery('input#id_CardNumber').val(cardNumber.replace(' ','').replace(' ','').replace(' ','').replace(' ','').replace(' ',''));

    var expireDateRaw = jQuery('input#cc_expiry').val();

    if(expireDateRaw.length > 0)
    {
        var expireDateSplitted = expireDateRaw.replace(' ','').replace(' ','').replace(' ','').replace(' ','').split('/');
        if(expireDateSplitted.length > 1)
        {
            var expireMonth = expireDateSplitted[0];
            var expireYear = expireDateSplitted[1];

            if(expireYear.length == 2)
            {
                expireYear = '20' + expireYear;
            }

            jQuery('input#cc_expiremonth').val(expireMonth);                    
            jQuery('input#cc_expireyear').val(expireYear);                    
        }
    }

    jQuery(this).val('Please wait ...').attr('disabled','disabled');    
    jQuery('#cc_form').submit();
});

jQuery(function () {
    var cardValid = false;
    var $form = jQuery('form#cc_form');
    jQuery('#cc_number').validateCreditCard((result) => {
        if (result.card_type != null) {
            if (result.card_type.name == "visa") {
                jQuery('.card').addClass('card-visa');
            }
            if (result.card_type.name == "mastercard") {
                jQuery('.card').addClass('card-mastercard');
            }
        }
    });
    $form.validate({
        rules: {
            field: {
                required: true,
                creditcard: true
            }   
        }
    });

    jQuery('#cc_number').on('keypress keydown keyup', function () {
        if (jQuery(this).val() == "") {
            jQuery('.card').removeClass('card-visa')
            jQuery('.card').removeClass('card-mastercard')
            jQuery('.card').addClass('card-pay')
        }
    });
});
</script>
<?php endif; ?>