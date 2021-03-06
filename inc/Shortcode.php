<?php

class InMap_Shortcode extends Joe_Shortcode {

	function __construct() {
		parent::__construct();

		$this->load_assets();
	}
	
	function load_assets() {
		//Leaflet CSS
		Joe_Assets::css_enqueue(Joe_Helper::plugin_url('assets/css/leaflet.css'));	

		//Leaflet JS
		Joe_Assets::js_enqueue([
			'id' => 'leaflet_js',
			'url' => Joe_Helper::plugin_url('assets/js/leaflet.js'),
			'deps' => [ 'jquery' ]
		]);

		//InMap CSS
		Joe_Assets::css_inline('
			.inmap-map .inmap-point {
				background: ' . Joe_Config::get_setting('appearance', 'colours', 'tracking_colour') . ';
			}
		');
		Joe_Assets::css_enqueue(Joe_Helper::plugin_url('assets/css/front.min.css'));	
		
		//InMap JS
		Joe_Assets::js_enqueue([
			'id' => 'inmap_js',
			'url' => Joe_Helper::plugin_url('assets/js/front.min.js'),
			'deps' => [ 'leaflet_js' ],
			'data' => [
// 				'lang' => []						
			]
		]);		
	}

	public function handle_shortcode($shortcode_data, $content = null) {
		Joe_Log::reset();
		
		$out = "\n" . '<!-- START ' . Joe_Config::get_name() . ' Shortcode -->' . "\n";
		$out .= '<div class="inmap-wrap">';
	
		$shortcode_data = shortcode_atts(array(
			'mapshare_identifier' => 'demo',
			'mapshare_password' => false,
			'mapshare_date_start' => false,
			'mapshare_date_end' => false
		), $shortcode_data, Joe_Config::get_item('plugin_shortcode'));
	
		if($shortcode_data['mapshare_identifier']) {					
			
			$Inreach_Mapshare = new InMap_Inreach($shortcode_data);		
			
			//Error?
			if($error = Joe_Log::in_error()) {
				Joe_Log::render_item($error, 'console');			
			//Proceed
			} else {
				$hash = Joe_Helper::make_hash($Inreach_Mapshare->get_parameters());
				$geojson = $Inreach_Mapshare->get_geojson();

				if(is_string($geojson) && ! empty($geojson)) {		
					Joe_Log::add(sprintf('Displaying %s MapShare Points.', $Inreach_Mapshare->get_point_count()), 'success', 'rendering_points');
						
					//JS
					Joe_Assets::js_onready('
						inmap_create_map(
							"' . $hash . '",
							' . $geojson . '
						);
					');
			
					$out .= '	<div id="inmap-' . $hash . '" class="inmap-map"></div>';
					$out .= '	<div class="inmap-info"></div>';
				} else {
					Joe_Log::add('GeoJSON contains no Points.', 'error', 'empty_geojson');				
				}
			}
		}	else {
			Joe_Log::add('MapShare Identifier not provided.', 'error', 'missing_identifier');
		}

		$out .= '</div>';
		$out .= '<!-- END ' . Joe_Config::get_name() . ' Shortcode -->' . "\n\n";
		
		//Log?
		
		//Display Full log to admin
		if(current_user_can('administrator')) {
			Joe_Log::render();
		//Error?
		} elseif($error = Joe_Log::in_error()) {
			Joe_Log::render_item($error);
		}

		return $out;
	}	
}