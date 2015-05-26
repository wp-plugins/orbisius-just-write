<?php

$orb_just_write_admin_obj = orb_just_write_admin::get_instance();

add_action('admin_init', array($orb_just_write_admin_obj, 'register_admin_settings'));
//add_action('admin_init', array($orb_just_write_admin_obj, 'add_buttons'));
add_action('admin_menu', array($orb_just_write_admin_obj, 'setup_admin_menu'));
add_action('wp_before_admin_bar_render', array($orb_just_write_admin_obj, "add2admin_bar") );

/**
 * Description of admin
 *
 * @author User
 */
class orb_just_write_admin {
    public function add2admin_bar() {
        global $wp_admin_bar;

        $icon_url = ORBISIUS_JUST_WRITE_URL . 'modules/images/icon.png';

        $wp_admin_bar->add_node(array(
            'id'    => __CLASS__ . '-toolbar',
            'title' => "<img style='vertical-align:middle;' alt='' src='$icon_url' /> " . ORBISIUS_JUST_WRITE_PLUGIN_NAME,
            'href' => site_url('/?orbisius_just_write'),
            'meta' => array(
                "class" => __CLASS__ . '-toolbar',
                'target' => '_blank',
            ),
        ));
    }

    /**
     * Singleton pattern i.e. we have only one instance of this obj
     * 
     * @staticvar type $instance
     * @return \cls
     */
    public static function get_instance() {
        static $instance = null;

        if (is_null($instance)) {
            $cls = __CLASS__;
            $instance = new $cls();
        }

        return $instance;
    }

    private $plugin_tinymce_name = 'orbisius_just_write'; // i
    
    /**
     * Adds buttons only for RichText mode
     * @return void
     */
    function add_buttons() {
        // Don't bother doing this stuff if the current user lacks permissions
        if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
            return;
        }

        // Add only in Rich Editor mode
        if ( get_user_option('rich_editing') == 'true' ) {
            // add the button for wp2.5 in a new way
            add_filter( 'mce_buttons', array( $this, 'register_button' ), 5 );
            add_filter( "mce_external_plugins", array( $this, "add_tinymce_plugin" ), 5);

            // Required by TinyMCE button
            add_action( 'wp_ajax_orb_just_write_ajax_render_popup_content', array( $this, 'ajax_render_popup_content' ) );
        }
    }

    /**
     * This is triggered by editor_plugin.min.js and WP proxies the ajax calls to this action.
     *
     * @return void
     */
    function ajax_render_popup_content() {
        if ( ! is_user_logged_in() ) { // check for rights. This shouldn't be necessary as we don't handle nopriv ajax call.
            wp_die(__("You must be logged in order to use this plugin."));
        }

        $site_url = site_url();
        $plugin_title = ORBISIUS_JUST_WRITE_PLUGIN_NAME;

        ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <title><?php echo $plugin_title; ?></title>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <script language="javascript" type="text/javascript" src="<?php echo $site_url; ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
                <script language="javascript" type="text/javascript" src="<?php echo $site_url; ?>/wp-includes/js/tinymce/utils/mctabs.js"></script>
                <script language="javascript" type="text/javascript" src="<?php echo $site_url; ?>/wp-includes/js/tinymce/utils/form_utils.js"></script>

                <script language="javascript" type="text/javascript">
                    function orb_just_write_js_init() {
                        tinyMCEPopup.resizeToInnerSize();
                    }

                    function orb_just_write_js_insert_shortcode() {
                        var content = '';
                        var attribs_str = '';
                        var template_recently_viewed = '<br/><p>[orb_just_write %%ATTRIBS%%]</p><br/>';
                        var template_related = '<br/><p>[orb_just_write_related %%ATTRIBS%%]</p><br/>';

                        var orb_just_write = document.getElementById('orb_just_write_panel');

                        // who is active ?
                        if (orb_just_write.className.indexOf('current') != -1) {
                            var limits = document.getElementById('limits').value;
                            limits = parseInt(limits);

                            if ( limits > 0 ) {
                                attribs_str += ' limit="' + limits + '"';
                            }

                            var label = document.getElementById('orb_just_write_label').value;

                            if ( label != '' ) {
                                attribs_str += ' label="' + label + '"';
                            }

                            content = document.getElementById('orb_just_write_type').checked
                                ? template_recently_viewed
                                : template_related;

                            content = content.replace(/\s*%%ATTRIBS%%/ig, attribs_str);
                        }

                        if (window.tinyMCE) {
                            /* get the TinyMCE version to account for API diffs */
                            // @see http://stackoverflow.com/questions/22813970/typeerror-window-tinymce-execinstancecommand-is-not-a-function
                            var tmce_ver = window.tinyMCE.majorVersion;

                            if (tmce_ver >= "4") {
                                window.tinyMCE.execCommand('mceInsertContent', false, content);
                            } else {
                                window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, content);
                            }

                            //Peforms a clean up of the current editor HTML.
                            //tinyMCEPopup.editor.execCommand('mceCleanup');
                            //Repaints the editor. Sometimes the browser has graphic glitches.
                            tinyMCEPopup.editor.execCommand('mceRepaint');
                            tinyMCEPopup.close();
                        }

                        return;
                    }
                </script>
                <style>
                    body {
                        color: #444;
                        font-family: "Open Sans",sans-serif;
                        font-size: 13px;
                        line-height: 1.4em;
                    }

                    .tabs a {
                        font-size: 13px;
                    }

                    .orb_just_write .app_positive_button {
                        background:#99CC66 !important;
                        color: black !important;
                    }

                    .orb_just_write .app_negative_button {
                        background:#F19C96 !important;
                        color: black !important;
                    }

                    .orb_just_write .app_max_width {
                        width: 100%;
                    }

                    .orb_just_write .app_text_field {
                        border: 1px solid #888888;
                        padding: 3px;
                    }
                </style>
                <base target="_self" />
            </head>
            <body id="orb_just_write" class="orb_just_write"
                  onload="tinyMCEPopup.executeOnLoad('orb_just_write_js_init();');
                        document.body.style.display = '';"
                  style="display: none">
                <form name="orb_just_write_form" action="#">
                    <div class="tabs">
                        <ul>
                            <li id="orb_just_write_tab" class="current"><span><a href="javascript:mcTabs.displayTab('orb_just_write_tab','orb_just_write_panel');" onmousedown="return false;"><?php _e("Orbisius Just Write", 'orb_just_write'); ?></a></span></li>
                        </ul>
                    </div>

                    <div class="panel_wrapper">
                        <!-- panel -->
                        <div id="orb_just_write_panel" class="panel current">
                            <br />
                            <div>
                                Please fill out the fields and click insert to insert the shortcode into the post/page.
                            </div>
                            <br/>
                            <table border="0" cellpadding="4" cellspacing="0" class="app_max_width">
                                <tr>
                                    <td nowrap="nowrap">
                                        <label for="orb_just_write_product_name"><?php _e("Type", 'orb_just_write'); ?></label>
                                    </td>
                                    <td>
                                        <label for="orb_just_write_type">
                                            <input type="radio" id='orb_just_write_type' name='orb_just_write_type' value="recently_viewed" checked="checked" /> Recently Viewed
                                        </label>
                                        <br />

                                        <label for="orb_just_write_type2">
                                            <input type="radio" id='orb_just_write_type2' name='orb_just_write_type' value="related" /> Related
                                        </label>
                                        <br />
                                    </td>
                                </tr>
                                <tr>
                                    <td nowrap="nowrap">
                                        <label for="orb_just_write_label"><?php _e("Label", 'orb_just_write'); ?></label>
                                    </td>
                                    <td>
                                        <input type="text" id='orb_just_write_label' class="app_max_width" name='orb_just_write_label' value="" placeholder="Text shown before items (optional)" />
                                    </td>
                                </tr>
                                <tr>
                                    <td nowrap="nowrap">
                                        <label for="orb_just_write_product_name"><?php _e("Items per page", 'orb_just_write'); ?></label>
                                    </td>
                                    <td>
                                        <input type="number" id='limits' name='limits' placeholder="Limits (optional)" />
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <!-- end panel -->
                    </div>

                    <div class="mceActionPanel">
                        <div style="float: left">
                            <input type="submit" id="insert" name="insert" value="<?php _e("Insert", 'orb_just_write'); ?>"
                                   class='app_positive_button'
                                   onclick="orb_just_write_js_insert_shortcode(); return false;" />
                        </div>

                        <div style="float: right">
                            <input type="button" id="cancel" name="cancel" value="<?php _e("Cancel", 'orb_just_write'); ?>"
                                   class='app_negative_button'
                                   onclick="tinyMCEPopup.close();" />
                        </div>
                    </div>
                </form>
            </body>
        </html>
        <?php
        die(); // This is required to return a proper result
    }



    // used to insert button in wordpress 2.5x editor
    function register_button($buttons) {
        array_push($buttons, "separator", $this->plugin_tinymce_name);
        return $buttons;
    }

    // Load the TinyMCE plugin : editor_plugin.js (wp2.5)
    function add_tinymce_plugin($plugin_array) {
        $plugin_array[$this->plugin_tinymce_name] = plugins_url('/modules/tinymce/editor_plugin.js', ORBISIUS_JUST_WRITE_BASE_PLUGIN_FILE);
        return $plugin_array;
    }

    /**
     * We don't want any assholes using the site.
     */
    public function register_admin_settings() {
        register_setting('orb_just_write_settings', 'orb_just_write_options', array($this, 'validate_settings'));
    }

    /**
     * This is called by WP after the user hits the submit button.
     * The variables are trimmed first and then passed to the who ever wantsto filter them.
     * @param array the entered data from the settings page.
     * @return array the modified input array
     * @todo force numbers for 2 params
     */
    function validate_settings($input) { // whitelist options
        $input = array_map('trim', $input);

        // let extensions do their thing
        $input_filtered = apply_filters('orb_just_write_admin_filter_settings', $input);

        // did the extension break stuff?
        $input = is_array($input_filtered) ? $input_filtered : $input;

        return $input;
    }

    /**
     * Set up administration
     *
     * @package Orbisius Just Write
     * @since 0.1
     */
    public function setup_admin_menu() {
        $hook = add_options_page(ORBISIUS_JUST_WRITE_PLUGIN_NAME, ORBISIUS_JUST_WRITE_PLUGIN_NAME, 'manage_options', ORBISIUS_JUST_WRITE_BASE_PLUGIN_FILE, array($this, 'output_options_page'));
        add_filter('plugin_action_links', array($this, 'add_quick_settings_link'), 10, 2);

        /*$menu_slug = 'orbisius_just_write';
        $callback = array($this, 'output_options_page');
        add_submenu_page( 'tools.php', ORBISIUS_JUST_WRITE_PLUGIN_NAME, ORBISIUS_JUST_WRITE_PLUGIN_NAME, 'edit_others_posts', $menu_slug, $callback);*/
    }

    /**
     * Adds the action link to settings. That's from Plugins. It is a nice thing.
     * @param type $links
     * @param type $file
     * @return type
     */
    function add_quick_settings_link($links, $file) {
        if ($file == plugin_basename(ORBISIUS_JUST_WRITE_BASE_PLUGIN_FILE)) {
            $link = admin_url('options-general.php?page=' . plugin_basename(ORBISIUS_JUST_WRITE_BASE_PLUGIN_FILE));
            $html_link = "<a href=\"{$link}\">Settings</a>";
            array_unshift($links, $html_link);
        }

        return $links;
    }

    /**
     * Retrieves the plugin options. It inserts some defaults.
     * The saving is handled by the settings page. Basically, we submit to WP and it takes
     * care of the saving.
     *
     * @return array
     */
    function get_options() {
        $defaults = array(
            'limit' => 5,
        );

        $opts = get_option('orb_just_write_options');

        $opts = (array) $opts;
        $opts = array_merge($defaults, $opts);

        return $opts;
    }

    /**
     * Options page
     *
     * @package Orbisius Just Write
     * @since 1.0
     */
    function output_options_page() {
        $opts = $this->get_options();
        ?>

        <div class="wrap orb_just_write_admin_wrapper orb_just_write_container">

            <div id="icon-options-general" class="icon32"></div>
            <h2><?php echo ORBISIUS_JUST_WRITE_PLUGIN_NAME; ?></h2>

            <div id="poststuff">

                <div id="post-body" class="metabox-holder columns-2">

                    <!-- main content -->
                    <div id="post-body-content">

                        <div class="meta-box-sortables ui-sortable">
                            <?php do_action( 'orbisius_plugin_license_section_' . ORBISIUS_JUST_WRITE_BASE_PLUGIN_FILE ); ?>
                            
                            <div class="postbox">
                                <!--<h3><span>Settings</span></h3>-->
                                <div class="inside">
                                    <?php if (0) : ?>
                                        <form method="post" action="options.php">
                                            <?php settings_fields('orb_just_write_settings'); ?>
                                            <table class="form-table">

                                                <tr valign="top">
                                                    <th scope="row">Recently Viewed Limit </th>
                                                    <td>
                                                        <label for="orb_just_write_options_limit">
                                                            <input type="number" id="orb_just_write_options_limit"
                                                                   name="orb_just_write_options[limit]"
                                                                   value="<?php echo $opts['limit']; ?>" />
                                                        </label>
                                                    </td>
                                                </tr>
                                               
                                            </table>

                                            <p class="submit">
                                                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
                                            </p>
                                        </form>
                                    <?php else : ?>
                                        <div>
                                            The plugin doesn't have settings options at the moment.
                                        </div>

                                    <?php endif; ?>
                                </div> <!-- .inside -->
                            </div> <!-- .postbox -->

                            <div class="postbox">

                                <h3><span>Content</span></h3>
                                <div class="inside">
                                    <a href='<?php echo site_url('/?orbisius_just_write'); ?>' class="button-primary" target="_blank">Write Something Awesome</a>
                                </div> <!-- .inside -->

                            </div> <!-- .postbox -->

                            <?php if (0) : ?>
                                <!-- Demo -->
                                <div class="postbox">
                                    <h3><span>Demo</span></h3>
                                    <div class="inside">


                                            <p>
                                                Link: <a href="http://www.youtube.com/watch?v=RsRBmCGuz1w&hd=1" target="_blank" title="[opens in a new and bigger tab/window]">http://www.youtube.com/watch?v=RsRBmCGuz1w&hd=1</a>
                                            <p>
                                                <iframe width="640" height="480" src="http://www.youtube.com/embed/RsRBmCGuz1w?hl=en&fs=1" frameborder="0" allowfullscreen></iframe>
                                            </p>

                                            <?php
                                            $plugin_data = get_plugin_data(ORBISIUS_JUST_WRITE_BASE_PLUGIN_FILE);
                                            $product_name = trim($plugin_data['Name']);
                                            $product_page = trim($plugin_data['PluginURI']);
                                            $product_descr = trim($plugin_data['Description']);
                                            $product_descr_short = substr($product_descr, 0, 50) . '...';

                                            $product_name .= ' #WordPress #plugin';
                                            $product_descr_short .= ' #WordPress #plugin';

                                            $base_name_slug = basename(ORBISIUS_JUST_WRITE_BASE_PLUGIN_FILE);
                                            $base_name_slug = str_replace('.php', '', $base_name_slug);
                                            $product_page .= (strpos($product_page, '?') === false) ? '?' : '&';
                                            $product_page .= "utm_source=$base_name_slug&utm_medium=plugin-settings&utm_campaign=product";

                                            $product_page_tweet_link = $product_page;
                                            $product_page_tweet_link = str_replace('plugin-settings', 'tweet', $product_page_tweet_link);

                                            $app_link = 'http://www.youtube.com/embed/RsRBmCGuz1w?hl=en&fs=1';
                                            $app_title = esc_attr($product_name);
                                            $app_descr = esc_attr($product_descr_short);
                                            ?>
                                            <p>Share this video:
                                                <!-- AddThis Button BEGIN -->
                                            <div class="addthis_toolbox addthis_default_style addthis_32x32_style">
                                                <a class="addthis_button_facebook" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                                <a class="addthis_button_twitter" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                                <a class="addthis_button_google_plusone" g:plusone:count="false" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                                <a class="addthis_button_linkedin" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                                <a class="addthis_button_email" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                                <a class="addthis_button_myspace" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                                <a class="addthis_button_google" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                                <a class="addthis_button_digg" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                                <a class="addthis_button_delicious" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                                <a class="addthis_button_stumbleupon" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                                <a class="addthis_button_tumblr" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                                <a class="addthis_button_favorites" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                                <a class="addthis_button_compact"></a>
                                            </div>
                                            <!-- The JS code is in the footer -->
                                            </p>

                                            <script type="text/javascript">
                                                var addthis_config = {"data_track_clickback": true};
                                                var addthis_share = {
                                                    templates: {twitter: 'Check out {{title}} @ {{lurl}} (from @orbisius)'}
                                                }
                                            </script>
                                            <!-- AddThis Button START part2 -->
                                            <script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=lordspace"></script>
                                            <!-- AddThis Button END part2 -->
                                            </p>

                                    </div> <!-- .inside -->
                                </div> <!-- .postbox -->
                                <!-- /Demo -->
                            <?php endif; ?>

                        </div> <!-- .meta-box-sortables .ui-sortable -->

                    </div> <!-- post-body-content -->

                    <!-- sidebar -->
                    <div id="postbox-container-1" class="postbox-container">

                        <div class="meta-box-sortables">

                            <!-- Newsletter-->
                            <div class="postbox">
                                <h3><span>Newsletter</span></h3>
                                <div class="inside">
                                    <!-- Begin MailChimp Signup Form -->
                                    <div id="mc_embed_signup">
                                        <?php
                                        $current_user = wp_get_current_user();
                                        $email = empty($current_user->user_email) ? '' : $current_user->user_email;
                                        ?>

                                        <form action="http://WebWeb.us2.list-manage.com/subscribe/post?u=005070a78d0e52a7b567e96df&amp;id=1b83cd2093" method="post"
                                              id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank">
                                            <input type="hidden" value="settings" name="SRC2" />
                                            <input type="hidden" value="<?php echo str_replace('.php', '', basename(ORBISIUS_JUST_WRITE_BASE_PLUGIN_FILE)); ?>" name="SRC" />

                                            <span>Get notified about cool plugins we release</span>
                                            <!--<div class="indicates-required"><span class="app_asterisk">*</span> indicates required
                                            </div>-->
                                            <div class="mc-field-group">
                                                <label for="mce-EMAIL">Email</label>
                                                <input type="email" value="<?php echo esc_attr($email); ?>" name="EMAIL" class="required email" id="mce-EMAIL">
                                            </div>
                                            <div id="mce-responses" class="clear">
                                                <div class="response" id="mce-error-response" style="display:none"></div>
                                                <div class="response" id="mce-success-response" style="display:none"></div>
                                            </div>	<div class="clear"><input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button-primary"></div>
                                        </form>
                                    </div>
                                    <!--End mc_embed_signup-->
                                </div> <!-- .inside -->
                            </div> <!-- .postbox -->
                            <!-- /Newsletter-->

                            <div class="postbox">

                                <h3><span>Share</span></h3>
                                <div class="inside">
        <?php
        $plugin_data = get_plugin_data(ORBISIUS_JUST_WRITE_BASE_PLUGIN_FILE);

        $app_link = urlencode($plugin_data['PluginURI']);
        $app_title = urlencode($plugin_data['Name']);
        $app_descr = urlencode($plugin_data['Description']);
        ?>
                                    <p>
                                        <!-- AddThis Button BEGIN -->
                                    <div class="addthis_toolbox addthis_default_style addthis_32x32_style">
                                        <a class="addthis_button_facebook" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                        <a class="addthis_button_twitter" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                        <a class="addthis_button_google_plusone" g:plusone:count="false" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                        <a class="addthis_button_linkedin" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                        <a class="addthis_button_email" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                        <!--<a class="addthis_button_myspace" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                        <a class="addthis_button_google" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                        <a class="addthis_button_digg" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                        <a class="addthis_button_delicious" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                        <a class="addthis_button_stumbleupon" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                        <a class="addthis_button_tumblr" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>
                                        <a class="addthis_button_favorites" addthis:url="<?php echo $app_link ?>" addthis:title="<?php echo $app_title ?>" addthis:description="<?php echo $app_descr ?>"></a>-->
                                        <a class="addthis_button_compact"></a>
                                    </div>
                                    <!-- The JS code is in the footer -->

                                    <script type="text/javascript">
                                        var addthis_config = {"data_track_clickback": true};
                                        var addthis_share = {
                                            templates: {twitter: 'Check out {{title}} @ {{lurl}} (from @orbisius)'}
                                        }
                                    </script>
                                    <!-- AddThis Button START part2 -->
                                    <script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=lordspace"></script>
                                    <!-- AddThis Button END part2 -->
                                    </p>
                                </div> <!-- .inside -->

                            </div> <!-- .postbox -->

                        </div> <!-- .meta-box-sortables -->

                    </div> <!-- #postbox-container-1 .postbox-container -->

                </div> <!-- #post-body .metabox-holder .columns-2 -->

                <br class="clear">
            </div> <!-- #poststuff -->

        </div> <!-- .wrap -->
        <?php
    }
}
