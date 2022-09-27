jQuery(document).ready(function($){
    $('.has-depends').on('change', function(){
        var id = $(this).attr('id');
        if ($(this).is(":checked")){
            $('[data-depends="' + id + '"]' ).parents('tr').fadeIn();
        } else {
            $('[data-depends="' + id + '"]' ).parents('tr').fadeOut();
        }
    });
    $('.has-depends').trigger('change');
    
    $('button.terminals-sync-btn').on('click', function() {
        var btn = $(this);
        btn.addClass('loading').prop('disabled', true);
        jQuery.post(
            omnivadata.ajax_url, 
            {
                'action': 'omniva_terminals_sync'
            }, 
            function(response) {
                
            }
        ).always(function() {
            btn.removeClass('loading').prop('disabled', false);
        });
    });
    
    $('button.services-sync-btn').on('click', function() {
        var btn = $(this);
        btn.addClass('loading').prop('disabled', true);
        jQuery.post(
            omnivadata.ajax_url, 
            {
                'action': 'omniva_services_sync'
            }, 
            function(response) {
                location.reload();
            }
        ).always(function() {
            btn.removeClass('loading').prop('disabled', false);
        });
    });

    $(".omniva-toggle_controller").each(function( index ) {
        set_toggle_data_show(this);
        toggle_group_fields(this);
    });

    $(document).on("change", ".omniva-toggle_controller", function() {
        set_toggle_data_show(this);
        toggle_group_fields(this);
    });

    function set_toggle_data_show(controller) {
        var controller_value = $(controller).val();
        var field = $(controller).attr("data-field");

        if (field == "price_type") {
            if (controller_value == "fixed") {
                $(controller).attr("data-show", "fixed");
            } else {
                $(controller).attr("data-show", "additional");
            }
        }
    }

    function toggle_group_fields(controller) {
        var group = $(controller).attr("data-group");
        var field = $(controller).attr("data-field");
        var show = $(controller).attr("data-show");
        var toggle_elements = $(".omniva-toggle-" + group + "-" + field);
        var show_class = "omniva-toggle_show-" + show;

        for (var i = 0; i <= toggle_elements.length; i++) {
            if ($(toggle_elements[i]).hasClass(show_class)) {
                $(toggle_elements[i]).closest("tr").show();
            } else {
                $(toggle_elements[i]).closest("tr").hide();
            }
        }
    }
});

