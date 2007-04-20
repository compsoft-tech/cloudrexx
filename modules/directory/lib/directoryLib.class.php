<?php
/**
 * Directory library
 * @copyright   CONTREXX CMS - ASTALAVISTA IT AG
 * @author        Astalavista Development Team <thun@astalavista.ch>
 * @package     contrexx
 * @subpackage  module_directory
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Includes
 */
require_once ASCMS_LIBRARY_PATH . '/FRAMEWORK/File.class.php';
require_once ASCMS_MODULE_PATH . '/directory/lib/xmlfeed.class.php';

/**
 * Directory library
 *
 * External functions for the directory
 * @copyright   CONTREXX CMS - ASTALAVISTA IT AG
 * @author        Astalavista Development Team <thun@astalavista.ch>
 * @package     contrexx
 * @subpackage  module_directory
 */
class directoryLibrary
{
	var $path;
	var $fileSize;
	var $webPath;
	var $imagePath;
	var $imageWebPath;
	var $dirLog;
	var $mediaPath;
	var $mediaWebPath;
	var $rssLatestTitle = "Directory News";
	var $rssLatestDescription =  "The latest Directory entries";
	var $categories = array();


	/**
    * Constructor
    *
    * @access   public
    */
	function directoryLibrary()
	{
	    //nothing...
	}



	function checkPopular()
	{
		global $_CONFIG, $objDatabase;

		//get popular days
		$populardays = $settings['populardays']['value'];

		//get startdate
		$objResult = $objDatabase->Execute("SELECT popular_date FROM ".DBPREFIX."module_directory_dir LIMIT 1");

		if($objResult !== false){
			while(!$objResult->EOF){
				$startdate = $objResult->fields['popular_date'];
				$objResult->MoveNext();
			}
		}

		$today = mktime(0, 0, 0, date("m")  , date("d"), date("Y"));

		$tempDays 	= date("d",$startdate);
		$tempMonth 	= date("m",$startdate);
		$tempYear 	= date("Y",$startdate);

		$enddate  = mktime(0, 0, 0, $tempMonth, $tempDays+$populardays,  $tempYear);

		if($today >= $enddate){
			$this->restorePopular();
		}
	}



	function restorePopular()
	{
		global $_CONFIG, $objDatabase;

		$date = mktime(0, 0, 0, date("m")  , date("d"), date("Y"));
		$objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_dir SET popular_hits='0', popular_date='".$date."'");
	}


	/**
    * get hits
    *
    * get hits
    *
    * @access   public
    * @param    string  $id
    * @global	object	$objDatabase
	* @global	array	$_ARRAYLANG
    */
	function getHits($id)
	{
		global $objDatabase, $_ARRAYLANG;

		//get feed data
		$objResult = $objDatabase->Execute("SELECT  hits, lastip, popular_hits FROM ".DBPREFIX."module_directory_dir WHERE status = '1' AND id = '".contrexx_addslashes($id)."'");

		if($objResult !== false){
			while(!$objResult->EOF){
				$hits = $objResult->fields['hits'];
				$popular_hits = $objResult->fields['popular_hits'];
				$lastip = $objResult->fields['lastip'];
				$objResult->MoveNext();
			}
		}

		$hits++;
		$popular_hits++;
		$ip = $_SERVER['REMOTE_ADDR'];

		//update hits
		if($lastip != $ip){
			$objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_dir SET
	                hits='".$hits."', popular_hits='".$popular_hits."', lastip='".$ip."' WHERE id='".contrexx_addslashes($id)."'");
		}
	}


	/**
    * get categories
    *
    * get added categories
    *
    * @access   public
    * @param	string	$catId
    * @return	string	$options
    * @global	object	$objInit
	* @global	object	$template
	* @global	array	$_CORELANG
    */
	function getSearchCategories($catId)
	{
		global $_CONFIG, $objDatabase, $_CORELANG;

		//get all categories
		$objResultCat = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_directory_categories ORDER BY displayorder");

		if($objResultCat !== false){
			while(!$objResultCat->EOF){
				$this->getCategories['name'][$objResultCat->fields['id']]			=$objResultCat->fields['name'];
				$this->getCategories['parentid'][$objResultCat->fields['id']]		=$objResultCat->fields['parentid'];
				$objResultCat->MoveNext();
			}
		}


		$options = "";

		//make categories dropdown
		if (!empty($this->getCategories['name'])) {
			foreach($this->getCategories['name'] as $catKey => $catName){
				$checked = "";
				if($this->getCategories['parentid'][$catKey] == 0){
					if ($catKey==$catId){
						$checked = "selected";
					}
					$options .= "<option value='".$catKey."' ".$checked.">".$catName."</option>";

					//get subcategories
					$options .=$this->getSearchSubcategories($catName, $catKey, $catId);
				}
			}
		}

		return $options;
	}




	/**
    * get subcategories
    *
    * get added subcategories
    *
    * @access   public
    * @param	string	$catName
    * @param	string	$parentId
    * @param	string	$catId
    * @return	string	$options
    */
	function getSearchSubcategories($catName, $parentId, $catId)
	{
		$category = $catName;
		$subOptions = "";

		//get subcategories
		foreach($this->getCategories['name'] as $catKey => $catName){
			if($this->getCategories['parentid'][$catKey] == $parentId){
				$checked = "";
				if ($catKey==$catId){
					$checked = "selected";
				}

				$subOptions .= "<option value='".$catKey."' ".$checked.">".$category." >> ".$catName."</option>";

				//get more subcategories
				$subOptions .= $this->getSearchSubcategories($category." >> ".$catName, $catKey, $catId);
			}
		}

		return $subOptions;
	}


	/**
    * get categories
    *
    * get added categories
    *
    * @access   public
    * @param	string	$catId
    * @return	string	$options
    * @global	object	$objInit
	* @global	object	$template
	* @global	array	$_CORELANG
    */
	function getCategories($id, $type)
	{
		global $_CONFIG, $objDatabase, $_CORELANG;

		//get selected levels
		$objResultCat = $objDatabase->Execute("SELECT cat_id FROM ".DBPREFIX."module_directory_rel_dir_cat WHERE dir_id='".$id."'");
		if($objResultCat !== false){
			while(!$objResultCat->EOF){
				$this->categories[$x] = $objResultCat->fields['cat_id'];
				$x++;
				$objResultCat->MoveNext();
			}
		}


		//get all categories
		$objResultCat = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_directory_categories ORDER BY displayorder");

		if($objResultCat !== false){
			while(!$objResultCat->EOF){
				$this->getCategories['name'][$objResultCat->fields['id']]			=$objResultCat->fields['name'];
				$this->getCategories['parentid'][$objResultCat->fields['id']]		=$objResultCat->fields['parentid'];
				$objResultCat->MoveNext();
			}
		}


		$options = "";

		//make categories dropdown
		foreach($this->getCategories['name'] as $catKey => $catName){
			if($this->getCategories['parentid'][$catKey] == 0){
				if($type == 1){
					if (!in_array($catKey, $this->categories)){
						$options .= "<option value='".$catKey."'>".$catName."</option>";
					}
				}else{
					if (in_array($catKey, $this->categories)){
						$options .= "<option value='".$catKey."'>".$catName."</option>";
					}
				}

				//get subcategories
				$options .=$this->getSubcategories($catName, $catKey, '&nbsp;&nbsp;&nbsp;', $type);
			}
		}

		return $options;
	}




	/**
    * get subcategories
    *
    * get added subcategories
    *
    * @access   public
    * @param	string	$catName
    * @param	string	$parentId
    * @param	string	$catId
    * @return	string	$options
    */
	function getSubcategories($catName, $parentId, $spacer, $type)
	{
		//get subcategories
		foreach($this->getCategories['name'] as $catKey => $catName){
			if($this->getCategories['parentid'][$catKey] == $parentId){

				if($type == 1){
					if (!in_array($catKey, $this->categories)){
						$options .= "<option value='".$catKey."' >".$spacer.$catName."</option>";
					}
				}else{
					if (in_array($catKey, $this->categories)){
						$options .= "<option value='".$catKey."' >".$catName."</option>";
					}
				}

				//get more subcategories
				$options .= $this->getSubcategories($catName, $catKey, $spacer.'&nbsp;&nbsp;&nbsp;', $type);
			}
		}

		return $options;
	}


	/**
    * get languages
    *
    * get all languages
    *
    * @access   public
    * @param	string	$langId
    * @return	string	$languages
    * @global	object	$objInit
	* @global	object	$template
	* @global	array	$_CORELANG
    */
	function getLanguages($langId)
	{
		global $_CONFIG, $objDatabase, $_CORELANG;

		//get all languages
		$objResultLang = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_directory_settings WHERE setname = 'language'");

		if($objResultLang !== false){
			while(!$objResultLang->EOF){
				$languages 			= $objResultLang->fields['setvalue'];
				$objResultLang->MoveNext();
			}
		}

		//explode languages
		$this->getLanguage=explode(",", $languages);

		//make languages dropdown
		foreach($this->getLanguage as $langKey => $langName){
			$checked = "";
			if ($langName==$langId) {
				$checked ="selected";
			}
			$languages .= "<option value='".$langName."' $checked>".$langName."</option>";
		}

		return $languages;
	}



	function getCantons($cantId)
	{
		global $_CONFIG, $objDatabase, $_CORELANG;

		//get all languages
		$objResult = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_directory_settings WHERE setname = 'canton'");
		if($objResult !== false){
			while(!$objResult->EOF){
				$cantons 			= $objResult->fields['setvalue'];
				$objResult->MoveNext();
			}
		}

		//explode languages
		$this->getCantons=explode(",", $cantons);

		//make languages dropdown
		foreach($this->getCantons as $cantKey => $cantName){
			$checked = "";
			if ($cantName==$cantId) {
				$checked ="selected";
			}
			$cantons .= "<option value='".$cantName."' $checked>".$cantName."</option>";
		}

		return $cantons;
	}



	/**
	 * Enter description here...
	 *
	 * @param unknown_type $spezId
	 * @return unknown
	 */
	function getSpezDropdown($spezId, $spezField)
	{
		global $_CONFIG, $objDatabase, $_CORELANG;

		//get all languages
		$objResult = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_directory_settings WHERE setname = '".$spezField."'");
		if($objResult !== false){
			while(!$objResult->EOF){
				$spez 			= $objResult->fields['setvalue'];
				$objResult->MoveNext();
			}
		}

		//explode languages
		$tmpArray =explode(",", $spez);

		//make languages dropdown
		$spezial = "";
		foreach($tmpArray as $spezKey => $spezName){
			$checked = "";
			if ($spezName==$spezId) {
				$checked ="selected";
			}
			$spezial .= "<option value='".$spezName."' $checked>".$spezName."</option>";
		}

		return $spezial;
	}


	/**
	 * Enter description here...
	 *
	 * @param unknown_type $spezId
	 * @return unknown
	 */
	function getSpezVotes($spezId, $spezField)
	{
		global $_CONFIG, $objDatabase, $_CORELANG;

		//get all languages
		$objResult = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_directory_settings WHERE setname = '".$spezField."'");
		if($objResult !== false){
			while(!$objResult->EOF){
				$spez 			= $objResult->fields['setvalue'];
				$objResult->MoveNext();
			}
		}

		//explode languages
		$tmpArray=explode(",", $spez);

		//make languages dropdown
		$spezial = "";
		$i = 0;
		foreach($tmpArray as $spezKey => $spezName){
			$checked = "";
			$value = "";
			if ($spezId==$i) {
				$checked ="selected";
			}

			if ($i==0 && $spezName=="") {
				$value = "";
			} else {
				$value = $i;
			}

			$spezial .= "<option value='".$value."' $checked>".$spezName."</option>";
			$i++;
		}

		return $spezial;
	}


	/**
    * get platforms
    *
    * get all platforms
    *
    * @access   public
    * @param	string	$osId
    * @return	string	$platforms
    * @global	object	$objInit
	* @global	object	$template
	* @global	array	$_CORELANG
    */
	function getPlatforms($osId)
	{
		global $_CONFIG, $objDatabase, $_CORELANG;

		//get all plattforms
		$objResultPlat = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_directory_settings WHERE setname = 'platform'");
		if($objResultPlat !== false){
			while(!$objResultPlat->EOF){
				$platforms 			= $objResultPlat->fields['setvalue'];
				$objResultPlat->MoveNext();
			}
		}

		//explode platforms
		$this->getPlatforms=explode(",", $platforms);

		//make platforms dropdown
		foreach($this->getPlatforms as $osKey => $osName){
			$checked = "";
			if ($osName==$osId) {
				$checked ="selected";
			}
			$platforms .= "<option value='".$osName."' $checked>".$osName."</option>";
		}

		return $platforms;
	}



	/**
    * upload media
    *
    * upload added media
    *
    * @access   public
    * @return   string  $fileName
	* @global	array	$_CORELANG
    */
	function uploadMedia($name, $path)
    {
        global $_CORELANG;

        //check file array
        if(isset($_FILES) && !empty($_FILES))
        {
        	//get file info
        	$status        	= "";
        	$tmpFile  		= $_FILES[$name]['tmp_name'];
           	$fileName 		= $_FILES[$name]['name'];
			$fileType 		= $_FILES[$name]['type'];
           	$this->fileSize = $_FILES[$name]['size'];

           	if($fileName != ""){
				//check extension
           		$info     = pathinfo($fileName);
				$exte     = $info['extension'];
				$exte     = (!empty($exte)) ? '.' . $exte : '';
				$part1    = substr($fileName, 0, strlen($fileName) - strlen($exte));
				$rand	  = rand(10, 99);
				$fileName = md5($rand.$part1).$exte;

				//check file
				if(file_exists($this->mediaPath.$path.$fileName)){
					$fileName = $rand.$part1 . '_' . (time() + $x) . $exte;
					$fileName = md5($fileName).$exte;
				}

				//upload file
				if(@move_uploaded_file($tmpFile, $this->mediaPath.$path.$fileName)) {
					$obj_file = new File();
                    $obj_file->setChmod($this->mediaPath, $this->mediaWebPath, $path.$fileName);
					$status = $fileName;
				}else{
					$status = "error";
				}

				//make thumb
				if(($fileType == "image/gif" || $fileType == "image/jpeg" || $fileType == "image/jpg" || $fileType == "image/png") && $path != "uploads/") {
					$this->createThumb($fileName, $path);
				}

			}else {
				$status = "error";
			}
        }

        return $status;
    }



    /**
    * create thumb
    *
    * Create an Thumbnail Image
    *
    * @param     string        $fileName: The name of the image
    */
    function createThumb($fileName, $filePath)
    {
        //copy image

        $oldFile		= $this->mediaPath.$filePath.$fileName;
        $newFile		= $this->mediaPath."thumbs/".$fileName;
        $arrSettings 	= $this->getSettings();
        $arrInfo    	= getimagesize($oldFile); //ermittelt die Gr��e des Bildes
        $setSize		= $arrSettings['thumbSize']['value'];
        $strType    	= $arrInfo[2]; //type des Bildes

        if ($arrInfo[0] >= $setSize || $arrInfo[1] >= $setSize) {
			if($arrInfo[0] <= $arrInfo[1]){
				$intFactor	= $arrInfo[1]/$setSize;
				$intHeight 	= $setSize;
				$intWidth 	= $arrInfo[0]/$intFactor;
			}else{
				$intFactor = $arrInfo[0]/$setSize;
				$intResult = $arrInfo[1]/$intFactor;
				if($intResult > $setSize){
					$intHeight 	= $setSize;
					$intWidth	= $arrInfo[0]/$intFactor;
				}else{
					$intWidth = $setSize;
					$intHeight	= $arrInfo[1]/$intFactor;
				}
			}
		} else {
			$intWidth 	= $arrInfo[0];
        	$intHeight 	= $arrInfo[1];
		}

		if (imagetypes() & IMG_GIF) {
			$boolGifEnabled = true;
		}

		if (imagetypes() &  IMG_JPG) {
			$boolJpgEnabled = true;
		}

		if (imagetypes() & IMG_PNG) {
			$boolPngEnabled = true;
		}

        @touch($newFile);



        switch ($strType)
        {
            case 1: //GIF
                if($boolGifEnabled){
                    $handleImage1 = ImageCreateFromGIF($oldFile);
                    $handleImage2 = @ImageCreateTrueColor($intWidth,$intHeight);
                    ImageCopyResampled($handleImage2, $handleImage1,0,0,0,0,$intWidth,$intHeight, $arrInfo[0],$arrInfo[1]);
                    ImageGIF($handleImage2, $newFile);

                    ImageDestroy($handleImage1);
                    ImageDestroy($handleImage2);
                }
            break;
            case 2: //JPG
                if($boolJpgEnabled){
                    $handleImage1 = ImageCreateFromJpeg($oldFile);
                    $handleImage2 = @ImageCreateTrueColor($intWidth,$intHeight);
                    ImageCopyResampled($handleImage2, $handleImage1,0,0,0,0,$intWidth,$intHeight, $arrInfo[0],$arrInfo[1]);
                    ImageJpeg($handleImage2, $newFile, 95);

                    ImageDestroy($handleImage1);
                    ImageDestroy($handleImage2);
                }
            break;
            case 3: //PNG
                if($boolPngEnabled){
                    $handleImage1 = ImageCreateFromPNG($oldFile);
                    ImageAlphaBlending($handleImage1, true);
                    ImageSaveAlpha($handleImage1, true);
                    $handleImage2 = @ImageCreateTrueColor($intWidth,$intHeight);
                    ImageCopyResampled($handleImage2, $handleImage1,0,0,0,0,$intWidth,$intHeight, $arrInfo[0],$arrInfo[1]);
                    ImagePNG($handleImage2, $newFile);

                    ImageDestroy($handleImage1);
                    ImageDestroy($handleImage2);
                }
            break;
        }
    }



    /**
    * create xml
    *
    * create xml
    *
    * @access   public
    * @param    string  $link
    * @return   string  $filename
    */
    function createXML ($link)
    {
    	//copy
    	$time = time();
		$filename = "feed_".$time."_".basename($link).".xml";


		if(!@copy($link, $this->mediaPath."ext_feeds/".$filename)){
			return "error";
        }else{
			//rss class
			$rss =& new XML_RSS($this->mediaPath."ext_feeds/".$filename);
			$rss->parse();
			$content = '';
			foreach($rss->getStructure() as $array)
			{
				$content .= $array;
			}

			//set chmod
			$obj_file = &new File();
        	$obj_file->setChmod($this->mediaPath, $this->mediaWebPath, "ext_feeds/".$filename);

			if($content == ''){
				//del xml
				@unlink($this->mediaPath."ext_feeds/".$filename);
				return "error";
			}else{
				return $filename;
			}
        }
    }



    /**
    * refresh xml
    *
    * refresh ex. xml
    *
    * @access   public
    * @param    string  $id
    * @return   string  $filename
    * @global   object  $objDatabase
    * @global   array   $_ARRAYLANG
    */
    function refreshXML($id)
	{
		global $objDatabase, $_ARRAYLANG;

		//get filename
		$objResult = $objDatabase->Execute("SELECT  id, filename, link FROM ".DBPREFIX."module_directory_dir WHERE status = '1' AND id = '".contrexx_addslashes($id)."'");

		if($objResult !== false){
			while(!$objResult->EOF){
				$hits 		= $objResult->fields['hits'];
				$filename 	= $objResult->fields['filename'];
				$link 		= $objResult->fields['link'];
				$objResult->MoveNext();
			}
		}

		//del old
		if(file_exists($this->mediaPath."ext_feeds/".$filename)){
			@unlink($this->mediaPath."ext_feeds/".$filename);
		}

		//copy
		if(!copy($link, $this->mediaPath."ext_feeds/".$filename))
		{
		    $this->statusMessage = $_ARRAYLANG['DIRECTORY_NO_NEWS'];
		    die;
        }

		//rss class
		$rss =& new XML_RSS($this->mediaPath."ext_feeds/".$filename);
		$rss->parse();
		$content = '';
		foreach($rss->getStructure() as $array)
		{
			$content .= $array;
		}

		$objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_dir SET xml_refresh='".mktime(date("G"),  date("i"), date("s"), date("m"), date("d"), date("Y"))."' WHERE id='".$id."'");

		if($content == '')
		{
			unlink($this->mediaPath."ext_feeds/".$filename);
		}
	}



	/**
    * add feed
    *
    * add new feed
    *
    * @access   public
    * @global   object  $objDatabase
    * @global   array   $_ARRAYLANG
    */
	function addFeed()
	{
		global $objDatabase, $_ARRAYLANG;

		$arrSettings = $this->getSettings();

		//check file type
		if($_POST['type'] == "rss"){
			$link 		= $_POST['rssname'];
			$file 		= $this->createXML($link);
			$msg_error 	= $_ARRAYLANG['DIRECTORY_NO_RSS'];
			$checksum 	= "";
		}elseif($_POST['type'] == "file"){
			$file 		= $this->uploadMedia("fileName", "uploads/");
			$link 		= $_FILES['fileName']['name'];
			$checksum 	= md5_file($this->mediaPath."uploads/".$file);
			$msg_error 	= $_ARRAYLANG['DIRECTORY_UPLOAD_FAILED'];
		}elseif($_POST['type'] == "link"){
			if($_POST['linkname'] == "http://"){
				$msg_error 	= $_ARRAYLANG['DIRECTORY_NO_LINK'];
				$file 		= "error";
			}else{
				$link 		= $_POST['linkname'];
				if(substr($link, 0,7) != "http://"){
					$link = "http://".$link;
				}
				$checksum 	= "";
				$file 		= "";
			}
		}



		//get post data
		if($file != "error"){

			$query = "INSERT INTO ".DBPREFIX."module_directory_dir SET ";

			foreach($_POST["inputValue"] as $inputName => $inputValue){

				//check links
				if($inputName == "relatedlinks" || $inputName == "homepage"){
					if(substr($inputValue, 0,7) != "http://" && $inputValue != ""){
						$inputValue = "http://".$inputValue;
					}
				}

				//upload spez pics
				if ($inputName == "logo" ||
					$inputName == "lokal" ||
					$inputName == "map" ||
					$inputName == "spez_field_11" ||
					$inputName == "spez_field_12" ||
					$inputName == "spez_field_13" ||
					$inputName == "spez_field_14" ||
					$inputName == "spez_field_15" ||
					$inputName == "spez_field_16" ||
					$inputName == "spez_field_17" ||
					$inputName == "spez_field_18" ||
					$inputName == "spez_field_19" ||
					$inputName == "spez_field_20"){

					$inputValue = $this->uploadMedia($inputName, "images/");

					if($inputValue == "error"){
						$inputValue = "";
					}
				}

				//upload spez files
				if ($inputName == "spez_field_25" ||
					$inputName == "spez_field_26" ||
					$inputName == "spez_field_27" ||
					$inputName == "spez_field_28" ||
					$inputName == "spez_field_29"){

					$inputValue = $this->uploadMedia($inputName, "uploads/");

					if($inputValue == "error"){
						$inputValue = "";
					}
				}

				//get author id
				if($inputName == "addedby"){
					if(isset($_SESSION['auth']['userid'])){
						$inputValue = $_SESSION['auth']['userid'];
					}else{
						$inputValue = $inputValue;
					}
				}

				$query .= contrexx_strip_tags($inputName)." ='".contrexx_addslashes($inputValue)."', ";
			}

			//get status settings
			$objResult = $objDatabase->Execute("SELECT setvalue FROM ".DBPREFIX."module_directory_settings WHERE setname = 'status' LIMIT 1");

			if($objResult !== false){
				while(!$objResult->EOF){
					$entryStatus = $objResult->fields['setvalue'];
					$objResult->MoveNext();
				}
			}

			$query .= " checksum='".$checksum."', filename='".contrexx_strip_tags($file)."', link='".contrexx_strip_tags($link)."', date='".mktime(date("G"),  date("i"), date("s"), date("m"), date("d"), date("Y"))."', size='".$this->fileSize."', typ='".contrexx_strip_tags($_POST['type'])."', status='".contrexx_strip_tags($entryStatus)."', provider='".gethostbyaddr($_SERVER['REMOTE_ADDR'])."', ip='".$_SERVER['REMOTE_ADDR']."',  validatedate='".mktime(date("G"),  date("i"), date("s"), date("m"), date("d"), date("Y"))."', xml_refresh='".mktime("now")."'";

			//add entry
			$objResult = $objDatabase->query($query);

			if($objResult !== false){
				$id = $objDatabase->insert_ID();

				foreach($_POST["selectedCat"] as $inputName => $inputValue){
					$query = "INSERT INTO ".DBPREFIX."module_directory_rel_dir_cat SET dir_id='".$id."', cat_id='".$inputValue."'";
					$objDatabase->query($query);
				}

				foreach($_POST["selectedLevel"] as $inputName => $inputValue){
					$query = "INSERT INTO ".DBPREFIX."module_directory_rel_dir_level SET dir_id='".$id."', level_id='".$inputValue."'";
					$objDatabase->query($query);
				}

				if($entryStatus == 1){
					$this->confirmEntry_step2($id);
				}

				$this->strOkMessage = "Eintrag".$entryName." erfolgreich erstellt.";
				$status = $id;

				$this->createRSS();
			}
		}else{
			 $status = 'error';
			 $this->strErrMessage = $msg_error;
		}

		return $status;
	}


		/**
    * confirm entry
    *
    * confirm selected entry
    *
    * @access   public
    * @param	string	$id
    * @global	object	$objDatabase
	* @global	array	$_CORELANG
	* @global	array	$_CONFIG
    */
	function confirmEntry_step2($id)
	{
		global $_CONFIG, $objDatabase, $_ARRAYLANG;

		$entryId = $id;

		//update popular
		$objResult = $objDatabase->Execute("SELECT popular_date FROM ".DBPREFIX."module_directory_dir LIMIT 1");

		if($objResult !== false){
			while(!$objResult->EOF){
				$date = $objResult->fields['popular_date'];
				$objResult->MoveNext();
			}
		}

		if($date == ""){
			$date = mktime(date("G"),  date("i"), date("s"), date("m"), date("d"), date("Y"));
		}

		//confirm entry
		$query = "UPDATE ".DBPREFIX."module_directory_dir SET ";
		$query .= "validatedate='".mktime(date("G"),  date("i"), date("s"), date("m"), date("d"), date("Y"))."', popular_date='".$date."', status ='1'  WHERE id='".contrexx_addslashes($id)."'";

		//add entry
		$objResult = $objDatabase->Execute($query);
		if($objResult !== false){
			//send mail
			$this->sendMail($id, '');

			//update xml
			$this->createRSS();
			$this->strOkMessage = $_ARRAYLANG['TXT_FEED_SUCCESSFULL_CONFIRM'];
		}else{
			$this->strErrMessage = $_ARRAYLANG['TXT_FEED_CORRUPT_CONFIRM'];
		}
	}



	function sendMail($id, $email)
	{
		global $_CONFIG, $objDatabase, $_ARRAYLANG;

		$feedId = contrexx_addslashes($id);

		//get user id
		$objResult = $objDatabase->Execute("SELECT addedby, title FROM ".DBPREFIX."module_directory_dir WHERE id='".$feedId."' LIMIT 1");
	    if ($objResult !== false) {
			while (!$objResult->EOF) {
				$userId			= $objResult->fields['addedby'];
				$feedTitle		= $objResult->fields['title'];
				$objResult->MoveNext();
			};
		}

		//get user data
		if(is_numeric($userId)){
			$objResult = $objDatabase->Execute("SELECT email, firstname, lastname, username FROM ".DBPREFIX."access_users WHERE id='".$userId."' LIMIT 1");
		    if ($objResult !== false) {
				while (!$objResult->EOF) {
					$userMail			= $objResult->fields['email'];
					$userFirstname		= $objResult->fields['firstname'];
					$userLastname		= $objResult->fields['lastname'];
					$userUsername		= $objResult->fields['username'];
					$objResult->MoveNext();
				};
			}

			if(!empty($email)){
				$to         = $email;
				$sendTo		= $email;
				$mailId 	= 2;
			}else{
				$to         = $userMail;
				$sendTo		= $userMail;
				$mailId 	= 1;
			}

			//get mail content n title
			$objResult = $objDatabase->Execute("SELECT title, content FROM ".DBPREFIX."module_directory_mail WHERE id='".$mailId."' LIMIT 1");
		    if ($objResult !== false) {
				while (!$objResult->EOF) {
					$mailTitle		= $objResult->fields['title'];
					$mailContent	= $objResult->fields['content'];
					$objResult->MoveNext();
				};
			}

			$url	= $_SERVER['SERVER_NAME'].ASCMS_PATH_OFFSET;;
			$link	= "http://".$url."/index.php?section=directory&cmd=detail&id=".$feedId;
			$now 	= date(ASCMS_DATE_FORMAT);

			//replase placeholder
			$array_1 = array('[[USERNAME]]', '[[FIRSTNAME]]', '[[LASTNAME]]', '[[TITLE]]', '[[LINK]]', '[[URL]]', '[[DATE]]');
			$array_2 = array($userUsername, $userFirstname, $userLastname, $feedTitle, $link, $url, $now);

			for($x = 0; $x < 7; $x++){
			  $mailTitle = str_replace($array_1[$x], $array_2[$x], $mailTitle);
			}

			for($x = 0; $x < 7; $x++){
			  $mailContent = str_replace($array_1[$x], $array_2[$x], $mailContent);
			}

			//create mail
			$subject 	= $mailTitle;

		   // Email message
		    $message  	= $mailContent;

		    $sendTo 	= explode(';', $sendTo);

			if (@include_once ASCMS_LIBRARY_PATH.'/phpmailer/class.phpmailer.php') {
				$objMail = new phpmailer();

				if ($_CONFIG['coreSmtpServer'] > 0 && @include_once ASCMS_CORE_PATH.'/SmtpSettings.class.php') {
					$objSmtpSettings = new SmtpSettings();
					if (($arrSmtp = $objSmtpSettings->getSmtpAccount($_CONFIG['coreSmtpServer'])) !== false) {
						$objMail->IsSMTP();
						$objMail->Host = $arrSmtp['hostname'];
						$objMail->Port = $arrSmtp['port'];
						$objMail->SMTPAuth = true;
						$objMail->Username = $arrSmtp['username'];
						$objMail->Password = $arrSmtp['password'];
					}
				}

				$objMail->CharSet = CONTREXX_CHARSET;
				$objMail->From = $_CONFIG['coreAdminEmail'];
				$objMail->FromName = $_CONFIG['coreAdminName'];
				$objMail->AddReplyTo($_CONFIG['coreAdminEmail']);
				$objMail->Subject = $subject;
				$objMail->IsHTML(false);
				$objMail->Body = $message;

				foreach($sendTo as $x => $mailAdress){
					$objMail->AddAddress($mailAdress);
					$objMail->Send();
					$objMail->ClearAddresses();
				}
			}
		}
	}


	/**
    * create rss
    *
    * create new xml
    *
    * @access   public
    * @param    string  $id
    * @global   object  $objDatabase
    * @global   array   $_CORELANG
    * @global   array   $_CONFIG
    */
	function createRSS()
    {
		//crate latest xml
		$this->createRSSlatest();
    }



    /**
    * create rss latest
    *
    * create new xml latest
    *
    * @access   public
    * @global   object  $objDatabase
    * @global   array   $_CONFIG
    */
    function createRSSlatest()
    {
    	global $_CONFIG, $objDatabase;

    	//check file
    	$obj_file = new File();
		if(file_exists($this->mediaPath."feeds/directory_latest.xml")){
    		$obj_file->delFile($this->mediaPath, $this->mediaWebPath, "media/feeds/directory_latest.xml");
		}

		$query = "SELECT * FROM ".DBPREFIX."module_directory_settings WHERE setname='latest_xml'";
		$objResult = $objDatabase->Execute($query);
		if ($objResult !== false) {
			while (!$objResult->EOF) {
				$limit 	= $objResult->fields['setvalue'];
				$objResult->MoveNext();
			}
		}


    	if($this->dirLog != "error"){
    		//create xml
    		$objRSS = &new rssFeed(0);
			$objRSS->channelTitle = $this->rssLatestTitle;
			$objRSS->channelDescription = $this->rssLatestDescription;
			$objRSS->channelWebmaster = $_CONFIG['coreAdminEmail'];
			$objRSS->newsLimit = $limit;
			$objRSS->channelLink = ASCMS_PROTOCOL."://".$_SERVER['SERVER_NAME']."/index.php?section=directory";

			$objRSS->create();
    	}
    }



    function getAuthor($id)
    {
    	global $objDatabase, $_ARRAYLANG;

    	$userId = contrexx_addslashes($id);

    	if(is_numeric($userId)){
			$objResultauthor = $objDatabase->Execute("SELECT id, username FROM ".DBPREFIX."access_users WHERE id = '".$userId."'");
		    if ($objResultauthor !== false) {
				while (!$objResultauthor->EOF) {
					$author = $objResultauthor->fields['username'];
					$objResultauthor->MoveNext();
				}
			}
		}else{
			$author = $userId;
		}

	    return $author;
    }


    function getAuthorID($author)
    {
    	global $objDatabase, $_ARRAYLANG;

    	$objResultauthor = $objDatabase->Execute("SELECT id, username FROM ".DBPREFIX."access_users WHERE username = '".contrexx_addslashes($author)."'");
	    if ($objResultauthor !== false) {
			while (!$objResultauthor->EOF) {
				$author = $objResultauthor->fields['id'];
				$objResultauthor->MoveNext();
			}
		}

	    return $author;
    }



    function getInputfields($addedby, $action, $id, $area)
    {
    	global $objDatabase, $_ARRAYLANG;

    	//get settings
		$i=1;
      	$width= "300";

		$this->_objTpl->setCurrentBlock('inputfieldsOutput');
		$arrInputfieldsActive = array();
		$arrInputfieldsValue = array();
		$arrSettings = $this->getSettings();

		if($area == "backend"){
			$where = "active_backend='1'";
		}elseif($area == "frontend"){
			$where = "active='1'";
		}

		//get inputfields
		if($action == "add"){
			//get plattforms and languages and user
			$arrInputfieldsValue['language'] 			= $this->getLanguages($_POST['inputValue']['language']);
			$arrInputfieldsValue['platform'] 			= $this->getPlatforms($_POST['inputValue']['platform']);
			$arrInputfieldsValue['canton'] 				= $this->getCantons($_POST['inputValue']['canton']);
			$arrInputfieldsValue['spez_field_21'] 		= $this->getSpezDropdown($_POST['inputValue']['spez_field_21'], 'spez_field_21');
			$arrInputfieldsValue['spez_field_22'] 		= $this->getSpezDropdown($_POST['inputValue']['spez_field_22'], 'spez_field_22');
			$arrInputfieldsValue['spez_field_23'] 		= $this->getSpezVotes($_POST['inputValue']['spez_field_23'], 'spez_field_23');
			$arrInputfieldsValue['spez_field_24'] 		= $this->getSpezVotes($_POST['inputValue']['spez_field_24'], 'spez_field_24');
			$arrInputfieldsValue['addedby']				= $addedby;
		}elseif ($action == "edit" || $action == "confirm"){
			//get file data
			$objResult = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_directory_dir WHERE id = ".intval($id));
			if($objResult !== false){
				while(!$objResult->EOF){
					$arrInputfieldsValue['id'] 					= $objResult->fields['id'];
					$arrInputfieldsValue['title'] 				= $objResult->fields['title'];
					$arrInputfieldsValue['filename']	 		= $objResult->fields['filename'];
					$arrInputfieldsValue['date']	 			= $objResult->fields['date'];
					$arrInputfieldsValue['description'] 		= $objResult->fields['description'];
					$arrInputfieldsValue['relatedlinks']		= $objResult->fields['relatedlinks'];
					$arrInputfieldsValue['status'] 				= $objResult->fields['status'];
					$arrInputfieldsValue['addedby'] 			= $objResult->fields['addedby'];
					$arrInputfieldsValue['provider'] 			= $objResult->fields['provider'];
					$arrInputfieldsValue['ip'] 					= $objResult->fields['ip'];
					$arrInputfieldsValue['validatedate'] 		= $objResult->fields['validatedate'];
					$arrInputfieldsValue['size'] 				= $objResult->fields['size'];
					$arrInputfieldsValue['link'] 				= $objResult->fields['link'];
					$arrInputfieldsValue['typ'] 				= $objResult->fields['typ'];
					$arrInputfieldsValue['platform'] 			= $objResult->fields['platform'];
					$arrInputfieldsValue['language'] 			= $objResult->fields['language'];
					$arrInputfieldsValue['canton'] 				= $objResult->fields['canton'];
					$arrInputfieldsValue['searchkeys'] 			= $objResult->fields['searchkeys'];
					$arrInputfieldsValue['company_name'] 		= $objResult->fields['company_name'];
					$arrInputfieldsValue['street'] 				= $objResult->fields['street'];
					$arrInputfieldsValue['zip'] 				= $objResult->fields['zip'];
					$arrInputfieldsValue['phone'] 				= $objResult->fields['phone'];
					$arrInputfieldsValue['contact'] 			= $objResult->fields['contact'];
					$arrInputfieldsValue['hits'] 				= $objResult->fields['hits'];
					$arrInputfieldsValue['xml_refresh'] 		= $objResult->fields['xml_refresh'];
					$arrInputfieldsValue['checksum'] 			= $objResult->fields['checksum'];
					$arrInputfieldsValue['city'] 				= $objResult->fields['city'];
					$arrInputfieldsValue['information'] 		= $objResult->fields['information'];
					$arrInputfieldsValue['fax'] 				= $objResult->fields['fax'];
					$arrInputfieldsValue['mobile'] 				= $objResult->fields['mobile'];
					$arrInputfieldsValue['mail'] 				= $objResult->fields['mail'];
					$arrInputfieldsValue['homepage'] 			= $objResult->fields['homepage'];
					$arrInputfieldsValue['industry'] 			= $objResult->fields['industry'];
					$arrInputfieldsValue['legalform'] 			= $objResult->fields['legalform'];
					$arrInputfieldsValue['conversion'] 			= $objResult->fields['conversion'];
					$arrInputfieldsValue['employee'] 			= $objResult->fields['employee'];
					$arrInputfieldsValue['foundation'] 			= $objResult->fields['foundation'];
					$arrInputfieldsValue['mwst'] 				= $objResult->fields['mwst'];
					$arrInputfieldsValue['opening'] 			= $objResult->fields['opening'];
					$arrInputfieldsValue['holidays'] 			= $objResult->fields['holidays'];
					$arrInputfieldsValue['places'] 				= $objResult->fields['places'];
					$arrInputfieldsValue['logo'] 				= $objResult->fields['logo'];
					$arrInputfieldsValue['team'] 				= $objResult->fields['team'];
					$arrInputfieldsValue['portfolio'] 			= $objResult->fields['portfolio'];
					$arrInputfieldsValue['offers'] 				= $objResult->fields['offers'];
					$arrInputfieldsValue['concept'] 			= $objResult->fields['concept'];
					$arrInputfieldsValue['map'] 				= $objResult->fields['map'];
					$arrInputfieldsValue['lokal'] 				= $objResult->fields['lokal'];
					$arrInputfieldsValue['spez_field_1'] 		= $objResult->fields['spez_field_1'];
					$arrInputfieldsValue['spez_field_2'] 		= $objResult->fields['spez_field_2'];
					$arrInputfieldsValue['spez_field_3'] 		= $objResult->fields['spez_field_3'];
					$arrInputfieldsValue['spez_field_4'] 		= $objResult->fields['spez_field_4'];
					$arrInputfieldsValue['spez_field_5'] 		= $objResult->fields['spez_field_5'];
					$arrInputfieldsValue['spez_field_6'] 		= $objResult->fields['spez_field_6'];
					$arrInputfieldsValue['spez_field_7'] 		= $objResult->fields['spez_field_7'];
					$arrInputfieldsValue['spez_field_8'] 		= $objResult->fields['spez_field_8'];
					$arrInputfieldsValue['spez_field_9'] 		= $objResult->fields['spez_field_9'];
					$arrInputfieldsValue['spez_field_10'] 		= $objResult->fields['spez_field_10'];
					$arrInputfieldsValue['spez_field_11'] 		= $objResult->fields['spez_field_11'];
					$arrInputfieldsValue['spez_field_12'] 		= $objResult->fields['spez_field_12'];
					$arrInputfieldsValue['spez_field_13'] 		= $objResult->fields['spez_field_13'];
					$arrInputfieldsValue['spez_field_14'] 		= $objResult->fields['spez_field_14'];
					$arrInputfieldsValue['spez_field_15'] 		= $objResult->fields['spez_field_15'];
					$arrInputfieldsValue['spez_field_16'] 		= $objResult->fields['spez_field_16'];
					$arrInputfieldsValue['spez_field_17'] 		= $objResult->fields['spez_field_17'];
					$arrInputfieldsValue['spez_field_18'] 		= $objResult->fields['spez_field_18'];
					$arrInputfieldsValue['spez_field_19'] 		= $objResult->fields['spez_field_19'];
					$arrInputfieldsValue['spez_field_20'] 		= $objResult->fields['spez_field_20'];
					$arrInputfieldsValue['spez_field_21'] 		= $objResult->fields['spez_field_21'];
					$arrInputfieldsValue['spez_field_22'] 		= $objResult->fields['spez_field_22'];
					$arrInputfieldsValue['spez_field_23'] 		= $objResult->fields['spez_field_23'];
					$arrInputfieldsValue['spez_field_24'] 		= $objResult->fields['spez_field_24'];
					$arrInputfieldsValue['spez_field_25'] 		= $objResult->fields['spez_field_25'];
					$arrInputfieldsValue['spez_field_26'] 		= $objResult->fields['spez_field_26'];
					$arrInputfieldsValue['spez_field_27'] 		= $objResult->fields['spez_field_27'];
					$arrInputfieldsValue['spez_field_28'] 		= $objResult->fields['spez_field_28'];
					$arrInputfieldsValue['spez_field_29'] 		= $objResult->fields['spez_field_29'];
					$objResult->MoveNext();
				}
			}

			//get plattforms and languages and user
			$arrInputfieldsValue['platform'] 			= $this->getPlatforms($arrInputfieldsValue['platform']);
			$arrInputfieldsValue['language'] 			= $this->getLanguages($arrInputfieldsValue['language']);
			$arrInputfieldsValue['canton'] 				= $this->getCantons($arrInputfieldsValue['canton']);
			$arrInputfieldsValue['spez_field_21'] 		= $this->getSpezDropdown($arrInputfieldsValue['spez_field_21'], 'spez_field_21');
			$arrInputfieldsValue['spez_field_22'] 		= $this->getSpezDropdown($arrInputfieldsValue['spez_field_22'], 'spez_field_22');
			$arrInputfieldsValue['spez_field_23'] 		= $this->getSpezVotes($arrInputfieldsValue['spez_field_23'], 'spez_field_23');
			$arrInputfieldsValue['spez_field_24'] 		= $this->getSpezVotes($arrInputfieldsValue['spez_field_24'], 'spez_field_24');
		}
		$objResult = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_directory_inputfields WHERE ".$where." ORDER BY sort ASC");
		if($objResult !== false){
			while(!$objResult->EOF){
				$arrInputfieldsActive['name'][$objResult->fields['id']] = $objResult->fields['name'];
				$arrInputfieldsActive['typ'][$objResult->fields['id']] = $objResult->fields['typ'];
				$arrInputfieldsActive['read_only'][$objResult->fields['id']] = $objResult->fields['read_only'];
				$arrInputfieldsActive['title'][$objResult->fields['id']] = $objResult->fields['title'];
				$arrInputfieldsActive['is_required'][$objResult->fields['id']] = $objResult->fields['is_required'];
				$objResult->MoveNext();
			}
		}

		//form action
		if($arrSettings['levels']['int'] == 1){
			$formOnSubmit = "selectAll(document.addForm.elements['selectedCat[]']); selectAll(document.addForm.elements['selectedLevel[]']); return CheckFields();";
		}else{
			$formOnSubmit = "selectAll(document.addForm.elements['selectedCat[]']); return CheckFields();";
		}

		//default required fields
		$javascript 	.= 'function CheckFields() {
							var errorMsg = "";
							with( document.addForm ) {
							';
		$javascript 	.= 'if (document.getElementsByName(\'type\')[0].checked == false && document.getElementsByName(\'type\')[1].checked == false && document.getElementsByName(\'type\')[2].checked == false) {
								errorMsg = errorMsg + "- '.$_ARRAYLANG['TXT_DIR_FILETYP'].'\n";
							}

							if (document.getElementsByName(\'selectedCat[]\')[0].value == "") {
										errorMsg = errorMsg + "- '.$_ARRAYLANG['TXT_DIR_CATEGORIE'].'\n";
							}
							';

		if($arrSettings['levels']['int'] == 1){
			$javascript 	.= 'if (document.getElementsByName(\'selectedLevel[]\')[0].value == "") {
										errorMsg = errorMsg + "- '.$_ARRAYLANG['TXT_LEVEL'].'\n";
								}';
		}

		if($action != "edit"){
			$javascript 	.= 'if (document.getElementsByName(\'linkname\')[0].value == "http://" && document.getElementsByName(\'rssname\')[0].value == "http://" && document.getElementsByName(\'fileName\')[0].value == "") {
										errorMsg = errorMsg + "- '.$_ARRAYLANG['TXT_DIRECTORY_ATTACHMENT'].'\n";
								}';
		}

		foreach($arrInputfieldsActive['name'] as $inputKey => $inputName){
			$disabled = "";
			$inputValueField = "";
			$fieldName = $_ARRAYLANG[$arrInputfieldsActive['title'][$inputKey]];

			if($arrSettings['levels']['int'] == 1){
				($i % 2)? $class = "row2" : $class = "row1";
			}else{
				($i % 2)? $class = "row1" : $class = "row2";
			}


			switch ($arrInputfieldsActive['typ'][$inputKey]) {
	    		case '1':
	    			if($arrInputfieldsActive['read_only'][$inputKey] == 1){
						$disabled = "disabled";
						$inputValueField = "<input type=\"hidden\" name=\"inputValue[".$inputName."]\" value=\"".$arrInputfieldsValue[$inputName]."\" style=\"width:".$width."px;\" maxlength='250'>";
					}
					if($inputName == "addedby"){
						$value = $this->getAuthor($arrInputfieldsValue[$inputName]);
						if($action == "edit"){
							$value .= $this->getAuthor($_POST['inputValue'][$inputName]);
						}
					}else{
						$value = $arrInputfieldsValue[$inputName];
					}

					$inputValueField .= "<input type=\"text\" name=\"inputValue[".$inputName.$disabled."]\" value=\"".$value."\" style=\"width:".$width."px;\" maxlength='250' ".$disabled.">";
	    			break;
	    		case '2':
	    			$inputValueField = "<textarea name=\"inputValue[".$inputName."]\" style=\"width:".$width."px; overflow: auto;\" rows='7'>".$arrInputfieldsValue[$inputName]."</textarea>";
	    			break;
	    		case '3':
	    		 	$inputValueField = "<select name=\"inputValue[".$inputName."]\" style=\"width:".($width+2)."px;\">".$arrInputfieldsValue[$inputName]."</select>";
	    			break;
	    		case '4':
	    			if ($action !== "add"){
		    			if(!file_exists($this->mediaPath."images/".$arrInputfieldsValue[$inputName]) || $arrInputfieldsValue[$inputName] == ""){
	    					$inputValueField = "<img src='".$this->mediaWebPath."/images/no_picture.gif' alt='' /><br /><br />";
	    				}else{
	    					$inputValueField = "<img src='".$this->mediaWebPath."thumbs/".$arrInputfieldsValue[$inputName]."' alt='' /><br /><input type=\"checkbox\" value=\"1\" name=\"deleteMedia[".$inputName."]\">".$_ARRAYLANG['TXT_DIR_DEL']."<br /><br />";
	    				}
	    			}
	    			if ($action !== "confirm"){
	    				$inputValueField .= "<input type=\"file\" name=\"".$inputName."\" size=\"37\" style=\"width:".$width."px;\"'>";
	    		 		$inputValueField .="<input type=\"hidden\" name=\"inputValue[".$inputName."]\" value='".$arrInputfieldsValue[$inputName]."'>";
	    			}
	    			break;
	    		case '5':
					$inputValueField .= "<input type=\"text\" name=\"inputValue[".$inputName."]\" value=\"".$arrInputfieldsValue[$inputName]."\" style=\"width:".$width."px;\" maxlength='250'>";
	    			$fieldName = $arrInputfieldsActive['title'][$inputKey];
					break;
	    		case '6':
	    			$inputValueField = "<textarea name=\"inputValue[".$inputName."]\" style=\"width:".$width."px; overflow: auto;\" rows='7'>".$arrInputfieldsValue[$inputName]."</textarea>";
	    			$fieldName = $arrInputfieldsActive['title'][$inputKey];
	    			break;
	    		case '7':
	    			if ($action !== "add"){
		    			if(!file_exists($this->mediaPath."images/".$arrInputfieldsValue[$inputName]) || $arrInputfieldsValue[$inputName] == ""){
	    					$inputValueField = "<img src='".$this->mediaWebPath."/images/no_picture.gif' alt='' /><br /><br />";
	    				}else{
	    					$inputValueField = "<img src='".$this->mediaWebPath."thumbs/".$arrInputfieldsValue[$inputName]."' alt='' /><br /><input type=\"checkbox\" value=\"1\" name=\"deleteMedia[".$inputName."]\">".$_ARRAYLANG['TXT_DIR_DEL']."<br /><br />";
	    				}
	    			}
	    			if ($action !== "confirm"){
	    				$inputValueField .= "<input type=\"file\" name=\"".$inputName."\" size=\"37\" style=\"width:".$width."px;\"'>";
	    		 		$inputValueField .="<input type=\"hidden\" name=\"inputValue[".$inputName."]\" value='".$arrInputfieldsValue[$inputName]."'>";
	    			}
	    			$fieldName = $arrInputfieldsActive['title'][$inputKey];
	    			break;
	    		case '8':
	    		 	$inputValueField = "<select name=\"inputValue[".$inputName."]\" style=\"width:".($width+2)."px;\">".$arrInputfieldsValue[$inputName]."</select>";
	    			$fieldName = $arrInputfieldsActive['title'][$inputKey];
	    		 	break;
	    		case '9':
	    		 	$inputValueField = "<select name=\"inputValue[".$inputName."]\" style=\"width:".($width+2)."px;\">".$arrInputfieldsValue[$inputName]."</select>";
	    			$fieldName = $arrInputfieldsActive['title'][$inputKey];
	    		 	break;
	    		case '10':
	    			if ($action !== "add"){
		    			if(!file_exists($this->mediaPath."uploads/".$arrInputfieldsValue[$inputName]) || $arrInputfieldsValue[$inputName] == ""){
	    					$inputValueField = "-<br /><br />";
	    				}else{
	    					$inputValueField = "<a href='".$this->mediaWebPath."uploads/".$arrInputfieldsValue[$inputName]."' target='_blank' />".$arrInputfieldsValue[$inputName]."</a><br /><input type=\"checkbox\" value=\"1\" name=\"deleteMedia[".$inputName."]\">".$_ARRAYLANG['TXT_DIR_DEL']."<br /><br />";
	    				}
	    			}
	    			if ($action !== "confirm"){
	    				$inputValueField .= "<input type=\"file\" name=\"".$inputName."\" size=\"37\" style=\"width:".$width."px;\"'>";
	    		 		$inputValueField .="<input type=\"hidden\" name=\"inputValue[".$inputName."]\" value='".$arrInputfieldsValue[$inputName]."'>";
	    			}
	    			$fieldName = $arrInputfieldsActive['title'][$inputKey];
	    			break;
			}


			$requiered = "";
			if($arrInputfieldsActive['is_required'][$inputKey] == 1){
				$requiered 		 = "<font color='red'>*</font>";

				$javascript 	.= 'if (document.getElementsByName(\'inputValue['.$inputName.']\')[0].value == "") {
										errorMsg = errorMsg + "- '.$fieldName.'\n";
									}

									';
			}

	    	// initialize variables
			$this->_objTpl->setVariable(array(
				'FIELD_ROW'					=> $class,
				'FIELD_VALUE'				=> $inputValueField,
				'FIELD_NAME'  				=> $fieldName,
				'FIELD_REQUIRED'  			=> $requiered,
				'DIRECTORY_FORM_ONSUBMIT'  	=> $formOnSubmit,
			));

			$this->_objTpl->parse('inputfieldsOutput');
			$i++;
	    }

	    $javascript .= '}

						if (errorMsg != "") {
							alert ("'.$_ARRAYLANG['TXT_DIR_FILL_ALL'].':\n\n" + errorMsg);
							return false;
						}else{
							return true;
						}
					}';

	    // initialize variables
		$this->_objTpl->setVariable(array(
			'DIRECTORY_JAVASCRIPT'  => $javascript,
		));
    }

    function getSettings(){
    	global $objDatabase, $_ARRAYLANG;

    	//get settings
		$objResult = $objDatabase->Execute("SELECT setname, setvalue, settyp FROM ".DBPREFIX."module_directory_settings");
		if($objResult !== false){
			while(!$objResult->EOF){
				$settings[$objResult->fields['setname']]['value'] = $objResult->fields['setvalue'];
				if($objResult->fields['settyp'] == 2){
					$settings[$objResult->fields['setname']]['int'] = 		$objResult->fields['setvalue'] ==1 ? "1" : "0";
					$settings[$objResult->fields['setname']]['boolean'] = 	$objResult->fields['setvalue'] ==1 ? "true" : "false";
					$settings[$objResult->fields['setname']]['selected'] = 	$objResult->fields['setvalue'] ==1 ? "selected" : "";
					$settings[$objResult->fields['setname']]['disabled'] = 	$objResult->fields['setvalue'] ==1 ? "" : "disabled";
					$settings[$objResult->fields['setname']]['checked'] = 	$objResult->fields['setvalue'] ==1 ? "checked" : "";
					$settings[$objResult->fields['setname']]['display'] = 	$objResult->fields['setvalue'] ==1 ? "block" : "none";
				}
				$objResult->MoveNext();
			}
		}

		$objResult = $objDatabase->Execute("SELECT setname, setvalue, settyp FROM ".DBPREFIX."module_directory_settings_google");
		if($objResult !== false){
			while(!$objResult->EOF){
				$settings['google'][$objResult->fields['setname']] = $objResult->fields['setvalue'];
				$objResult->MoveNext();
			}
		}

		return $settings;
    }


     /**
    * count
    *
    *
    * @access   public
    * @param    string  $id
    */
	function count($lid, $cid)
	{
		global $objDatabase;

		if (empty($cid)) {
			$this->countLevels($lid, $lid);
			$count = $this->countFeeds($this->numLevels[$lid], 'level', $lid);
		} else {
			$this->countCategories($cid, $cid);
			$count = $this->countFeeds($this->numCategories[$cid], 'cat', $lid);
		}

		return intval($count);
	}



    /**
    * count Catecories
    *
    *
    * @access   public
    * @param    string  $id
    */
	function countCategories($ckey, $cid)
	{
		global $objDatabase;

		$this->numCategories[$ckey][] = $cid;
		$objResultCat = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_directory_categories WHERE status = 1 AND parentid =".intval($cid));
		if($objResultCat !== false){
			while(!$objResultCat->EOF){
				$this->countCategories($ckey, $objResultCat->fields['id']);
				$objResultCat->MoveNext();
			}
		}
	}


		/**
    * count Levels
    *
    *
    * @access   public
    * @param    string  $id
    */
	function countLevels($lkey, $lid)
	{
		global $objDatabase;

		$this->numLevels[$lkey][] = $lid;
		$objResultLevel = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_directory_levels WHERE status = 1 AND parentid =".intval($lid));
		if($objResultLevel !== false){
			while(!$objResultLevel->EOF){
				$this->countLevels($lkey, $objResultLevel ->fields['id']);
				$objResultLevel->MoveNext();
			}
		}
	}

	/**
    * count Feeds
    *
    *
    * @access   public
    * @param    string  $id
    */
	function countFeeds($array, $type, $level)
	{
		global $objDatabase;

		if ($type == 'level') {
			$query = '
			SELECT
				SUM(1) AS feedCount
			FROM
				`'.DBPREFIX.'module_directory_dir` AS files
				INNER JOIN `'.DBPREFIX.'module_directory_rel_dir_level` AS rel_level ON rel_level.`dir_id`=files.`id`
			WHERE
				(rel_level.`level_id`='.implode(' OR rel_level.`level_id`=', $array).')
				AND `status` !=0';
		} elseif (!empty($level)) {
			$query = '
			SELECT
				SUM(1) AS feedCount
			FROM
				`'.DBPREFIX.'module_directory_dir` AS files
				INNER JOIN `'.DBPREFIX.'module_directory_rel_dir_cat` AS rel_cat ON rel_cat.`dir_id`=files.`id`
				INNER JOIN `'.DBPREFIX.'module_directory_rel_dir_level` AS rel_level USING (`dir_id`)
			WHERE
				(rel_cat.`cat_id`='.implode(' OR rel_cat.`cat_id`=', $array).')
				AND rel_level.`level_id`='.$level.'
				AND `status` !=0';
		} else {
			$query = '
			SELECT
				SUM(1) AS feedCount
			FROM
				`'.DBPREFIX.'module_directory_dir` AS files
				INNER JOIN `'.DBPREFIX.'module_directory_rel_dir_cat` AS rel_cat ON rel_cat.`dir_id`=files.`id`
			WHERE
				(rel_cat.`cat_id`='.implode(' OR rel_cat.`cat_id`=', $array).')
				AND `status` !=0';
		}

		$objResultCount = $objDatabase->SelectLimit($query, 1);
		if($objResultCount !== false){
			return $objResultCount->fields['feedCount'];
		} else {
			return 0;
		}
	}


	/**
    * update file
    *
    * update selected file
    *
    * @access   public
    * @global	object	$objDatabase
    * @global	object	$template
	* @global	array	$_CORELANG
	* @global	array	$_CONFIG
    */
	function updateFile($addedby)
	{
		global $_CONFIG, $objDatabase, $_ARRAYLANG;

		//get post data
		if(isset($_POST['edit_submit'])){

			//check attachment changes
			if(!empty($_FILES['fileName']['name']) && ($_POST['type']  == "file")){
				$obj_file 	= new File();
				$obj_file->delFile($this->mediaPath, $this->mediaWebPath, "uploads/".$_POST['edit_fileName']);
				$file 		= $this->uploadMedia("fileName", "uploads/");
				$link 		= $_FILES['fileName']['name'];
				$size 		= $this->fileSize;
				$typ 		= "file";
			} elseif ($_POST['type']  == "rss"){
				if($_POST['rssname'] == "http://"){
					$link 		= $_POST['edit_linkName'];
					$file 		= $_POST['edit_fileName'];
				}else{
					$obj_file 	= new File();
					$obj_file->delFile($this->mediaPath, $this->mediaWebPath, "ext_feeds/".$_POST['edit_fileName']);
					$link 		= $_POST['rssname'];
					$file 		= $this->createXML($link);
				}
				$size 		= "";
				$typ 		="rss";
			} elseif (($_POST['type']  == "link")){
				if(!empty($_POST['edit_fileName'])){
					$obj_file 	= new File();
					$obj_file->delFile($this->mediaPath, $this->mediaWebPath, "uploads/".$_POST['edit_fileName']);
					$obj_file->delFile($this->mediaPath, $this->mediaWebPath, "ext_feeds/".$_POST['edit_fileName']);
				}
				if($_POST['linkname'] == "http://"){
					$link 		= $_POST['edit_linkName'];
					if(substr($link, 0,7) != "http://"){
						$link = "http://".$link;
					}
				}else{
					$link 		= $_POST['linkname'];
					if(substr($link, 0,7) != "http://"){
						$link = "http://".$link;
					}
				}
				$size 		= "";
				$file 		= "";
				$typ 		="link";
			} else {
				$size = $_POST['edit_size'];
				if($_POST['type']  == "link"){
					$file 		= "";
				}else{
					$file 		= $_POST['edit_fileName'];
				}
				$link 		= $_POST['edit_linkName'];
				$typ 		= $_POST['type'];
			}

			$dirId 		= intval($_POST['edit_id']);

			$query 		= "UPDATE ".DBPREFIX."module_directory_dir SET ";

			foreach($_POST["inputValue"] as $inputName => $inputValue){
				//check links
				if($inputName == "relatedlinks" || $inputName == "homepage"){
					if(substr($inputValue, 0,7) != "http://" && $inputValue != ""){
						$inputValue = "http://".$inputValue;
					}
				}

				//get author id
				if($inputName == "addedby"){
					if($addedby != ''){
						$inputValue = $addedby;
					}else{
						$inputValue = $this->getAuthorID($inputValue);
					}
				}



				//check pics
				if ($inputName == "logo" ||
					$inputName == "lokal" ||
					$inputName == "map" ||
					$inputName == "spez_field_11" ||
					$inputName == "spez_field_12" ||
					$inputName == "spez_field_13" ||
					$inputName == "spez_field_14" ||
					$inputName == "spez_field_15" ||
					$inputName == "spez_field_16" ||
					$inputName == "spez_field_17" ||
					$inputName == "spez_field_18" ||
					$inputName == "spez_field_19" ||
					$inputName == "spez_field_20"){



					if(!empty($_FILES[$inputName]['name']) || $_POST["deleteMedia"][$inputName] == 1){
						$obj_file = new File();

						//thumb
						if (file_exists($this->mediaPath."thumbs/".$_POST["inputValue"][$inputName])){
							$obj_file->delFile($this->mediaPath, $this->mediaWebPath, "thumbs/".$_POST["inputValue"][$inputName]);
						}

						//picture
						if (file_exists($this->mediaPath."images/".$_POST["inputValue"][$inputName])){
							$obj_file->delFile($this->mediaPath, $this->mediaWebPath, "images/".$_POST["inputValue"][$inputName]);
						}

						if($_POST["deleteMedia"][$inputName] != 1){
							$inputValue = $this->uploadMedia($inputName, "images/");

							if($inputValue == "error"){
								$inputValue 	= "";
							}
						} else {
							$inputValue 	= "";
						}
					}
				}

				//check uploads
				if ($inputName == "spez_field_25" ||
					$inputName == "spez_field_26" ||
					$inputName == "spez_field_27" ||
					$inputName == "spez_field_28" ||
					$inputName == "spez_field_29"){


					if(!empty($_FILES[$inputName]['name']) || $_POST["deleteMedia"][$inputName] == 1){

						$obj_file = new File();

						//upload
						if (file_exists($this->mediaPath."uploads/".$_POST["inputValue"][$inputName])){
							$obj_file->delFile($this->mediaPath, $this->mediaWebPath, "uploads/".$_POST["inputValue"][$inputName]);
						}

						if($_POST["deleteMedia"][$inputName] != 1){
							$inputValue = $this->uploadMedia($inputName, "uploads/");

							if($inputValue == "error"){
								$inputValue 	= "";
							}
						} else {
							$inputValue 	= "";
						}
					}
				}

				$query .= contrexx_addslashes($inputName)." ='".contrexx_strip_tags(contrexx_addslashes($inputValue))."', ";
			}

			//get status settings
			$objResult = $objDatabase->Execute("SELECT setvalue FROM ".DBPREFIX."module_directory_settings WHERE setname = 'editFeed_status' LIMIT 1");

			if($objResult !== false){
				while(!$objResult->EOF){
					$entryStatus = $objResult->fields['setvalue'];
					$objResult->MoveNext();
				}
			}

			$query .= " premium='".$_POST["premium"]."', filename='".contrexx_strip_tags($file)."', link='".contrexx_strip_tags($link)."', status='".contrexx_strip_tags($entryStatus)."', typ='".contrexx_strip_tags($typ)."',  validatedate='".mktime("now")."' WHERE id='".$dirId."'";


			//edit entry
			$objResult = $objDatabase->Execute($query);

			if($objResult !== false){

				$objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_directory_rel_dir_cat WHERE dir_id='".$dirId."'");
				$objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_directory_rel_dir_level WHERE dir_id='".$dirId."'");

				foreach($_POST["selectedCat"] as $inputName => $inputValue){
					$query = "INSERT INTO ".DBPREFIX."module_directory_rel_dir_cat SET dir_id='".$dirId."', cat_id='".$inputValue."'";
					$objDatabase->query($query);
				}

				foreach($_POST["selectedLevel"] as $inputName => $inputValue){
					$query = "INSERT INTO ".DBPREFIX."module_directory_rel_dir_level SET dir_id='".$dirId."', level_id='".$inputValue."'";
					$objDatabase->query($query);
				}

				if($entryStatus == 1){
					$this->confirmEntry_step2($id);
				}

				$this->strOkMessage = $_ARRAYLANG['TXT_FEED_SUCCESSFULL_ADDED'];
				$status = $dirId;

				$this->createRSS();
			}

			//update xml
			$this->createRSS();

			return $status;
		}
	}


	/**
	* get/count votes for feeds
	*
	* getContentLatest
	*
	* @access	public
	* @param    string $pageContent
	* @param 	string
	*/
    function getVotes($id){
    	global $objDatabase, $_ARRAYLANG;

    	$countVotes 	= "";
    	$averageVotes 	= "";
    	$averageVotesImg = '';

    	//count votes
    	$objResult = $objDatabase->Execute("SELECT id, vote, count FROM ".DBPREFIX."module_directory_vote WHERE feed_id = '".$id."'");
		if ($objResult !== false) {
			while (!$objResult->EOF) {
				$averageVotes 	= $objResult->fields['vote'];
				$countVotes 	= $objResult->fields['count'];
				$objResult->MoveNext();
			}
		}

    	$averageVotes		= $countVotes<1 ? "0" : round($averageVotes/$countVotes, 1);
    	$countVotes 		= $countVotes<1 ? "0" : $countVotes;


    	//get stars
    	$i = 1;
    	for ($x = 1; $x <= 10; $x++){
    		if($i <= $averageVotes){
				$averageVotesImg .= '<img src="'.$this->imageWebPath.'directory/star_on.gif" border="0" alt="" />';
    		}else{
    			$averageVotesImg .= '<img src="'.$this->imageWebPath.'directory/star_off.gif" border="0" alt="" />';
    		}
    		$i++;
		}

    	// set variables
		$this->_objTpl->setVariable(array(
			'DIRECTORY_FEED_COUNT_VOTES'    	=> "(".$countVotes." ".$_ARRAYLANG['TXT_DIRECTORY_VOTES']." &Oslash;&nbsp;".$averageVotes.")",
			'DIRECTORY_FEED_AVERAGE_VOTE'    	=> $averageVotesImg,
		));
    }


    /**
    * get levels
    *
    * get added levels
    *
    * @access   public
    * @param	string	$catId
    * @return	string	$options
    * @global	object	$objInit
	* @global	object	$template
	* @global	array	$_CORELANG
    */
	function getSearchLevels($levelId)
	{
		global $_CONFIG, $objDatabase, $_CORELANG;

		//get all categories
		$objResultLevel = $objDatabase->Execute("SELECT id, name, parentid FROM ".DBPREFIX."module_directory_levels ORDER BY displayorder");

		if($objResultLevel !== false){
			while(!$objResultLevel->EOF){
				$this->getLevels['name'][$objResultLevel->fields['id']]			= $objResultLevel->fields['name'];
				$this->getLevels['parentid'][$objResultLevel->fields['id']]		= $objResultLevel->fields['parentid'];
				$objResultLevel->MoveNext();
			}
		}


		$options = "";

		//make categories dropdown
		foreach($this->getLevels['name'] as $levelKey => $levelName){
			$checked = "";
			if($this->getLevels['parentid'][$levelKey] == 0){
				if ($levelKey==$levelId){
					$checked = "selected";
				}
				$options .= "<option value='".$levelKey."' ".$checked.">".$levelName."</option>";

				//get subcategories
				$options .=$this->getSearchSublevels($levelName, $levelKey, $levelId);
			}
		}

		return $options;
	}




	/**
    * get sublevels
    *
    * get added sublevels
    *
    * @access   public
    * @param	string	$catName
    * @param	string	$parentId
    * @param	string	$catId
    * @return	string	$options
    */
	function getSearchSublevels($levelName, $parentId, $levelId)
	{
		$level = $levelName;
		$subOptions = "";

		//get subcategories
		foreach($this->getLevels['name'] as $levelKey => $levelName){
			if($this->getLevels['parentid'][$levelKey] == $parentId){
				$checked = "";
				if ($levelKey==$levelId){
					$checked = "selected";
				}

				$subOptions .= "<option value='".$levelKey."' ".$checked.">".$level." >> ".$levelName."</option>";

				//get more subcategories
				$subOptions .= $this->getSearchSublevels($level." >> ".$levelName, $levelKey, $levelId);
			}
		}

		return $subOptions;
	}


	/**
    * get levels
    *
    * get added levels
    *
    * @access   public
    * @param	string	$catId
    * @return	string	$options
    * @global	object	$objInit
	* @global	object	$template
	* @global	array	$_CORELANG
    */
	function getLevels($id, $type)
	{
		global $_CONFIG, $objDatabase, $_CORELANG;

		//get selected levels
		$objResultCat = $objDatabase->Execute("SELECT level_id FROM ".DBPREFIX."module_directory_rel_dir_level WHERE dir_id='".$id."'");
		if($objResultCat !== false){
			while(!$objResultCat->EOF){
				$this->levels[$x] = $objResultCat->fields['level_id'];
				$x++;
				$objResultCat->MoveNext();
			}
		}

		//get all levels
		$objResultCat = $objDatabase->Execute("SELECT id, name, parentid, showcategories FROM ".DBPREFIX."module_directory_levels ORDER BY displayorder");

		if($objResultCat !== false){
			while(!$objResultCat->EOF){
				$this->getLevels['name'][$objResultCat->fields['id']]			=$objResultCat->fields['name'];
				$this->getLevels['parentid'][$objResultCat->fields['id']]		=$objResultCat->fields['parentid'];
				$this->getLevels['showcategories'][$objResultCat->fields['id']]	=$objResultCat->fields['showcategories'];
				$objResultCat->MoveNext();
			}
		}

		$options = "";

		//make levels dropdown
		if (!empty($this->getLevels['name'])) {
			foreach($this->getLevels['name'] as $levelKey => $levelName){
				if($this->getLevels['parentid'][$levelKey] == 0){
					if ($this->getLevels['showcategories'][$levelKey] == 0 ){
						$style = "style='color: #ff0000;'";
					} else {
						$style = "";
					}
					if ($type == 1) {
						if (!in_array($levelKey, $this->levels)){
							$options .= "<option value='".$levelKey."' $style>".$levelName."</option>";
						}
					}else{
						if (in_array($levelKey, $this->levels)){
							$options .= "<option value='".$levelKey."' $style>".$levelName."</option>";
						}
					}

					//get sublevels
					$options .=$this->getSublevels($levelName, $levelKey, $type, '&nbsp;&nbsp;&nbsp;');
				}
			}
		}

		return $options;
	}




	/**
    * get levels
    *
    * get added levels
    *
    * @access   public
    * @param	string	$catName
    * @param	string	$parentId
    * @param	string	$catId
    * @return	string	$options
    */
	function getSublevels($levelName, $parentId, $type, $spacer)
	{
		//get subcategories
		foreach($this->getLevels['name'] as $levelKey => $levelName){
			if($this->getLevels['parentid'][$levelKey] == $parentId){
				if ($this->getLevels['showcategories'][$levelKey] == 0 ){
					$style = "style='color: #ff0000;'";
				} else {
					$style = "";
				}
				if($type == 1){
					if (!in_array($levelKey, $this->levels)){
						$options .= "<option value='".$levelKey."' $style>".$spacer.$levelName."</option>";
					}
				}else{
					if (in_array($levelKey, $this->levels)){
						$options .= "<option value='".$levelKey."' $style>".$levelName."</option>";
					}
				}

				//get more subcategories
				$options .= $this->getSublevels($levelName, $levelKey, $type, $spacer.'&nbsp;&nbsp;&nbsp;');
			}
		}

		return $options;
	}
}
?>
