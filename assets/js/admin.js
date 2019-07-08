(function( $ ) {
	$( '#unset-date' ).click(function(){
		$( '#jquery-datepicker-entry' ).attr('value', '');
		$( '#jquery-datepicker-end' ).attr('value', '');
		$( '#status-filter' ).attr('value', '');
		$( '#callback-filter' ).attr('value', '');
		$( '#search_id-search-input' ).attr('value', '');
		$( '#date-submit' ).trigger( 'click' )
	})
})( jQuery );