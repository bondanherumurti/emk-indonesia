jQuery( function($){
	
	//hide some additional settings if needed
	//rounding type
	/*jQuery('.wmcs_rounding_type').each(function(index, element){
		if( jQuery(element).val() == 'none'){
			jQuery(element).next('.additional_opts').hide();
		}
	});
	
	//exchange rate type
	jQuery('.wmcs_exchange_rate_type').each(function(index, element){
		if( jQuery(element).val() == 'live'){
			jQuery(element).next('.additional_opts').hide();
		}
	});*/
	
	
	//initialise selects with our params
	wmcs_init_chosen_select();
	
});

function wmcs_init_chosen_select(){
	
	if(typeof jQuery("select.wmcs_chosen_select").select2 === "function") { //Select2 implement in Woo 2.3 in place of chosen
		jQuery("select.wmcs_chosen_select").select2({
			minimumResultsForSearch: 10,
			width: '250px',
		});
	} else {
		jQuery("select.wmcs_chosen_select").chosen({
			width: '250px',
			disable_search_threshold: 5
		});
	}
	
}



function toggleAddOpts( el, value ){
	
	if(jQuery(el).hasClass('wmcs_rounding_type')){
		if( value == 'none' )
			jQuery(el).closest('table').find('.rounding_options').hide();
		else
			jQuery(el).closest('table').find('.rounding_options').show();
		return;
	}
	
	if(jQuery(el).hasClass('wmcs_exchange_rate_type')){
		if( value == 'live' )
			jQuery(el).closest('table').find('.custom_exchange_rate').hide();
		else
			jQuery(el).closest('table').find('.custom_exchange_rate').show();
		return;
	}
	
}