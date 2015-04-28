<style><?php echo @file_get_contents(KYASH_DIR.'assets/css/success.css') ?></style>
<h1>Kyash Code: <?php echo $kyash_code?></h1>
<div class="kyash_succcess_instructions" style="border-top:1px solid #ededed;">
    <p><?php echo nl2br(html_entity_decode($kyash_instructions))?></p>
</div>
<div class="kyash_succcess_instructions2">
    <input type="text" class="input-text" id="postcode" value="<?php echo $postcode?>" maxlength="12" style="width:120px; text-align:center" 
    onblur="if(this.value ==''){this.value='Enter Pincode';}" 
    onclick="if(this.value == 'Enter Pincode'){this.value='';}" />
    <input type="button" class="button" id="kyash_postcode_button" value="See nearby shops" onclick="preparePullShops()">
</div>
<div style="display: none" id="see_nearby_shops_container" class="content">
</div>
<script>
	var loader = '<img src="<?php echo includes_url()?>images/spinner.gif" alt="Processing..." />';
	var url = "<?php echo $url?>";
	<?php echo @file_get_contents(KYASH_DIR.'assets/js/success.js') ?>
</script>