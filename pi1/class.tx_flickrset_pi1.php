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
 *
 * @package
 */


require_once PATH_tslib.'class.tslib_pibase.php';
require_once 'class.tx_flickrset_flkapi.php';

/**
 * Plugin 'flickr set' for the 'flickrset' extension.
 *
 * @author Thomas Boley <thomas.boley@googlemail.com>
 * @package TYPO3
 * @subpackage tx_flickrset
 */
class tx_flickrset_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_flickrset_pi1';  // Same as class name
	var $scriptRelPath = 'pi1/class.tx_flickrset_pi1.php'; // Path to this script relative to the extension dir.
	var $extKey        = 'flickrset'; // The extension key.
	var $pi_checkCHash = true;
	var $picture_amount;
	var $template;
	var $total_pages; // amount of total pages, depends on pictures per page
	var $flickr_request_object;
	var $userid;




	/**
	 * The main method of the PlugIn
	 *
	 * @param string  $content: The PlugIn content
	 * @param array   $conf:    The PlugIn configuration
	 * @return The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->init($conf);
				

		$this->pi_loadLL();
		$this->piVars = t3lib_div::GParrayMerged($this->prefixId);
		
	
		 
		if ($this->piVars['page']==0) {
			$this->piVars['page']=1;
		}

		if ($this->piVars['singlefotoid']) {
			$this->conf['singlefotoid'] = $this->piVars['singlefotoid'];
		}

		// load template file

		$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);

		$this->addHeaderParts();

		$this->flickr_request_object = new tx_flickrset_flkapi ($this->conf['flickrapi']);

		$content ='';

		if ($this->conf['viewmode']=='single') {
			$content = $this->_SingleViewResponder($content);
		}
		
		else if ($this->conf['viewmode']=='setview') {
			$content = $this->_SetViewResponder($content,$this->conf['userset']);
		}

		else {
			$content = $this->_ListViewResponder($content);
		}

		
		return $this->pi_wrapInBaseClass($content);

	}


	/**
	 * _SingleViewResponder function.
	 *
	 * @access private
	 * @param mixed   $content
	 * @return void
	 */
	function _SingleViewResponder($content) {

		// display singelview

		if ($this->piVars['flickrfoto']) {
			$rsp_obj = $this->flickr_request_object->flickr_photosGetInfo($this->piVars['flickrfoto']);
		}
		else {
			$rsp_obj = $this->flickr_request_object->flickr_photosGetInfo($this->conf['singlefotoid']);
		}
		if ($rsp_obj['stat']=='ok') {

			$content = $this->_build_SingleView($rsp_obj);
		}
		else {
			$content =  $this->_build_error_message($rsp_obj['code']);
		}

		return $content;
	}
	
	function _SetViewResponder($content,$username) {
	
		$rsp_obj = $this->flickr_request_object->flickr_peoplefindByUsername($username);
		$this->userid = $rsp_obj['user']['id'];
		$rsp_obj = $this->flickr_request_object->flickr_photosetsgetList($this->userid);
		
	
		
		if ($rsp_obj['stat']=='ok') {
		
			$content .= $this->build_set_list($rsp_obj['photosets']['photoset'],$username);
			}
						
		else {

			$content =  $this->_build_error_message($rsp_obj['code']);

			}
			
				
		
		//return '<pre>'.var_export($rsp_obj['photosets']['photoset'] ,true).'</pre>';
		return $content;
	}


	/**
	 * _ListViewResponder function.
	 *
	 * @access private
	 * @param mixed   $content
	 * @return void
	 */
	function _ListViewResponder($content) {
		// get infos from foto set
		
		($this->piVars['fotoset']) ? $set=$this->piVars['fotoset'] : $set=$this->conf['fotoset'];
		
		$rsp_obj = $this->flickr_request_object->flickr_photosetsGetInfo($set);

		// amount of pictures in set

		$this->picture_amount = $rsp_obj['photoset']['photos'];

		// set total pages
		if($this->conf['fotosperpage']) {
			$this->total_pages = ceil($this->picture_amount/$this->conf['fotosperpage']);
		}	
		

		$content .= $this->build_set_info($rsp_obj['photoset']);

		$rsp_obj = $this->flickr_request_object->flickr_photosetsGetPhotos($set, $this->conf['fotosperpage'], $this->piVars['page']);

		if ($rsp_obj['stat']=='ok') {
			$content .= $this->build_list($rsp_obj['photoset']['photo']);
			
			if($this->conf['fotosperpage']) {
				$pagination = $this->pagination($this->piVars['page']);
			}
			
			if ($pagination) {
			
				$template = $this->cObj->getSubpart($this->templateCode, '###PAGENAVIGATION###');
				$marker['###PAGENAVIGATIONLIONKS###'] = $pagination;
				$content .=  $this->cObj->substituteMarkerArrayCached($template, $marker);
			}	


		}
		else {

			$content =  $this->_build_error_message($rsp_obj['code']);

			}
		return $content;
	}


	/**
	 * Init Function: here all the needed configuration values are stored in class variables
	 *
	 * @return   void
	 * @param array   $conf: configuration array from TS
	 */
	function init($conf) {
		$this->conf = $conf; // Store configuration

		$this->pi_setPiVarDefaults(); // Set default piVars from TS
		$this->pi_initPIflexForm(); // Init FlexForm configuration for plugin

		// Read extension configuration
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);

		if (is_array($extConf)) {
			$conf = t3lib_div::array_merge($extConf, $conf);

		}

		// Read TYPO3_CONF_VARS configuration
		$varsConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey];

		if (is_array($varsConf)) {

			$conf = t3lib_div::array_merge($varsConf, $conf);

		}

		// Read FlexForm configuration
		if ($this->cObj->data['pi_flexform']['data']) {

			foreach ($this->cObj->data['pi_flexform']['data'] as $sheetName => $sheet) {

				foreach ($sheet as $langName => $lang) {
					foreach (array_keys($lang) as $key) {

						$flexFormConf[$key] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],
							$key, $sheetName, $langName);

						if (!$flexFormConf[$key]) {
							unset($flexFormConf[$key]);

						}
					}
				}
			}
		}

		if (is_array($flexFormConf)) {

			$conf = t3lib_div::array_merge($conf, $flexFormConf);
		}

		$this->conf = $conf;

	}


	/**
	 * addHeaderParts function.
	 *
	 * @access public
	 * @return void
	 */
	function addHeaderParts() {

		if ($this->conf['includeDefaultCssandJs']) {
			$key = 'EXT:'.$this->extKey.md5($this->templateCode);

			if (!isset($GLOBALS['TSFE']->additionalHeaderData[$key])) {
				$headerParts = $this->cObj->getSubpart($this->templateCode, '###HEADER_PARTS###');

				if ($headerParts) {
					$headerParts = $this->cObj->substituteMarker($headerParts, '###SITE_REL_PATH###', t3lib_extMgm::siteRelPath($this->extKey));
					$GLOBALS['TSFE']->additionalHeaderData[$key] = $headerParts;

				}

			}
		}

	}


	/**
	 * build_list function.
	 *
	 * @access public
	 * @param mixed   $photoset_array
	 * @return void
	 */
	function build_list($photoset_array) {

		$content="";
		$marker = array();
		$anchor_counter = 1;

		// Get the parts out of the template

		$template['total'] = $this->cObj->getSubpart($this->templateCode, '###LISTVIEW###');

		foreach ($photoset_array as $single_photo_array) {

			if ($this->conf['thumbnailstyle']=='_o') {
				$secret = $single_photo_array['originalsecret'];
			}
			else {
				$secret = $single_photo_array['secret'];
			}


			$url_param = 'http://farm'.$single_photo_array['farm'].'.static.flickr.com/'.$single_photo_array['server'].'/'.      $single_photo_array['id'].'_'.$secret;

			if ($this->conf['thumbnailstyle']=='def') {
				$suffix='';
			}
			else {
				$suffix = $this->conf['thumbnailstyle'];
			}


			$url_param_t = $url_param.$suffix.'.jpg';
			$url_param_o = $url_param.'.jpg';
			$marker['###ANCHORNAME###'] = $this->extKey.$anchor_counter;
			$marker['###IMGURL###'] = $url_param_o;
			$marker['###IMGSRC###'] = $url_param_t;
			$marker['###IMGTITLE###'] = $single_photo_array['title'];
			if ($this->conf['listviewdescription']) {
				$photo_info = $this->flickr_request_object->flickr_photosGetInfo($single_photo_array['id']);
				$marker['###DESCRIPTION###'] = $photo_info['photo']['description']['_content'];
			}
			else {
				$marker['###DESCRIPTION###'] = $photo_info['photo']['description']['_content'];

			}
			if ($this->conf['singlepageid']) {
				$singlelink = $this->cObj->getSubpart($this->templateCode, '###SINGLELINK###');
				$linkmarker['###SINGLEVIEWTEXT###'] = $this->pi_getLL('singleviewtext');
				$linkmarker['###SINGLEVIEWLINK###'] =$this->_createLinkURLSingle($single_photo_array['id'],$anchor_counter,$this->conf['singlepageid'],$this->piVars['page']);
				$marker['###LINKTOSINGLE###'] = $this->cObj->substituteMarkerArrayCached($singlelink, $linkmarker);

			}
			else {
				$marker['###LINKTOSINGLE###'] = '';

			}

			$content .=$this->cObj->substituteMarkerArrayCached($template['total'], $marker);
			$anchor_counter ++;
		}


		return $content;

	}


	/**
	 * _build_SingleView function.
	 *
	 * @access private
	 * @param mixed   $photo_array
	 * @return void
	 */
	function _build_SingleView($photo_array) {

		$content="";
		$marker = array();

		// Get the parts out of the template

		$template['total'] = $this->cObj->getSubpart($this->templateCode, '###SINGLEVIEW###');

		if ($this->conf['singlepicturestyle']=='_o') {
			$secret = $photo_array['photo']['originalsecret'];
		}
		else {
			$secret = $photo_array['photo']['secret'];
		}

		$url_param = 'http://farm'.$photo_array['photo']['farm'].'.static.flickr.com/'.$photo_array['photo']['server'].'/'.$photo_array['photo']['id'].'_'.$secret;

		if ($this->conf['singlepicturestyle']=='def') {
			$suffix='';
		}
		else {
			$suffix= $this->conf['singlepicturestyle'];
		}

		$url_param_t = $url_param.$suffix.'.jpg';
		$url_param_o = $url_param.'.jpg';
		$marker['###IMGURL###'] = $url_param_o;
		$marker['###IMGSRC###'] = $url_param_t;
		$marker['###IMGTITLE###'] = $photo_array['photo']['title']['_content'];
		$marker['###DESCRIPTION###'] = $photo_array['photo']['description']['_content'];

		if ($this->conf['listpageid']) {
			$listlink = $this->cObj->getSubpart($this->templateCode, '###LISTLINK###');

			$linkmarker['###LISTVIEWTEXT###'] = $this->pi_getLL('listviewtext');
			$linkmarker['###LISTVIEWLINK###'] = $this->_createLinkURLList().'#'.'flickrset'.$this->piVars['anchorid'];

			$marker['###LINKTOLIST###'] = $this->cObj->substituteMarkerArrayCached($listlink, $linkmarker);

		}
		else {
			$marker['###LINKTOLIST###'] = '';

		}
		if ($this->conf['prevnextlink']) {
		
			$marker['###LINKTOPREVNEXT###'] = $this->_getPrevNext($photo_array['photo']['id']);
		
		}
		
		else {
			$marker['###LINKTOPREVNEXT###'] = '';
		}


		$content =$this->cObj->substituteMarkerArrayCached($template['total'], $marker);


		return $content;

	}


	/**
	 * build_set_info function.
	 *
	 * @access public
	 * @param mixed   $set_array
	 * @return void
	 */
	function build_set_info($set_array) {

		$content = "";
		$marker = array();

		$template['total'] = $this->cObj->getSubpart($this->templateCode, '###SETVIEW###');

		$marker['###SETTITLE###'] = $set_array['title']['_content'];
		$marker['###PICTUREAMOUNT###'] = $set_array['photos'];
		$marker['###DESCRIPTION###'] = $set_array['description']['_content'];

		$content .=$this->cObj->substituteMarkerArrayCached($template['total'], $marker);

		return $content;


	}


	function build_set_list($set_array,$username) {

		$content = "";
		$marker = array();
		$userid = 

		$template['total'] = $this->cObj->getSubpart($this->templateCode, '###SETLIST###');
		foreach($set_array as $single_array) {
			$url_param = 'http://farm'.$single_array['farm'].'.static.flickr.com/'.$single_array['server'].'/'.      $single_array['primary'].'_'.$single_array['secret'].'_s.jpg';
			$marker['###IMGSRC###'] = $url_param;
			$marker['###SETTITLE###'] = $single_array['title']['_content'];
			$marker['###PICTUREAMOUNT###'] = $single_array['photos'];
			$marker['###DESCRIPTION###'] = $single_array['description']['_content'];
			$marker['###SETLINK###'] = 'http://www.flickr.com/photos/'.$username.'/sets/'.$single_array['id'].'/';
			$marker['###SETID###'] = $single_array['id'];
			$marker['###USERID###'] = $this->userid;
			$marker['###PAGEURL###'] = $this->_createLinkURLSET($single_array['id']);
			

			$content .=$this->cObj->substituteMarkerArrayCached($template['total'], $marker);
		}
		$content = $this->cObj->dataWrap($content,$this->conf['wrapset']);
		return $content;


	}

	/**
	 * pagination function.
	 *
	 * @access public
	 * @param mixed   $current_page
	 * @return void
	 */
	function pagination($current_page) {

		$navigation_content ='';


		if ($current_page > 1 and $this->total_pages > 1) {

			// set navigation links

			$navigation_content .=  $this->build_navigation($this->pi_linkTP_keepPIvars_url(array('page'=>$current_page-1), 1), htmlspecialchars($this->pi_getLL('back')), 'back');

		}

		if ($current_page<$this->total_pages) {

			// set navigation links

			$navigation_content .=  $this->build_navigation($this->pi_linkTP_keepPIvars_url(array('page'=>$current_page+1), 1), htmlspecialchars($this->pi_getLL('forward')), 'forward');

		}

		return $navigation_content;

	}


	/**
	 * _build_error_message function.
	 *
	 * @access private
	 * @param mixed   $code
	 * @return void
	 */
	function _build_error_message($code) {
		$content = "";

		// get flickr error message

		switch ($code) {

		case 1:
			$message = $this->pi_getLL('error1');
			break;

		case 100:
			$message = $this->pi_getLL('error100');
			break;

		case 105:
			$message = $this->pi_getLL('error105');
			break;
		
		default:
			$message = 'Unknown error.';
		break;

		}

		$marker = array();
		$template= $this->cObj->getSubpart($this->templateCode, '###ERRORMESSAGE###');
		$marker['###ERROR###'] = $message;
		$content .=$this->cObj->substituteMarkerArrayCached($template, $marker);

		return $content;
	}
	
	/**
	 * _getPrevNext function.
	 * 
	 * @access private
	 * @param mixed $photo_id
	 * @return void
	 */
	function _getPrevNext($photo_id) {
		
		if ($this->conf['listpageid']) {
			if ($this->piVars['anchorid'] == 1 AND $this->piVars['page'] >1) {
				$anchor_previous = $this->conf['fotosperpage'];
				$page_previous = $this->piVars['page']-1;

			}
			// must have?
			else if($this->piVars['anchorid'] == 1 AND $this->piVars['page'] ==1) {
				$anchor_previous = 1;
				$page_previous = 1;
			}
			
			else {
				$anchor_previous = $this->piVars['anchorid'] -1;
				$page_previous = $this->piVars['page'];
				}
				
			if ($this->piVars['anchorid'] == $this->conf['fotosperpage']) {
				$anchor_next = 1;
				$page_next = $this->piVars['page']+1;

			
			}
			
			
			else {
				$anchor_next = $this->piVars['anchorid']+1;
				$page_next = $this->piVars['page'];
			}
			
			
			}
		
		$rsp_obj = $this->flickr_request_object->flickr_photosetsGetContext($photo_id,$this->conf['fotoset']);
		
		$content = "";
		$marker = array();
		$template['prev'] = $this->cObj->getSubpart($this->templateCode, '###SINGLELINKTOPREVIOUS###');
		$template['next'] = $this->cObj->getSubpart($this->templateCode, '###SINGLELINKTONEXT###');
		
		if ( $rsp_obj['prevphoto']['id']) {
			$marker_prev['###PREVIOUSLINKTEXT###'] = $this->pi_getLL('previousphoto');
			$marker_prev['###PREVIOUSLINK###'] =  $this->_createLinkURLSingle($rsp_obj['prevphoto']['id'],$anchor_previous,$GLOBALS['TSFE']->id,$page_previous);
						
			$content .=$this->cObj->substituteMarkerArrayCached($template['prev'], $marker_prev);
		}
		
		if ($rsp_obj['nextphoto']['id']) {
			$marker_next['###NEXTLINKTEXT###'] =  $this->pi_getLL('nextphoto');
			$marker_next['###NEXTLINK###'] =  $this->_createLinkURLSingle($rsp_obj['nextphoto']['id'],$anchor_next,$GLOBALS['TSFE']->id,$page_next);
			$content .=$this->cObj->substituteMarkerArrayCached($template['next'], $marker_next);
			
		}
		
		return $content;
	}


	/**
	 * build_navigation function.
	 *
	 * @access public
	 * @param mixed   $link
	 * @param mixed   $label
	 * @param mixed   $class
	 * @return void
	 */
	function build_navigation($link, $label, $class) {

		$content = "";
		$marker = array();

		$template['total'] = $this->cObj->getSubpart($this->templateCode, '###NAVIGATIONVIEW###');
		$marker['###LINKTOPAGE###'] = $link;
		$marker['###NAVIGATIONCLASS###'] = $class;
		$marker['###NAVIGATIONLABEL###'] = $label;
		$content .=$this->cObj->substituteMarkerArrayCached($template['total'], $marker);

		return $content;
	}





	/**
	 * _createLinkURLSingle function.
	 * 
	 * @access private
	 * @param mixed $img_ID
	 * @return void
	 */
	function _createLinkURLSingle($img_ID,$anchorid,$parameter,$page) {
	
		$typolink_conf = array(
			'parameter' => $parameter,
			'useCacheHash' => true,
			'additionalParams' => '&tx_flickrset_pi1[page]='.$page.
			'&tx_flickrset_pi1[singlefotoid]='.$img_ID.
			'&tx_flickrset_pi1[anchorid]='.$anchorid
			,
		);
		return $this->cObj->typolink_URL($typolink_conf);
	}
	



	/**
	 * _createLinkURLList function.
	 * 
	 * @access private
	 * @return void
	 */
	function _createLinkURLList() {
		$typolink_conf = array(
			'parameter' => $this->conf['listpageid'],
			'useCacheHash' => true,
			'additionalParams' => '&tx_flickrset_pi1[page]='.$this->piVars['page']
			,
		);

		return $this->cObj->typolink_URL($typolink_conf);
	}
	
		function _createLinkURLSET($setid) {
		$typolink_conf = array(
			'parameter' => $this->conf['listpageid'],
			'useCacheHash' => true,
			'additionalParams' => '&tx_flickrset_pi1[fotoset]='.$setid
			,
		);

		return $this->cObj->typolink_URL($typolink_conf);
	}



}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/flickrset/pi1/class.tx_flickrset_pi1.php']) {
	include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/flickrset/pi1/class.tx_flickrset_pi1.php'];
}

?>