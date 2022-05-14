<?php

if ( ! function_exists( 'write_my_log' ) ) {
 
    function write_my_log( $log ) {
        if ( true === WP_DEBUG && true === WP_DEBUG_LOG) {
            error_log( is_array( $log ) || is_object( $log ) ? print_r( $log, true ) : $log );
        }
    }
 
}

function wwp_custom_query_vars_filter($vars) {
    $vars[] .= 'classtitle';
    $vars[] .= 'classid';
    return $vars;
}
add_filter( 'query_vars', 'wwp_custom_query_vars_filter' );

add_action( 'wp_enqueue_scripts', 'enqueue_parent_styles' );
function enqueue_parent_styles() {
 wp_enqueue_style( 'parent-style', 
 get_template_directory_uri().'/style.css' );
 wp_enqueue_style( 'child-style',
 get_stylesheet_directory_uri() . '/style.css', array( 'parent-style' )
 );
}

add_shortcode( 'list-manuals', 'list_manuals_func' );
function list_manuals_func( $atts ) {
    $uploadDirArray = wp_get_upload_dir();
    $fullpath = $uploadDirArray['basedir'] . "/rv_manuals";
    $docs = scandir($fullpath);
    ?>
        <div class="center_table">
    <?php
    
    foreach ($docs as $doc) {
        if (strlen($doc) < 3) {
            continue;
        }
    
        $info = pathinfo($doc);
    
    ?>
        <a href="<?php echo $uploadDirArray['baseurl'] . "/rv_manuals/" . $info['basename'] ?>"><?php echo $info['filename']; ?> </a> <br>
    <?php
    }
    ?>
    </div>
    <?php
}

add_shortcode( 'monitorsignupform', 'monitorsignupform_func' );
function monitorsignupform_func( $atts ) {
    ?>
    <style>
    label { font-weight:bold; font-size:25px; margin-top: 10px; }
    input[type="radio"] { height: 20px; width: 20px; }
    .ml-10px { margin-left: 10px; }
    .radiodiv {
        margin: 20px 0;
        border: 1px solid black;
        padding: 10px 0px 10px 50px; 
        width: 300px;
    }

    .boldp {
        margin-top: 20px; 
        font-size:25px;
    }
</style>

<?php
global $wpdb;
$classid = "624f698a5ef363.98485273";
$row = null;
$mydb = null;
if (!isset($wpdb)) {
    include '.\MySqlDb.php';
    $mydb = new MySqlDb;
    define("OBJECT", "Nothing");
} else {
    $mydb = $wpdb;
    $classid = sanitize_text_field( get_query_var( 'classid' ) );
    if (strlen($classid) != 23) {
       echo "Input Validation failed. Contact System Administrator, aka Ed";
       exit();
    } 
}
$query = $wpdb->prepare("select * from shortnoticemonitors where Monitor_Secret = %s", $classid);
$result = $mydb->get_results($query, OBJECT);
$row = $result[0];
$originalSettings = print_r($row, 1);

if ($_POST) {
    $where = array("Monitor_Secret" => $row->Monitor_Secret);
    if ($_POST['Monitor_Arrive_SCW'] == '') {
        $_POST['Monitor_Arrive_SCW'] = null;
    }

    if ($_POST['Monitor_Depart_SCW'] == '') {
        $_POST['Monitor_Depart_SCW'] = null;
    }
    
    $dbResult = $mydb->update("shortnoticemonitors", $_POST, $where);
    
    if ($dbResult) {
        $result = $mydb->get_results($query, OBJECT);
        $row = $result[0];
    }
    
    $message = "ClassId: " . $classid . "\n\nOriginal Settings: \n" . 
        $originalSettings . "\n\n" . "New Settings \n" . print_r($row, 1);

    wp_mail("ecs3@po.cwru.edu", "Monitor Update" , $message);

}

$query = $wpdb->prepare("select * from shortnoticemonitors where Monitor_Secret = %s", $classid);
$result = $mydb->get_results($query, OBJECT);
$row = $result[0];
?>

<div id="content">
    <form  method="post">
        <label for="fullname">Full Name</label>
        <input class="ml-10px" type="text" name="Monitor_Name" id="fullname" value= <?php echo "'" . $row->Monitor_Name . "'" ?> required readonly><br>
        <label for="email">Email Address</label>
        <input class="ml-10px" type="email" name="Monitor_Email" id="email" value= <?php echo "'" . $row->Monitor_Email . "'" ?> required><br>
        <label for="phone">Phone for TEXT</label>
        <input class="ml-10px" type="tel" id="phone" name="Monitor_Phone" value= <?php echo "'" . $row->Monitor_Phone . "'" ?> pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" required><br>
        
        <p class="boldp"><b>Input dates if you are gone for an extended period of the year. <br> 
                            It is Ok to leave them blank.<br>
                        To clear the date click the calander can select the 'Clear' option <br></b></P>
        <label for="depart">SCW Depart Date:</label>
        <input class="ml-10px" type="date" id="depart" name="Monitor_Depart_SCW" value= <?php echo $row->Monitor_Depart_SCW ?>><br>
        <label for="arrive">SCW Arrival Date:</label>
        <input  class="ml-10px" type="date" id="arrive" name="Monitor_Arrive_SCW" value= <?php echo $row->Monitor_Arrive_SCW ?>>
    
        <div class="radiodiv">
        <label><input type="radio" id="email" name="Monitor_Preferred_Contact" value="email" <?php  if ($row->Monitor_Preferred_Contact == "email") { echo "checked='checked'"; } ?>>&ensp;Email Only</label><br>
        <label><input type="radio" id="text" name="Monitor_Preferred_Contact" value="text" <?php  if ($row->Monitor_Preferred_Contact == "text") { echo "checked='checked'"; } ?>>&ensp;Text Only</label><br>
        <label><input type="radio" id="both" name="Monitor_Preferred_Contact" value="both" <?php  if ($row->Monitor_Preferred_Contact == "both") { echo "checked='checked'"; } ?>>&ensp;Both</label><br>
        <label><input type="radio" id="none" name="Monitor_Preferred_Contact" value="none" <?php  if ($row->Monitor_Preferred_Contact == "none") { echo "checked='checked'"; } ?>>&ensp;None</label>
        </div>

        <input type="submit" value="Submit">
    </form>
</div>
<?php
}

define('temp_file', ABSPATH.'/_temp_out.txt' );

add_action("activated_plugin", "activation_handler1");
function activation_handler1(){
    $cont = ob_get_contents();
    if(!empty($cont)) file_put_contents(temp_file, $cont );
}

add_action( "pre_current_active_plugins", "pre_output1" );
function pre_output1($action){
    if(is_admin() && file_exists(temp_file))
    {
        $cont= file_get_contents(temp_file);
        if(!empty($cont))
        {
            echo '<div class="error"> Error Message:' . $cont . '</div>';
            @unlink(temp_file);
        }
    }
}