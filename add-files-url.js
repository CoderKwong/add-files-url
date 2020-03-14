jQuery(function ( $ ) {
	var isAdding = false;

	function clear() {
		$( '#aef-urls' ).val( '' );
		$( '#aef-hidden' ).hide();
		$( '#aef-error' ).text( '' );
		$( '#aef-width' ).val( '' );
		$( '#aef-height' ).val( '' );
		$( '#aef-mime-type' ).val( '' );
	}

	$( 'body' ).on( 'click', '#aef-clear', function ( e ) {
		clear();
	});

	$( 'body' ).on( 'click', '#aef-show', function ( e ) {
		$( '#aef-media-new-panel' ).show();
		e.preventDefault();
	});

	$( 'body' ).on( 'click', '#aef-in-upload-ui #aef-add', function ( e ) {
		if ( isAdding ) {
			return;
		}
		isAdding = true;

		$('#aef-in-upload-ui #aef-add').prop('disabled', true);

		var postData = {
			'urls': $( '#aef-urls' ).val(),
			'width': $( '#aef-width' ).val(),
			'height': $( '#aef-height' ).val(),
			'mime-type': $( '#aef-mime-type' ).val()
		};
		wp.media.post( 'add_external_files_url', postData )
			.done(function ( response ) {
				var frame = wp.media.frame || wp.media.library;
				if ( frame ) {
					frame.content.mode( 'browse' );
					var library = frame.state().get( 'library' ) || frame.library;
					response.attachments.forEach( function ( elem ) {
						var attachment = wp.media.model.Attachment.create( elem );
						attachment.fetch();
						library.add( attachment ? [ attachment ] : [] );
						if ( wp.media.frame._state != 'library' ) {
							var selection = frame.state().get( 'selection' );
							if ( selection ) {
								selection.add( attachment );
							}
						}
					} );
				}
				if ( response['error'] ) {
					$( '#aef-error' ).text( response['error'] );
					$( '#aef-width' ).val( response['width'] );
					$( '#aef-height' ).val( response['height'] );
					$( '#aef-mime-type' ).val( response['mime-type'] );
					$( '#aef-hidden' ).show();
				} else {
					// Reset the input.
					clear();
					$( '#aef-hidden' ).hide();
				}
				$( '#aef-urls' ).val( response['urls'] );
				$( '#aef-buttons-row .spinner' ).css( 'visibility', 'hidden' );
				$( '#aef-in-upload-ui #aef-add').prop('disabled', false);
				isAdding = false;
			}).fail(function (response ) {
				$( '#aef-error' ).text( 'An unknown network error occured' );
				$( '#aef-buttons-row .spinner' ).css( 'visibility', 'hidden' );
				$( '#aef-in-upload-ui #aef-add' ).prop('disabled', false);
				isAdding = false;
			});
		e.preventDefault();
		$( '#aef-buttons-row .spinner' ).css( 'visibility', 'visible' );
	});

	$( 'body' ).on( 'click', '#aef-in-upload-ui #aef-cancel', function (e ) {
		clear();
		$( '#aef-media-new-panel' ).hide();
		$( '#aef-buttons-row .spinner' ).css( 'visibility', 'hidden' );
		$( '#aef-in-upload-ui #aef-add' ).prop('disabled', false);
		isAdding = false;
		e.preventDefault();
	});
});
