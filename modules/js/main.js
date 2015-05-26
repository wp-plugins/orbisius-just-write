var just_write_app = {
    loader: function (status, msg) {
        msg = msg || '';
        status = status || 0;

        if (status && msg == '') {
            msg = 'Please, wait ...';
        }

        var cls = 'alert alert-info';

        $('#message').html(msg).removeClass('alert alert-success alert-info alert-danger');

        if (status) {
            $('#message').addClass(cls);
        }
    },
    // just_write_app.message();
    message: function (msg, status) {
        msg = msg || '';

        var cls = 'alert ';

        if (status == 1 || status == true) {
            cls += 'alert-success';
        } else if (status == 0 || status == false) {
            cls += 'alert-danger';
        } else if (status == 2) {
            cls += 'alert-info';
        }

        $('#message').html(msg).removeClass('alert alert-success alert-info alert-danger');
        
        if (msg !== '') {
            $('#message').addClass(cls);
        }
    },
    util: {
        // @see http://stackoverflow.com/questions/1499889/remove-html-tags-in-javascript-with-regex
        // @see http://stackoverflow.com/questions/6659351/removing-all-script-tags-from-html-with-js-regular-expression
        strip_all_tags: function (str) {
            var regex = /(<([^>]+)>)/ig;
            str = str.replace(regex, '');
            return str;
        },
        encodeHTML: function (s) {
            return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        },
        nl2br: function (str) {
            str = str.replace(/[\n\r]/ig, '<br/>');
            return str;
        }
    },

    handle_setting_auto_save : function(clicked_element) {
        var $e = $(clicked_element);
        var data = {
            field_id : $e.prop('id'),
            checked : $e.prop('checked') ? 1 : 0
        };

        $.ajax({
            url: just_write_cfg.ajax_url + '?action=orb_just_write_auto_save',
            method: "POST",
            data: data
        }).done(function (json) {
        });
    },

    sites : {
        load : function(cb) {
            $('#app_delete_site_button').off('click');
            $('#site_id').off('click');
            $('#sites_container').html('Loading sites...');

            $.ajax({
                url: just_write_cfg.ajax_url + '?action=orb_just_write_load_sites',
                method: "POST"
                //data: $(this).serialize()
            }).done(function (json) {
                $('#sites_container').html(json.buffer);
                just_write_app.loader();

                if (typeof cb != 'undefined') {
                    cb();
                }

                $('#site_id').on('change', function () {
                    var site_id = parseInt( $('#site_id').val() );

                    if ( site_id > 0 ) {
                        just_write_app.sites.load_site_extras();
                    }
                    
                    $('#post_title').focus();
                });
                
                $('#app_delete_site_button').on('click', function () {
                    if ( confirm('Are you sure?' ,'') ) {
                        just_write_app.sites.delete();
                    }
                });
            });
        },

        load_site_extras : function(cb) {
            //$('#app_delete_site_button').off('click');
            //$('#sites_container').html('Loading sites...');
            var data = {
                site_id : $('#site_id').val()
            };

            $.ajax({
                url: just_write_cfg.ajax_url + '?action=orb_just_write_load_site_extras',
                method: "POST",
                data: data
            }).done(function (json) {
                $('#site_extras_container').html(json.buffer);
                just_write_app.loader();

                if (typeof cb != 'undefined') {
                    cb();
                }
            });
        },

        delete : function(cb) {
            just_write_app.loader(2);

            $.ajax({
                url: just_write_cfg.ajax_url + '?action=orb_just_write_delete_site',
                method: "POST",
                data: { site_id : $('#site_id').val() }
            }).done(function (json) {
                just_write_app.sites.load();

                if (typeof cb != 'undefined') {
                    cb();
                }
            });
        }
    }
};

jQuery(document).ready(function ($) {
    just_write_app.sites.load();

    if ( typeof just_write_cfg.ed_prefs != 'undefined' ) {
        for ( var key in just_write_cfg.ed_prefs ) {
            var jq_key = '#' + key;

            $(jq_key).prop('checked', just_write_cfg.ed_prefs[ key ] ? 1 : 0 );
        }
    }

    $('.setting_auto_save').on('change', function () {
        just_write_app.handle_setting_auto_save(this);
        return false;
    });

    $('#app_show_reqs').on('click', function () {
        $('.app_reqs').toggle();
        return false;
    });

    $('#app_toggle_add_site').on('click', function () {
        $('.add_site_container').toggle();
        return false;
    });

    $('#btn_cancel_login').on('click', function () {
        $('.add_site_container').hide();
        return false;
    });

    $(":input:enabled:visible:first").first().focus();

    $("#post_title, #post_content").on('keyup', function () {
        var content = $(this).val().trim();

        // Allow some HTML
        // http://phpjs.org/functions/strip_tags/
        content = just_write_app.util.encodeHTML(content);
        content = just_write_app.util.nl2br(content);

        if ($(this).prop('id') === 'post_title') {
            $("#title_preview").html(content);
        } else {
            $("#content_preview").html(content);
        }

        return false;
    });

    $('#main_form').on('submit', function () {
        just_write_app.loader(2);

        $.ajax({
            url: just_write_cfg.ajax_url + '?action=orb_just_write_save_content',
            method: "POST",
            data: $(this).serialize()
        }).done(function (json) {
            just_write_app.message(json.message, json.status);

            if (parseInt(json.post_id) > 0) {
                $('#post_id').val(json.post_id);
            }

            if ( json.status ) {
                setTimeout( function () {
                    just_write_app.message();
                }, 2000);
            }
        });

        return false;
    });

    $('#quick_form').on('submit', function () {
        just_write_app.loader(2);

        $.ajax({
            url: just_write_cfg.ajax_url + '?action=orb_just_quick_contact',
            method: "POST",
            data: $(this).serialize()
        }).done(function (json) {
            just_write_app.loader(0);
            just_write_app.message(json.message, json.status);

            if ( json.status ) {
                $('#quick_contact_message').val('').focus();

                setTimeout( function () {
                    just_write_app.message();
                }, 2000);
            }
        });

        return false;
    });

    $('#btn_save_login').on('click', function () {
        just_write_app.loader(2);

        var data = {
            'site_id' : $('#site_id').val() || 0,
            'site_url' : $('#site_url').val() || '',
            'site_user' : $('#site_user').val() || '',
            'site_pass' : $('#site_pass').val() || ''
        };

        $.ajax({
            url: just_write_cfg.ajax_url + '?action=orb_just_write_auth_save',
            method: "POST",
            data: data,
        }).done(function (json) {
            just_write_app.loader(0);
            just_write_app.message(json.message, json.status);

            if ( parseInt( json.site_id ) > 0 ) {
                just_write_app.sites.load( function () {
                    $('#site_id').val( json.site_id );
                });
            }

            if ( json.status ) {
                $('#add_site_container').hide();
                
                setTimeout( function () {
                    just_write_app.message();
                }, 2000);
            }
        });

        return false;
    });
});
