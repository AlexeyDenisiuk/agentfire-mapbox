( function( $ ) {

	// settings
	var isDebugMode = false;
	var mapMarkerOffset = 45;
	var mapMarkerTagsAbsentStr = '-';
	var colorMapMarkerGeneral = '#3db1cd';

	// service variables
	var map;
	var isClickedOnMarker = false;
	var newMarker;
	var newMarkersArr = [];

	// initializations
	jQuery( document ).ready( function() {

		function agfmIsWeHaveEverything() 
		{
			// check whether we have all we need
			if ( isEmpty( agfmBackendExtraData.mapboxToken ) ) throw "Mapbox token is empty. Set it in admin panel.";
			if ( isEmpty( agfmBackendExtraData.mapboxStyleUrl ) ) throw "Mapbox style URL is empty. Set it in admin panel.";

			return true;
		}

		function agfmInit()
		{
			jQuery( '#agfm_main_map_app_cont #filters_map_marker_search_btn' ).on( 'click', function() { searchMarkers() } );
			jQuery( '#agfm_main_map_app_cont #map_marker_add_btn' ).on( 'click', function() { setupMarker() } );
			jQuery( '#agfm_main_map_app_cont #map_marker_cancel_btn' ).on( 'click', function() { cancelMarker( newMarker ) } );
			jQuery( '#agfm_main_map_app_cont #btn_edit_map' ).on( 'click', function() { toggleEditMapMode(this) } );

			jQuery( '#agfm_main_map_app_cont form').on('submit', function() { return false; });

			// initialize Select2 custom select element
			jQuery( '.agfm-select' ).each( function() {
				jQuery( this ).select2({
					placeholder: jQuery( this ).attr( 'placeholder' ),
					allowClear: true,
					dropdownParent: jQuery( this ).parent()
				});
			})

			// is map exists?
			if ( jQuery( '#map' ).length )
			{
			    // the value for 'accessToken' begins with 'pk...'
				mapboxgl.accessToken = agfmBackendExtraData.mapboxToken; 
			    map = new mapboxgl.Map({
			      container: 'map',
			      style: agfmBackendExtraData.mapboxStyleUrl, 
			      center: [30.4, 50.3],
			      zoom: 8.7
			    });

			    // @REFACTOR: this code helps to determine whether click was on the map or was on marker?
			    //            If on the marker then do not create new marker.
			    if ( agfmBackendExtraData.isCanAddMapMarkers )
			    {

					jQuery( '#map' ).on( 'click', '.mapboxgl-marker', function( e ) {
						isClickedOnMarker = true;
					})

					// Set an event listener
					map.on( 'click', ( e ) => {
						
						setTimeout(
							function() {
								if ( !isEditMapMode() ) return false;
								if ( isClickedOnMarker != true ) {

									newMarker = new mapboxgl.Marker({draggable: true, color: '#ff0000'})
									 	.setLngLat([e.lngLat.lng, e.lngLat.lat])
									 	.addTo(map);

								 	// clear all values in the fields in the popup window
									jQuery( '#popup_window_add_map_marker input[type=text]' ).val( '' ).removeClass( 'is-invalid is-valid ');
									jQuery( '#popup_window_add_map_marker select' ).val( '' ).removeClass( 'is-invalid is-valid' );
									jQuery( '#popup_window_add_map_marker select ').val( null ).trigger( 'change' );

									// show popup window
								 	jQuery( '#popup_window_add_map_marker ').modal( 'show' );

								 	// @BUG: this code is used for a bug related to the Select2 element in the
								 	//       popup window. For some reason if remove this then its placeholder dissapears
								 	//       when no options are selected
									setTimeout(
										function() {
											jQuery( '#popup_window_add_map_marker select' ).select2({
												placeholder: jQuery( '#popup_window_add_map_marker select' ).attr( 'placeholder' ),
												allowClear: true,
												dropdownParent: jQuery( '#popup_window_add_map_marker select' ).parent()
											});
										},
										200
									);
				
								}
								isClickedOnMarker = false; 
							}, 
							50
						)
					});
				}

				loadMarkers({markerName: '', markerTags: []});
			}
		}

		// check whether we have all we need
		agfmIsWeHaveEverything();

		// run initialization
		agfmInit();

        // return that the function successfully completed
        return true;
	})

	/**
	 * Check whether the variable is empty
	 * 
	 * @param mixed val 
	 * @return boolean
	 */
	function isEmpty( val )
	{
		switch ( val ) {
		    case "":
		    case 0:
		    case "0":
		    case null:
		    case false:
		    case undefined:
		      return true;
		    default:
		      return false;
	  	}
	}

	/**
	 * Check whether the map is in the edit mode
	 * 
	 * @return boolean Return 'true' If the map in the edit mode
	 */
	function isEditMapMode()
	{
		if ( jQuery( '.btn_edit-map' ).length == 0 ) return true;
		else return false;
	}

	/**
	 * Load map markers
	 * 
	 * @param mixed filters 
	 * @return boolean
	 */
	function loadMarkers( filters )
	{
		// disable search filters
		disableSearchFilters();

		// clear the map
		jQuery('#map .mapboxgl-marker').remove();
		jQuery('#map .mapboxgl-popup').remove();

		var dataRequest = {};

		// define which data to send in the request for getting map markers
		if ( !isEmpty( filters ) )
		{
			if ( !isEmpty( filters.markerName) ) {
				dataRequest.name = filters.markerName;
			}
			if ( !isEmpty( filters.markerTags ) ) {
				dataRequest.tags = filters.markerTags;
			}
		}

		// get map markers
		jQuery.ajax({
			url: '/wp-json/agentfire-mapbox/v1/map-markers/',
			type: 'GET',
			data: dataRequest,
			dataType: 'json',

			success: function( data )
			{
				jQuery.each( data.items, function( index, marker )
				{
					// is a marker has no longitude or latitude?
					if ( isEmpty( marker.longitude ) && isEmpty( marker.latitude ) ) {
						return;
					}

					// form tags
					let tags = '';
					jQuery.each( marker.tags, function( index, tag ) {
						tags += '<span class="popup-tag" data-map-marker-tag-id="'+tag.id+'">'+tag.name+'</span>';
					})

					if ( isEmpty( tags ) ) {
						tags = mapMarkerTagsAbsentStr;
					}

					let markerPopup = new mapboxgl
						.Popup({ offset: mapMarkerOffset })
						.setHTML(
							'<table>' +
							    '<tr>' +
							        '<td>' +
							            '<span class="label">Name: </span>' +
				            		'</td>' + 
				              		'<td>' + marker.name + '</td>' +
		            			'</tr>' +
							    '<tr>' +
							        '<td>' +
							            '<span class="label">Tags: </span>' +
							            '</td>' + 
						              '<td>' + tags + '</td>' +
					            '</tr>' +
							    '<tr>' +
							        '<td>' +
							            '<span class="label">Added: </span>' +
						            '</td>' + 
					              	'<td>' + marker.date+ '</td>' +
					            '</tr>' +
					        '</table>'
					);

				    new mapboxgl.Marker({})
						.setLngLat([ marker.longitude, marker.latitude ])
						.setPopup( markerPopup ) // sets a popup on this marker
						.addTo( map );
				});

				// activate search filters
				activateSearchFilters();

				return true;
			}
		})
	}

	function isSearchFiltersDisabled()
	{
		if ( jQuery( '#filters_map_marker_name' ).attr( 'disabled' ) == 'disabled' ) return true;
		else return false;
	}

	function disableSearchFilters()
	{
		jQuery( '#filters_cont select' ).attr( 'disabled', 'disabled' ).trigger( 'change' );
		jQuery( '#filters_cont input[type=text]' ).attr( 'disabled', 'disabled' );
		jQuery( '#filters_cont button' ).attr( 'disabled', 'disabled' );

        // return that the function successfully completed
        return true;
	}

	function activateSearchFilters()
	{
		jQuery( '#filters_cont select' ).removeAttr( 'disabled' ).trigger( 'change' );
		jQuery( '#filters_cont input[type=text]' ).removeAttr( 'disabled' );
		jQuery( '#filters_cont button' ).removeAttr( 'disabled' );

        // return that the function successfully completed
        return true;
	}

	function searchMarkers()
	{
		// if search filters disabled then do not start new search
		if ( isSearchFiltersDisabled() ) return false;

		if ( !isSearchFiltersDisabled() ) {
			disableSearchFilters();
		}

		let markerName = jQuery( '#filters_map_marker_name' ).val();
		let markerTags = jQuery( '#filters_map_marker_tags' ).val();

		// load markers
		loadMarkers({markerName: markerName, markerTags: markerTags});

        // return that the function successfully completed
        return true;
	}

	function cancelMarker( newMarker )
	{
		newMarker.remove();

        // return that the function successfully completed
        return true;
	}

	function toggleEditMapMode( obj )
	{
		if ( !isEditMapMode() )
		{
			// disable search filters
			disableSearchFilters();

			// edit map mode
			jQuery( obj )
				.removeClass('btn_edit-map')
				.addClass('btn_save-map')
				.find('.fa-wrench')
					.removeClass('fa-wrench')
					.addClass('fa-save');
		}
		else
		{
			// activate search filters
			activateSearchFilters();

			// save map mode
			jQuery( obj )
				.removeClass('btn_save-map')
				.addClass('btn_edit-map')
				.find('.fa-save')
					.removeClass('fa-save')
					.addClass('fa-wrench');

			var newMarkersData = [];

			jQuery.each( newMarkersArr, function( index, elem )
			{
				newMarkersData[newMarkersData.length] = [{
					// update location because it could be dragged	
			    	'marker': {
			    		'longitude': newMarkersArr[index].marker_handler.getLngLat().lng,
			    		'latitude': newMarkersArr[index].marker_handler.getLngLat().lat,
						'name': newMarkersArr[index].marker.name,
						'tags': newMarkersArr[index].marker.tags,
						'date': newMarkersArr[index].marker.date
					},
				}];

				// set colors for newly created markers as default colors
				jQuery( newMarkersArr[index].marker_handler.getElement ).find( 'path[fill]' ).attr( 'fill', colorMapMarkerGeneral );

				// do not allow to drag created markers
				newMarkersArr[index].marker_handler.setDraggable( false );
			})

			// save to cookies newly created markers
			Cookies.set( 'newMarkersData', JSON.stringify( newMarkersData ) );

			// send a request for adding new map markers
			jQuery.ajax({
				url: '/wp-json/agentfire-mapbox/v1/map-markers/',
				type: 'POST',
				data: { 'list': Cookies.get( 'newMarkersData' )	},
				dataType: 'json',
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', agfmBackendExtraData.nonce );
				},
				success: function( data ) {
					Cookies.set( 'newMarkersData', JSON.stringify('') );
					newMarkersData = [];
					newMarkersArr = [];
				}
			});
		}

        // return that the function successfully completed
        return true;
	}

	function setupMarker()
	{
		var isErrors = false;
		jQuery( '#popup_window_add_map_marker [required]' ).each( function() {
			if (jQuery( this ).val() == '') {
				jQuery( this ).addClass('is-invalid');
				isErrors = true;
			} else {
				jQuery( this ).removeClass('is-invalid');
			}
		})
		if ( isErrors ) return false;

		let newMarkerPopupName = jQuery( '#new_marker_name' ).val();
		var newMarkerPopupTags = '';
		var newMarkerTagIds = [];
		var newMarkerDateAdded = '';

		jQuery( '#popup_window_add_map_marker select option:selected' ).each( function( index, element ) {
			newMarkerPopupTags += '<span class="popup-tag" data-map-marker-tag-id="' + jQuery( element ).val() + '">';
			newMarkerPopupTags += jQuery(element).text();
			newMarkerPopupTags += '</span>';

			newMarkerTagIds[newMarkerTagIds.length] = jQuery( element ).val();
		})

		if ( isEmpty( newMarkerPopupTags ) ) {
			newMarkerPopupTags = mapMarkerTagsAbsentStr;
		}

		// get formatted current date like 'dd.mm.YYYY'
		var date = new Date();
		var formattedDate = date.toLocaleString(undefined, { 
		    year: 'numeric', 
		    month: '2-digit', 
		    day: '2-digit' 
		}).replace(/(\d+)\/(\d+)\/(\d+)/, '$2/$1/$3');

		newMarkerDateAdded = formattedDate;

		let newMarkerPopup = new mapboxgl
			.Popup({ offset: mapMarkerOffset })
			.setHTML(
				'<table>' +
				    '<tr>' +
				        '<td>' +
				            '<span class="label">Name: </span>' +
				        '</td>' + 
			            '<td>' + newMarkerPopupName + '</td>' +
		            '</tr>' +
				    '<tr>' +
				        '<td>' +
				            '<span class="label">Tags: </span>' +
			            '</td>' + 
		                '<td>' + newMarkerPopupTags + '</td>' +
		            '</tr>' +
				    '<tr>' +
				        '<td>' +
				            '<span class="label">Added: </span>' +
			            '</td>' + 
		                '<td>' + newMarkerDateAdded + '</td>' +
		            '</tr>' +
		        '</table>'
			);

		    newMarker.setPopup( newMarkerPopup );

		    newMarkersArr[newMarkersArr.length] = {
		    	'marker_handler': newMarker,
		    	'marker': {
		    		'longitude': newMarker.getLngLat().lng,
		    		'latitude': newMarker.getLngLat().lat,
		    		'name': newMarkerPopupName,
		    		'tags': newMarkerTagIds,
		    		'date': newMarkerDateAdded,
		    	}
		    };

		jQuery( '#popup_window_add_map_marker' ).modal( 'hide' );

        // return that the function successfully completed
        return true;
	}

})(jQuery);




