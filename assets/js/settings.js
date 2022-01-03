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
});

