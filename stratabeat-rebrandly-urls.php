<?php
 
/*
Plugin Name: Rebrandly Short URLs
Description: Create shortened URLs for your posts, pages, or custom post types using the Rebrandly.com API.
Version: 1.2.1
Author: Stratabeat
Author URI: http://stratabeat.com
*/


if ( is_admin() ) :

  /**
   * Register plugin settings page.
   */

  add_action( 'admin_menu', 'sbrb_add_admin_menu' );
  add_action( 'admin_init', 'sbrb_settings_init' );

  function sbrb_add_admin_menu() {
    $page = add_submenu_page( 'options-general.php', 
    __( 'Rebrandly URL Shortener', 'stratabeat_rebrandly_urls' ), 
    __( 'Rebrandly URLs', 'stratabeat_rebrandly_urls' ), 
    'manage_options', 
    'sbrb-url-shortener', 
    'sbrb_options_page'
    );
  }

  function sbrb_settings_init() {
    register_setting('pluginOptions', 'sbrb_settings');

    add_settings_section(
      'sbrb_pluginOptions_section', 
      __('URL Shortener Settings', 'stratabeat_rebrandly_urls'), 
      'sbrb_settings_section_callback', 
      'pluginOptions'
      );

    // Rebrandly API Key
    add_settings_field(
      'sbrb_api_key', // url-shortener-input-field
      __('Rebrandly API Key (required)', 'stratabeat_rebrandly_urls'), 
      'sbrb_api_key_field_render', 
      'pluginOptions', 
      'sbrb_pluginOptions_section'
      );

    if ( true == check_rebrandly_account() ) {
      // Rebrandly Domain ID
      add_settings_field(
        'sbrb_domain_id', // branded-url-input-field
        __('Short Link Domain', 'stratabeat_rebrandly_urls'), 
        'sbrb_domain_id_field_render', 
        'pluginOptions', 
        'sbrb_pluginOptions_section'
        );

      // Site Post Types
      add_settings_field(
        'sbrb_post_types', // shorten-post-types-input-field
        __('Post Types', 'stratabeat_rebrandly_urls'), 
        'sbrb_post_types_field_render', 
        'pluginOptions', 
        'sbrb_pluginOptions_section'
        );
    }
  }

  function sbrb_settings_section_callback() { 
    echo '<p>' . sprintf( __( 'Enter your API key below and save the settings to link to your Rebrandly account. You can find or create your API key <a href="%s" target="_blank">here</a>.', 'stratabeat_rebrandly_urls' ), esc_url( 'https://rebrandly.com/api-settings' ) ) . '</p>';
  }

  function sbrb_plugin_add_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=sbrb-url-shortener">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
    return $links;
  }
  $sbrb_plugin = plugin_basename( __FILE__ );
  add_filter( "plugin_action_links_$sbrb_plugin", 'sbrb_plugin_add_settings_link' );


  /**
   * Render inputs on plugin settings page.
   */

  function sbrb_api_key_field_render() {
    $options = get_option('sbrb_settings');
    $valid_class = 'valid';
    if ( false == check_rebrandly_account() && $options['sbrb_api_key'] != '' ) {
      $feedback = '<p>' . __('Your API Key is invalid.', 'stratabeat_rebrandly_urls') . '</p>';
      $valid_class = 'invalid';
    }
    ?>
    <input type="text" id="url-shortener-input-field" name="sbrb_settings[sbrb_api_key]" value="<?php echo $options['sbrb_api_key']; ?>" class="<?php echo $valid_class; ?>" required> 
    <?php
    if ( isset($feedback) ) echo $feedback;
  }

  function sbrb_domain_id_field_render() {
    $options = get_option('sbrb_settings');
    $default_domain_id = '8f104cc5b6ee4a4ba7897b06ac2ddcfb'; // defaults to rebrand.ly
    $current_domain_name = ( isset( $options['sbrb_domain_id'] ) ) ? check_rebrandly_domain( $options['sbrb_domain_id'] )->fullName : 'rebrand.ly';
    ?>
    <div id="current-domain-output">
      <div class="current-domain-name"><input type="text" value="<?php echo $current_domain_name; ?>" disabled></div>
      <div class="get-domains-button"><a href="#" id="get-rebrandly-domains" class="button button-secondary"><?php echo __('List Your Rebrandly Domains', 'stratabeat_rebrandly_urls'); ?></a></div>
    </div>
    <select id="rebrandly-domain-list">
      <option value=""><?php echo __('Select a domain...', 'stratabeat_rebrandly_urls'); ?></option>
      <option value="<?php echo $default_domain_id; ?>">rebrand.ly</option>
    </select>
    <input type="hidden" id="branded-url-input-field" name="sbrb_settings[sbrb_domain_id]" value="<?php echo $options['sbrb_domain_id']; ?>"> 
    <?php
  }

  function sbrb_post_types_field_render() {
    $options = get_option('sbrb_settings');
    $args = array(
      'public' => true,
      );
    $post_types = get_post_types( $args, 'objects' );
    ?>
    <div class="shorten-post-types">
    <!-- <p>Choose which post types you would like to shorten the URLs for:</p> -->
    <?php foreach ( $post_types as $type ) {
      if ( isset($options['sbrb_post_types']) && is_array($options['sbrb_post_types']) && in_array( $type->name, $options['sbrb_post_types'] ) ) {
        echo '<p><input type="checkbox" name="sbrb_settings[sbrb_post_types][]" value="'.$type->name.'" checked="checked"><label>'.$type->label.'</label><p>';
      } else {
        echo '<p><input type="checkbox" name="sbrb_settings[sbrb_post_types][]" value="'.$type->name.'"><label>'.$type->label.'</label><p>';
      }
    } ?>
    </div>
    <?php 
  }


  /**
   * Render settings page content.
   */

  function sbrb_plugin_admin_scripts() {
    if ( !is_admin() ) 
      return;

    global $hook_suffix;
    if ( $hook_suffix != 'settings_page_sbrb-url-shortener' ) 
      return;

    wp_register_script(
      'sbrb-admin-functions', 
      plugin_dir_url(__FILE__) . 'js/url-shortener-admin-functions.js', 
      array('jquery'), 
      '1.0', 
      true
      );
    wp_enqueue_script( 'sbrb-admin-functions' );
  }
  add_action('admin_print_scripts-settings_page_sbrb-url-shortener', 'sbrb_plugin_admin_scripts');

  function sbrb_plugin_admin_styles() {
    if ( !is_admin() ) 
      return;

    global $hook_suffix;
    if ( $hook_suffix != 'settings_page_sbrb-url-shortener' ) 
      return;

    wp_register_style(
      'sbrb-admin-style', 
      plugin_dir_url(__FILE__) . 'css/url-shortener-admin-style.css', 
      array(), 
      '1.0', 
      'all'
      );
    wp_enqueue_style( 'sbrb-admin-style' );
  }
  add_action('admin_print_scripts-settings_page_sbrb-url-shortener', 'sbrb_plugin_admin_styles');

  function sbrb_plugin_post_edit_styles() {
    echo '<link rel="stylesheet" href="' . plugin_dir_url(__FILE__) . 'css/url-shortener-post-style.css" type="text/css" media="all" />';
  }
  add_action('admin_head', 'sbrb_plugin_post_edit_styles');


  /**
   * Check a Rebrandly account API Key.
   * 
   * @return boolean
   */
  function check_rebrandly_account() {
    $options = get_option('sbrb_settings');
    // If API Key option not set, do nothing
    if( $options['sbrb_api_key'] == '' )
      return;

    $url = 'https://api.rebrandly.com/v1/account';

    $result = wp_remote_get(
      $url, 
      array(
        'headers' => array(
          'apikey' => $options['sbrb_api_key']
        )
      )
    );

    $response = ( $result['response']['code'] == 200 ) ? true : false;

    return $response;
  }

  function sbrb_options_page() {
    // if ( ! current_user_can( 'manage_options' ) ) {
    //  return;
    // }
    ?>
    <div class="wrap">
      <h1>Rebrandly URL Shortener</h1>
      <p><?php echo __("If you don't already have an account, sign up for free at", 'stratabeat_rebrandly_urls'); ?> <a href="https://oauth.rebrandly.com/localregistration" target="_blank">Rebrandly.com</a>.
      <form method="post" action="options.php">
        <?php
        settings_fields('pluginOptions');
        do_settings_sections('pluginOptions');
        submit_button();
        ?>
      </form>
    </div>
    <?php
  }


  /**
   * Get Domain ID option value.
   *
   * @param WP_Post $post The post object.
   */
  function get_branded_domain_id_option() {
    $options = get_option('sbrb_settings');
    $domain = $options['sbrb_domain_id'];

    if( isset($domain) || $domain != '') {
      return array('id' => $domain);
    } else {
      return;
    }
  }


  /**
   * Create a new Rebrandly link.
   *
   * @param WP_Post $post The post object.
   */
  function create_rebrandly_link( $_link_slug, $post_id ) {
    $options = get_option('sbrb_settings');
    $permalink = get_permalink($post_id);
    $title = get_the_title($post_id);

    $url = 'https://api.rebrandly.com/v1/links';

    $result = wp_remote_post(
      $url, 
      array(
        'body' => json_encode(array(
          'title' => html_entity_decode($title, ENT_COMPAT, 'UTF-8'),
          'slashtag' => $_link_slug,
          'destination' => esc_url_raw($permalink),
          'domain' => get_branded_domain_id_option()
        )),
        'headers' => array(
          'apikey' => $options['sbrb_api_key'],
          'Content-Type' => 'application/json'
        )
      )
    );

    if ( is_wp_error($result) ) {
      var_dump( $result->get_error_message() ); die;
    } else {
      return json_decode($result['body']);
    }
  }


  /**
   * Update an existing Rebrandly link.
   *
   * @param WP_Post $post The post object.
   */
  function update_rebrandly_link( $_link_id, $_link_slug, $_link_url, $post_id ) {
    $options = get_option('sbrb_settings');
    $permalink = get_permalink($post_id);
    $title = get_the_title($post_id);
    $url = 'https://api.rebrandly.com/v1/links/' . $_link_id;

    $result = wp_remote_post(
      $url, 
      array(
        'body' => json_encode(array(
          'id' => $_link_id,
          'title' => html_entity_decode($title, ENT_COMPAT, 'UTF-8'),
          'slashtag' => $_link_slug,
          'destination' => esc_url_raw($permalink),
          'shortUrl' => $_link_url,
          'domain' => get_branded_domain_id_option(),
          'favourite' => false
        )),
        'headers' => array(
          'apikey' => $options['sbrb_api_key'],
          'Content-Type' => 'application/json'
        )
      )
    );

    return json_decode($result['body']);
  }


  /**
   * Check an existing Rebrandly link.
   *
   * @param WP_Post $post The post object.
   */
  function check_rebrandly_link( $link_id ) {
    $options = get_option('sbrb_settings');
    $url = 'https://api.rebrandly.com/v1/links/' . $link_id;

    $result = wp_remote_get(
      $url, 
      array(
        'headers' => array(
          'apikey' => $options['sbrb_api_key']
        )
      )
    );

    return json_decode($result['body']);
  }


  /**
   * Check an existing Rebrandly domain by ID.
   *
   * @param $rebrandly_id
   */
  function check_rebrandly_domain( $rebrandly_id ) {
    $options = get_option('sbrb_settings');
    $url = 'https://api.rebrandly.com/v1/domains/' . $rebrandly_id;

    $result = wp_remote_get(
      $url, 
      array(
        'headers' => array(
          'apikey' => $options['sbrb_api_key']
        )
      )
    );

    return json_decode($result['body']);
  }


  /**
   * Polyfill for wp_remote_delete HTTP method (see function below).
   *
   * @source http://www.alnorth.com/fixes/polyfilling-wp_remote_delete/
   */
  if ( ! function_exists( 'wp_remote_delete' ) ) {
    function wp_remote_delete($url, $args) {
      $defaults = array('method' => 'DELETE');
      $r = wp_parse_args( $args, $defaults );
      return wp_remote_request($url, $r);
    }
  }


  /**
   * Delete an existing Rebrandly link.
   *
   * @param WP_Post $post The post object.
   */
  function delete_rebrandly_link( $link_id ) {
    $options = get_option('sbrb_settings');
    $url = 'https://api.rebrandly.com/v1/links/' . $link_id . '?trash=true';

    $result = wp_remote_delete(
      $url, 
      array(
        'headers' => array(
          'apikey' => $options['sbrb_api_key']
        )
      )
    );

    return $result;
  }


  /**
   * Calls the class on the post edit screen.
   */
  function call_makeShortUrl() {
    new makeShortUrl();
  }
   
  if ( is_admin() ) {
    add_action( 'load-post.php',     'call_makeShortUrl' );
    add_action( 'load-post-new.php', 'call_makeShortUrl' );
  }
   
  /**
   * The Class.
   */
  class makeShortUrl {

    /**
     * Hook into the appropriate actions when the class is constructed.
     */
    public function __construct() {
      add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
      add_action( 'save_post',      array( $this, 'save'         ) );
    }

    /**
     * Adds the meta box container.
     */
    public function add_meta_box( $post_type ) {
      // Limit meta box to certain post types.
      $options = get_option('sbrb_settings');
      $post_types = $options['sbrb_post_types'];

      if ( is_array($post_types) && in_array( $post_type, $post_types ) ) {
        add_meta_box(
          'sbrb_short_url_metabox',
          __( 'Rebrandly URL', 'stratabeat_rebrandly_urls' ),
          array( $this, 'render_meta_box_content' ),
          $post_type,
          'side',
          'high'
        );
      }
    }

    /**
     * Save the meta when the post is saved.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function save( $post_id ) {
      /*
       * We need to verify this came from the our screen and with proper authorization,
       * because save_post can be triggered at other times.
       */

      // Check if our nonce is set.
      if ( ! isset( $_POST['sbrb_inner_custom_box_nonce'] ) ) {
          return $post_id;
      }

      $nonce = $_POST['sbrb_inner_custom_box_nonce'];

      // Verify that the nonce is valid.
      if ( ! wp_verify_nonce( $nonce, 'sbrb_inner_custom_box' ) ) {
          return $post_id;
      }

      /*
       * If this is an autosave, our form has not been submitted,
       * so we don't want to do anything.
       */
      if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
          return $post_id;
      }

      // Check the user's permissions.
      if ( 'page' == $_POST['post_type'] ) {
          if ( ! current_user_can( 'edit_page', $post_id ) ) {
              return $post_id;
          }
      } else {
          if ( ! current_user_can( 'edit_post', $post_id ) ) {
              return $post_id;
          }
      }

      /* OK, it's safe for us to save the data now. */

      // Sanitize the user input.
      $_short_link_slug = sanitize_text_field( $_POST['sbrb_rebrandly_slug'] );
      $_short_link_id = sanitize_text_field( $_POST['sbrb_rebrandly_id'] );
      $_short_link_url = sanitize_text_field( $_POST['sbrb_rebrandly_url'] );

      // Communicate with Rebrandly to create short link.
      if( !$_short_link_id ) {

        // If there is no short link ID yet, create one and save it to the post meta.
        $rebrandly = create_rebrandly_link( $_short_link_slug, $post_id );
        $_short_link_slug = $rebrandly->slashtag;
        $_short_link_id =  $rebrandly->id;
        $_short_link_url = $rebrandly->shortUrl;

      } else {

        // If there is a short link, check the Rebrandly link data and make adjustments if necessary.
        $rebrandly = check_rebrandly_link($_short_link_id);
        // Get the Rebrandly link slug value if post meta field is empty.
        if( $_short_link_slug == '' ) {
          $_short_link_slug = $rebrandly->slashtag;
        }
        // Check if the link slug has changed, and update Rebrandly & post_meta if necessary.
        if( $_short_link_slug != $rebrandly->slashtag ) {
          $rebrandly_update = update_rebrandly_link( $_short_link_id, $_short_link_slug, $_short_link_url, $post_id );
          $_short_link_url = $rebrandly_update->shortUrl;
        }
        // Check if the post permalink has changed, and update Rebrandly if necessary.
        if( get_permalink($post_id) != $rebrandly->destination ) {
          $rebrandly_update = update_rebrandly_link( $_short_link_id, $_short_link_slug, $_short_link_url, $post_id );
        }

      }

      // Update the meta fields.
      update_post_meta( $post_id, '_rebrandly_link_slug', $_short_link_slug );
      update_post_meta( $post_id, '_rebrandly_link_id', $_short_link_id );
      update_post_meta( $post_id, '_rebrandly_link_url', $_short_link_url );
    }


    /**
     * Render Meta Box content.
     *
     * @param WP_Post $post The post object.
     */
    public function render_meta_box_content( $post ) {
      // Add an nonce field so we can check for it later.
      wp_nonce_field( 'sbrb_inner_custom_box', 'sbrb_inner_custom_box_nonce' );

      if ( false == check_rebrandly_account() ) {
        echo '<p>' . sprintf( __( 'Enter your Rebrandly API Key on the <a href="%s" target="_blank">settings</a> page.', 'stratabeat_rebrandly_urls' ), 'options-general.php?page=sbrb-url-shortener' ) . '</p>';
      } else {
        // Use get_post_meta to retrieve an existing value from the database.
        $_custom_link_slug = get_post_meta( $post->ID, '_rebrandly_link_slug', true );
        
        // $value_link_slug = ($_custom_link_slug) ? $_custom_link_slug : $post->post_name;
        $value_link_slug = get_post_meta( $post->ID, '_rebrandly_link_slug', true );
        $value_link_id = get_post_meta( $post->ID, '_rebrandly_link_id', true );
        $value_link_url = get_post_meta( $post->ID, '_rebrandly_link_url', true );

        // Display the form, using the current value.
        if( $value_link_id ) : ?>
          <label for="sbrb_rebrandly_slug"><?php echo substr( $value_link_url, 0, stripos( $value_link_url, '/' ) ) . '/'; ?></label><input type="text" id="sbrb_rebrandly_slug" name="sbrb_rebrandly_slug" value="<?php echo esc_attr( $value_link_slug ); ?>" />
        <?php else : ?>
          <p><?php echo __('Publish or Update the post to create a new Rebrandly short link.', 'stratabeat_rebrandly_urls'); ?></p>
          <input type="hidden" id="sbrb_rebrandly_slug" name="sbrb_rebrandly_slug" value="<?php echo esc_attr( $value_link_slug ); ?>" />
        <?php endif; ?>
        <input type="hidden" id="sbrb_rebrandly_url" name="sbrb_rebrandly_url" value="<?php echo esc_attr( $value_link_url ); ?>" />
        <input type="hidden" id="sbrb_rebrandly_id" name="sbrb_rebrandly_id" value="<?php echo esc_attr( $value_link_id ); ?>" />
        <?php 
      }
    }
  }

endif; // is_admin


/**
 * When post is trashed, delete its link in Rebrandly.
 *
 * @param WP_Post $post The post object.
 */
function sbrb_delete_post_action( $post_id ) {
  $_shortlink_id = get_post_meta( $post_id, '_rebrandly_link_id', true );
  delete_rebrandly_link( $_shortlink_id );
}
// add_action('wp_trash_post','sbrb_delete_post_action');
// add_action( 'delete_post', 'sbrb_delete_post_action', 10 );


/**
 * Shortcode to get shortened URL for the post
 * 
 * @param $post_id (optional)
 */
function rebrandly_url_shortcode( $atts ) {
  // global $post;

  $atts = shortcode_atts(
    array(
      'id' => get_the_ID(),
    ),
    $atts,
    'rebrandly_url'
  );

  // $_scheme = ( is_ssl() ) ? 'https://' : 'http://'; // Rebrandly domains don't work with HTTPS
  $_scheme = 'http://';
  $_short_url = get_post_meta( $atts['id'], '_rebrandly_link_url', true );

  if ( $_short_url != '' ) {
    return $_scheme . $_short_url;
  } else {
    return get_permalink( $atts['id'] );
  }
}
add_shortcode( 'rebrandly_url', 'rebrandly_url_shortcode' );


/**
 * Function get shortened URL for the post
 * 
 * @param $post_id (optional)
 */
function get_rebrandly_url( $post_id ) {
  $_post_id = ( $post_id ) ? $post_id : get_permalink( $post->ID );
  $_short_url = get_post_meta( $_post_id, '_rebrandly_link_url', true );

  return $_short_url;
}


/* eof */