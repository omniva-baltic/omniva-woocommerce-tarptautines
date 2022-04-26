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
});

