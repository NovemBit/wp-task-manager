(function( $ ) {
	//filer
	$( '#btm-reset' ).click(function(){
		$( '#jquery-datepicker-start' ).val( '' );
		$( '#jquery-datepicker-end' ).val( '' );
		$( '#status-filter' ).val( '' );
		$( '#callback-filter' ).val( '' );
		$( '#search_id-search-input' ).val( '' );
		$( '#btm-submit' ).trigger( 'click' )
	});

	//Bulk verify alert
	$( '#doaction' ).click(function () {
		let confirmAction;
		confirmAction = confirm( 'Are you sure?' );
		if( false === confirmAction ){
			$('#tasks-filter').submit(function (evt) {
				evt.preventDefault();
				window.history.back();
			});
		}
	});


	//select2
	$(document).ready(function() {

		if( $('#btm-failed').attr('checked') ){
			$('.on-fail').show()
		}
		if( $('#btm-checkbox-callback').attr('checked') ){
			$('.select-callbacks').hide()
		}

		$('#btm-failed').click(function(){
			if( this.checked ){
				$('.on-fail').show(300)
			}else{
				$('.on-fail').hide(300)
				$("#btm-checkbox-callback").prop('checked', false);
				$('.select-callbacks').show();
				$("#btm-notification-callback > option").removeAttr("selected");
				$("#btm-notification-callback").trigger("change");
			}
		});
		$('#btm-checkbox-callback').click(function(){
			if( this.checked ){
				$('.select-callbacks').hide(300)
				$("#btm-notification-callback > option").removeAttr("selected");
				$("#btm-notification-callback").trigger("change");
				$("#btm-add-all").removeClass( "added" );
				$("#btm-add-all").text('Select all');
			}else{
				$('.select-callbacks').show(300)
			}
		});
		$('#btm-notification-callback').select2();
		$("#btm-add-all").click(function(){
			if( ! $("#btm-add-all").hasClass( "added" ) ){
				$("#btm-notification-callback > option").prop("selected","selected");
				$("#btm-notification-callback").trigger("change");
				$("#btm-add-all").addClass( "added" );
				$("#btm-add-all").text('Deselect all');
			}else{
				$("#btm-notification-callback > option").removeAttr("selected");
				$("#btm-notification-callback").trigger("change");
				$("#btm-add-all").removeClass( "added" );
				$("#btm-add-all").text('Select all');
			}
		});
	});

})( jQuery );