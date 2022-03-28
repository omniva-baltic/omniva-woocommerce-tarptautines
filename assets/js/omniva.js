var omniva_global_terminals_loading = false;
jQuery(document).on('ready', function($) {
    jQuery('body').on('click','.shipping_method', function(){
        if (typeof(omnivaint_terminal_reference) !== 'undefined' ) {
            if (jQuery(this).attr('value') !== omnivaint_terminal_reference) {
                jQuery('.tmjs-container').hide();
            }
        }
    });
  
  jQuery('body').on('load-omniva-terminals', () => {
     if(jQuery('.tmjs-container').length == 0 && omniva_global_terminals_loading === false){
        loadTerminalMapping();
     }
  });
});
function omniva_getPostcode() {
        var postcode;
        if (jQuery('#ship-to-different-address-checkbox').length && jQuery('#ship-to-different-address-checkbox').is(':checked')) {
            if (jQuery("#shipping_postcode").length && $("#shipping_postcode").val()) {
                postcode = jQuery("#shipping_postcode").val();
            }
        } else {
            if (jQuery("#billing_postcode").length && jQuery("#billing_postcode").val()) {
                postcode = jQuery("#billing_postcode").val();
            } else if (jQuery("#calc_shipping_postcode").length && jQuery("#calc_shipping_postcode").val()) {
                postcode = jQuery("#calc_shipping_postcode").val();
            }
        }
        return postcode;
    }
    
    function omniva_getCity() {
        var city;
        if (jQuery('#ship-to-different-address-checkbox').length && jQuery('#ship-to-different-address-checkbox').is(':checked')) {
            if (jQuery("#shipping_city").length && $("#shipping_city").val()) {
                city = jQuery("#shipping_city").val();
            }
        } else {
            if (jQuery("#billing_city").length && jQuery("#billing_city").val()) {
                city = jQuery("#billing_city").val();
            } else if (jQuery("#calc_shipping_city").length && jQuery("#calc_shipping_city").val()) {
                city = jQuery("#calc_shipping_city").val();
            }
        }
        return city;
    }
    
    function omniva_getAddress() {
        var address;
        if (jQuery('#ship-to-different-address-checkbox').length && jQuery('#ship-to-different-address-checkbox').is(':checked')) {
            if (jQuery("#shipping_address_1").length && $("#shipping_address_1").val()) {
                address = jQuery("#shipping_address_1").val();
            }
        } else {
            if (jQuery("#billing_city").length && jQuery("#billing_city").val()) {
                address = jQuery("#billing_address_1").val();
            } else if (jQuery("#calc_shipping_address_1").length && jQuery("#calc_shipping_address_1").val()) {
                address = jQuery("#calc_shipping_address_1").val();
            }
        }
        return address;
    }
    
function loadTerminalMapping() {
  omniva_global_terminals_loading = true;
  let isModalReady = false;
  var tmjs = new TerminalMapping(omnivaSettings.api_url + '/api/v1');
  tmjs
    .sub('terminal-selected', data => {
      jQuery('input[name="order[receiver_attributes][parcel_machine_id]"]').val(data.id);
      jQuery('#order_receiver_attributes_terminal_address').val(data.name + ", " + data.address);
      jQuery('.receiver_parcel_machine_address_filled').text('');
      jQuery('.receiver_parcel_machine_address_filled').append('<div class="d-inline-flex" style="margin-top: 5px;">' +
        '<img class="my-auto mx-0 me-2" src="'+omnivaSettings.api_url + '/default_icon_icon.svg" width="25" height="25">' +
        '<h5 class="my-auto mx-0">' + data.address + ", " + data.zip + ", " + data.city + '</h5></div>' +
        '<br><a class="select_parcel_btn select_parcel_href" data-remote="true" href="#">Pakeisti</a>')
      jQuery('.receiver_parcel_machine_address_filled').show();
      jQuery('.receiver_parcel_machine_address_notfilled').hide();

      tmjs.publish('close-map-modal');
    });

  tmjs_country_code = jQuery('#order_receiver_attributes_country_code').val();
  tmjs_identifier = jQuery('#order_receiver_attributes_service_identifier').val();


  tmjs.setImagesPath(omnivaSettings.api_url + '/');
  tmjs.init({country_code: omnivaSettings.country , identifier: omnivaSettings.identifier, city: omniva_getCity() , post_code: omniva_getPostcode(), receiver_address: omniva_getAddress(), max_distance: omnivaSettings.max_distance});

  window['tmjs'] = tmjs;

  tmjs.setTranslation({
    modal_header: omnivaglobaldata.text_map,
    terminal_list_header: omnivaglobaldata.text_list,
    seach_header: omnivaglobaldata.text_search,
    search_btn: omnivaglobaldata.text_search,
    modal_open_btn: omnivaglobaldata.text_select_terminal,
    geolocation_btn: omnivaglobaldata.text_my_loc,
    your_position: 'Distance calculated from this point',
    nothing_found: omnivaglobaldata.text_not_found,
    no_cities_found: omnivaglobaldata.text_no_city,
    geolocation_not_supported: 'Geolocation not supported',

    // Unused strings
    search_placeholder: omnivaglobaldata.text_enter_address,
    workhours_header: 'Work hours',
    contacts_header: 'Contacts',
    select_pickup_point: omnivaglobaldata.text_select_terminal,
    no_pickup_points: 'No terminal',
    select_btn: omnivaglobaldata.text_select,
    back_to_list_btn: omnivaglobaldata.text_reset,
    no_information: omnivaglobaldata.text_not_found
  })

  tmjs.sub('tmjs-ready', function(t) {
    t.map.ZOOM_SELECTED = 8;
    isModalReady = true;
    jQuery('.spinner-border').hide();
    jQuery('.select_parcel_btn').removeClass('disabled').html(omnivaglobaldata.text_select_terminal);
    omniva_global_terminals_loading = false;
  });

  jQuery(document).on('click', '.select_parcel_btn', function(e) {
    e.preventDefault();
    if (!isModalReady) {
      return;
    }
    tmjs.publish('open-map-modal');
    coords = {lng: jQuery('.receiver_coords').attr('value-x'), lat: jQuery('.receiver_coords').attr('value-y')};
    if (coords != undefined) {
      tmjs.map.addReferencePosition(coords);
      tmjs.dom.renderTerminalList(tmjs.map.addDistance(coords), true)
    }
  });

}
