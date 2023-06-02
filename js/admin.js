( function( $ ) { 'use strict';
	jQuery( document ).ready( function() {
		var gmlmadmin ={
			init: function() {
				$( '#gmlm_clear_log_button' ).on( 'click', function() {
					var btn = $(this);
					btn.val('Wait. Processing...').html('Wait. Processing...');
					btn.attr('disabled', true);
					jQuery.ajax({
                        type: "POST",
                        url: ajaxurl,
                        data: {
                            action: 'gmlm_delete_load_with_ajax',
                        },
                        success: function (output) {
                           btn.attr('disabled', false).val('Clear the Log').html('Clear the Log');
						   if( 'yes' != output ) {
								window.location.reload();
						   }
                        }
                    });
				});
			},
			
		}
		gmlmadmin.init();
	} );
} )( jQuery );