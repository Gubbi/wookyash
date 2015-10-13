jQuery(document).ready(function(){
	var postcode = jQuery("#billing_postcode").val();
	if(postcode.length > 0)
	{
		jQuery("#kyash_postcode").val(postcode);
	}
	else
	{
		jQuery("#kyash_postcode").val(pincodePlaceHolder);
	}
	
	jQuery("#kyash").parent().css({"vertical-align":"top","padding-top":"9px"});
						   
	jQuery("input[name='payment_method']").on("click",function(){
		if(jQuery(this).val() != "kyash")
		{
			jQuery("#kyash_postcode_payment_sub").hide();
			jQuery("#see_nearby_shops_container").hide();
			jQuery("#kyash_open").show();
		}
	});
	
	jQuery("#kyash_postcode").on("focus",function(){
		if(jQuery(this).val() == pincodePlaceHolder)
		{
			jQuery(this).val("");
		}
	});
	
	jQuery("#kyash_postcode").on("blur",function(){
		if(jQuery(this).val().length == 0)
		{
			jQuery(this).val(pincodePlaceHolder);
		}
	});
	
});

function openShops(url,loader)
{
	//jQuery("#kyash_postcode_payment_sub").show();
	jQuery("#see_nearby_shops_container").hide();
	selectKyash();
	jQuery("#kyash_open").hide();
	pullNearByShops(url,loader);
}

function selectKyash()
{
	jQuery("input[value='kyash']").prop("checked",true);
}

var old_postcode = "";
var errorMessage = "Due to some unexpected errors, this is not available at the moment. We are working on fixing it.";

function closeShops()
{
	jQuery("#see_nearby_shops_container").hide();
	jQuery("#kyash_close").hide();
}

function pullNearByShops(url,loader2)
{
	closeShops();
	postcode = jQuery("#kyash_postcode").val();
	if(postcode.length == 0 || postcode == pincodePlaceHolder)
	{
		alert("Enter your post code to retrieve the shops");
	}
	else
	{
		if(old_postcode == postcode)
		{
			jQuery("#see_nearby_shops_container").show();
			jQuery("#kyash_close").show();
		}
		else
		{
			jQuery("#see_nearby_shops_container").show();
			jQuery("#see_nearby_shops_container").html(loader);
			jQuery.ajax({
				 url: url+"&postcode="+postcode, 
				 success: function(output, textStatus, xhr){
					 if(xhr.status == 400 || xhr.status == 200)
					 {
						jQuery("#see_nearby_shops_container").html(output);
						 old_postcode = postcode;
					 }
					 else
					 {
						 jQuery("#see_nearby_shops_container").html(errorMessage);
					 }
					 jQuery("#kyash_close").show();
				 }
			});
		}
	}
}