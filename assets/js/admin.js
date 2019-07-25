(function( $ ) {
	//filer
	$( '#unset-date' ).click(function(){
		$( '#jquery-datepicker-entry' ).val( '' );
		$( '#jquery-datepicker-end' ).val( '' );
		$( '#status-filter' ).val( '' );
		$( '#callback-filter' ).val( '' );
		$( '#search_id-search-input' ).val( '' );
		$( '#date-submit' ).trigger( 'click' )
	});

	//Bulk verify alert
	$( '#doaction' ).click(function (e) {
		alert( 'Are you sure?' );
	});

	/* Using custom settings */

	$("a#btm-log-data").fancybox({
		'centerOnScroll' : true
	});

	$("a#btm-arg-data").fancybox({
		'centerOnScroll' : true
	});

	//select2
	$(document).ready(function() {
		$('.btm-callback-action-settings').select2();
		$('.btm-status-settings').select2();
		$('.btm-users-settings').select2({
			placeholder: 'Select users',
		});
	});

	//custom bulk
	$( '.btm-bulk-delete' ).click( function(){
		if($(this).prop("checked") == true){
			$( '.btm-delete' ).prop("checked", true);
		}
		else if($(this).prop("checked") == false){
			$( '.btm-delete' ).prop("checked", false);
		}
	} );

	$( '.btm-bulk-delete-button' ).click(function(){
		if( $( '.btm-bulk-select' ).val() === 'delete' ){
			let searchIDs = $("input:checkbox[name=delete]:checked").map(function(){
				return $(this).val();
			}).get();

			let data_obj = { 'action' : 'btm_bulk_delete_ajax' ,
								'callback_action_ids' : searchIDs
			};
			let ajax_url = ajax_object.ajax_url;

			$.ajax({
				type : 'POST',
				url : ajax_url,
				data : data_obj,
			}).success(function(data){
				if( data ){
					$("input:checkbox[name=delete]:checked").parent().parent().hide();
				}
			});
		}
	});

	//user remove ajax
	$('.btm-user-remove').click(function(e){
		let data_obj = { 'action' : 'btm_ajax' ,
						notification_callback_id : e.target.attributes.data_notification_callback_id.nodeValue,
						user_id : e.target.attributes.data_user_id.nodeValue
						};
		let ajax_url = ajax_object.ajax_url;

		$.ajax({
			type : 'POST',
			url : ajax_url,
			data : data_obj,
		}).success(function( data ){
				if( data ){
					$( e.target ).parent().text( 'User removed' );
					$( e.target ).hide();
				}
			});

	})


})( jQuery );