<?php

$orb_just_write_cpt_obj = orb_just_write_cpt::get_instance();

add_action('init', array($orb_just_write_cpt_obj, 'init'));

/**
 * Description of admin
 *
 * @author User
 */
class orb_just_write_cpt {

    const RETURN_REC = 1;
    const RETURN_ID = 2;

    private $meta_key_prefix = '_orb_jr_';

    /**
     * Singleton pattern i.e. we have only one instance of this obj
     *
     * @staticvar type $instance
     * @return cls
     */
    public static function get_instance() {
        static $instance = null;

        if (is_null($instance)) {
            $cls = __CLASS__;
            $instance = new $cls();
        }

        return $instance;
    }

    public function get_post_type($type, $return = self::RETURN_REC) {
        $types = array(
            'site' => array(
                'id' => 'orb_jr_site',
                'label' => 'Just Write Sites',
                'label_singular' => 'Just Write Site',
                'fields' => array(
                    'user' => '',
                    'pass' => '',
                    'url' => '',
                )
            ),
        );

        return $return == self::RETURN_REC ? $types[$type] : $types[$type]['id'];
    }

    /**
     * We don't want any using the site.
     */
    public function init() {
        $this->register_cpt();
    }

    public function register_cpt() {
        $site_cpt = $this->get_post_type('site');

        $label = $site_cpt['label'];
        $label_singular = $site_cpt['label_singular'];

        $labels = array(
            'name'               => _x( $label, 'post type general name', 'your-plugin-textdomain' ),
            'singular_name'      => _x( $label_singular, 'post type singular name', 'your-plugin-textdomain' ),
            'menu_name'          => _x( $label, 'admin menu', 'your-plugin-textdomain' ),
            'name_admin_bar'     => _x( $label_singular, 'add new on admin bar', 'your-plugin-textdomain' ),
            'add_new'            => _x( "Add New", $site_cpt['id'], 'your-plugin-textdomain' ),
            'add_new_item'       => __( "Add New $label_singular", 'your-plugin-textdomain' ),
            'new_item'           => __( "New $label_singular", 'your-plugin-textdomain' ),
            'edit_item'          => __( "Edit $label_singular", 'your-plugin-textdomain' ),
            'view_item'          => __( "View $label_singular", 'your-plugin-textdomain' ),
            'all_items'          => __( "All $label", 'your-plugin-textdomain' ),
            'search_items'       => __( "Search $label", 'your-plugin-textdomain' ),
            'parent_item_colon'  => __( "Parent $label:", 'your-plugin-textdomain' ),
            'not_found'          => __( "No $label found.", 'your-plugin-textdomain' ),
            'not_found_in_trash' => __( "No $label found in Trash.", 'your-plugin-textdomain' )
        );

        $show_in_ui = !empty($_SERVER['DEV_ENV']);

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => $show_in_ui,
            'show_in_menu'       => $show_in_ui,
            'query_var'          => false,
            'rewrite'            => false,//array( 'slug' => 'book' ),
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', /*'editor', 'author', 'thumbnail', 'excerpt', 'comments'*/ ),
            'map_meta_cap'       => true,
            'capability_type'    => 'post',
        );

        register_post_type( $site_cpt['id'], $args );
    }

    public function load($params) {
        $id = empty($params['id']) ? 0 : $params['id'];
        $type = empty($params['type']) ? 'site' : $params['type'];

        $args = array(
            'posts_per_page'   => -1, // all
            'offset'           => 0,
            'post_type'        => $this->get_post_type( $type, self::RETURN_ID ),
            'post_status'      => 'publish', // could we deactivate sites in future?
            'suppress_filters' => true,
            'order'            => 'ASC',
            'orderby'          => 'title',
        );

        $posts_array = array();

        if ( $id > 0 ) {
            // @todo : how to better check permissions and ownership???
            if ( 0 && ! current_user_can( 'edit_' . $this->get_post_type( $type, self::RETURN_ID ), $id ) ) {
                throw new Exception( "Cannot allow access to resource #$id." );
            }

            $posts_array_of_objects[] = get_post( $id, ARRAY_A );
        } else {
            $user_id = empty($params['user_id']) ? get_current_user_id() : $params['user_id'];
            $args['author'] = $user_id;
            $args = apply_filters( 'orb_just_write_filter_pre_get_sites_args', $args, $this );
            $posts_array_of_objects = get_posts( $args );
        }

        $type_rec = $this->get_post_type( $type );

        // wise?
        foreach ( $posts_array_of_objects as $obj ) {
            $rec = (array) $obj;

            if ( ! empty( $params['load_fields'] ) && ! empty( $type_rec['fields'] ) ) {
                $return_single_val = true;

                foreach ($type_rec['fields'] as $key => $empty_val) {
                    $meta_key = $this->meta_key_prefix . $key;
                    $meta_val = get_post_meta( $rec['ID'], $meta_key, $return_single_val );

                    $rec[$key] = $meta_val;
                }
            }

            $posts_array[] = $rec;
        }

        if ( $id > 0 ) { // return just 1 rec
            $posts_array = array_shift($posts_array);
        }

        return $posts_array;
    }

    /**
     * We don't want any using the site.
     */
    public function insert($params) {
        $allow_wp_error_as_return_type = true;

        $id = empty($params['id']) ? 0 : intval( $params['id'] );
        $type = empty($params['type']) ? 'site' : $params['type'];

        $post_data = array(
            'ID' => $id,
            'post_title' => $params['title'],
            'post_type' => $this->get_post_type($type, self::RETURN_ID),
            'post_status'   => 'publish',
        );

        $post_id = wp_insert_post( $post_data, $allow_wp_error_as_return_type );

        $res = new Orbisius_Just_Write_Result();
        $res->status( is_wp_error( $post_id ) ? 0 : 1 );
        $res->data( 'id', $res->success() ? $post_id : 0 );

        if ( $res->success() && !empty($params['fields']) ) {
            foreach ( $params['fields'] as $key => $value ) {
                $meta_key = $this->meta_key_prefix . $key;
                $meta_value = Orbisius_Just_Write_HTML_Util::sanitizeVal( $value );
                update_post_meta($post_id, $meta_key, $meta_value);
            }
        }

        return $res;
    }
}
