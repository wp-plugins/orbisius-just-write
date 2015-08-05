<?php

$pm_front = orb_just_write_front::get_instance();

add_action( 'init', array( $pm_front, 'init' ) );

add_action( 'wp_ajax_orb_just_quick_contact', array( $pm_front, 'quick_contact' ) );
add_action( 'wp_ajax_orb_just_write_save_content', array( $pm_front, 'save_content' ) );
add_action( 'wp_ajax_orb_just_write_load_sites', array( $pm_front, 'load_sites' ) );
add_action( 'wp_ajax_orb_just_write_load_site_extras', array( $pm_front, 'load_site_extras' ) );
add_action( 'wp_ajax_orb_just_write_auth_save', array( $pm_front, 'save_auth' ) );
add_action( 'wp_ajax_orb_just_write_auto_save', array( $pm_front, 'auto_save' ) );
add_action( 'wp_ajax_orb_just_write_delete_site', array( $pm_front, 'delete_site' ) );

class orb_just_write_front {
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

    /**
     * We don't want any using the site.
     */
    public function init() {
        /*$suffix = '';

        wp_register_style('orb_just_write_front', plugins_url("/modules/css/front{$suffix}.css", ORBISIUS_JUST_WRITE_BASE_PLUGIN_FILE), false,
            filemtime( plugin_dir_path( ORBISIUS_JUST_WRITE_BASE_PLUGIN_FILE ) . "/modules/css/front{$suffix}.css" ) );
        wp_enqueue_style('orb_just_write_front');*/

        if ( isset( $_REQUEST['orbisius_just_write'] ) ) {
            $user_id = get_current_user_id();

            if ( $user_id <= 0 ) {
                wp_die( 'You must be logged in to access this page.' );
            }

            $post_title = '';
            $post_content = '';
            $user = wp_get_current_user();
            $quick_email = empty($user->user_email) ? '' : $user->user_email;
            
            $ed_prefs = $this->get_user_prefs();
            
            $just_write_cfg = array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'ed_prefs' => $ed_prefs,
            );

            $tpl_file = ORBISIUS_JUST_WRITE_DIR . '/modules/templates/just_write_full_page.php';
            include $tpl_file;


            exit;
        }
    }

    /**
     * Returns current prefs or the value for a key
     */
    public function get_user_prefs($key = null) {
        $ed_prefs = get_user_meta(get_current_user_id(), $this->user_prefs_meta_key, true);
        $ed_prefs = empty($ed_prefs) ? array() : $ed_prefs;
        
        if (empty($key)) {
            return $ed_prefs;
        } else {
            return isset($ed_prefs[$key]) ? $ed_prefs[$key] : null;
        }
    }

    /**
     * Changes a key in user prefs
     */
    public function update_user_prefs($k, $v) {
        $ed_prefs = $this->get_user_prefs();
        $ed_prefs[$k] = $v;
        $status = update_user_meta(get_current_user_id(), $this->user_prefs_meta_key, $ed_prefs);
    }

    /**
     * ajax
     * @throws Exception
     */
    public function delete_site() {
        $params = Orbisius_Just_Write_HTML_Util::sanitizeData();
        $buff = '';
        $res = new Orbisius_Just_Write_Result();

        try {
            if ( $params['site_id'] > 0) {
                $force = true; // no trash
                $status = wp_delete_post( $params['site_id'], $force );
            }

            $res->status(!empty($status));
        } catch (Exception $e) {
            $res->status(0);
            $res->data('error_message', $e->getMessage());
        }

        $return = array(
            'buffer' => $buff,
            'message' => $res->success() ? 'Done.' : $res->data('error_message'),
            'status'  => $res->success(),
            'site_id' => $res->data('id'),
        );

        wp_send_json( $return );
    }

    /**
     * ajax
     * @throws Exception
     */
    public function load_sites() {
        $params = Orbisius_Just_Write_HTML_Util::sanitizeData();
        $buff = '';
        $res = new Orbisius_Just_Write_Result();
        $last_used_site_id = $this->get_user_prefs('last_used_site_id');

        try {
            $orb_just_write_cpt_obj = orb_just_write_cpt::get_instance();
            $records = $orb_just_write_cpt_obj->load( array( 'load_fields' => 1 ) );

            $drop_down = array( '' => '== Select Site ==' );
            $first_id = 0;
            $last_found = 0;

            foreach ( $records as $idx => $rec ) {
                $drop_down[ $rec[ 'ID' ] ] = $rec['user'] . ' | '. $rec['post_title'];

                if ( $idx == 0 ) {
                    $first_id = $rec[ 'ID' ];
                }
                
                if ( ! empty( $last_used_site_id ) && $last_used_site_id == $rec[ 'ID' ] ) {
                    $last_found = 1;
                }
            }

            // We don't have a last used site OR it was deleted.
            if ( empty( $last_used_site_id ) || ! $last_found ) {
                $last_used_site_id = $first_id;
                $this->update_user_prefs( 'last_used_site_id', $last_used_site_id );
            }

            if ( ! empty( $records ) ) {
                //$drop_down[0] = 'new';
                // @see http://stackoverflow.com/questions/19905166/bootstrap-3-select-input-form-inline
                $buff .= "<div class='input-group' style='display:inline;'>";
                $buff .= "<div style='display:inline; width:50%;'>";
                $buff .= "| Site: " . Orbisius_Just_Write_HTML_Util::htmlSelect('site_id',
                        $last_used_site_id, $drop_down, " class='form-control2 XXXwide_field' XXXstyle='display:inline;' ");
                $buff .= "</div>";
                $buff .= "<span class='input-group-btn00'>";
                $buff .= "<a href='javascript:void(0);' id='app_delete_site_button' class='app_delete_site_button btn-sm btn-danger' XXXstyle='float:right;'>X</a>";
                $buff .= "</span>";
                $buff .= "</div>";
            } else {
                $buff = 'No sites found. Please, add one now.';
            }

            //$buff = var_export($drop_down, 1);
            $res->status(1);
        } catch (Exception $e) {
            $res->status(0);
            $res->data('error_message', $e->getMessage());
        }

        $return = array(
            'buffer' => $buff,
            'message' => $res->success() ? 'Done.' : $res->data( 'error_message' ),
            'status'  => $res->success(),
            'site_id' => $res->data( 'id' ),
        );

        wp_send_json( $return );
    }

    /**
     * ajax: loads category, tags etc
     * @throws Exception
     */
    public function load_site_extras() {
        $params = Orbisius_Just_Write_HTML_Util::sanitizeData();
        $res = new Orbisius_Just_Write_Result();
        $buff = '';
        $site_id = empty( $params['site_id'] ) ? 0 : intval( $params['site_id'] );

        try {
            $buff = '';

            if ( $site_id > 0 ) {
                $this->update_user_prefs('last_used_site_id', $site_id);
            }

            //$buff = var_export($drop_down, 1);
            $res->status(1);
        } catch (Exception $e) {
            $res->status(0);
            $res->data('error_message', $e->getMessage());
        }

        $cats_buffer = '';
        $tags_buffer = '';

        if ( $site_id ) {
            $orb_just_write_cpt_obj = orb_just_write_cpt::get_instance();
            $site_rec = $orb_just_write_cpt_obj->load( array( 'id' => $site_id, 'load_fields' => 1, ) );
                
            if ( ! empty( $site_rec ) ) {
                $cb = new Orbisius_Just_Write( $site_rec['url'], $site_rec['user'], $site_rec['pass'] );
                $res = $cb->getCategories();

                if ( $res->success() ) {
//                    $cats_buffer .= "<pre>" . var_export( $res->data('req_response'), 1 ) . "</pre>";

                    $cats = $res->data('req_response');

                    $last_used_cat_name = null;
                    $cat_drop_down = array( '' => '', );

                    // For some weird reason category names are supposed to be passed.
                    foreach ( $cats as $rec ) {
                        $cat_drop_down[ $rec[ 'categoryName' ] ] = $rec[ 'categoryName' ];
                        //$cat_drop_down[ $rec[ 'categoryId' ] ] = $rec[ 'categoryName' ];
                    }

                    $cats_buffer .= Orbisius_Just_Write_HTML_Util::htmlSelect( 'cat_name',
                        $last_used_cat_name, $cat_drop_down, " class='form-control2 XXXwide_field' XXXstyle='display:inline;' ");
                }
            }
        }

        $return = array(
            'buffer' => $buff,
            'cats_buffer' => $cats_buffer,
            'tags_buffer' => $tags_buffer,
            'message' => $res->success() ? 'Done.' : $res->data('error_message'),
            'status'  => $res->success(),
            'site_id' => $site_id,
        );

        wp_send_json( $return );
    }

    private $user_prefs_meta_key = '_orb_jr_ed_prefs';

    /**
     * ajax
     * @throws Exception
     */
    public function auto_save() {
        $params = Orbisius_Just_Write_HTML_Util::sanitizeData();
        $res = new Orbisius_Just_Write_Result();

        try {
            $el_id = empty( $params['field_id'] ) ? 0 :  $params['field_id'];
            $checked = empty( $params['checked'] ) ? 0 : 1;
            $this->update_user_prefs($el_id, $checked);

            $res->status(1);
        } catch (Exception $e) {
            $res->status(0);
            $res->data('error_message', $e->getMessage());
        }

        $return = array(
            'message' => $res->success() ? 'Done.' : $res->data('error_message'),
            'status'  => $res->success(),
            'site_id' => $res->data('id'),
        );

        wp_send_json( $return );
    }

    /**
     * ajax
     * @throws Exception
     */
    public function save_auth() {
        $params = Orbisius_Just_Write_HTML_Util::sanitizeData();

         try {
            $site_id = empty( $params['edit_site_id'] ) ? 0 : intval( $params['edit_site_id'] );
            $site_user = empty( $params['site_user'] ) ? '' : $params['site_user'];
            $site_pass = empty( $params['site_pass'] ) ? '' : $params['site_pass'];
            $site_url = empty( $params['site_url'] ) ? '' : $params['site_url'];

            $req_fields = array(
                'site_url' => 'WP Site URL',
                'site_user' => 'User',
                'site_pass' => 'Password',
            );

            foreach ($req_fields as $key => $field_label) {
                if ( empty( $params[$key] )) {
                    throw new Exception("Empty/invalid $field_label");
                }
            }

            $site_url = trailingslashit( $site_url );

            if (!preg_match('#^https?://#si', $site_url)) {
                $site_url = 'http://' . $site_url;
            }

            $xml_rpc_url = $site_url . 'xmlrpc.php';

            if (!preg_match('#^https?://#si', $xml_rpc_url)) {
                $xml_rpc_url = 'http://' . $xml_rpc_url;
            }

            $cb = new Orbisius_Just_Write( $xml_rpc_url, $site_user, $site_pass );
            $res = $cb->getCategories();

            if ( $res->error() ) {
                throw new Exception("Error. Couldn't pull the categories. Possibly wrong user/password combination?");
            }

            $params = array(
                'id' => $site_id,
                'title' => $site_url,
                'fields' => array(
                    'user' => $site_user,
                    'pass' => $site_pass,
                    'url' => $xml_rpc_url,
                ),
            );

            $orb_just_write_cpt_obj = orb_just_write_cpt::get_instance();
            $res = $orb_just_write_cpt_obj->insert($params);
        } catch (Exception $e) {
            $res = new Orbisius_Just_Write_Result();
            $res->status(0);
            $res->data('error_message', $e->getMessage());
        }

        $return = array(
            'message' => $res->success() ? 'Done.' : $res->data('error_message'),
            'status'  => $res->success(),
            'site_id' => $res->data('id'),
        );

        wp_send_json( $return );
    }

    /**
     * ajax
     * @throws Exception
     */
    public function save_content() {
        Orbisius_Just_Write_HTML_Util::allowHTML();
        $params = Orbisius_Just_Write_HTML_Util::sanitizeData();

        try {
            $site_id = empty( $params['site_id'] ) ? 0 : intval( $params['site_id'] );

            $nl2br = !empty( $params['nl2br'] );
            $publish = !empty( $params['publish'] );

            $cat_name = empty($params['cat_name']) ? '' : $params['cat_name'];
            $post_id = empty( $params['post_id'] ) ? 0 : intval( $params['post_id'] );
            $post_title = empty( $params['post_title'] ) ? '' : $params['post_title'];
            $post_content = empty( $params['post_content'] ) ? '' : $params['post_content'];

            $site_url = empty( $params['site_url'] ) ? '' : $params['site_url'];
            $site_user = empty( $params['site_user'] ) ? '' : $params['site_user'];
            $site_pass = empty( $params['site_pass'] ) ? '' : $params['site_pass'];

            $req_fields = array(
                'post_title' => 'Post Title',
                'post_content' => 'Post Content',
            );

            foreach ($req_fields as $key => $field_label) {
                if ( empty( $params[ $key ] ) ) {
                    throw new Exception( "Empty/invalid $field_label" );
                }
            }

            if ( $site_id  > 0 ) {
                $orb_just_write_cpt_obj = orb_just_write_cpt::get_instance();
                $site_rec = $orb_just_write_cpt_obj->load( array( 'id' => $site_id, 'load_fields' => 1, ) );
                
                $site_url  = $site_rec['url'];
                $site_user = $site_rec['user'];
                $site_pass = $site_rec['pass'];
            } else {
                $site_req_fields = array(
                    'site_url' => 'WP Site URL',
                    'site_user' => 'User',
                    'site_pass' => 'Password',
                );
                
                foreach ( $site_req_fields as $key => $field_label ) {
                   if ( empty( $params[ $key ] ) ) {
                       throw new Exception( "Empty/invalid $field_label" );
                   }
                }
            }

            if ( ! preg_match('#^https?://#si', $site_url ) ) {
                $site_url = 'http://' . $site_url;
            }

            $site_url = trailingslashit( $site_url );

            $xml_rpc_url = $site_url . 'xmlrpc.php';

            $cb = new Orbisius_Just_Write( $xml_rpc_url, $site_user, $site_pass );
            //$cb->debug(true);

            if ( $nl2br ) {
                $post_content = nl2br( $post_content );
            }

            $post_params = array(
                //'parent_id' => 517,//?
                'post_id' => $post_id,
                'title' => $post_title,
                'content' => $post_content,
                'publish' => $publish,
                'categories' => $cat_name,
            );

            $res = $cb->insertPost( $post_params );
        } catch (Exception $e) {
            $res = new Orbisius_Just_Write_Result();
            $res->status(0);
            $res->data('error_message', $e->getMessage());
        }

        $return = array(
            'message' => $res->success() ? 'Done.' : $res->data('error_message'),
            'status'  => $res->success(),
            'post_id' => $res->data('post_id'),
        );

        wp_send_json( $return );
    }

    /**
     *
     */
    public function quick_contact() {
        $msg = 'Failed to send message.';
        $status = 0;
        $params = Orbisius_Just_Write_HTML_Util::sanitizeData();
        $admin_recipient = 'Orbisius Support (WP Plugin Just Write) <help+plugins+orbisius+just+write@orbisius.com>';

        try {
            $user = wp_get_current_user();
            
            $from_name = empty($user->display_name) ? '' : $user->display_name;
            //$default_email = empty($user->user_email) ? '' : $user->user_email;

            $host = $_SERVER['HTTP_HOST'];
            $email = empty( $params['email'] ) ? '' : $params['email'];
            $subject = "WP Plugin Just Write Feedback";
            $message = empty( $params['message'] ) ? '' : $params['message'];

            if ( empty( $message) || strlen($message) > 5 * 1024) {
                throw new Exception("Empty message or longer than 5KB.");
            }

            if ( empty( $email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email");
            }

            $headers = '';
            $headers .= "From: Orbisius Just Write (WP) <mailer@$host\r\n";
            $headers .= "Reply-to: $from_name <$email>\r\n";

            $status = wp_mail( $admin_recipient, $subject, $message );

            if ( empty( $status) ) {
                throw new Exception("Couldn't send email. The pigeon is tired. Can you try again?");
            }

            $msg = 'Sent';
        } catch (Exception $e) {
            $msg = $e->getMessage();
        }
        
        $return = array(
            'status' => $status,
            'message' => $msg,
        );

        wp_send_json( $return );
    }

}
