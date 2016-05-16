<?php
define('slide_FILE_DIR', WP_CONTENT_DIR.'/uploads/myslides/');
define('slide_FILE_URL', WP_CONTENT_URL.'/uploads/myslides/');
define('JS_PATH' , plugins_url().'/WordPress-Slideshow/js/');
define('CSS_PATH' , plugins_url().'/WordPress-Slideshow/css/');
define('IMG_PATH' , plugins_url().'/WordPress-Slideshow/img/');

/**
 * Function fn_add_update() is used to add or update data.
 * @param $strTbl is to set table name.
 * @param $arrData is to set data.
 * @param $arrWhere is to set WHERE condition.
*/
function fn_add_update($strTbl,$arrData,$arrWhere= array())
{
    global $wpdb;

    if(count($arrWhere)==0)
    {
        $wpdb->insert($strTbl,$arrData);
        return $wpdb->insert_id;
    }
    else
    {
        $wpdb->update($strTbl,$arrData,$arrWhere);

        return true;
    }
    return false;
}

/**
 * Function fn_edit_data() is used to edit data.
 * @param $strTbl is to set table name.
 * @param $boolLimit is to set limit.
 * @param $arrWhere is to set WHERE condition.
*/
function fn_edit_data($strTbl,$arrWhere="",$boolLimit=true)
{
    global $wpdb;
    $strWhere = "";

    if(count($arrWhere) > 0 )
    {

        $strSep =  (count($arrWhere) > 1?" AND ":"");
        
        $strWhere = " WHERE ".implode($strSep, $arrWhere);

    }
    if($boolLimit)
    {
        $strLimit = "LIMIT 1";
    }
    $strSql = "Select id ,slide_img, img_order,img_title,img_description from $strTbl $strWhere $strLimit";
    
    if($boolLimit)
    {
        $arrResult =  $wpdb->get_row($strSql);  
    }
    else
    {
        $arrResult =  $wpdb->get_results($strSql);      
    }
    return $arrResult;
}

/**
 * Function fn_delete_data() is used to delete slider.
 * @param $intId is the id.
 */
function fn_delete_data($intId)
{
    global $wpdb;
    $strTbl = $wpdb->prefix."mks_slideshow";

    $chkArray = is_array($intId);
    if($chkArray)
    {
        foreach($intId as $del_id)
        {
            $old_file_name = $wpdb->get_var( $wpdb->prepare( 'SELECT slide_img FROM '.$strTbl.' WHERE id = %d', $del_id ) );
            delete_file(slide_FILE_DIR, $old_file_name);
            $deleteSlide = $wpdb->query("DELETE FROM ".$strTbl." WHERE id = ".$del_id);
        }
    }
    else
    {
        $old_file_name = $wpdb->get_var( $wpdb->prepare( 'SELECT slide_img FROM '.$strTbl.' WHERE id = %d', $intId ) );
        delete_file(slide_FILE_DIR, $old_file_name);

        $deleteSlide = $wpdb->query("DELETE FROM $strTbl WHERE id =".$intId);        
    }

    if($deleteSlide)
    {
        $arrMsg = array('msg' => 'Slider(s) Deleted.','msgClass' =>'updated');
    }
    if(!empty($arrMsg)){
        return $arrMsg;    
    }
    else{
        return "";
    }
}


/**
* This function is used to upload file to server.
*
* @param string $sInputName  
* @param string $sStorePath         File desintaion to store file with Trailing slash at end
* @param array  $aFiles             $_FILES
* @param array  $aAllowedTypes      Allowed file types to upload
* @param int    $iAllowedMaxSize    Allowed max upload size for individual file
* @param array  $aResize {
    *     Optional. Array of Array of parameters.
    *
    *     @type int     $w Width for resize image
    *     @type int     $h Height for resize image
    *     @type bool    $bCrop True if need to crop to exact size
    *     @type string  $store_at Absolute path to store image afre resizing with Trailing slash at end
    * }
* @param bool   $bIsAmazon          If true then file need to upload on Amazon
* @param bool   $bUploadFrmMob      If file upload from mobile webservice
*
* @return bool|array                This will return false in case on singular file upload fail Orelse array of uploaded file names
*/
function upload_file_on_server($sInputName, $sStorePath , $aFiles, $aAllowedTypes, $iAllowedMaxSize = 1000)
{
    if(file_exists($sStorePath) == FALSE) {
        mkdir($sStorePath, 0755, true);

        // Create empty index file to prevent directory index
        file_put_contents($sStorePath."index.html", "<!-- Silence is golden -->");
    }
   
    $max_size = get_max_upload_file_size( $iAllowedMaxSize );
    $aFiles = $aFiles[$sInputName];
    $aValidFiles = array();
    $aSavedFiles = array();

    // Validate for allowed file types & max upload size for individual file or any other upload errors
    // This will return false in case on singular file upload
    if(is_array($aFiles['name'])) {
        $iTotalFiles = count($aFiles['name']);
        for($i = 0; $i < $iTotalFiles; $i++){
            $file_size_in_mb = bytes_to_mb($aFiles['size'][$i]);

            if( !in_array($aFiles['type'][$i], $aAllowedTypes ) || $file_size_in_mb > $max_size || $aFiles['error'][$i] > 0)
                continue;

            $aFile              = array();
            $aFile['name']      = $aFiles['name'][$i];
            $aFile['type']      = $aFiles['type'][$i];
            $aFile['tmp_name']  = $aFiles['tmp_name'][$i];
            $aFile['error']     = $aFiles['error'][$i];
            $aFile['size']      = $aFiles['size'][$i];
            $aValidFiles[]      = $aFile;
        }
    } else {
        $file_size_in_mb = bytes_to_mb( $aFiles['size'] );
        $aFile              = array();
        $aFile['name']      = $aFiles['name'];
        $aFile['type']      = $aFiles['type'];
        $aFile['tmp_name']  = $aFiles['tmp_name'];
        $aFile['error']     = $aFiles['error'];
        $aFile['size']      = $aFiles['size'];
        $aValidFiles[]      = $aFile;
    }

    if(!empty($aValidFiles)) {
        foreach ($aValidFiles as $aValidFile) {
            // Clean the file name to make it safe to save by removing any special characters & whitespaces
            $sFileName = preg_replace('/[^A-Za-z0-9\-._]/', '', $aValidFile['name']);

            // Append timestamp to each file name to make it unique
            $path_parts = pathinfo($sFileName);

            // Shorten file name to 200 character length max
            $path_parts['filename'] = (strlen($path_parts['filename']) > 200) ? substr($path_parts['filename'], 0,200): $path_parts['filename'];

            // Append timestamp
            ##$sFileName = $path_parts['filename'].'_'.time().'.'.$path_parts['extension'];
            $number = mt_rand(10000,999999);
            $sFileName = $path_parts['filename'].$number.'.'.$path_parts['extension'];

            $org_file_path = $sStorePath.$sFileName;
            $bSuccess = @move_uploaded_file($aValidFile['tmp_name'], $org_file_path);

            if($bSuccess) {
                $aSavedFiles[] = $sFileName;
            }    
        }
    }
            
    return $aSavedFiles;          
}

/**
 * Convert bytes to MB
 *
 * @param integer bytes Size in bytes to convert
 * @return int|float
 */
function bytes_to_mb($bytes, $precision = 2) {  
    $kilobyte = 1024;
    $megabyte = $kilobyte * 1024;
    
    return round($bytes / $megabyte, $precision);
}

/**
 * Get maximum file upload size allowed on server in MegaBytes(MB)
 * 
 * @link http://www.kavoir.com/2010/02/php-get-the-file-uploading-limit-max-file-size-allowed-to-upload.html
 *
 * @param integer $iUserSepcificMaxSize User specific size to consider while calculation max size.
 * @return int $max_upload_size Maximum file upload size on server
 */
function get_max_upload_file_size( $iUserSepcificMaxSize = 0 ) {
    $aAllSizes   = array();
    $aAllSizes[] = (int)(ini_get('upload_max_filesize'));
    $aAllSizes[] = (int)(ini_get('post_max_size'));
    $aAllSizes[] = (int)(ini_get('memory_limit'));

    if($iUserSepcificMaxSize > 0)
        $aAllSizes[] = $iUserSepcificMaxSize;

    $max_upload_size = min($aAllSizes);

    return $max_upload_size;
}

function delete_file( $sPath, $sFileName ) {
    if( is_file( $sPath.$sFileName ) ) 
        @unlink( $sPath.$sFileName );
}




function pr($data){
    echo "<pre>"; print_r($data); echo "</pre>";
}