<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Thomas Boley <thomas.boley@googlemail.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */


//plugin.tx_myext_pi1.wert = {$wert_der_Konstante}

class tx_flickrset_flkapi {
	
	var $paramter_array;
	var $status_code;
	
	/**
	 * tx_flickrset_dummy function.
	 * 
	 * @access public
	 * @param mixed $api_key
	 * @return void
	 */
	function tx_flickrset_flkapi($api_key) {
		$this->paramter_array = array();
		$this->paramter_array['api_key'] = $api_key;
		$this->paramter_array['format'] = 'php_serial';
	}
	
	/**
	 * flickr_photosetsGetPhotos function.
	 * 
	 * @access public
	 * @param mixed $photoset_id
	 * @param int $per_page. (default: 0)
	 * @param int $page. (default: 0)
	 * @return void
	 */
	function flickr_photosetsGetPhotos($photoset_id,$per_page=0,$page=0) {
		$this->paramter_array['method'] = 'flickr.photosets.getPhotos';
		$this->paramter_array['photoset_id'] = $photoset_id;
		
		if($per_page) {
			$this->paramter_array['per_page'] =$per_page;
		}
		
		if ($page) {
			$this->paramter_array['page'] =$page;
		}
		
		return $this->_call_flickr_api();
		
	}
	
	/**
	 * flickr_photosetsGetInfo function.
	 * 
	 * @access public
	 * @param mixed $photoset_id
	 * @return void
	 */
	function flickr_photosetsGetInfo($photoset_id) {
		$this->paramter_array['method'] = 'flickr.photosets.getInfo';
		$this->paramter_array['photoset_id'] = $photoset_id;
		
		return $this->_call_flickr_api();
		
	}
	
	/**
	 * flickr_photosGetInfo function.
	 * 
	 * @access public
	 * @param mixed $photo_id
	 * @return void
	 */
	function flickr_photosGetInfo($photo_id) {
		$this->paramter_array['method'] = 'flickr.photos.getInfo';
		$this->paramter_array['photo_id'] = $photo_id;
	return $this->_call_flickr_api();
	}
	
	
	/**
	 * flickr_peopleGetPublicPhotos function.
	 * 
	 * @access public
	 * @param mixed $user_id
	 * @return void
	 */
	function flickr_peopleGetPublicPhotos($user_id) {
		$this->paramter_array['method'] = 'flickr.people.getPublicPhotos';
		$this->paramter_array['user_id'] = $user_id;
		
	return $this->_call_flickr_api();
	
	
	}
	
	function flickr_photosetsgetList($user_id) {
		$this->paramter_array['method'] = 'flickr.photosets.getList';
		$this->paramter_array['user_id'] = $user_id;
		
	return $this->_call_flickr_api();	
	}
	
	/**
	 * flickr_peoplefindByUsername function.
	 * 
	 * @access public
	 * @param mixed $user_name
	 * @return void
	 */
	function flickr_peoplefindByUsername($user_name) {
		$this->paramter_array['method'] = 'flickr.people.findByUsername';
		$this->paramter_array['username'] = $user_name;
		
		return $this->_call_flickr_api();
	}
		
	/**
	 * _param_encode function.
	 * 
	 * @access private
	 * @return void
	 */
	function _param_encode() {
		$encoded_params = array();
		foreach ($this->paramter_array as $k => $v){

			$encoded_params[] = urlencode($k).'='.urlencode($v);
		}
			return $encoded_params;
		
	}
	
	function flickr_photosetsGetContext($photo_id,$photoset_id) {
		$this->paramter_array['method'] = 'flickr.photosets.getContext';
		$this->paramter_array['photo_id'] = $photo_id;
		$this->paramter_array['photoset_id'] = $photoset_id;
		return $this->_call_flickr_api();
		
	}
	
	/**
	 * _call_flickr_api function.
	 * 
	 * @access private
	 * @return void
	 */
	function _call_flickr_api() {
		$url = "http://api.flickr.com/services/rest/?".implode('&', $this->_param_encode());
		$rsp = file_get_contents($url);
		$rsp_obj = unserialize($rsp);
		return $rsp_obj;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/flickrset/pi1/class.tx_flickrset_flkapi.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/flickrset/pi1/class.tx_flickrset_flkapi.php']);
}

?>
