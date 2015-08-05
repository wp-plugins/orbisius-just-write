<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <!--<link rel="icon" href="favicon.ico" />-->

    <title><?php echo ORBISIUS_JUST_WRITE_PLUGIN_NAME; ?></title>

	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" />

	<!-- Optional theme -->
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap-theme.min.css" />

    <!-- Custom styles for this template -->
    <!--<link rel="stylesheet" href="theme.css" />-->

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <style>
		.main_form .wide_field  { width : 95%; }
        #message {
            margin: 10px 0px;
        }
        
        .app_hide {
            display: none;
        }
	</style>
    <script>
        var just_write_cfg = <?php echo json_encode( $just_write_cfg ); ?>;
    </script>
    <?php wp_head(); ?>
  </head>

  <body role="document">

    <div class="container theme-showcase" role="main">

	  <form id='main_form' class='main_form' method="POST">
          <input type="hidden" id="post_id" name="post_id" value="" />
          
		  <div class="page-header">
			<h3><?php echo ORBISIUS_JUST_WRITE_PLUGIN_NAME; ?>
                 <span id="sites_container" class="sites_container"></span>
            </h3>
		  </div>
		  <div class="row">

			<div class="col-sm-9">
			  <div class="panel panel-default">
				<div class="panel-heading">
				  <div>
					<input type='text' id='post_title' name='post_title' value='<?php echo $post_title; ?>' class='wide_field' placeholder='Post title' />
				  </div>
				</div>
				<div class="panel-body">
                  <!--<textarea class='wide_field' id='post_content' name='post_content' class='post_content' rows='10' cols='10'><?php //echo $post_content; ?></textarea>-->
                  <?php if ( 1 || has_action( 'orbisius_just_write_action_post_content' ) ) : ?>
                    <?php
                        // @see got some ideas from https://plugins.svn.wordpress.org/indypress/tags/1.0/indypress/form_inputs/tinymce.php
                        $content = esc_html( $post_content );
                        $editor_id = 'post_content';
                        $settings = array(
                            'textarea_rows' => 14,
                            /*'mode' => 'textareas',
                            'editor_selector' => 'post_content',*/
                            //'width' => '100%', 'height' => '400',
                            'paste_auto_remove_styles' => 'true',
                            'paste_auto_remove_spans' => 'true',
                            'paste_auto_cleanup_on_paste' => 'true',
                            /*'theme' => 'advanced',
                            'skin' => 'default',*/
                        );
                        wp_editor( $content, $editor_id, $settings );
                    ?>
                  <?php else : ?>
                    
                  <?php endif; ?>
				  
				  <br/>
                  <div class="checkbox">
                      <label class="input-group">
                          <input type="checkbox" name="publish" id="publish" class="setting_auto_save" value="1" checked="checked" />
                          Publish
                      </label>
                      <?php if ( 0 ) : ?>
                      <label class="input-group">
                          <input type="checkbox" name="nl2br" id="nl2br" value="1" class="setting_auto_save" checked="checked" />
                          Convert new lines to HTML newlines (nl2br)
                      </label>
                      <?php endif; ?>
                  </div>
				  
				  <div>
                    <button type="submit" class="btn btn-sm btn-primary" name="btn_publish" id="btn_publish" value="btn_publish">Post Content</button>
				  </div>

                  <div id="message" class="message"></div>

                  <br />
                  <div id="preview_container">
                    <strong>
                        <div id="title_preview"></div>
                    </strong>
                    <div id="content_preview"></div>
                   </div>

				</div>
			  </div>
			</div><!-- /.col-sm-9 -->
			
			<div class="col-sm-3">
			  <div class="panel panel-primary">
				<div class="panel-heading">
				  <h3 class="panel-title">Site</h3>
				</div>
				<div class="panel-body">
                  <div id="site_extras_container" class="site_extras_container"></div>

                  <a href='#' id='app_toggle_add_site' class='app_toggle_add_site'>Add Site</a>
                  <div id='add_site_container' class='add_site_container app_hide'>
                        <div class="input-group">
                            <input type="hidden" id="add_new_site" name="add_new_site" value="1" />
                            <input type="text" id="site_url" name="site_url" class="form-control" placeholder="WP Site URL" aria-describedby="basic-addon2" />
                            <input type="text" id="site_user" name="site_user" class="form-control" placeholder="Username" aria-describedby="basic-addon2" />
                            <input type="password" id="site_pass" name="site_pass" class="form-control" placeholder="Password" aria-describedby="basic-addon2" />
                        </div>

                        <a href='#' id='app_show_reqs' class='app_show_reqs'>show/hide requirements</a>
                        <div class="checkbox0 app_hide app_reqs">
                            Note 1: The app requires <a href='https://codex.wordpress.org/XML-RPC_Support' target='_blank'>XML-RPC</a> to be enabled
                            (which is ON by default since WP 3.5) on the site you intend to post articles.<br/>
                              Note 2: Please use an account with Editor or Contributor role.
                        </div>

                        <div>
                          <button type="button" class="btn btn-sm btn-primary" id="btn_save_login" name="btn_save_login">Save</button>
                          <button type="button" class="btn btn-sm btn-danger" id="btn_cancel_login" name="btn_cancel_login">Cancel</button>
                        </div>
				  </div>

                  <!--<div class="checkbox">
                      <label class="input-group">
                          <input type="checkbox" name="store_creds" id="store_creds" />
                          Store Credentials.
                      </label>
                  </div> -->

				</div>
			  </div> <!-- /.panel -->
  
            
                  <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-title">Categories / Tags</h3>
                        </div>
                        <div class="panel-body">
                            <div id="cats_container" class="cats_container"></div>
                            <div id="tags_container" class="tags_container"></div>
                        </div>
                  </div> <!-- /.panel -->
  	</form>

                  <form id='quick_form' class='quick_form' method="POST">
                        <div class="panel panel-primary">
                          <div class="panel-heading">
                            <h3 class="panel-title">Share Feedback</h3>
                          </div>
                          <div class="panel-body">

                            <div class="input-group">
                                <textarea id="quick_contact_message" name="message" class="form-control" placeholder="How can we make it super awesome?" aria-describedby="basic-addon2"></textarea>
                                <input type="text" id="quick_contact_email" name="email" class="form-control" value="<?php echo $quick_email; ?>" placeholder="Your Email you@example.com" />
                            </div>

                            <br/>
                            <div>
                              <button type="submit" class="btn btn-sm btn-primary" name="btn_publish">Send Message</button>
                            </div>

                            <!--<div class="checkbox">
                                <label class="input-group">
                                    <input type="checkbox" name="store_creds" id="store_creds" />
                                    Store Credentials.
                                </label>
                            </div> -->
                          </div>
                        </div> <!-- /.panel -->
                  </form>
            </div><!-- /.col-sm-3 -->
		</div><!-- /.row -->
    </div> <!-- /container -->

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
	<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
	<script src="<?php echo ORBISIUS_JUST_WRITE_URL; ?>/modules/js/main.js"></script>

    <?php wp_footer(); ?>
  </body>
</html>
