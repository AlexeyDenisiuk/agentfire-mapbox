<?php
	/**
	* Plugin Name: agentfire-mapbox
	* Plugin URI: https://agentfire.com/
	* Description: AgentFire MapBox GL.
	* Version: 0.1
	* Author: Alexey Denisiuk
	**/

    // include necessary files
    require_once __DIR__ . '/settings.php';
    require_once __DIR__ . '/includes/helpers.php';
    require_once __DIR__ . '/includes/wp-rest.php';

    /**
     * Initializations for the implementation of the shortcode '[agentfire_test]'
     */
	function init_agentfire_map() 
	{
		// include js files
		wp_enqueue_script( 'jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js', [], '3.6.4', true);
		wp_enqueue_script( 'mapbox-gl.js', 'https://api.tiles.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js', [], '2.14.1', true);
		wp_enqueue_script( 'js.cookie.min.js', 'https://cdn.jsdelivr.net/npm/js-cookie@3.0.5/dist/js.cookie.min.js', [], '3.0.5', true);
		wp_enqueue_script( 'popper.min.js', 'https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js', [], '1.14.7', true);
		wp_enqueue_script( 'bootstrap.min.js', 'https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js', [], '4.3.1', true);
		wp_enqueue_script( 'select2.min.js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', [], '4.1.0', true);

		// include css
		wp_enqueue_style( 'mapbox-gl.css', 'https://api.tiles.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css', false, '2.14.1', 'all' );
		wp_enqueue_style( 'fontawesome-css', 'https://use.fontawesome.com/releases/v5.7.2/css/all.css', false, '5.7.2', 'all' );
		wp_enqueue_style( 'select2.min.css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', false, '4.1.0', 'all' );
		wp_enqueue_style( 'bootstrap.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css', false, '4.3.1', 'all' );

		// include plugin's files
		wp_enqueue_style( 'agentfire-mapbox.css', AGFM_URL_PLUGIN_FOLDER . 'css/agentfire-mapbox.css', false, '1.0.1', 'all' );

		// register the script 'agentfire-mapbox.js' and process it
		wp_register_script( 'agentfire-mapbox.js', AGFM_URL_PLUGIN_FOLDER . 'js/agentfire-mapbox.js', [ 'jquery', 'mapbox-gl.js', 'js.cookie.min.js', 'bootstrap.min.js' ], '1.0.1', true );
		 
		// send backend data to frontend
		// can user add map markers?
		$is_can_add_map_markers = (get_current_user_id()) ? true : false;

		// NONCE is needed to have ability get current user ID on the server side in WP REST requests
		$nonce = wp_create_nonce( 'wp_rest' );

        // get Mapbox token and send it to the map
        $agentfire_mapbox_mapbox_token = get_field( 'agfm_mapbox_token', 'option' );
        // get Mapbox style file URL and send it to the map
        $agentfire_mapbox_style_url= get_field( 'agfm_mapbox_style_url', 'option' );

		// send backend data to frontend
		$agfm_backend_extra_data = [
		   'isCanAddMapMarkers' => $is_can_add_map_markers,
		   'nonce' => $nonce,
           'mapboxToken' => $agentfire_mapbox_mapbox_token,
		   'mapboxStyleUrl' => $agentfire_mapbox_style_url,
		];
		wp_localize_script( 'agentfire-mapbox.js', 'agfmBackendExtraData', $agfm_backend_extra_data );
		 
		// enqueued script with localized data
		wp_enqueue_script( 'agentfire-mapbox.js' );
	}

    /**
     * Implementation of the shortcode '[agentfire_test]'
     */
	function agfm_insert_agentfire_test() 
	{
        // run initialization
		init_agentfire_map();

        // get data for the template
        $tmpl = [];
        // get map marker tags/terms
        $tmpl['map_marker_tags'] = get_terms(
            [
            'taxonomy' => 'agfm_map_marker_tag',
            'hide_empty' => false,
            ]
        );
        // get current user Id
        $tmpl['current_user_id'] = get_current_user_id();

		// get main map tempkate
		ob_start();
		include AGFM_ABS_PLUGIN_FOLDER . 'templates/map.php';
		$tmpl_map = ob_get_contents();
		ob_end_clean();

		// return template
		return $tmpl_map;
	}

    /**
     * Initialization
     */
    function agfm_init()
    {
        // register custom post type 'agfm_map_marker'
        $labels = [
            'name'                => AGFM_Helpers::__( 'Map Markers' ),
            'singular_name'       => AGFM_Helpers::__( 'Map Marker' ),
            'menu_name'           => AGFM_Helpers::__( 'Map Markers' ),
            'all_items'           => AGFM_Helpers::__( 'All Map Markers' ),
            'view_item'           => AGFM_Helpers::__( 'View Map Marker' ),
            'add_new_item'        => AGFM_Helpers::__( 'Add New Map Marker' ),
            'add_new'             => AGFM_Helpers::__( 'Add New' ),
            'edit_item'           => AGFM_Helpers::__( 'Edit Map Marker' ),
            'update_item'         => AGFM_Helpers::__( 'Update Map Marker' ),
            'search_items'        => AGFM_Helpers::__( 'Search Map Marker' ),
            'not_found'           => AGFM_Helpers::__( 'Not Found' ),
            'not_found_in_trash'  => AGFM_Helpers::__( 'Not found in Trash' ),
        ];

        $args = [
            'description'         => AGFM_Helpers::__( 'Map markers tags' ),
            'labels'              => $labels,
            'supports'            => [ 'title', 'custom-fields', 'author' ],
            'taxonomies'          => [ 'agfm_map_marker_tag' ],
            'hierarchical'        => false,
            'has_archive'         => true,
            'public'              => true,
            'exclude_from_search' => false,
            'show_in_rest'        => true,
            'rewrite'             => [ 'slug' => 'agfm_map_marker' ],
        ];
          
        register_post_type( 'agfm_map_marker', $args );

        // register custom taxonomy 'agfm_map_marker_tag'
        $labels = [
            'name'          => AGFM_Helpers::__( 'Map Marker Tags' ),
            'singular_name' => AGFM_Helpers::__( 'Map Marker Tag' ),
            'search_items'  => AGFM_Helpers::__( 'Search Map Marker Tags' ),
            'all_items'     => AGFM_Helpers::__( 'All ' ),
            'edit_item'     => AGFM_Helpers::__( 'Edit Map Marker Tag' ), 
            'update_item'   => AGFM_Helpers::__( 'Update Map Marker Tag' ),
            'add_new_item'  => AGFM_Helpers::__( 'Add New Map Marker Tag' ),
            'new_item_name' => AGFM_Helpers::__( 'New Subject Map Marker Tag' ),
            'menu_name'     => AGFM_Helpers::__( 'Map Marker Tags' ),
        ];    

        register_taxonomy( 'agfm_map_marker_tag', ['agfm_map_marker'], [
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'agfm_map_marker_tag' ],
        ]);
    
        // add options page
        if ( function_exists('acf_add_options_page') )
        {
            // register options page
            $option_page = acf_add_options_page([
                'page_title'    => AGFM_Helpers::__( 'Agentfire Mapbox Settings' ),
                'menu_title'    => AGFM_Helpers::__( 'Agentfire Mapbox' ),
                'menu_slug'     => 'agentfire-mapbox-settings',
                'capability'    => 'edit_posts',
                'redirect'      => false
            ]);
        }

        // add acf custom fields
        if ( function_exists( 'acf_add_local_field_group' ) )
        {
            // add fields in the options page
            acf_add_local_field_group([
                'key'    => 'agfm_group_field_general',
                'title'  => AGFM_Helpers::__( 'General' ),
                'fields' => [
                    [
                        'key'               => 'agfm_mapbox_token',
                        'label'             => AGFM_Helpers::__( 'Token' ),
                        'name'              => 'agfm_mapbox_token',
                        'type'              => 'text',
                        'prefix'            => '',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ],
                        'default_value' => '',
                        'placeholder'   => '',
                        'prepend'       => '',
                        'append'        => '',
                        'maxlength'     => '',
                        'readonly'      => 0,
                        'disabled'      => 0,
                    ],
                    [
                        'key'               => 'agfm_mapbox_style_url',
                        'label'             => AGFM_Helpers::__( 'Style URL' ),
                        'name'              => 'agfm_mapbox_style_url',
                        'type'              => 'text',
                        'prefix'            => '',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ],
                        'default_value' => '',
                        'placeholder'   => '',
                        'prepend'       => '',
                        'append'        => '',
                        'maxlength'     => '',
                        'readonly'      => 0,
                        'disabled'      => 0,
                    ],
                ],
                'location' => [
                    [
                        [
                            'param'    => 'options_page',
                            'operator' => '==',
                            'value'    => 'agentfire-mapbox-settings',
                        ],
                    ],
                ],
                'menu_order'            => 0,
                'position'              => 'normal',
                'style'                 => 'default',
                'label_placement'       => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen'        => '',
            ]);

            // add fields in the map markers edit page
            acf_add_local_field_group([
                'key' => 'agfm_group_field_map_marker',
                'title' => AGFM_Helpers::__( 'Map marker group field' ),
                'fields' => [
                    [
                        'key'               => 'agfm_map_marker_longtitude',
                        'label'             => AGFM_Helpers::__( 'Longtitude' ),
                        'name'              => 'agfm_map_marker_longtitude',
                        'type'              => 'text',
                        'prefix'            => '',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ],
                        'default_value' => '',
                        'placeholder'   => '',
                        'prepend'       => '',
                        'append'        => '',
                        'maxlength'     => '',
                        'readonly'      => 0,
                        'disabled'      => 0,
                    ],
                    [
                        'key'               => 'agfm_map_marker_latitude',
                        'label'             => AGFM_Helpers::__( 'Latitude' ),
                        'name'              => 'agfm_map_marker_latitude',
                        'type'              => 'text',
                        'prefix'            => '',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ],
                        'default_value' => '',
                        'placeholder'   => '',
                        'prepend'       => '',
                        'append'        => '',
                        'maxlength'     => '',
                        'readonly'      => 0,
                        'disabled'      => 0,
                    ]
                ],
                'location' => [
                    [
                        [
                            'param'    => 'post_type',
                            'operator' => '==',
                            'value'    => 'agfm_map_marker',
                        ],
                    ],
                ],
                'menu_order'            => 0,
                'position'              => 'normal',
                'style'                 => 'default',
                'label_placement'       => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen'        => '',
            ]);
        }
    }

    /**
     * General initialization
     */
    function agfm_init_general() 
    {
        // create schortcode for the map's plugin
        add_shortcode( 'agentfire_test', 'agfm_insert_agentfire_test' );

        // main initialization
        agfm_init();

        // run WP REST custom endpoint
        $agfm_wp_rest = new AGFM_Wp_Rest();
    }

    // run initialization functions
    add_action( 'init', 'agfm_init_general', 0 );
?>