<?php

/*
 * Plugin Admin Settings
 */


add_action( 'admin_menu', 'ga_events_menu');

function ga_events_menu() {
    add_menu_page('WP Google Analytics Settings','WP GA Events','manage_options', 'wp-google-analytics-events', 'ga_events_settings_page', plugins_url( 'images/icon.png', dirname(__FILE__)));
    add_submenu_page('wp-google-analytics-events','General Settings','General Settings', 'manage_options', 'wp-google-analytics-events' , 'ga_events_settings_page' );
    add_submenu_page('wp-google-analytics-events','Click Tracking','Click Tracking', 'manage_options', 'wp-google-analytics-events-click' , 'ga_events_settings_page' );
    add_submenu_page('wp-google-analytics-events','Scroll Tracking','Scroll Tracking', 'manage_options', 'wp-google-analytics-events-scroll' , 'ga_events_settings_page' );
    add_submenu_page('wp-google-analytics-events','Upgrade','Upgrade Now', 'manage_options', 'wp-google-analytics-events-upgrade', 'ga_events_settings_page' );
}

function ga_events_settings_page() {

	?>
    <link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css" rel="stylesheet">
    <div id="ga_main" class="wrap">
        <?php screen_icon( 'plugins' ); ?>
    <h2>GA Scroll Events Plugin</h2>

			<?php
            $active_page = isset( $_GET[ 'page' ] ) ? $_GET[ 'page' ] : 'wp-google-analytics-events';
      ?>
			<h2 class="nav-tab-wrapper">
            <a href="?page=wp-google-analytics-events" class="nav-tab <?php echo $active_page == 'wp-google-analytics-events' ? 'nav-tab-active' : ''; ?>">General Settings</a>
            <a href="?page=wp-google-analytics-events-click" class="nav-tab <?php echo $active_page == 'wp-google-analytics-events-click' ? 'nav-tab-active' : ''; ?>">Click Tracking</a>
            <a href="?page=wp-google-analytics-events-scroll" class="nav-tab <?php echo $active_page == 'wp-google-analytics-events-scroll' ? 'nav-tab-active' : ''; ?>">Scroll Tracking</a>
        </h2>

        <form method="post" action='options.php'>
            <?php settings_fields('ga_events_options'); ?>
            <?php
								$show_sidebar = true;
                if ($active_page == 'wp-google-analytics-events-click') {
                    do_settings_sections('ga_events_click');
										$show_sidebar = false;
                } else if ($active_page == 'wp-google-analytics-events-scroll') {
                    do_settings_sections('ga_events_scroll');
										$show_sidebar = false;
                } else {
                    do_settings_sections('ga_events');
                }
            ?>
						<input class="button-primary" type="submit" name="submit" value="Save Changes" />
        </form>
				        <div class="settings_content">
            <form action="" method="post" enctype="multipart/form-data">
                <a href="#" class="btn_close"><img src="<?=plugins_url( 'images/close.png', dirname(__FILE__))?>"></a>
                <input type="file" name="settings">
                <input type="submit" name="set_settings">
            </form>
        </div>
    </div>
   <?php
	 	if ($show_sidebar) {
		?>
	 <div class="wrap ga_events_sidebar">
        <table class="form-table widefat" >
            <thead>
                <th>Upgrade to Pro</th>
            </thead>
            <tbody>
                <tr class="features">
                    <td>
                        <ul>
                            <li><i class="fa fa-check-square-o fa-lg"></i><strong>One Checkbox Link Tracking</strong></li>
                            <li><i class="fa fa-check-square-o fa-lg"></i><strong>YouTube Video Tracking</strong></li>
                            <li><i class="fa fa-check-square-o fa-lg"></i><strong>Shortcode support</strong></li>
                            <li><i class="fa fa-check-square-o fa-lg"></i><strong>Set Value for Events</strong></li>
                            <li><i class="fa fa-check-square-o fa-lg"></i><strong>HTML Tag support</strong></li>
                            <li><i class="fa fa-check-square-o fa-lg"></i><strong>Bounce Rate Control</strong></li>
                            <li><i class="fa fa-check-square-o fa-lg"></i><strong>Pro Support</strong></li>
                        </ul>
                    </td>
                </tr>
                <tr class="tfoot">
                    <td>
                        <div class="wpcta">
                            <a class="btn btn-subscribe" href="http://wpflow.com/upgrade/">
                                <span class="btn-title ">
                                    Starting from
                                    <span class="btn-data">
                                        <span class="price">$29</span>
                                    </span>
                                </span>
                            </a>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <!-- Begin MailChimp Signup Form -->
        <link href="//cdn-images.mailchimp.com/embedcode/slim-081711.css" rel="stylesheet" type="text/css">
        <style type="text/css">
            #mc_embed_signup{background:#fff; clear:left; font:13px Helvetica,Arial,sans-serif;  width:250px; margin-top:30px; }
                /* Add your own MailChimp form style overrides in your site stylesheet or in this style block.
                   We recommend moving this block and the preceding CSS link to the HEAD of your HTML file. */
        </style>
        <div id="mc_embed_signup">
            <form action="http://wpflow.us6.list-manage.com/subscribe/post?u=3a1990ecd0eee6198c11e5fc1&amp;id=7e01c30e7f" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
                <div class="form-content">
                    <h4>Receive plugin updates, news, promo codes and more</h4>
                    <label for="mce-EMAIL">Subscribe to our mailing list</label>
                    <input type="email" value="" name="EMAIL" class="email" id="mce-EMAIL" placeholder="email address" required>
                    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
                    <div style="position: absolute; left: -5000px;"><input type="text" name="b_3a1990ecd0eee6198c11e5fc1_7e01c30e7f" value=""></div>
                </div>
                <div class="clear"><input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="btn-subscribe "></div>
            </form>
        </div>

        <!--End mc_embed_signup-->
    </div>
		<?php } ?>


<?php

    echo "<script>
            jQuery('.remove').click(function (event) {
                event.preventDefault();
                jQuery(this).closest('tr').remove();
            });
            jQuery('.add').click(function (event) {
                event.preventDefault();
            });
          </script>
    ";
}

function load_custom_wp_admin_style() {
    wp_register_style( 'custom_wp_admin_css', plugins_url('css/style.css', dirname(__FILE__)));
    wp_enqueue_style( 'custom_wp_admin_css' );
		wp_enqueue_script( 'admin-init', plugins_url('js/admin.js', dirname(__FILE__)) , array('jquery'), null, true );

}

add_action( 'admin_enqueue_scripts', 'load_custom_wp_admin_style' );

add_action('admin_init', 'ga_events_admin_init');

function ga_events_admin_init() {


		if(isset($_GET['download']) && isset($_GET['page'])){
					if ($_GET['page'] == 'wp-google-analytics-events') {
										ga_events_file();
												}
		}

		if(isset($_POST['set_settings'])){
					ga_events_upload_settings($_FILES);
		}


    register_setting('ga_events_options','ga_events_options','ga_events_validate');
    add_settings_section('ga_events_main','WP Google Analytics Events Settings', 'ga_events_section_text','ga_events');
    add_settings_section('ga_events_click_section','Click Tracking', 'ga_events_section_text','ga_events_click');
    add_settings_section('ga_events_scroll_section','Scroll Tracking', 'ga_events_section_text','ga_events_scroll');
    add_settings_field('ga_events_id', '','ga_events_setting_input','ga_events','ga_events_main');
    add_settings_field('ga_events_exclude_snippet', '','ga_events_setting_snippet_input','ga_events','ga_events_main');
    add_settings_field('ga_events_universal', '','ga_events_setting_uni_input','ga_events','ga_events_main');
    add_settings_field('ga_events_divs', '','ga_events_setting_divs_input','ga_events_scroll','ga_events_scroll_section');
    add_settings_field('ga_events_click', '','ga_events_setting_click_input','ga_events_click','ga_events_click_section');
    add_settings_field('ga_events_sidebar', '','ga_events_setting_sidebar','ga_events','ga_events_main');
    add_settings_field('ga_events_download_settings', '','ga_events_settings_download','ga_events','ga_events_main');
    add_settings_field('ga_events_upload_settings', '','ga_events_settings_upload','ga_events','ga_events_main');


}

function ga_events_section_text() {
    echo "<a href='http://wpflow.com/documentation'>Plugin Documentation</a>";
}

function ga_events_setting_input() {
    $options = get_option('ga_events_options');
    $id = $options['id'];
    echo "<label>Google Analytics Identifier</label>";
    echo "<span class='ga_intable'><input class='inputs' id='id' name='ga_events_options[id]' type='text' value='$id' /></span>";

}


function ga_events_setting_snippet_input() {
    $options = get_option('ga_events_options');
    $id = $options['exclude_snippet'];
    echo "<label>Don't add the GA tracking code</label>";
    echo "<span class='ga_intable'><input id='snippet' name='ga_events_options[exclude_snippet]' type='checkbox' value='1' " . checked( $id , 1,false) . " /></span>";

}

function ga_events_setting_uni_input() {
    $options = get_option('ga_events_options');
    $id = $options['universal'];
    echo "<label>Universal Tracking Code</label>";
    echo "<span class='ga_intable'><input id='universal' name='ga_events_options[universal]' type='checkbox' value='1' " . checked( $id , 1,false) . " /></span>";
}

function ga_events_settings_download(){
	    echo '<a class="button" href="http://'.$_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] .'&download=1">Export settings</a>';
}

function ga_events_settings_upload(){
	    echo '<a href="#" class="button btn_upload">Import settings</a>';
}

function ga_events_setting_divs_input() {
    $options = get_option('ga_events_options');
    $divs= $options['divs'];
    echo "<label>Scroll Events</label><br />";
    echo "<table class='widefat inputs inner_table'><thead><th>Element Name</th><th>Type</th><th>Event Category</th><th>Event Action</th><th>Event Label</th><th></th></thead><tbody>";
    if (!($divs[0][0])){
        echo "<tr>";
        echo "<td><input id='divs' name='ga_events_options[divs][0][0]' type='text' value='".$divs[0][0]."' /></td>";
        echo "<td><select id='divs' name='ga_events_options[divs][0][1]'><option selected value='id' >id</option><option value='class'>class</option></select></td>";
        echo "<td><input id='divs' name='ga_events_options[divs][0][2]' type='text' value='".$divs[0][2]."' /></td>";
        echo "<td><input id='divs' name='ga_events_options[divs][0][3]' type='text' value='".$divs[0][3]."' /></td>";
        echo "<td><input id='divs' name='ga_events_options[divs][0][4]' type='text' value='".$divs[0][4]."' /></td>";
        echo "</tr>";

    }else{
        for ($i = 0; $i < sizeof($divs)+1; $i++){

                echo "<tr>";
                echo "<td><input id='divs' name='ga_events_options[divs][$i][0] type='text' value='".$divs[$i][0]."' /></td>";
                echo "<td><select id='divs' name='ga_events_options[divs][$i][1]'>";
                if ($divs[$i][1] == 'id'){
                    echo "<option selected value='id' >id</option><option value='class'>class</option></select></td>";
                }else {
                    echo "<option  value='id' >id</option><option selected value='class'>class</option></select></td>";
                }
                echo "<td><input id='divs' name='ga_events_options[divs][$i][2]' type='text' value='".$divs[$i][2]."' /></td>";
                echo "<td><input id='divs' name='ga_events_options[divs][$i][3]' type='text' value='".$divs[$i][3]."' /></td>";
                echo "<td><input id='divs' name='ga_events_options[divs][$i][4]' type='text' value='".$divs[$i][4]."' /></td>";
                echo "<td><a class='remove' href=''>Remove</a></td>";
                echo "</tr>";

        }

    }
    echo "</tbody></table>";
}


function ga_events_setting_click_input() {
    $options = get_option('ga_events_options');
    $click = $options['click'];
    echo "<label>Click Events</label><br />";
    echo "<table class='widefat inputs inner_table'><thead><th>Element Name</th><th>Type</th><th>Event Category</th><th>Event Action</th><th>Event Label</th><th></th></thead><tbody>";
    if (!($click[0][0])){
        echo "<tr>";
        echo "<td><input id='click' name='ga_events_options[click][0][0]' type='text' value='".$click[0][0]."' /></td>";
        echo "<td><select id='click' name='ga_events_options[click][0][1]'><option selected value='id'>id</option><option value='class'>class</option></select></td>";
        echo "<td><input id='click' name='ga_events_options[click][0][2]' type='text' value='".$click[0][2]."' /></td>";
        echo "<td><input id='click' name='ga_events_options[click][0][3]' type='text' value='".$click[0][3]."' /></td>";
        echo "<td><input id='click' name='ga_events_options[click][0][4]' type='text' value='".$click[0][4]."' /></td>";
        echo "</tr>";

    }else{
        for ($i = 0; $i < sizeof($click)+1; $i++){

                echo "<tr>";
                echo "<td><input id='divs' name='ga_events_options[click][$i][0] type='text' value='".$click[$i][0]."' /></td>";
                echo "<td><select id='click' name='ga_events_options[click][$i][1]'>";
                if ($click[$i][1] == 'id'){
                    echo "<option selected value='id' >id</option><option value='class'>class</option></select></td>";
                }else {
                    echo "<option  value='id' >id</option><option selected value='class'>class</option></select></td>";
                }
                echo "<td><input id='click' name='ga_events_options[click][$i][2] type='text' value='".$click[$i][2]."' /></td>";
                echo "<td><input id='click' name='ga_events_options[click][$i][3] type='text' value='".$click[$i][3]."' /></td>";
                echo "<td><input id='click' name='ga_events_options[click][$i][4] type='text' value='".$click[$i][4]."' /></td>";
                echo "<td><a class='remove' href=''>Remove</a></td>";
                echo "</tr>";

        }

    }
    echo "</tbody></table>";


}

function ga_events_setting_sidebar(){
}

function ga_events_validate($form){
    $options = get_option('ga_events_options');
		$updated = $options;
    if (array_key_exists('divs', $form)) {
			$updated['divs'] = array();
			for ($i = 0, $j = 0; $i< sizeof($form['divs']); $i++){
					if ($form['divs'][$i][0]){
							$updated['divs'][$j] = $form['divs'][$i];
							$j++;
					}
			}
		} else if(array_key_exists('click', $form)) {
			$updated['click'] = array();
			for ($i = 0, $j = 0; $i< sizeof($form['click']); $i++){
					if ($form['click'][$i][0]){
							$updated['click'][$j] = $form['click'][$i];
							$j++;
					}
			}
		} else {
    	$updated['id'] = $form['id'];
    	$updated['exclude_snippet'] = $form['exclude_snippet'];
    	$updated['universal'] = $form['universal'];
		}

    return $updated;
}


add_action('admin_footer', 'ga_events_admin_footer');

function ga_events_admin_footer() {
?>
	<script>
		jQuery('body').on('click','a[href="admin.php?page=wp-google-analytics-events-upgrade"]', function (e) {
					e.preventDefault();
					window.open('http://wpflow.com/upgrade', '_blank');
				});
	</script>
	<?php
}


function ga_events_get_settings(){
	    $options = get_option('ga_events_options');
			    $current = json_encode($options);

					    return $current;
}

function ga_events_upload_settings($file){
	    $uploadedfile = $file['settings'];
			    if($uploadedfile['type'] != 'application/octet-stream'){
						        ga_event_popup();
										        return;
														    }
					    $content = file_get_contents($uploadedfile["tmp_name"]);

							    ga_event_get_content($content);
}

function ga_event_get_content($content){
	    if(!$current = json_decode($content,true)){
				        ga_event_popup();
								        return;
												    }
			    if (!array_key_exists('id', $current) && !array_key_exists('domain', $current)) {
						        ga_event_popup();
										        return;
														    }
					    update_option( 'ga_events_options', $current );

}
function ga_event_popup(){
	    echo "<dev class='popup'>";
			echo '<h1>Wrong file format <a href="#" class="btn_close_popup"><img src="'.plugins_url( 'images/close.png', dirname(__FILE__)).'"></a></h1>';
			echo "</dev>";
}
function ga_events_file(){
	    header("Content-type: text/plain");
			header("Content-Disposition: attachment; filename='settings.ini'");
			   echo ga_events_get_settings();
							    exit();
}

?>
