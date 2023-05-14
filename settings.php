<?php

    class AGFM_Settings
    {
        public function __construct()
        {
            define( 'AGFM_URL_PLUGINS_FOLDER', '/wp-content/plugins/' );
            define( 'AGFM_URL_PLUGIN_FOLDER', '/wp-content/plugins/agentfire-mapbox/' );
            define( 'AGFM_ABS_PLUGIN_FOLDER', __DIR__ . '/' );

            define( 'AGFM_TEXTDOMAIN', 'agfm_text_namespace' );
        }
    }

    // create the class instance
    $agfm_settings = new AGFM_Settings();

