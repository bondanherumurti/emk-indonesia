/*global $, jQuery, alert : false*/
jQuery(function ($) {
    
    // cek ongkir param is required to continue, ensure param is the object exists
    if (typeof agenwebsite_woocommerce_jne_cekongkir_params === 'undefined') {
        return false;
    }
    
    // select2 configuration
    var agenwebsite_woocommerce_select2_cong = {
        formatNoMatches: function (term) {return agenwebsite_woocommerce_jne_cekongkir_params.i18n_no_matches; },
        placeholderOption: 'first'
    };
    
    function agenwebsite_get_slug_cekongkir(str) {
        if (str.indexOf("_widget_") >= 0) {
            return 'widget';
        } else if (str.indexOf("_shortcode_") >= 0) {
            return 'shortcode';
        } else if (str.indexOf("_tab_") >= 0) {
            return 'tab';
        }
    }
    
    $(document).on('change', '#woocommerce_jne_cekongkir_widget_provinsi, #woocommerce_jne_cekongkir_shortcode_provinsi, #woocommerce_jne_cekongkir_tab_provinsi', function () {

        var slug        = 'woocommerce_jne_cekongkir_',
            tipe        = agenwebsite_get_slug_cekongkir($(this).attr('id')),
            parent      = $('#' + slug + tipe),
            kota        = $('#' + slug + tipe + '_kota'),
            kecamatan   = $('#' + slug + tipe + '_kecamatan'),
            loader      = slug + tipe + '_loader',
            data        = {
                action: 'woocommerce_jne_shipping_get_kota',
                provinsi: $(this).find('option:selected').text(),
                _wpnonce: agenwebsite_woocommerce_jne_cekongkir_params._wpnonce
            };
        
		parent.addClass(loader);
		
		$.post(agenwebsite_woocommerce_jne_cekongkir_params.ajax_url, data, function (data) {
			kota.find('option').remove();
			kota.append(new Option(agenwebsite_woocommerce_jne_cekongkir_params.i18n_placeholder_kota, ''));
			
			$.each(data, function (i, value) {
				kota.append(new Option(value, value));
			});
			
			kecamatan.find('option').remove();
			kecamatan.append(new Option(agenwebsite_woocommerce_jne_cekongkir_params.i18n_placeholder_kecamatan, ''));
			
			if ($().select2) {
				kota.select2(agenwebsite_woocommerce_select2_cong);
				kecamatan.select2(agenwebsite_woocommerce_select2_cong);
			}
			
            $('#' + slug + tipe + '_result').hide();
            parent.removeClass(loader);
		});

    });
    
    $(document.body).on('change', '#woocommerce_jne_cekongkir_widget_kota, #woocommerce_jne_cekongkir_shortcode_kota, #woocommerce_jne_cekongkir_tab_kota', function () {
        
        var slug        = 'woocommerce_jne_cekongkir_',
            tipe        = agenwebsite_get_slug_cekongkir($(this).attr('id')),
            parent      = $('#' + slug + tipe),
            provinsi    = $('#' + slug + tipe + '_provinsi'),
            kecamatan   = $('#' + slug + tipe + '_kecamatan'),
            loader      = slug + tipe + '_loader',
            data        = {
                action		: 'woocommerce_jne_shipping_get_kecamatan',
                provinsi	: provinsi.find('option:selected').text(),
                kota		: $(this).find('option:selected').text(),
                _wpnonce	: agenwebsite_woocommerce_jne_cekongkir_params._wpnonce
            };
		
		parent.addClass(loader);
		
		$.post(agenwebsite_woocommerce_jne_cekongkir_params.ajax_url, data, function (data) {
			kecamatan.find('option').remove();
			kecamatan.append(new Option(agenwebsite_woocommerce_jne_cekongkir_params.i18n_placeholder_kecamatan, ''));
			
			$.each(data, function (key, value) {
				kecamatan.append(new Option(value, value));
			});
			
			if ($().select2) {
				$(kecamatan).select2(agenwebsite_woocommerce_select2_cong);
			}
			
            $('#' + slug + tipe + '_result').hide();
            parent.removeClass(loader);
		});
        
    });
    
    $(document.body).on('change', '#woocommerce_jne_cekongkir_widget_kecamatan, #woocommerce_jne_cekongkir_shortcode_kecamatan, #woocommerce_jne_cekongkir_tab_kecamatan', function () {
        
        var slug        = 'woocommerce_jne_cekongkir_',
            tipe        = agenwebsite_get_slug_cekongkir($(this).attr('id')),
            parent      = $('#' + slug + tipe),
            provinsi    = $('#' + slug + tipe + '_provinsi'),
            kota        = $('#' + slug + tipe + '_kota'),
            berat       = $('#' + slug + tipe + '_berat'),
            loader      = slug + tipe + '_loader',
            data  = {
                action		: 'woocommerce_jne_cekongkir_get_harga',
                provinsi	: provinsi.find('option:selected').text(),
                kota		: kota.find('option:selected').text(),
                kecamatan	: $(this).find('option:selected').text(),
                berat		: berat.val(),
                type		: tipe,
                _wpnonce	: agenwebsite_woocommerce_jne_cekongkir_params._wpnonce
            };
        
        if (tipe === 'tab') {
            data.weight_unit = $('#' + slug + tipe + '_weight_unit').val();
        }
		
		parent.addClass(loader);
		
		$.post(agenwebsite_woocommerce_jne_cekongkir_params.ajax_url, data, function (result) {
			
            if (tipe === 'widget' || tipe === 'shortcode') { $('#' + slug + tipe + '_container').hide(); }

			$('#' + slug + tipe + '_result').html(result).show();

			parent.removeClass(loader);
		});
        
    });
    
    $(document.body).on('change', '#woocommerce_jne_cekongkir_widget_berat, #woocommerce_jne_cekongkir_shortcode_berat', function () {
        var slug        = 'woocommerce_jne_cekongkir_',
            tipe        = agenwebsite_get_slug_cekongkir($(this).attr('id')),
            provinsi    = $('#' + slug + tipe + '_provinsi').find('option:selected').val(),
            kota        = $('#' + slug + tipe + '_kota').find('option:selected').val(),
            kecamatan   = $('#' + slug + tipe + '_kecamatan').find('option:selected').val();
        
        if (provinsi !== '0' && kota !== '0' && kecamatan !== '0') {
            if (provinsi !== '' && kota !== '' && kecamatan !== '') {            
                $('#woocommerce_jne_cekongkir_widget_kecamatan, #woocommerce_jne_cekongkir_shortcode_kecamatan, #woocommerce_jne_cekongkir_tab_kecamatan').change();
            }
        }
    });
    
    $(document.body).on('click', '#woocommerce_jne_cekongkir_widget_tutup, #woocommerce_jne_cekongkir_shortcode_tutup, #woocommerce_jne_cekongkir_tab_tutup', function () {
        var slug    = 'woocommerce_jne_cekongkir_',
            tipe    = agenwebsite_get_slug_cekongkir($(this).attr('id'));
        
        $('#' + slug + tipe + '_result').hide();
        $('#' + slug + tipe + '_container').show();
    });
    
    $(document).ready(function () {
         
		if ($().select2) {
			$('#woocommerce_jne_cekongkir_widget_provinsi, #woocommerce_jne_cekongkir_shortcode_provinsi, #woocommerce_jne_cekongkir_tab_provinsi').select2(agenwebsite_woocommerce_select2_cong);
			$('#woocommerce_jne_cekongkir_widget_kota, #woocommerce_jne_cekongkir_shortcode_kota, #woocommerce_jne_cekongkir_tab_kota').select2(agenwebsite_woocommerce_select2_cong);
			$('#woocommerce_jne_cekongkir_widget_kecamatan, #woocommerce_jne_cekongkir_shortcode_kecamatan, #woocommerce_jne_cekongkir_tab_kecamatan').select2(agenwebsite_woocommerce_select2_cong);
		}
        
    });
    
});