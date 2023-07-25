<?php
defined('BASEPATH') OR exit('No direct script access allowed');

	function extract_email($text) {
		$res = preg_match_all("/[a-z\d._%+-]+@[a-z\d.-]+\.[a-z]{2,4}\b/i", $text,$match);
		return $match;
	}

    function create_thumbnail($image_name,$image_dir,$new_width,$new_height,$output_dir)
    {

        $path = $image_dir . '/' . $image_name;

        $mime = getimagesize($path);

        if($mime['mime']=='image/png') {
            $src_img = imagecreatefrompng($path);
        }
        if($mime['mime']=='image/jpg' || $mime['mime']=='image/jpeg' || $mime['mime']=='image/pjpeg') {
            $src_img = imagecreatefromjpeg($path);
        }

        $old_x          =   imageSX($src_img);
        $old_y          =   imageSY($src_img);

        if($old_x > $old_y)
        {
            $thumb_w    =   $new_width;
            $thumb_h    =   $old_y*($new_height/$old_x);
        }

        if($old_x < $old_y)
        {
            $thumb_w    =   $old_x*($new_width/$old_y);
            $thumb_h    =   $new_height;
        }

        if($old_x == $old_y)
        {
            $thumb_w    =   $new_width;
            $thumb_h    =   $new_height;
        }

        $dst_img        =   ImageCreateTrueColor($thumb_w,$thumb_h);

        imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y);


        // New save location
        $new_thumb_loc = $output_dir . $image_name;

        if($mime['mime']=='image/png') {
            $result = imagepng($dst_img,$new_thumb_loc,8);
        }
        if($mime['mime']=='image/jpg' || $mime['mime']=='image/jpeg' || $mime['mime']=='image/pjpeg') {
            $result = imagejpeg($dst_img,$new_thumb_loc,80);
        }

        imagedestroy($dst_img);
        imagedestroy($src_img);

        return $result;

    }

    function create_captcha2($image_path,$code = '',$colors = array()) {

		    $CI = & get_instance();
			$CI->load->helper('captcha');

			$data['captchaCode'] =  ($code == '' ? rand(1000,9999) : $code);

			$vals                =  array(
				'word'           => $data['captchaCode'],
				'img_path'       => $image_path,
				'img_url'        => $CI->config->base_url(),
				'font'           => '',
				'img_width'      => '100',
				'img_height'     => 36,
				'expiration'     => 295, // 2 minute
				'time'           => time(),
                'colors'         => (count($colors) > 0 ? $colors : array(
                    'background' => array(255, 255, 255),
                    'border'     => array(255, 255, 255),
                    'text'       => array(0, 0, 0),
                    'grid'       => array(255, 40, 40)
                ))
			);

			$data['imgCaptcha']  = create_captcha($vals);

			return array('code' => $data['captchaCode'],'image_name' => $data['imgCaptcha']['image'],'filename' => $data['imgCaptcha']['filename']);

	}
	
	function delete_directory($dir) {

	    if (!is_dir($dir)) return;

		$it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
		$files = new RecursiveIteratorIterator($it,RecursiveIteratorIterator::CHILD_FIRST);
		
		foreach($files as $file) {
			if ($file->isDir()){
			@rmdir(@$file->getRealPath());
			} else {
				@unlink($file->getRealPath());
			}
		}
		
		@rmdir($dir);
		
	 }
	
    //truncate string to words and limit it
	function truncate_string($text, $limit) {
      if (str_word_count($text, 0) > $limit) {
          $words = str_word_count($text, 2);
          $pos = array_keys($words);
          $text = substr($text, 0, $pos[$limit]) . '...';
      }
      return $text;
	}

    function curl($url,$method = "POST",$fields = array(),$header = array(),$return_transfer = 0,$nobody = FALSE) {

        if (strtolower($method) == 'get')
            $url .= http_build_query($fields);

        $curl = curl_init();
        curl_setopt ($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, $return_transfer);
        curl_setopt($curl, CURLOPT_NOBODY, $nobody);

        if (strtolower($method) == 'post') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));
        }

        if (count($header) > 0)
            curl_setopt($curl, CURLOPT_HTTPHEADER,$header);

        $res = curl_exec ($curl);
        curl_close ($curl);
        return $res;

    }

    function prevent_cache_page() {

        $ci = & get_instance();

        $ci->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $ci->output->set_header('Pragma: no-cache');
        $ci->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

    }

    function bearingLocation($lat1, $long1, $lat2, $long2){
        $bearingradians = atan2(asin($long1-$long2)*cos($lat2),
            cos($lat1)*sin($lat2) - sin($lat1)*cos($lat2)*cos($long1-$long2));
        $bearingdegrees = abs(rad2deg($bearingradians));
        return $bearingdegrees;
    }

    //default value for unit is meter
    //m is meter
    //k is kilometer
    //empty is mile
    function distance($lat1, $lon1, $lat2, $lon2, $unit) {

        $theta = $lon1 - $lon2;
        $miles = (sin(deg2rad($lat1)) * sin(deg2rad($lat2))) + (cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;
        $feet = $miles * 5280;
        $yards = $feet / 3;
        $kilometers = $miles * 1.609344;
        $meters = $kilometers * 1000;

        $unit = strtolower($unit);

        if ($unit == 'm' || $unit == '')
            return $meters;

        if ($unit == 'k')
            return $kilometers;

        if ($unit == 'y')
            return $yards;

    }

    //register recaptcha in https://www.google.com/recaptcha
    //you have to add <div class="g-recaptcha" data-sitekey="YOUR_SITE_KEY"></div> in your form
    //add below link to header of page
    //<script src='https://www.google.com/recaptcha/api.js?hl=en'></script>
    //you can change language with hl parameter
    function check_google_robot() {

        if (is_localhost()) return TRUE;

        $secret_key     =   config_item('google_robot_secret_key');
        $response_key   =   $_POST["g-recaptcha-response"];
        $user_ip        =   $_SERVER['REMOTE_ADDR'];

        $url = "https://www.google.com/recaptcha/api/siteverify?secret=$secret_key&response=$response_key&remoreip=$user_ip";
        $url = file_get_contents($url);
        $url = json_decode($url);
        if($url -> success) {
            return TRUE;
        } else {
            return FALSE;
        }

    }

    //0 is همراه اول
    //1 is ایرانسل
    //2 is رایتل
    function detect_simcard_operator($number) {

        if (substr($number,0,3) == '091' || substr($number,0,3) == '099')
            return 0;

        if (substr($number,0,3) == '093' || substr($number,0,4) == '0901' || substr($number,0,4) == '0902' || substr($number,0,4) == '0903')
            return 1;

        if (substr($number,0,4) == '0921' || substr($number,0,4) == '0922')
            return 2;

    }

    function force_lowercase_urls() {
        // Grab requested URL
        $url = $_SERVER['REQUEST_URI'];
        // If URL contains a period, halt (likely contains a filename and filenames are case specific)
        if ( preg_match('/[\.]/', $url) ) {
            return;
        }
        // If URL contains a question mark, halt (likely contains a query variable)
        if ( preg_match('/[\?]/', $url) ) {
            return;
        }
        if ( preg_match('/[A-Z]/', $url) ) {
            // Convert URL to lowercase
            $lc_url = strtolower($url);
            // 301 redirect to new lowercase URL
            header('Location: ' . $lc_url, TRUE, 301);
            exit();
        }
    }