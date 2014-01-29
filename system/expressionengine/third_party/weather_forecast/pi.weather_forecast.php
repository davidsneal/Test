<?php

$plugin_info       = array(
   'pi_name'        => 'Weather Forecast',
   'pi_version'     => '1.0.3',
   'pi_author'      => 'Marcel Villerius',
   'pi_author_url'  => 'http://www.villerius.net/weather-forecast/',
   'pi_description' => 'Parses Weather information from Wunderground',
   'pi_usage'       => Weather_forecast::usage()
   );

class Weather_forecast
{
	var $return_data  = "";
	var $cache_name = "weather_forecast";
	var $cache_refresh = 60; // minutes


	function Weather_forecast()
	{

		$this->EE =& get_instance();
		$data = $this->EE->TMPL->tagdata;

		// Fetch data or set defaults
		$key = $this->EE->TMPL->fetch_param('key');
		$location  = str_replace(" ","_",$this->EE->TMPL->fetch_param('location'));
		$imgpath  = ( ! $this->EE->TMPL->fetch_param('imagepath')) ? 'http://icons-ak.wxug.com/i/c/k/': $this->EE->TMPL->fetch_param('imagepath');
		$extension = ( ! $this->EE->TMPL->fetch_param('extension')) ? 'gif' : $this->EE->TMPL->fetch_param('extension');
		$language = ( ! $this->EE->TMPL->fetch_param('language')) ? 'EN' : $this->EE->TMPL->fetch_param('language');
		
		$this->cache_refresh = ( $this->EE->TMPL->fetch_param('cache_refresh') === FALSE ) ? $this->cache_refresh : $this->EE->TMPL->fetch_param('cache_refresh');
  
		//Load the Weather JSON and convert data-parts

		$parsed_json = $this->fetch_feed( $key, $location, $language );

		if( $parsed_json === FALSE ) {
			$this->return_data = $this->EE->TMPL->no_results();
			return;
		  }


		$current = 								$parsed_json->current_observation;
		$forecast =								$parsed_json->forecast->simpleforecast->forecastday;
		$forecast_txt = 						$parsed_json->forecast->txt_forecast->forecastday;
		
		$weatherinfo = array(
			'{city}' => 						$current->display_location->city,
			'{forecast_date}' => 				substr($current->observation_time_rfc822, 5, 11),		 
			'{current_date_time}' => 			substr($current->observation_time_rfc822, -14, 5),
			'{current_temp_f}' => 				$current->temp_f,
			'{current_temp_c}' => 				$current->temp_c,
			'{current_condition}' => 			$current->weather,
			'{current_humidity}' => 			$current->relative_humidity,
			'{current_icon}' => 				'<img src="'.$imgpath.basename($current->icon, 'gif').".".$extension.'" alt="'.$current->weather.'" />',
			'{current_icon_day_and_night}' => 	'<img src="'.$imgpath.basename($current->icon_url, 'gif').$extension.'" alt="'.$current->weather.'" />',
			'{current_wind_direction}' => 		$current->wind_dir,
			'{current_wind_speed_mph}' =>		$current->wind_mph,
			'{current_wind_speed_kph}' =>		$current->wind_kph,
			'{current_wind_speed_bft}' =>		$this->kmh_2_bf($current->wind_kph),

			'{day_of_week_0}' => 				$forecast[0]->date->weekday_short,
			'{text_0_c}' =>						$forecast_txt[0]->fcttext_metric,
			'{text_0_f}' =>						$forecast_txt[0]->fcttext,				
			'{low_0_c}' => 						$forecast[0]->low->celsius,
			'{high_0_c}' => 					$forecast[0]->high->celsius,
			'{low_0_f}' => 						$forecast[0]->low->fahrenheit,
			'{high_0_f}' => 					$forecast[0]->high->fahrenheit,
			'{icon_0}' => 						'<img src="'.$imgpath.basename($forecast[0]->icon_url, 'gif').$extension.'" alt="'.$forecast[0]->conditions.'" />',
			'{condition_0}' => 					$forecast[0]->conditions,
			'{wind_speed_0_mph}' =>				$forecast[0]->avewind->mph,
			'{wind_speed_0_kph}' =>				$forecast[0]->avewind->kph,
			'{wind_speed_0_bft}' =>				$this->kmh_2_bf($forecast[0]->avewind->kph),
			'{wind_speed_0_dir}' =>				$forecast[0]->avewind->dir,

			'{day_of_week_1}' => 				$forecast[1]->date->weekday_short,
			'{text_1_c}' =>						$forecast_txt[2]->fcttext_metric,
			'{text_1_f}' =>						$forecast_txt[2]->fcttext,
			'{low_1_c}' => 						$forecast[1]->low->celsius,
			'{high_1_c}' => 					$forecast[1]->high->celsius,
			'{low_1_f}' => 						$forecast[1]->low->fahrenheit,
			'{high_1_f}' => 					$forecast[1]->high->fahrenheit,
			'{icon_1}' => 						'<img src="'.$imgpath.basename($forecast[1]->icon_url, 'gif').$extension.'" alt="'.$forecast[1]->conditions.'" />',
			'{condition_1}' => 					$forecast[1]->conditions,
			'{wind_speed_1_mph}' =>				$forecast[1]->avewind->mph,
			'{wind_speed_1_kph}' =>				$forecast[1]->avewind->kph,
			'{wind_speed_1_bft}' =>				$this->kmh_2_bf($forecast[1]->avewind->kph),
			'{wind_speed_1_dir}' =>				$forecast[1]->avewind->dir,

			'{day_of_week_2}' => 				$forecast[2]->date->weekday_short,
			'{text_2_c}' =>						$forecast_txt[4]->fcttext_metric,
			'{text_2_f}' =>						$forecast_txt[4]->fcttext,
			'{low_2_c}' => 						$forecast[2]->low->celsius,
			'{high_2_c}' => 					$forecast[2]->high->celsius,
			'{low_2_f}' => 						$forecast[2]->low->fahrenheit,
			'{high_2_f}' => 					$forecast[2]->high->fahrenheit,
			'{icon_2}' => 						'<img src="'.$imgpath.basename($forecast[2]->icon_url, 'gif').$extension.'" alt="'.$forecast[2]->conditions.'" />',
			'{condition_2}' => 					$forecast[2]->conditions,
			'{wind_speed_2_mph}' =>				$forecast[2]->avewind->mph,
			'{wind_speed_2_kph}' =>				$forecast[2]->avewind->kph,
			'{wind_speed_2_bft}' =>				$this->kmh_2_bf($forecast[2]->avewind->kph),
			'{wind_speed_2_dir}' =>				$forecast[2]->avewind->dir,

			'{day_of_week_3}' => 				$forecast[3]->date->weekday_short,
			'{text_3_c}' =>						$forecast_txt[6]->fcttext_metric,
			'{text_3_f}' =>						$forecast_txt[6]->fcttext,
			'{low_3_c}' => 						$forecast[3]->low->celsius,
			'{high_3_c}' => 					$forecast[3]->high->celsius,
			'{low_3_f}' => 						$forecast[3]->low->fahrenheit,
			'{high_3_f}' => 					$forecast[3]->high->fahrenheit,
			'{icon_3}' => 						'<img src="'.$imgpath.basename($forecast[3]->icon_url, 'gif').$extension.'" alt="'.$forecast[3]->conditions.'" />',
			'{condition_3}' => 					$forecast[3]->conditions,
			'{wind_speed_3_mph}' =>				$forecast[3]->avewind->mph,
			'{wind_speed_3_kph}' =>				$forecast[3]->avewind->kph,
			'{wind_speed_3_bft}' =>				$this->kmh_2_bf($forecast[3]->avewind->kph),
			'{wind_speed_3_dir}' =>				$forecast[3]->avewind->dir,
   );  
      // Loop through the weatherinfo
      foreach ($weatherinfo as $short => $long)
      {
         if (strpos($data, $short) !== FALSE)
         {
            $data = str_replace($short, $long, $data);
         }         
      }
  
      // Returns the plugin data back to the ExpressionEngine template
      $this->return_data = $data;
   }
   
   
   /**
	 * Fetch file from cache or from URL
	 *
	 * @author Andrew Weaver, adapted for Weather Forecast
	 */
	function fetch_feed( $key, $location, $language ) {
		
		$parsed_json = FALSE;
		
		// Check cache
		
		$cache_path = APPPATH . 'cache/' . $this->cache_name . '/';
		$cache_refresh = $this->cache_refresh * 60; // seconds
		
		// Check cache folder exists
		
		if( ! @is_dir( $cache_path ) ) {
			@mkdir( $cache_path );
			@chmod( $cache_path, 0777 );
		}
		
		// Create hash of the URL
		$file_hash = md5( $location.$language );
		
		// Is file in the cache?
		if ( file_exists( $cache_path . $file_hash ) ) {
		
			// Is cache file new enough?
			$mtime = filemtime( $cache_path . $file_hash );
			$age = time() - $mtime;

			if ( $cache_refresh > $age ) {

				// Cache found and still valid
				$json_string = @file_get_contents( $cache_path . $file_hash );
				$parsed_json = json_decode($json_string);
			}
		}
		
		if( $parsed_json === FALSE ) {

			 $json_string = file_get_contents("http://api.wunderground.com/api/$key/conditions/forecast/lang:$language/q/$location.json");
 			 $parsed_json = json_decode($json_string);

			// Write to cache
			if( $json_string != "" ) {
				$f_h = fopen( $cache_path . $file_hash, "w" );
				fwrite( $f_h, $json_string );
				fclose( $f_h );
			}
				
			
		}
		
		return $parsed_json;
	}


   /**
	 * Convert kph to beaufort
	 *
	 * @author Joachim Rijsdam
	 */
   	function kmh_2_bf( $v ) {
	  	$beaufort = array();
		$beaufort[0] = 2;
		$beaufort[1] = 6;
		$beaufort[2] = 12;
		$beaufort[3] = 19;
		$beaufort[4] = 30;
		$beaufort[5] = 40;
		$beaufort[6] = 51;
		$beaufort[7] = 62;
		$beaufort[8] = 75;
		$beaufort[9] = 87;
		$beaufort[10] = 103;
		$beaufort[11] = 117;
		$beaufort[12] = 50000;

		$ndx = 0;

		while( 1 ) {
		    if( $v <= $beaufort[ $ndx ] ) {
		        break;
		    }
		$ndx ++;
  		}

  		return $ndx;

	}

   /**
    * Plugin Usage
    */

   function usage()
   {
      ob_start(); 
?>

Parameters
===========================

THE KEY PARAMETER (REQUIRED):
Weather Forecast requires an API key form Wunderground (free in most cases). Sign up for a key at www.wunderground.com.

THE LOCATION PARAMETER (REQUIRED):
For the location (location="NL/Leiden"), there are different options:
* US state/city: CA/San Francisco 
* US zipcode: 60290	
* Country/city: AU/Sydney
* Latitude,longitude: 37.8,-122.4
* Airport code:	KJFK	
* Personal Weather Station id: pws:KCASANFR70
* AutoIP address location: autoip
* Specific IP address location: autoip.json?geo_ip=38.102.136.138
More information about used country codes, see: http://www.villerius.net/weather-forecast/.

THE LANGUAGE PARAMETER:
Use a language code (language="FR"). More information about used language codes, see: http://www.villerius.net/weather-forecast/.
Default language is English.

THE IMAGEPATH PARAMETER:
If you want to use your own icons specify your imagepath (imagepath="/images/").
Default icons are Wunderground's weather icons.

THE EXTENSION PARAMETER:
If you use your custom icons you can specify the file format (extension="png").
Default extension is gif.

THE CACHE_REFRESH PARAMETER:
The number of minutes between cache refreshes. The default is 60 minutes. (cache_refresh="120")



Example
===========================

{exp:weather_forecast key="1234567890abc" location="NY/New York" language="EN" imagepath="/images/" extension="gif" cache_refresh="10"}

	----------------------
	{city}
	returns city -> New York
	----------------------
	{forecast_date}
	returns the date of the forecast -> 16 Sep 2012
	----------------------
	{current_date_time}
	returns the time of the forecast -> 06:55
	----------------------
	{current_temp_c}
	returns the current temperature in Celsius -> 17.2
	----------------------
	{current_temp_f}
	returns the current temperature in Fahrenheit  -> 62
	----------------------
	{current_condition}
	returns the current condition -> Clear
	----------------------
	{current_humidity}
	returns the current humidity -> 52%
	----------------------
	{current_icon}
	returns the current icon -> <img alt="sunny" src="/images/sunny.gif"> (Source depends on default or custom image path).
	----------------------
	{current_icon_day_and_night}
	returns the current day or night icon (clear and nt_clear) based on location -> <img alt="clear" src="/images/nt_clear.gif"> (Source depends on default or custom image path).
	----------------------
	{current_wind_direction}
	returns the current wind direction -> SE
	----------------------
	{current_wind_speed_mph}
	returns the current wind speed in miles -> 0
	----------------------
	{current_wind_speed_kph}
	returns the current wind speed in kilometers -> 0
	----------------------
	{current_wind_speed_bft}
	returns the current wind speed in beaufort -> 0
	----------------------
	{day_of_week_0}
	returns the forecast of today and the next 3 days {day_of_week_0} ... {day_of_week_3} -> Sun
	----------------------
	{text_0_c}
	returns the forecast in text of today and the next 3 days{text_0_c}... {text_3_c} in Celcius -> Partly cloudy. Fog early. High of 20C. Breezy. Winds from the WSW at 15 to 25 km/h.
	----------------------	
	{low_0_c}
	returns the low temperature of today and the next 3 days {low_0_c}... {low_3_c} in Celcius -> 15 
	----------------------
	{high_0_c}
	returns the high temperature of today and the next 3 days {high_0_c}... {high_3_c} in Celcius -> 25 
	----------------------
	{text_0_f}
	returns the forecast in text of today and the next 3 days{text_0_c}... {text_3_c} in Fahrenheit -> Partly cloudy. Fog early. High of 68F. Breezy. Winds from the WSW at 10 to 15 mph.
	----------------------
	{low_0_f}
	returns the low temperature of today and the next 3 days {low_0_f}... {low_3_f} in Fahrenheit -> 59
	----------------------
	{high_0_f}
	returns the high temperature of today and the next 3 days {high_0_f}... {high_3_f} in Fahrenheit -> 77
	----------------------
	{icon_0}
	returns the weather icon of today and the next 3 days {icon_0}... {icon_3} -> <img alt="sunny" src="/images/mostly_sunny.gif"> (Source depends on default or custom image path).
	----------------------
	{wind_speed_0_mph}
	returns the windspeed of today and the next 3 days {wind_speed_0_mph}... {wind_speed_3_mph} in miles -> 12
	----------------------
	{wind_speed_0_kph}
	returns the windspeed of today and the next 3 days {wind_speed_0_kph}... {wind_speed_3_kph} in kilometers -> 19
	----------------------
	{wind_speed_0_bft}
	returns the windspeed of today and the next 3 days {wind_speed_0_bft}... {wind_speed_3_bft} in beaufort -> 3
	----------------------
	{wind_speed_0_dir}
	returns the winddirection of today and the next 3 days {wind_speed_0_dir}... {wind_speed_3_dir} -> WSW
	----------------------
	{condition_0}
	returns the condition of today and the next 3 days {condition_0}... {condition_3} -> Partly Cloudy
	----------------------


{/exp:weather_forecast}

<?php
      $buffer         = ob_get_contents();

      ob_end_clean(); 

      return $buffer;
   }
   // END

}
