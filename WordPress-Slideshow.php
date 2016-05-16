<?php
    /*
    Plugin Name: WordPress-Slideshow Plugin
    Description: Plugin for slide show
    Author: Manish K Srivastava
    Version: 1.0
    */


/**
 * Function fnInstallSlideshow() is to create table if it is not exists
*/
function fnInstallSlideshow()
{
   global $wpdb;
   $strTbl = $wpdb->prefix."mks_slideshow";
   $createTbl =  "CREATE TABLE $strTbl  (
                   `id` INT NOT NULL AUTO_INCREMENT ,
                   `slide_img` TEXT NOT NULL ,
                   `img_order` INT NOT NULL ,
                   `img_title` VARCHAR( 30 ) NOT NULL ,
                   `img_description` VARCHAR( 100 ) NOT NULL ,
                   `status` ENUM(  '0',  '1' ) NOT NULL ,
                   PRIMARY KEY (`id`),
                   UNIQUE KEY `img_order` (`img_order`)
               )";
  
   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   dbDelta($createTbl);
}

register_activation_hook(__FILE__,'fnInstallSlideshow');

include_once("functions.php"); 

//step1 Load basic Class
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Slideshow_List_Table extends WP_List_Table {
    var $strQuery;
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'slider',     //singular name of the listed records
            'plural'    => 'sliders',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
    }
    
    /**
     * Function column_default() is called when the parent class can't find a method specifically build for a given column.
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
    */
    function column_default($item, $column_name){
        switch($column_name){
            case 'id':
            case 'img_title':
            case 'img_description':
            case 'slide_img':
            case 'image_order':
            case 'status':
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }

    function column_slide_img($item){
        if(empty($item['slide_img'])){
            return 'Not Available';
        }else{    
            return "<img height='50px' width='70px' src='".site_url().'/wp-content/uploads/myslides/'.$item['slide_img']."'>";
        }
    }


    /**
     * Function column_description() is a custom column method and is responsible for what is rendered in any column with a name/slug of 'description'.
     * @param array $item a singular item.
    */
    function column_img_description($item){
        return sprintf('%s',substr(stripslashes($item['img_description']),0,50));
    }

    /**
     * Function column_description() is a custom column method and is responsible for what is rendered in any column with a name/slug of 'description'.
     * @param array $item a singular item.
    */
    function column_img_title($item){
         $actions = array(
            'edit'      => sprintf('<a href="?page=%s&mode=%s&slider=%s">Edit</a>',$_REQUEST['page'],'edit',$item['id']),
            'delete'    => sprintf("<a href=\"?page=%s&action=%s&slider_id=%s\" onclick=\"if ( confirm( '" . esc_js( sprintf( __( "You are about to delete this List '%s'\n  'Cancel' to stop, 'OK' to delete." ),  $item['id'] ) ) . "' ) ) { return true;}return false;\">Delete</a>",$_REQUEST['page'],'delete',$item['id']),
        );
        return sprintf('%s %s',$item['img_title'],$this->row_actions($actions));
    }

    /**
     * Function column_description() is a custom column method and is responsible for what is rendered in any column with a name/slug of 'description'.
     * @param array $item a singular item.
    */
    function column_img_order($item){
        return sprintf('%s',$item['img_order']);
    }
       
   
    /**
     * Function get_columns() is to set table's columns and titles.
    */  
    function get_columns(){
        $columns = array(
            'img_title' => "Title",
            'img_description'    => 'Description',
            'slide_img'     => 'Image',
            'img_order' => "Order",
        );
        return $columns;
    }
    
    /**
     * Function get_sortable_columns() is to sort one/more columns.
    */ 
    function get_sortable_columns() {
        $sortable_columns = array(
            'client_name' => array("client_name",false),
            'client_desg' => array("client_desg",false),            
            'company'    => array('company',false)            
        );
        return $sortable_columns;
    }

    /**
     * Function prepare_items() is to list slider and set order.
    */
    function prepare_items($searchvar= NULL) {
        global $wpdb; //This is used only if making any database queries

        $per_page = 5;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
      
        $strTbl = $wpdb->prefix."mks_slideshow";
        $this->_column_headers = array($columns, $hidden, $sortable);
        $wpdb->query("SET @a=0");
        
        $this->strQuery = "SELECT id ,slide_img, img_order,img_title,img_description FROM ".$strTbl ." ORDER BY id DESC";        
        $data = $wpdb->get_results($this->strQuery,ARRAY_A );
        //echo $wpdb->last_query;

                                      
        function usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'id'; //If no sort, default to rank
                       $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to desc
            if(is_numeric($a[$orderby]))
            {
                 $result = ($a[$orderby] > $b[$orderby]?-1:1); //Determine sort order
            }
            else
            {
                $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            }
            
            return ($order==='desc') ? $result : -$result; //Send final sort direction to usort
        }
        usort($data, 'usort_reorder');
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
              
        $this->items = $data;
      
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }
    
}

/**
* Function _fnAddMenuItems() is called to add menu in admin side
*/     
add_action('admin_menu', '_fnAddMenuItems');
function _fnAddMenuItems(){
    add_menu_page('Sliders', 'Sliders', 'activate_plugins', 'mks-sliders', '_fnDemoRenderListPage');
    add_submenu_page( 'mks-sliders', 'Help', 'Help', 'manage_options', 'help', 'fn_add_settings_menu' );
} 

/**
* Function _fnDemoRenderListPage is called to display list of table.
*/
function _fnDemoRenderListPage(){
                
    //Create an instance of our package class...
    $objSlide = new Slideshow_List_Table();

    

    $this_file = "?page=".$_REQUEST['page'];

    switch($objSlide->current_action())
    {
        case "add":
        case "edit":
        case "delete":
            global $wpdb;
                       
            if(isset($_GET['action2']) && ($_GET['action2']=="-1"))
            {
                $del_id = $_GET['slider_id'];
                if(is_array($del_id)){
                    foreach ($del_id as $value) {
                        $del_data = fn_delete_data($value);
                    }
                }else{
                    $del_data = fn_delete_data($del_id);    
                }
                
            }
            
            if(isset($_GET['slider_id']) && $_GET['slider_id'])
            {
                $del_id = $_GET['slider_id'];
                $del_data = fn_delete_data($del_id);
            }
            if(isset($del_data)){ ?>
                <div class='<?php if(!empty($del_data['msg'])): echo $del_data['msgClass']; endif; ?>'>
                    <p><?php if(!empty($del_data['msg'])): echo $del_data['msg']; endif; ?></p>
                </div>
            <?php } 
                     
            $this_file = $this_file."&update=delete";
        default:
        ?>
            <script type='text/javascript' src="<?php echo plugins_url('js/jquery.validate.js',__FILE__); ?>"></script>
            <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery("#add_slide").validate();
            });
            </script>

            <?php
            global $wpdb;
                
            $strTbl = $wpdb->prefix."mks_slideshow";
            $strPageListingParam ="slider";
            $arrWhere = array();
            if(!empty($_POST['description']))
            {
                substr($_POST['description'],0,500);
            }
            
            //check blank data & add record
            if (!empty($_POST['addSlide']))
            {
                if($_POST['id'] != "")
                {

                    $arrWhere = array("id" => $_POST['id'] );
                    unset($_POST['id']);
                }
                //remove submit button & remove blank field
                unset($_POST['addSlide']);                
                $arrData = array();
                foreach ($_POST as $key => $value) {
                    $arrData[$key] = stripslashes($value);
                }
               /* echo "<pre>";
                print_r($arrData);
                echo "</pre>";*/
                $arrMsg = array();
                
                if(count($arrData ) > 0)
                {
                    $aAllowedTypes = array('image/jpeg','image/jpg','image/png','image/gif');
                    if( $_FILES['slide_img']['name'] != "" ) {
                        $aSavedFiles = upload_file_on_server('slide_img', slide_FILE_DIR , $_FILES, $aAllowedTypes);
                    }
                    if( isset($aSavedFiles) ) {
                        $arrData['slide_img'] = $aSavedFiles[0];
                    }

                    $boolAdded = fn_add_update($strTbl,$arrData,$arrWhere); 
                    if(!empty($arrWhere) && $boolAdded )
                    {
                        $arrMsg = array('msg' => 'Slider Updated.','msgClass' =>'updated');
                        
                    }
                    elseif (empty($arrWhere) && $boolAdded) {
                        $arrMsg = array('msg' => 'Slider Added.','msgClass' =>'updated');
                        
                    }
                    else
                    {
                        $arrMsg = array('msg' => 'Error occured while saving your slider. OR Duplicate Image Order','msgClass' =>'error');
                    }
                }
            }
            
            if( isset($_GET['mode']) && ($_GET['mode'] == 'edit') ){
                if(isset($_GET['slider']))
                {
                    $intEditId = $_GET['slider'];
                    if($intEditId > 0)
                    {
                        $arrWhere = array("id=$intEditId");   
                        $arrSlideData = fn_edit_data($strTbl,$arrWhere);

                    }
                }
            }
            

            $objSlide->prepare_items();
            
            ?>
            
            <div class="wrap">
                
                <h2>Sliders</h2>
                <?php if(isset($arrMsg) && !empty($arrMsg)){ ?>
                    <div class="<?php echo $arrMsg['msgClass']; ?>">
                    <p><?php echo $arrMsg['msg']; ?></p>
                </div>
                <?php } 
                ?>
                <div id="col-container">
                    <div id="col-right">
                        <div class="col-wrap">
                            <div class="form-wrap">
                                <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
                                <form id="slider-filter" method="get">
                                    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                                   
                                    
                                    <!-- Now we can render the completed list table -->
                                    <?php $objSlide->display() ?>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div id="col-left">
                        <div class="col-wrap">
                            <div class="form-wrap">
                                <?php
                                    if(isset($intEditId)) 
                                    {
                                        $strLabel = "Edit";
                                    }
                                    else
                                    {
                                        $strLabel = "Add";
                                    }
                                ?>
                                <h3>
                                    <?php echo $strLabel; ?> Slider
                                    <?php if(isset($intEditId)) { ?>
                                    <a href="?page=mks-sliders" class="add-new-h2">Add New</a>
                                    <?php } ?>
                                </h3>
                                <form id="add_slide" name="add_slide" enctype="multipart/form-data" method="post" action="" class="frm_slide">
                                    <div class="form-field">
                                        <label for="Slider">Title<span class="chkRequired">*</span></label>
                                        <input type="text" size="40" class="required" value="<?php if(isset($arrSlideData->img_title)) {  echo stripslashes($arrSlideData->img_title);} ?>" id="img_title" name="img_title">
                                        <p>Title of Image.</p>
                                    </div>
                                    <div class="form-field">
                                        <label for="Slider">Image Description</label>
                                        <input type="text" size="40" value="<?php if(isset($arrSlideData->img_description)) {  echo stripslashes($arrSlideData->img_description);} ?>" id="img_description" name="img_description">
                                        <p>Description of Image</p>
                                    </div>
                                    <div class="form-field">
                                        <label for="Slider">Image Order<span class="chkRequired">*</span></label>
                                        <input type="number" size="40" class="required" value="<?php if(isset($arrSlideData->img_order)) {  echo stripslashes($arrSlideData->img_order);} ?>" id="img_order" name="img_order">
                                        <p>The image order of slide show.</p>
                                    </div>
                                    <div class="form-field">
                                        <label for="Slider">Slider Image</label>
                                        <input type="file" name="slide_img" id="slide_img">
                                        <?php if( isset($arrSlideData->slide_img) ):  ?>
                                        <img src="<?php echo slide_FILE_URL.$arrSlideData->slide_img; ?>" width="80" height="50"/>
                                    <?php endif; ?><br>
                                    </div>
                                    
                                    <p class="submit">
                                        <?php 
                                            $strBtn = 'Add';
                                            if(isset($_GET['slider']))
                                            {
                                                $strBtn = 'Update';
                                            }
                                        ?>
                                        <input type="hidden" value="<?php if(isset($_GET['slider'])){ echo $arrSlideData->id;} ?>" name="id">
                                        <input type="submit" value="<?php echo $strBtn; ?>" class="button button-primary" id="addSlides" name="addSlide">
                                    </p>
                                </form>
                            </div>
                        </div>
                    </div><!-- /col-left -->
                </div><!-- /col-container -->
            </div>
            <?php
            break;
    }
} 

/**
 * Function fn_register_shortcodes()  is used to register shortcode.
*/
function fn_register_shortcodes(){
    add_shortcode('myslideshow', 'fn_slider_shortcode');
}
add_action( 'init', 'fn_register_shortcodes');


function fn_slider_shortcode($atts){
        extract(shortcode_atts(array(
            'height' => '500',
            'width' => '1300',
          ), $atts));
    ob_start();
    require_once 'functions.php';
    include_once 'slider-page.php';

    $output_string = ob_get_contents();
    ob_end_clean();
    return $output_string;
}


/**
 * Function fn_add_settings_menu() is written to create sub menu Help.
*/ 
function fn_add_settings_menu(){
    include 'help.php';
}