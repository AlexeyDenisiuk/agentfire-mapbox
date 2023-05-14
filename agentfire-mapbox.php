<?php
	/**
	* Plugin Name: agentfire-mapbox
	* Plugin URI: https://agentfire.com/
	* Description: AgentFire MapBox GL.
	* Version: 0.1
	* Author: Alexey Denisiuk
	**/

    namespace AGFM;

    // define constants
    define( 'AGFM_URL_PLUGINS_FOLDER', '/wp-content/plugins/' );
    define( 'AGFM_URL_PLUGIN_FOLDER', '/wp-content/plugins/agentfire-mapbox/' );
    define( 'AGFM_ABS_PLUGIN_FOLDER', __DIR__ . '/' );
    define( 'AGFM_TEXTDOMAIN', 'agfm_text_namespace' );

    // include autoload
    require_once __DIR__ . '/vendor/autoload.php';

    use AGFM\WP_REST;
    use AGFM\Helpers;

    /**
     * Initializations for the implementation of the shortcode '[agentfire_test]'
     */
	function init_agentfire_map() 
	{
		// include js files
		wp_enqueue_script( 'mapbox-gl.js', AGFM_URL_PLUGIN_FOLDER . 'node_modules/mapbox-gl/dist/mapbox-gl.js', [ 'jquery' ], '2.14.1', true );
        wp_enqueue_script( 'select2.min.js', AGFM_URL_PLUGIN_FOLDER . 'bower_components/select2/dist/js/select2.min.js', [ 'jquery' ], '4.1.0', true );

        wp_enqueue_script( 'popper.min.js', AGFM_URL_PLUGIN_FOLDER . 'node_modules/popper.js/dist/umd/popper.min.js', [ 'jquery' ], '1.14.7', true );
        wp_enqueue_script( 'bootstrap.min.js', AGFM_URL_PLUGIN_FOLDER . 'node_modules/bootstrap/dist/js/bootstrap.min.js', [ 'jquery' ], '4.3.1', true );

		wp_enqueue_script( 'js.cookie.min.js', AGFM_URL_PLUGIN_FOLDER . 'node_modules/js-cookie/dist/js.cookie.min.js', [ 'jquery' ], '3.0.5', true );

		// include css
		wp_enqueue_style( 'mapbox-gl.css', AGFM_URL_PLUGIN_FOLDER . 'node_modules/mapbox-gl/src/css/mapbox-gl.css', false, '2.14.1', 'all' );
		wp_enqueue_style( 'fontawesome-css', AGFM_URL_PLUGIN_FOLDER . 'bower_components/font-awesome/css/all.css', false, '5.7.2', 'all' );
		wp_enqueue_style( 'select2.min.css', AGFM_URL_PLUGIN_FOLDER . 'bower_components/select2/dist/css/select2.min.css', false, '4.1.0', 'all' );
		wp_enqueue_style( 'bootstrap.min.css', AGFM_URL_PLUGIN_FOLDER . 'node_modules/bootstrap/dist/css/bootstrap.min.css', false, '4.3.1', 'all' );

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
            'name'                => Helpers::__( 'Map Markers' ),
            'singular_name'       => Helpers::__( 'Map Marker' ),
            'menu_name'           => Helpers::__( 'Map Markers' ),
            'all_items'           => Helpers::__( 'All Map Markers' ),
            'view_item'           => Helpers::__( 'View Map Marker' ),
            'add_new_item'        => Helpers::__( 'Add New Map Marker' ),
            'add_new'             => Helpers::__( 'Add New' ),
            'edit_item'           => Helpers::__( 'Edit Map Marker' ),
            'update_item'         => Helpers::__( 'Update Map Marker' ),
            'search_items'        => Helpers::__( 'Search Map Marker' ),
            'not_found'           => Helpers::__( 'Not Found' ),
            'not_found_in_trash'  => Helpers::__( 'Not found in Trash' ),
        ];

        $args = [
            'description'         => Helpers::__( 'Map markers tags' ),
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
            'name'          => Helpers::__( 'Map Marker Tags' ),
            'singular_name' => Helpers::__( 'Map Marker Tag' ),
            'search_items'  => Helpers::__( 'Search Map Marker Tags' ),
            'all_items'     => Helpers::__( 'All ' ),
            'edit_item'     => Helpers::__( 'Edit Map Marker Tag' ), 
            'update_item'   => Helpers::__( 'Update Map Marker Tag' ),
            'add_new_item'  => Helpers::__( 'Add New Map Marker Tag' ),
            'new_item_name' => Helpers::__( 'New Subject Map Marker Tag' ),
            'menu_name'     => Helpers::__( 'Map Marker Tags' ),
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
                'page_title'    => Helpers::__( 'Agentfire Mapbox Settings' ),
                'menu_title'    => Helpers::__( 'Agentfire Mapbox' ),
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
                'title'  => Helpers::__( 'General' ),
                'fields' => [
                    [
                        'key'               => 'agfm_mapbox_token',
                        'label'             => Helpers::__( 'Token' ),
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
                        'label'             => Helpers::__( 'Style URL' ),
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
                'title' => Helpers::__( 'Map marker group field' ),
                'fields' => [
                    [
                        'key'               => 'agfm_map_marker_longtitude',
                        'label'             => Helpers::__( 'Longtitude' ),
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
                        'label'             => Helpers::__( 'Latitude' ),
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
        add_shortcode( 'agentfire_test', 'AGFM\agfm_insert_agentfire_test' );

        // main initialization
        agfm_init();

        // run WP REST custom endpoint
        $agfm_wp_rest = new WP_REST();
    }

    // run initialization functions
    add_action( 'init', 'AGFM\agfm_init_general', 0 );
?> 