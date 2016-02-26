/* 
name: main admin jquery
version: 1.0.1
package: WooCommerce JNE Shipping
*/
jQuery(function($) {
	
	// agenwebsite_jne_admin_params is required to continue, ensure the object exists
	if ( typeof agenwebsite_jne_admin_params === 'undefined' ) {
		return false;
	}
	
	jQuery(window).load(function($) {
	
		var aw_status_version = jQuery('#aw_status_version');
		var aw_status_version_help = jQuery('#aw_status_version_help');
	
		jQuery.ajax({
			url: agenwebsite_jne_admin_params.ajax_url,
			type: "POST",
			dataType:"json",
			data:{ action: 'woocommerce_jne_check_version', _wpnonce: agenwebsite_jne_admin_params.jne_admin_wpnonce },
			success: function( data ){
				if( data != 0 ){
					var mark = jQuery( aw_status_version ).find('mark')
					var status;
					if( data.result == 1 ){
						status = data.version;
					}else if( data.result == 0 ){
						jQuery( mark ).removeClass( 'yes' ).addClass( 'no' );
						jQuery( mark ).before( data.version );
						jQuery( aw_status_version_help ).find('a').attr('href', data.update_url);
						jQuery( aw_status_version_help ).show();
						status = ' - ' + data.latest_version + ' ' + agenwebsite_jne_admin_params.i18n_is_available;
					}
					jQuery( mark ).text( status );
				}
			}
		});
        
        /**
         * Load status
         */
        jQuery.post( agenwebsite_jne_admin_params.ajax_url, { action: 'woocommerce_jne_check_status', license_code: agenwebsite_jne_admin_params.license, _wpnonce: agenwebsite_jne_admin_params.jne_admin_wpnonce }, function(response){
            jQuery('#wc_jne_status tbody').html(response.message);
        });
        
	});

	jQuery(document).ready(function($) {
		/*
		 * Add reset button
		 */
        if( agenwebsite_jne_admin_params.tab !== 'tools' && agenwebsite_jne_admin_params.tab !== 'shortcodes' ){
            $('#last_tab').before('<input name="reset_default" type="submit" value="Kembali ke Settingan Awal" class="button button_secondary" id="reset_default">');
        }else{
            $('input[type="submit"]').hide();
        }
        
		/*
		 * Event click reset default
		 */
		$("#reset_default").click(function(){
			var e = confirm( agenwebsite_jne_admin_params.i18n_reset_default );
			return e?void 0:!1
		});
        
		/*
		 * Date Picker
		 */
        $('#jne_shipping_free_shipping_date_from, #jne_shipping_free_shipping_date_until').datepicker({
            dateFormat: "dd-mm-yy"
        });

        $('.copy-shortcode').tipTip({
            'attribute': 'title',
            'activation': 'click',
            'fadeIn': 50,
            'fadeOut': 50,
            'delay': 0
        });
        
        $( '.copy-shortcode' ).on( 'copy', function( e ){
            e.clipboardData.clearData();
            e.clipboardData.setData( 'text/plain', e.currentTarget.value );
            e.preventDefault();
        });

		/*
		 * Free Shipping Provinsi
		 * Run select2 jquery to select option free shipping provinsi
		 */
        if($().select2){
            $("#jne_shipping_free_shipping_provinsi").select2({
                minimumInputLength: 3,
                multiple: true,
                ajax: {
                    url: agenwebsite_jne_admin_params.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    delay: 1000,
                    cache: true,
                    data: function (term, page) {
                        return {
                            q: term,
                            page: page,
                            action: 'woocommerce_jne_get_provinsi_bykeyword',
                            _wpnonce: agenwebsite_jne_admin_params.jne_admin_wpnonce
                        };
                    },
                    results: function( data, page ) {
                        var terms = [];
                        if ( data ) {
                            $.each( data, function( id, text ) {
                                terms.push( { id: text, text: text } );
                            });
                        };
                        return { results: terms };
                    }
                },
                initSelection: function( element, callback ){
                    var data     = $.parseJSON( element.attr( 'data-selected' ) );
                    var selected = [];
                    $( element.val().split( ',' ) ).each( function( i, val ) {
                        selected.push( { id: val, text: val } );
                    });
                    return callback( selected );
                },
                formatSelection: function( data ) {
                    return '<div class="selected-option" data-id="' + data.id + '">' + data.id + '</div>';
                },
                formatInputTooShort: function () {
                    return agenwebsite_jne_admin_params.i18n_input_too_short_3;	
                },
                formatSearching: function(){
                    return agenwebsite_jne_admin_params.i18n_searching;	
                },
                formatNoMatches: function(){
                    return agenwebsite_jne_admin_params.i18n_no_matches;
                }
            });
        }else if($().chosen){
        /**
         * Free provinsi with chosen
         */
            var tag_provinsi = $('#jne_shipping_free_shipping_provinsi'),
                prov_attr = {name: tag_provinsi.attr('name'), id: tag_provinsi.attr('id'), value: tag_provinsi.val(), placeholder: tag_provinsi.attr('data-placeholder')};
            
            if(tag_provinsi.is('input')){
                var new_prov = tag_provinsi.after($('<select />').attr({multiple: 'multiple', id: prov_attr.id + '_select', 'data-placeholder': prov_attr.placeholder}).css('min-width', '350px'));
                
                if( prov_attr.value !== '' ){
                    var values = prov_attr.value.split(',');
                    var option_tag = '';
                    $.each(values, function(i, val){
                        option_tag += '<option value="' + val + '" selected>' + val + '</option>'
                    });
                    $('#' + prov_attr.name + '_select').html(option_tag);
                    $('#' + prov_attr.name + '_select').trigger('chosen:updated');
                }
            }
            
            $('#jne_shipping_free_shipping_provinsi_select').ajaxChosen({
                minTermLength: 3,
                jsonTermKey: 'q',
                type: 'POST',
                url: agenwebsite_jne_admin_params.ajax_url,
                dataType: 'json',
                data: {
                    action: 'woocommerce_jne_get_provinsi_bykeyword',
                    _wpnonce: agenwebsite_jne_admin_params.jne_admin_wpnonce
                }
            }, function(data){
                var results = {};
                $.each(data, function (i, val) {
                    results[val] = val;
                });
                return results;
            },{
                // chosen options
                no_results_text: "Oops, nothing found!"
            }).change(function(){
                var val = $('#jne_shipping_free_shipping_provinsi_select').val();
                $('#jne_shipping_free_shipping_provinsi').attr('value', val);
            });        
        }
	
		/*
		 * Free Shipping City
		 * Run select2 jquery to select option free shipping city
		 */
        if($().select2){
            $("#jne_shipping_free_shipping_city").select2({
                minimumInputLength: 3,
                multiple: true,
                ajax: {
                    url: agenwebsite_jne_admin_params.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    delay: 1000,
                    cache: true,
                    data: function (term, page) {
                        return {
                            q: term,
                            page: page,
                            action: 'woocommerce_jne_get_city_bykeyword',
                            _wpnonce: agenwebsite_jne_admin_params.jne_admin_wpnonce
                        };
                    },
                    results: function( data, page ) {
                        var terms = [];
                        if ( data ) {
                            $.each( data, function( id, text ) {
                                terms.push( { id: text, text: text } );
                            });
                        };
                        return { results: terms };
                    }
                },
                initSelection: function( element, callback ){
                    var data     = $.parseJSON( element.attr( 'data-selected' ) );
                    var selected = [];
                    $( element.val().split( ',' ) ).each( function( i, val ) {
                        selected.push( { id: val, text: val } );
                    });
                    return callback( selected );
                },
                formatSelection: function( data ) {
                    return '<div class="selected-option" data-id="' + data.id + '">' + data.id + '</div>';
                },
                formatInputTooShort: function () {
                    return agenwebsite_jne_admin_params.i18n_input_too_short_3;	
                },
                formatSearching: function(){
                    return agenwebsite_jne_admin_params.i18n_searching;	
                },
                formatNoMatches: function(){
                    return agenwebsite_jne_admin_params.i18n_no_matches;
                }
            });
        }else if($().chosen){
        /**
         * Free provinsi with chosen
         */
            var tag_kota = $('#jne_shipping_free_shipping_city'),
                kota_attr = {name: tag_kota.attr('name'), id: tag_kota.attr('id'), value: tag_kota.val(), placeholder: tag_kota.attr('data-placeholder')};
            
            if(tag_kota.is('input')){
                var new_prov = tag_kota.after($('<select />').attr({multiple: 'multiple', id: kota_attr.id + '_select', 'data-placeholder': kota_attr.placeholder}).css('min-width', '350px'));
                
                if( kota_attr.value !== '' ){
                    var values = kota_attr.value.split(',');
                    var option_tag = '';
                    $.each(values, function(i, val){
                        option_tag += '<option value="' + val + '" selected>' + val + '</option>'
                    });
                    $('#' + kota_attr.name + '_select').html(option_tag);
                    $('#' + kota_attr.name + '_select').trigger('chosen:updated');
                }
            }
            
            $('#' + kota_attr.name + '_select').ajaxChosen({
                minTermLength: 3,
                jsonTermKey: 'q',
                type: 'POST',
                url: agenwebsite_jne_admin_params.ajax_url,
                dataType: 'json',
                data: {
                    action: 'woocommerce_jne_get_city_bykeyword',
                    _wpnonce: agenwebsite_jne_admin_params.jne_admin_wpnonce
                }
            }, function(data){
                var results = {};
                $.each(data, function (i, val) {
                    results[val] = val;
                });
                return results;
            },{
                // chosen options
                no_results_text: "Oops, nothing found!"
            }).change(function(){
                var val = $('#' + kota_attr.name + '_select').val();
                $('#' + kota_attr.name).attr('value', val);
            });
            
        }
        
        jQuery(".select_jabodetabek").click(function(){
            if($().select2) {
                $('#jne_shipping_free_shipping_city').attr({'data-selected': [], 'value': ''}).select2("val", agenwebsite_jne_admin_params.jabodetabek);
            }else if($().chosen) {
                var option_tag = '',
                    option_value = '';
                
                $.each(agenwebsite_jne_admin_params.jabodetabek, function(i, val){
                    option_tag += '<option value="' + val + '" selected>' + val + '</option>'                   
                    option_value += ( i == 0 ) ? val : ',' + val;
                });
                $('#jne_shipping_free_shipping_city').attr('value', option_value);
                $('#jne_shipping_free_shipping_city_select').html(option_tag);
                $('#jne_shipping_free_shipping_city_select').trigger('chosen:updated');                
            }
            return false;
        });

        jQuery(".select_none").click(function(){
            if(confirm(agenwebsite_jne_admin_params.i18n_delete_all)) {
                var input_tag = $( this ).closest( 'fieldset' ).find('#jne_shipping_free_shipping_city, #jne_shipping_free_shipping_provinsi, #jne_shipping_free_shipping_product');
                var select_tag = $( this ).closest( 'fieldset' ).find('#jne_shipping_free_shipping_city_select, #jne_shipping_free_shipping_provinsi_select, #jne_shipping_free_shipping_product_select');
                if($().select2) {
                    input_tag.select2("val", "");
                }else if($().chosen) {
                    input_tag.attr('value', '');
                    select_tag.find('option').remove();
                    select_tag.trigger('chosen:updated');
                }
            }
            return false;
        });
        
	});

});