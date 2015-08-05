<?php

/**
 * @see http://seegatesite.com/remote-posting-wordpress-with-xml-rpc-metaweblog-newpost-and-ixr_library-class/
 */
class Orbisius_Just_Write {
    private $client = null;
    private $user = null;
    private $pass = null;
    private $encoding = 'UTF-8';
    private $blog_id = 0;
    private $debug = false;

    public function __construct($xml_rpc_url, $user, $pass) {
        if (!class_exists('IXR_Client')) {
            throw new Exception("IXR_Client not found.");
        }

        $client = new IXR_Client($xml_rpc_url);
        $client->debug = $this->debug;
        $this->client = $client;

        $this->user = $user;
        $this->pass = $pass;
    }

    /**
     * getter/setter for debug
     * @param bool $debug
     * @return bool
     */
    public function debug($debug = null) {
        if ( !is_null($debug)) {
            $this->debug = $debug;
            $this->client->debug = $debug;
        }

        return $this->debug;
    }

    /**
     * Uploads a file.
     * 
     * @param array $param
     * @return str
     * @throws Exception
     * @see http://seegatesite.com/how-to-upload-using-wordpress-metaweblog-newmediaobject-and-ixr_library/
     */
    public function upload($param) {
        if ( empty($param['file']) ) {
            throw new Exception("File not supplied.");
        } elseif ( ! file_exists( $param['file'] ) ) {
            throw new Exception( sprintf("File [%s] doesn't exist.", esc_attr( $param['file'] ) ) );
        } elseif ( ! is_readable( $param['file'] ) ) {
            throw new Exception( sprintf("File [%s] is not readable.", esc_attr( $param['file'] ) ) );
        }

        $file = $param['file'];
        $save_as_name = empty( $param['save_as']) ? basename( $param['file'] ) : $param['save_as'];

        // Large files?
        $fh = fopen($file, 'rb');

        if ( empty( $fh ) ) {
            throw new Exception( sprintf("Couldn't open file [%s] for reading.", esc_attr( $param['file'] ) ) );
        }

        if ( ! flock($fh, LOCK_SH ) ) {
            throw new Exception( sprintf("Couldn't lock file [%s] for reading.", esc_attr( $param['file'] ) ) );
        }

        $fs = filesize($file);

        $limit = 10 * 1024 * 1024;

        if ( $fs > $limit ) {
            throw new Exception( sprintf( "File [%s] is more than %d.", esc_attr( $param['file'] ), $limit ) );
        }

        $theData = fread($fh, $fs);
        
        flock($fh, LOCK_UN);
        fclose($fh);

        $upload_params = array( 'name' => $save_as_name, /*'type' => 'image/jpg',*/ 'bits' => new IXR_Base64( $theData ), 'overwrite' => false );
        $params = array($this->blog_id, $this->user, $this->pass, $upload_params);

        $res = $this->client->query('wp.uploadFile', $params);

        /*
         * object(Orbisius_Just_Write_Result)[5]
            public 'status' => int 1
            public 'msg' => string '' (length=0)
            private 'data' =>
              array (size=3)
                'req_result' => boolean true
                'req_response' =>
                  array (size=4)
                    'id' => string '511' (length=3)
                    'file' => string 'never_give_up.jpg' (length=17)
                    'url' => string 'http://localhost/wordpress313/wp-content/uploads/2015/04/never_give_up1.jpg' (length=75)
                    'type' => string '' (length=0)
                'post_id' => int 0
         */

        return $this->parseResponse($res);
    }

    /**
     *
     * @param array $param
     * @see http://seegatesite.com/remote-posting-wordpress-with-xml-rpc-metaweblog-newpost-and-ixr_library-class/
     * @see http://djzone.im/2011/04/simple-xml-rpc-client-to-wordpress-made-easy/
     */
    public function insertPost($param) {
        $title = empty($param['title']) ? "Post Title" : $param['title']; // $title variable will insert your blog title
        $content = empty($param['content']) ? '' : $param['content']; // $title variable will insert your blog title
        $post_id = empty($param['post_id']) ? 0 : $param['post_id']; // $title variable will insert your blog title

        $mt_allow_comments = !isset($param['allow_comments']) || $param['allow_comments'] ? 1 : 0;

        /* $category = "category1, category2"; // Comma seperated pre existing categories. Ensure that these categories exists in your blog.
          $keywords = "keyword1, keyword2, keyword3";
          $customfields = array('key' => 'Author-bio', 'value' => 'Autor Bio Here'); // Insert your custom values like this in Key, Value format */
        //$title = htmlentities($title, ENT_NOQUOTES, $this->encoding);
        //$keywords = htmlentities($keywords, ENT_NOQUOTES, $this->encoding);

        $post_params = array(
            'title' => $title,
            'description' => $content,
            'mt_allow_comments' => $mt_allow_comments, // 1 to allow comments
            'mt_allow_pings' => 0, // 1 to allow trackbacks
        );

        $post_params['post_type'] = empty($param['post_type']) ? 'post' : $param['post_type'];
        
        if (!empty($param['parent_id'])) {
            $post_params['wp_page_parent_id'] = $param['parent_id'];
        }

        if (!empty($param['keywords'])) {
            $post_params['mt_keywords'] = htmlentities($param['keywords'], ENT_NOQUOTES, $this->encoding);
        }

        if (!empty($param['categories'])) {
            $post_params['categories'] = (array) $param['categories'];
        }

        if (!empty($param['tags'])) {
            $post_params['tags'] = array( $param['tags'] ); // encode???
        }

        if (!empty($param['customfields'])) {
            $post_params['customfields'] = array($customfields);
        }

        $publish_post_now = !empty($param['publish']); // true -> publish, false -> draft
        
        if ( $post_id ) {
            // http://lists.automattic.com/pipermail/wp-xmlrpc/2009-April/000324.html
            // first param is a post id and not blog
            $params = array($post_id, $this->user, $this->pass, $post_params, $publish_post_now);
            $res = $this->client->query('metaWeblog.editPost', $params);
        } else {
            $params = array($this->blog_id, $this->user, $this->pass, $post_params, $publish_post_now);
            $res = $this->client->query('metaWeblog.newPost', $params);
        }

        /*object(Orbisius_Just_Write_Result)[4]
  public 'status' => int 1
  public 'msg' => string '' (length=0)
  private 'data' =>
    array (size=6)
      'req_result' => boolean true
      'req_response' => string '513' (length=3)
      'id' => int 0
      'file' => string '' (length=0)
      'url' => string '' (length=0)
      'post_id' => int 513*/

        return $this->parseResponse($res);
    }

    /**
     *
     * @param array $param
     * @see http://seegatesite.com/remote-posting-wordpress-with-xml-rpc-metaweblog-newpost-and-ixr_library-class/
     */
    public function getCategories($param = array())  {
        $max_results = 100;
        $cat_starting_with = '';
        $params = array($this->blog_id, $this->user, $this->pass, $cat_starting_with, $max_results);

        $res = $this->client->query('wp.getCategories', $params);

        /*Orbisius_Just_Write_Result::__set_state(array(
            'status' => 1,
            'msg' => '',
            'data' =>
           array (
             'req_result' => true,
             'req_response' =>
             array (
               0 =>
               array (
                 'categoryId' => '6',
                 'parentId' => '0',
                 'description' => 'aha',
                 'categoryDescription' => '',
                 'categoryName' => 'aha',
                 'htmlUrl' => 'http://localhost/wordpress313/category/aha/',
                 'rssUrl' => 'http://localhost/wordpress313/category/aha/feed/',
               ),
               1 =>
               array (
                 'categoryId' => '26',
                 'parentId' => '0',
                 'description' => 'Category-1429647743',
                 'categoryDescription' => '',
                 'categoryName' => 'Category-1429647743',
                 'htmlUrl' => 'http://localhost/wordpress313/category/category-1429647743/',
                 'rssUrl' => 'http://localhost/wordpress313/category/category-1429647743/feed/',
               ),
               2 =>
               array (
                 'categoryId' => '4',
                 'parentId' => '0',
                 'description' => 'cool category',
                 'categoryDescription' => '',
                 'categoryName' => 'cool category',
                 'htmlUrl' => 'http://localhost/wordpress313/category/cool-category/',
                 'rssUrl' => 'http://localhost/wordpress313/category/cool-category/feed/',
               ),
               3 =>
               array (
                 'categoryId' => '25',
                 'parentId' => '0',
                 'description' => 'Sample Cat by API',
                 'categoryDescription' => '',
                 'categoryName' => 'Sample Cat by API',
                 'htmlUrl' => 'http://localhost/wordpress313/category/sample-cat-by-api/',
                 'rssUrl' => 'http://localhost/wordpress313/category/sample-cat-by-api/feed/',
               ),
               4 =>
               array (
                 'categoryId' => '5',
                 'parentId' => '4',
                 'description' => 'sub cool category',
                 'categoryDescription' => '',
                 'categoryName' => 'sub cool category',
                 'htmlUrl' => 'http://localhost/wordpress313/category/cool-category/sub-cool-category/',
                 'rssUrl' => 'http://localhost/wordpress313/category/cool-category/sub-cool-category/feed/',
               ),
               5 =>
               array (
                 'categoryId' => '1',
                 'parentId' => '0',
                 'description' => 'Uncategorized',
                 'categoryDescription' => '',
                 'categoryName' => 'Uncategorized',
                 'htmlUrl' => 'http://localhost/wordpress313/category/uncategorized/',
                 'rssUrl' => 'http://localhost/wordpress313/category/uncategorized/feed/',
               ),
             ),
             'id' => 0,
             'file' => '',
             'url' => '',
             'post_id' => 0,
           ),
         ))*/

        return $this->parseResponse($res);
    }

    /**
     * Create a category
     * @param array $param
     * @see http://seegatesite.com/create-new-category-on-wordpress-with-wp-newcategory-and-ixr_library/
     */
    public function insertCateogry($param) {
        $name = empty($param['name']) ? "Category-" . time() : $param['name'];
        $description = empty($param['description']) ? '' : $param['description'];

        $post_params = array(
            'name' => $name,
            'description' => $description,
        );

        $params = array($this->blog_id, $this->user, $this->pass, $post_params);
        $res = $this->client->query('wp.newCategory', $params);

        return $this->parseResponse($res);
    }

    public function parseResponse($res) {
        $res_obj = new Orbisius_Just_Write_Result();

        if (!$res) {
            $res_obj->status(0);
            $res_obj->data( 'error_code', $this->client->getErrorCode() );
            $res_obj->data( 'error_message', $this->client->getErrorMessage() );
        } else {
            $res_obj->status(1);
        }

        $req_response = $this->client->getResponse();

        $res_obj->data('req_result', $res);
        $res_obj->data('req_response', $req_response);
        
        $res_obj->data('id', is_array($req_response) && !empty($req_response['id']) ? (int) $req_response['id'] : 0); // could be an error or
        $res_obj->data('file', is_array($req_response) && !empty($req_response['file']) ? $req_response['file'] : ''); // could be an error or
        $res_obj->data('url', is_array($req_response) && !empty($req_response['url']) ? $req_response['url'] : ''); // could be an error or
        $res_obj->data('post_id', is_scalar($req_response) && is_numeric($req_response) ? (int) $req_response : 0); // could be an error or

        /*
          array (size=2)
          'faultCode' => int 403
          'faultString' => string 'Incorrect username or password.' (length=31)
         */

        return $res_obj;
    }
}
