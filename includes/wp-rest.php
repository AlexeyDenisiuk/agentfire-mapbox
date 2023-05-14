<?php

    class AGFM_Wp_Rest
    {
        public function __construct()
        {
            add_action( 'rest_api_init', [ $this, 'custom_endpoint' ] );
        }

        /**
         * Register WP REST custom endpoints
         */
        public function custom_endpoint()
        {
            // get map markers
            // Examples:
            //     - https://website.com/wp-json/agentfire-mapbox/v1/map-markers/?name=qwecg
            //     - https://website.com/wp-json/agentfire-mapbox/v1/map-markers/
            //     - https://website.com/wp-json/agentfire-mapbox/v1/map-markers/?name=gadga&tags[]=7&tags[]=89
            register_rest_route(
                'agentfire-mapbox/v1',
                '/map-markers/',
                [
                    'methods' => 'GET',
                    'callback' => [ $this, 'get_map_markers' ],
                    'args' => [
                        'name' => [
                            'required' => false,
                            'validate_callback' => function( $param, $request, $key ) {
                                return is_string( $param );
                            }
                        ],
                        'tags' => [
                            'required' => false,
                        ],
                    ]
                ]
            );

            // add map markers by JSON data
            // Examples:
            //     - https://website.com/wp-json/agentfire-mapbox/v1/map-markers/?list={}
            register_rest_route(
                'agentfire-mapbox/v1',
                '/map-markers/',
                [
                    'methods' => 'POST',
                    'callback' => [ $this, 'add_map_markers' ],
                    'args' => [
                        'list' => [
                            'required' => true,
                            'validate_callback' => function( $param, $request, $key ) {
                                return AGFM_Helpers::is_json( $param );
                            }
                        ],
                ]
            ]);
        }

        /**
         * Implementation of WP REST endpoint for getting map markers
         */ 
        public function get_map_markers( $data )
        {

            $name = $data['name'];

            $tags = $data['tags'];
            if ( !empty( $tags ) && count( $tags ) ) {
                $tags = array_map('intval', $tags);
            }

            $args = [ 
                'post_type'      => 'agfm_map_marker',
                'post_status'    => 'publish',
                'posts_per_page' => -1, 
                'orderby'        => 'title', 
                'order'          => 'ASC', 
            ];

            // do we need filter markers by their names?
            if ( !empty( $name ) ) {
                $args['s'] = $name; 
            }
            // do we need filter markers by their tags/terms?
            if (!empty($tags) && count($tags)) {
                $args['tax_query'] = [
                        [
                            'taxonomy' => 'agfm_map_marker_tag',
                            'field' => 'term_id',
                            'terms' => $tags,
                        ]
                    ];
                    
            };

            // get markers by the filters from the request
            $markers = new WP_Query( $args ); 
            $markers = $markers->posts;

            // create response
            $markerItems = [];
            foreach ( $markers as $key => $marker )
            {
                $markerItem = [];

                $markerItem['longtitude'] = get_field( 'agfm_map_marker_longtitude', $marker->ID );
                $markerItem['latitude'] = get_field( 'agfm_map_marker_latitude', $marker->ID );
                $markerItem['id'] = $marker->ID;
                $markerItem['name'] = $marker->post_title;
                $markerItem['date'] = date( 'd.m.Y', strtotime( $marker->post_date_gmt ) );

                $markerTags = [];
                $terms = get_the_terms( $marker->ID, 'agfm_map_marker_tag' );
                foreach ( $terms as $term )
                {
                    $markerTags[] = [
                        'id'   => $term->term_id,
                        'name' => $term->name
                    ];
                }
                $markerItem['tags'] = $markerTags;

                $markerItems[] = $markerItem;
            }

            // return result
            $response = [
                'status' => 200,
                'items'  => $markerItems
            ];

            return new WP_REST_Response( $response );
        }

        /**
         * Implementation of WP REST endpoint for adding map markers
         */ 
        public function add_map_markers( $data )
        {
            $markers_data = json_decode( $data['list'] );
            $cur_user_id = get_current_user_id();

            // is user logged in? if no then deny the request
            if ( !$cur_user_id ) {

                $response = [
                    'status'  => 403,
                    'message' => 'You must be logged in to add map markers.'
                ];

                return new WP_REST_Response( $response );
            }

            foreach ( $markers_data as $key => $marker_data )
            {
                // create marker object
                $new_marker = [
                  'post_title'  => $marker_data[0]->marker->name,
                  'post_status' => 'publish',
                  'post_author' => $cur_user_id,
                  'post_type'   => 'agfm_map_marker',
                ];

                // insert the marker into the database
                $new_marker_id = wp_insert_post( $new_marker );

                // set the marker's tags/terms
                $tags_ids = array_map( 'intval', $marker_data[0]->marker->tags );
                wp_set_post_terms( $new_marker_id, $tags_ids, 'agfm_map_marker_tag' );

                // store other marker data into the database
                update_field( 'agfm_map_marker_latitude', $marker_data[0]->marker->latitude, $new_marker_id );
                update_field( 'agfm_map_marker_longtitude', $marker_data[0]->marker->longtitude, $new_marker_id );
            }

            // return the response
            $response = [
                'status'  => 200,
                'message' => 'Markers added'
            ];

            return new WP_REST_Response( $response );
        }
    }

