<?php

Orbisius_Just_Write_HTML_Util::init();

/**
 * @see http://seegatesite.com/remote-posting-wordpress-with-xml-rpc-metaweblog-newpost-and-ixr_library-class/
 */
class Orbisius_Just_Write_HTML_Util {
    const CAST_INT = 2;
    public static $allowed_tags = array();
    public static $default_attribs = array(
        'id' => array(),
        'class' => array(),
        'title' => array(),
        'style' => array(),
        'data' => array(),
        'data-mce-id' => array(),
        'data-mce-style' => array(),
        'data-mce-bogus' => array(),
    );

    /**
     * Initializes allowed tags internal array.
     */
    public static function init() {
        $default_attribs = self::$default_attribs;
        self::$allowed_tags = array(
            'div'           => $default_attribs,
            'span'          => $default_attribs,
            'p'             => $default_attribs,
            'a'             => array_merge( $default_attribs, array(
                'href' => array(),
                'target' => array('_blank', '_top', '_self'),
            ) ),
            'img'           => array_merge( $default_attribs, array(
                'src' => array(),
                'alt' => array(),
                'border' => array(),
            ) ),
            'u'             => $default_attribs,
            'i'             => $default_attribs,
            'q'             => $default_attribs,
            'b'             => $default_attribs,
            'ul'            => $default_attribs,
            'ol'            => $default_attribs,
            'li'            => $default_attribs,
            'br'            => $default_attribs,
            'hr'            => $default_attribs,
            'strong'        => $default_attribs,
            'strike'        => $default_attribs,
            'q'             => $default_attribs,
            'em'            => $default_attribs,
            'del'           => $default_attribs,
            'ins'           => $default_attribs,
            'blockquote'    => $default_attribs,
            'del'           => $default_attribs,
            'em'            => $default_attribs,
            'pre'           => $default_attribs,
            'code'          => $default_attribs,
        );
    }

	// generates HTML select
    // Orbisius_Just_Write_HTML_Util::htmlSelect();
    public static function htmlSelect($name = '', $sel = null, $options = array(), $attr = '') {
        $html = "\n" . '<select name="' . esc_attr($name) . '" id="' . esc_attr($name) . '" ' . $attr . '>' . "\n";

        foreach ($options as $key => $label) {
            $selected = $sel == $key ? ' selected="selected"' : '';
            $key = esc_attr( $key );
            $label = esc_attr( $label );
            $html .= "\t<option value='$key' $selected>$label</option>\n";
        }

        $html .= '</select>';
        $html .= "\n";

        return $html;
    }

    /**
     * Uses WP's wp_kses to clear some of the html tags but allow some attribs
     * usage: Orbisius_Just_Write_HTML_Util::stripTags($str);
	 * uses WordPress' wp_kses()
     * @param str $buffer string buffer
     * @return str cleaned up text
     */
    public static function stripTags($buffer) {
        $default_attribs = self::$default_attribs;
        $allowed_tags = self::$allowed_tags;

        if (function_exists('wp_kses')) { // WP is here
            $buffer = wp_kses($buffer, $allowed_tags);
        } else {
            $tags = array();

            foreach (array_keys($allowed_tags) as $tag) {
                $tags[] = "<$tag>";
            }

            $buffer = strip_tags($buffer, join('', $tags));
        }

        $buffer = trim($buffer);

        return $buffer;
    }

    /**
     *
     * @param str $val
     * @return str
     */
    public static function sanitizeVal( $val, $allowed_tags = array() ) {
        if ( self::$allow_html  && empty($allowed_tags)) {
            $allowed_tags = self::$allowed_tags;
        }

        if ( is_scalar( $val ) ) {
            $val = wp_unslash( $val ) ;
            $val = wp_kses( $val, $allowed_tags );
            $val = trim( $val );
        } elseif ( is_array( $val ) ) {
            foreach ( $val as &$sub_value ) { //!pass by ref!
                $sub_value = self::sanitizeVal( $sub_value, $allowed_tags );
            }
        } else {
            trigger_error( __METHOD__ . ' Invalid data passed', E_USER_NOTICE);
        }

        return $val;
    }

    static private $allow_html = false;

    /**
    * Secure param retrieval or clean up of the whole req params.
    * Orbisius_Just_Write_HTML_Util::allowHTML();
    *
    * @param str $key
    * @param mixed $default
    * @return mixes
    */
    public static function allowHTML() {
        self::$allow_html = true;
    }

    /**
    * Secure param retrieval or clean up of the whole req params.
    * Orbisius_Just_Write_HTML_Util::disableHTML();
    *
    * @param str $key
    * @param mixed $default
    * @return mixes
    */
    public static function disableHTML() {
        self::$allow_html = false;
    }

    /**
    * Secure param retrieval or clean up of the whole req params.
    * Orbisius_Just_Write_HTML_Util::sanitizeData();
    *
    * @param str $key
    * @param mixed $default
    * @return mixes
    */
    public static function sanitizeData($key = '', $default = '', $flags = 1) {
       // special case, we want to see if it's set or not. We don't care about the value.
       if (is_null($default)) {
           return isset($_REQUEST[$key]);
       }

       // 2) cases;
       // 1) empty key -> use wants cleaned req params
       // 2) the user has an array that needs cleaned
       if (empty($key) || is_array($key)) {
           $params = is_array($key) ? $key : $_REQUEST;
           $params = wp_unslash( $params ) ;
           
           foreach ($params as $key => $val) {
               $params[$key] = self::sanitizeData($key, $val, $flags);
           }

           return $params;
       } elseif (!empty($_REQUEST[$key])) {
           $val = $_REQUEST[$key];
       } else {
           $val = $default;
       }

       $val = wp_unslash( $val ) ;

       if ($flags) {
           if (is_scalar($val)) {
               $val = self::sanitizeVal($val);

               if ($flags & self::CAST_INT) {
                   $val = intval($val);
               }
           } elseif (is_array($val)) {

               foreach ($val as $sub_key => $sub_val) {
                    $val[$sub_key] = self::sanitizeVal($sub_val);
               }
           }
       }

       return $val;
    }
}
