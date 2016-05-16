<?php

global $wpdb;
$strTbl = $wpdb->prefix."mks_slideshow";
$strQuery = "SELECT slide_img, img_order FROM ".$strTbl ." ORDER BY img_order ASC";        
$arrData = $wpdb->get_results($strQuery );

$arrow = (1300 - $width) + 12;

?>

    <script type="text/javascript" src="<?php echo JS_PATH; ?>jquery-1.11.3.min.js"></script>
    <script type="text/javascript" src="<?php echo JS_PATH; ?>jssor.slider.mini.js"></script>
    <script type="text/javascript" src="<?php echo JS_PATH; ?>custom.js"></script>
    <link rel="stylesheet" type="text/css" href="<?php echo CSS_PATH; ?>custom.css">


    <div id="jssor_1" style="position: relative; margin: 0 auto; top: 0px; left: 0px; width: 1300px; height: 500px; overflow: hidden; visibility: hidden;">
        <!-- Loading Screen -->
        <div data-u="loading" style="position: absolute; top: 0px; left: 0px;">
            <div style="filter: alpha(opacity=70); opacity: 0.7; position: absolute; display: block; top: 0px; left: 0px; "></div>
            <div style="position:absolute;display:block;background:url('<?php echo IMG_PATH; ?>loading.gif') no-repeat center center;top:0px;left:0px;width:100%;height:100%;"></div>
        </div>
        <div data-u="slides" style="cursor: default; position: relative; top: 0px; left: 0px; width: <?php echo $width; ?>px; height: <?php echo $height; ?>px; overflow: hidden;">
            <?php
                foreach ($arrData as $strKey => $strValue) { ?>
                    <div data-p="225.00" style="display: none;">
                        <img data-u="image" src="<?php echo slide_FILE_URL.$strValue->slide_img; ?>" />           
                    </div>
              <?php  }
            ?>            
        </div>
        <!-- Bullet Navigator -->
        <div data-u="navigator" class="jssorb05" style="bottom:16px;right:16px;" data-autocenter="1">
            <!-- bullet navigator item prototype -->
            <div data-u="prototype" style="width:16px;height:16px;"></div>
        </div>
        <!-- Arrow Navigator -->
        <span data-u="arrowleft" class="jssora22l" style="top:0px;left:12px;width:40px;height:58px;" data-autocenter="2"></span>
        <span data-u="arrowright" class="jssora22r" style="top:0px;right:<?php echo $arrow; ?>px;width:40px;height:58px;" data-autocenter="2"></span>
    </div>