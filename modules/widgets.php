<?php

add_action('widgets_init', create_function('', 'register_widget("orb_just_write_widget");'));
add_action('widgets_init', create_function('', 'register_widget("orb_just_write_widget_related");'));

/**
 * Handles the widget stuff i.e. can be added in the sidebar
 * @see http://codex.wordpress.org/Widgets_API
 */
class orb_just_write_widget extends WP_Widget {
    /** Constructor */
	function __construct() {
        $base_id = sanitize_title(__CLASS__);
        $name = ORBISIUS_JUST_WRITE_PLUGIN_NAME;
		$widget_attribs = array('description' => 'Shows recently viewed posts');

		parent::WP_Widget($base_id, $name, $widget_attribs);
	}

    /**
     * This is rendered on the public side
     * 
     * @param array $args
     * @param WP_Widget $instance - this contains the fields that were filled in e.g. vendor_id and width_style narrow
     */
	function widget($args, $instance) {
        global $post;
        $buff = '';

        if ( empty( $post->ID ) ) {
            $m = __CLASS__ . ' post id not found.';
            echo "<!-- $m -->";
            return;
        }

        $buff .= $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			$buff .= $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

        $buff .= do_shortcode("[orb_just_write]");
		$buff .= $args['after_widget'];

        if ( strpos($buff, 'no_records' ) !== false ) {
            $buff = '';
        }
        
		echo $buff;
	}

    /**
     * Renders the widget form in the admin area
     * @param type $instance
     * @return string
     */
    function form($instance) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Recently Viewed', 'text_domain' );
		?>
		<p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>" 
                   type="text"
                   value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php   
    }

	function update($new_instance, $old_instance) {
        $instance = array();
		$instance['title'] = ! empty( $new_instance['title'] ) ? wp_kses( $new_instance['title'], array() ) : '';

		return $instance;
	}
}


/**
 * Handles the widget stuff i.e. can be added in the sidebar
 * @see http://codex.wordpress.org/Widgets_API
 */
class orb_just_write_widget_related extends WP_Widget {
    /** Constructor */
	function __construct() {
        $base_id = sanitize_title(__CLASS__);
        $name = ORBISIUS_JUST_WRITE_PLUGIN_NAME . ' Related';
		$widget_attribs = array('description' => 'Shows related posts');

		parent::WP_Widget($base_id, $name, $widget_attribs);
	}

    /**
     * This is rendered on the public side
     *
     * @param array $args
     * @param WP_Widget $instance - this contains the fields that were filled in e.g. vendor_id and width_style narrow
     */
	function widget($args, $instance) {
        global $post;
        $buff = '';

        if ( empty( $post->ID ) ) {
            $m = __CLASS__ . ' post id not found.';
            echo "<!-- $m -->";
            return;
        }

        $buff .= $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			$buff .= $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

        $buff .= do_shortcode("[orb_just_write_related id='{$post->ID}']");
		$buff .= $args['after_widget'];

        if ( strpos($buff, 'no_records' ) !== false ) {
            $buff = '';
        }

		echo $buff;
	}

    /**
     * Renders the widget form in the admin area
     * @param type $instance
     * @return string
     */
    function form($instance) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Related Posts', 'text_domain' );
		?>
		<p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>"
                   type="text"
                   value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php
    }

	function update($new_instance, $old_instance) {
        $instance = array();
		$instance['title'] = ! empty( $new_instance['title'] ) ? wp_kses( $new_instance['title'], array() ) : '';

		return $instance;
	}
}
