jQuery(document).ready(function($){
    $('.omniva_global_terminal').select2();
    function loader(on){
        if (on){
            $('#omniva_global_shipping_meta_box').addClass('loading');
        } else {
            $('#omniva_global_shipping_meta_box').removeClass('loading');
        }
    }
    
    function parse_error(error){
        $('#omniva_global_shipping_meta_box .inside .errors').html(`
            <div class="omniva-notice-error">
                <p>${error}</p>
              </div>
        `);
        loader(false);
    }
    
    function load_order(){
        loader(true);
        wp.ajax.post( "load_omniva_order", {
            order_id: $('#post_ID').val()
        } )
        .done(function(response) {
            if (response.content !== 'undefined') {
                $('#omniva_global_shipping_meta_box .inside').html(response.content );
            }
            loader(false);
        });
    }
    
    $('#omniva_global_shipping_meta_box').on('click', '#omniva_global_create', function(){
        loader(true);
        var services = [];
        var terminal = 0;
        $('.omniva_global_services').each(function(index, el){
            if ($(el).is(":checked")){
                services.push($(el).val());
            }
        });
        if ($('.omniva_global_terminal').length > 0){
            terminal = $('.omniva_global_terminal').val();
        }
        if ($('.omniva_global_eori').length > 0){
            eori = $('.omniva_global_eori').val();
        }
        wp.ajax.post( "create_omniva_order", {
            order_id: $('#post_ID').val(),
            services: services,
            terminal: terminal,
            eori: eori
        } )
        .done(function(response) {
            if (response.status === 'error'){
                parse_error(response.msg);
            } else {
                load_order();
            }
        });
    });
    
    $('#omniva_global_shipping_meta_box').on('click', '#omniva_global_delete', function(){
        loader(true);
        wp.ajax.post( "delete_omniva_order", {
            order_id: $('#post_ID').val()
        } )
        .done(function(response) {
            if (response.status === 'error'){
                parse_error(response.msg);
            } else {
                load_order();
            }
        });
    });
});

