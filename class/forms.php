<?php

class WPUF_Forms {

    private $meta_key = 'wpuf_form';

    function __construct() {
        add_action( 'init', array($this, 'register_post_type') );
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );

        // meta boxes
        add_action( 'do_meta_boxes', array($this, 'do_meta_boxes') );
        add_action( 'add_meta_boxes_wpuf_forms', array($this, 'add_meta_box') );


        // custom columns
        add_filter( 'manage_edit-wpuf_forms_columns', array( $this, 'admin_column' ) );
        add_action( 'manage_wpuf_forms_posts_custom_column', array( $this, 'admin_column_value' ), 10, 2 );

        // ajax actions
        add_action( 'wp_ajax_wpuf_form_dump', array( $this, 'form_dump' ) );
        add_action( 'wp_ajax_wpuf_form_add_el', array( $this, 'ajax_add_element' ) );

        add_action( 'save_post', array( $this, 'save_meta' ), 1, 2 ); // save the custom fields
        add_action( 'save_post', array( $this, 'form_selection_metabox_save' ), 1, 2 ); // save the custom fields
    }

    function enqueue_scripts() {
        global $pagenow;

        if ( !in_array( $pagenow, array( 'post.php', 'post-new.php') ) ) {
            return;
        }

        $path = plugins_url( 'wp-user-frontend' );

        // scripts
        wp_enqueue_script( 'jquery-smallipop', $path . '/js/jquery.smallipop-0.4.0.min.js', array('jquery') );
        wp_enqueue_script( 'wpuf-formbuilder', $path . '/js/formbuilder.js', array('jquery', 'jquery-ui-sortable') );

        // styles
        wp_enqueue_style( 'jquery-smallipop', $path . '/css/jquery.smallipop.css' );
        wp_enqueue_style( 'wpuf-formbuilder', $path . '/css/formbuilder.css' );
        wp_enqueue_style( 'jquery-ui-core', $path . '/css/jquery-ui-1.9.1.custom.css' );
    }

    function register_post_type() {
        register_post_type( 'wpuf_forms', array(
            'label' => __( 'Forms', 'wpuf' ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'wpuf-admin-opt',
            'capability_type' => 'post',
            'hierarchical' => false,
            'query_var' => false,
            'supports' => array('title'),
            'labels' => array(
                'name' => __( 'Forms', 'wpuf' ),
                'singular_name' => __( 'Form', 'wpuf' ),
                'menu_name' => __( 'Forms', 'wpuf' ),
                'add_new' => __( 'Add Form', 'wpuf' ),
                'add_new_item' => __( 'Add New Form', 'wpuf' ),
                'edit' => __( 'Edit', 'wpuf' ),
                'edit_item' => __( 'Edit Form', 'wpuf' ),
                'new_item' => __( 'New Form', 'wpuf' ),
                'view' => __( 'View Form', 'wpuf' ),
                'view_item' => __( 'View Form', 'wpuf' ),
                'search_items' => __( 'Search Form', 'wpuf' ),
                'not_found' => __( 'No Form Found', 'wpuf' ),
                'not_found_in_trash' => __( 'No Form Found in Trash', 'wpuf' ),
                'parent' => __( 'Parent Form', 'wpuf' ),
            ),
        ) );
    }

    function admin_column( $columns ) {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => __( 'Form Name', 'wpuf' ),
            'shortcode' => __( 'Shortcode', 'wpuf' )
        );

        return $columns;
    }

    function admin_column_value( $column_name, $post_id ) {
        if ($column_name == 'shortcode') {
            printf( '[wpuf_form id="%d"]', $post_id );
        }
    }

    function do_meta_boxes() {
        remove_meta_box('submitbox', 'wpuf_forms', 'side');
        
        $post_types = get_post_types( array('public' => true) );
        foreach ($post_types as $post_type) {
            add_meta_box( 'wpuf-select-form', __('WPUF Form'), array($this, 'form_selection_metabox'), $post_type, 'side', 'high' );
        }
    }

    function add_meta_box() {
        add_meta_box( 'wpuf-metabox-settings', __( 'Form Settings', 'wpuf' ), array($this, 'form_settings'), 'wpuf_forms', 'normal', 'high' );
        add_meta_box( 'wpuf-metabox', __( 'Form Editor', 'wpuf' ), array($this, 'edit_form_area'), 'wpuf_forms', 'normal', 'high' );
        add_meta_box( 'wpuf-metabox-fields', __( 'Form Elements', 'wpuf' ), array($this, 'form_elements'), 'wpuf_forms', 'side', 'core' );
    }
    
    function form_selection_metabox() {
        global $post;
        
        $forms = get_posts( array('post_type' => 'wpuf_forms', 'numberposts' => '-1') );
        $selected = get_post_meta( $post->ID, '_wpuf_form_id', true );
        ?>

        <input type="hidden" name="wpuf_form_select_nonce" value="<?php echo wp_create_nonce( plugin_basename( __FILE__ ) ); ?>" />
        
        <select name="wpuf_form_select">
            <?php foreach ($forms as $form) { ?>
            <option value="<?php echo $form->ID; ?>"<?php selected($selected, $form->ID); ?>><?php echo $form->post_title; ?></option>
            <?php } ?>
        </select>
        <?php
    }
    
    function form_selection_metabox_save( $post_id, $post ) {
        if ( !isset($_POST['wpuf_form_select'])) {
            return $post->ID;
        }

        if ( !wp_verify_nonce( $_POST['wpuf_form_select_nonce'], plugin_basename( __FILE__ ) ) ) {
            return $post->ID;
        }

        // Is the user allowed to edit the post or page?
        if ( !current_user_can( 'edit_post', $post->ID ) ) {
            return $post->ID;
        }

        update_post_meta( $post->ID, '_wpuf_form_id', $_POST['wpuf_form_select'] );
    }

    function form_dump() {
        echo '<pre>';
        print_r($_POST['wpuf_input']);
        echo '</pre>';

        die();
    }

    function form_settings() {
        global $post;

        $form_settings = get_post_meta( $post->ID, 'wpuf_form_settings', true );

        $restrict_message = __( "This page is restricted. Please Log in / Register to view this page.", 'wpuf' );

        $post_type_selected = isset( $form_settings['post_type'] ) ? $form_settings['post_type'] : 'post';
        $post_status_selected = isset( $form_settings['post_status'] ) ? $form_settings['post_status'] : 'publish';

        $guest_post = isset( $form_settings['guest_post'] ) ? $form_settings['guest_post'] : 'false';
        $guest_details = isset( $form_settings['guest_details'] ) ? $form_settings['guest_details'] : 'true';
        $name_label = isset( $form_settings['name_label'] ) ? $form_settings['name_label'] : __( 'Name' );
        $email_label = isset( $form_settings['email_label'] ) ? $form_settings['email_label'] : __( 'Email' );
        $message_restrict = isset( $form_settings['message_restrict'] ) ? $form_settings['message_restrict'] : $restrict_message;

        $redirect_to = isset( $form_settings['redirect_to'] ) ? $form_settings['redirect_to'] : 'post';
        $message = isset( $form_settings['message'] ) ? $form_settings['message'] : __( 'Post saved', 'wpuf' );
        $page_id = isset( $form_settings['page_id'] ) ? $form_settings['page_id'] : 0;
        $url = isset( $form_settings['url'] ) ? $form_settings['url'] : '';
        $submit_text = isset( $form_settings['submit_text'] ) ? $form_settings['submit_text'] : __( 'Submit', 'wpuf' );
        $update_text = isset( $form_settings['update_text'] ) ? $form_settings['update_text'] : __( 'Update', 'wpuf' );
        ?>
        <table class="form-table">
            <tr class="wpuf-post-type">
                <th><?php _e( 'Post Type', 'wpuf' ); ?></th>
                <td>
                    <select name="wpuf_settings[post_type]">
                        <?php
                        $post_types = get_post_types();
                        unset($post_types['attachment']);
                        unset($post_types['revision']);
                        unset($post_types['nav_menu_item']);
                        unset($post_types['wpuf_forms']);

                        foreach ($post_types as $post_type) {
                            printf('<option value="%s"%s>%s</option>', $post_type, selected( $post_type_selected, $post_type, false ), $post_type );
                        }
                        ?>
                    </select>
                </td>
            </tr>

            <tr class="wpuf-post-status">
                <th><?php _e( 'Post Status', 'wpuf' ); ?></th>
                <td>
                    <select name="wpuf_settings[post_status]">
                        <?php
                        $statuses = get_post_statuses();

                        foreach ($statuses as $status => $label) {
                            printf('<option value="%s"%s>%s</option>', $status, selected( $post_status_selected, $status, false ), $label );
                        }
                        ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th><?php _e( 'Guest Post', 'wpuf' ); ?></th>
                <td>
                    <label>
                        <input type="hidden" name="wpuf_settings[guest_post]" value="false">
                        <input type="checkbox" name="wpuf_settings[guest_post]" value="true"<?php checked( $guest_post, 'true' ); ?> />
                        <?php _e( 'Enable Guest Post', 'wpuf' ) ?>
                    </label>
                    <div class="description"><?php _e( 'Unregistered users will be able to submit posts', 'wpuf' ); ?></div>
                </td>
            </tr>

            <tr class="show-if-guest">
                <th><?php _e( 'User Details', 'wpuf' ); ?></th>
                <td>
                    <label>
                        <input type="hidden" name="wpuf_settings[guest_details]" value="false">
                        <input type="checkbox" name="wpuf_settings[guest_details]" value="true"<?php checked( $guest_details, 'true' ); ?> />
                        <?php _e( 'Require Name and Email address', 'wpuf' ) ?>
                    </label>
                    <div class="description"><?php _e( 'If requires, users will be automatically registered to the site using the name and email address', 'wpuf' ); ?></div>
                </td>
            </tr>

            <tr class="show-if-guest show-if-details">
                <th><?php _e( 'Name Label', 'wpuf' ); ?></th>
                <td>
                    <label>
                        <input type="text" name="wpuf_settings[name_label]" value="<?php echo esc_attr( $name_label ); ?>" />
                    </label>
                    <div class="description"><?php _e( 'Label text for name field', 'wpuf' ); ?></div>
                </td>
            </tr>

            <tr class="show-if-guest show-if-details">
                <th><?php _e( 'E-Mail Label', 'wpuf' ); ?></th>
                <td>
                    <label>
                        <input type="text" name="wpuf_settings[email_label]" value="<?php echo esc_attr( $email_label ); ?>" />
                    </label>
                    <div class="description"><?php _e( 'Label text for email field', 'wpuf' ); ?></div>
                </td>
            </tr>

            <tr class="show-if-not-guest">
                <th><?php _e( 'Unauthorized Message', 'wpuf' ); ?></th>
                <td>
                    <textarea rows="3" cols="40" name="wpuf_settings[message_restrict]"><?php echo esc_textarea( $message_restrict ); ?></textarea>
                    <div class="description"><?php _e( 'Not logged in users will see this message', 'wpuf' ); ?></div>
                </td>
            </tr>

            <tr class="wpuf-redirect-to">
                <th><?php _e( 'Redirect To', 'wpuf' ); ?></th>
                <td>
                    <select name="wpuf_settings[redirect_to]">
                        <?php
                        $redirect_options = array(
                            'post' => __( 'Newly created post', 'wpuf' ),
                            'same' => __( 'Same Page', 'wpuf' ),
                            'page' => __( 'To a page', 'wpuf' ),
                            'url' => __( 'To a custom URL', 'wpuf' )
                        );

                        foreach ($redirect_options as $to => $label) {
                            printf('<option value="%s"%s>%s</option>', $to, selected( $redirect_to, $to, false ), $label );
                        }
                        ?>
                    </select>
                    <div class="description">
                        <?php _e( 'After successfull submit, where the page will redirect to', $domain = 'default' ) ?>
                    </div>
                </td>
            </tr>

            <tr class="wpuf-same-page">
                <th><?php _e( 'Message to show', 'wpuf' ); ?></th>
                <td>
                    <textarea rows="3" cols="40" name="wpuf_settings[message]"><?php echo esc_textarea( $message ); ?></textarea>
                </td>
            </tr>

            <tr class="wpuf-page-id">
                <th><?php _e( 'Page', 'wpuf' ); ?></th>
                <td>
                    <select name="wpuf_settings[page_id]">
                        <?php
                        $pages = get_posts(  array( 'numberposts' => -1, 'post_type' => 'page') );

                        foreach ($pages as $page) {
                            printf('<option value="%s"%s>%s</option>', $page->ID, selected( $page_id, $page->ID, false ), esc_attr( $page->post_title ) );
                        }
                        ?>
                    </select>
                </td>
            </tr>

            <tr class="wpuf-url">
                <th><?php _e( 'Custom URL', 'wpuf' ); ?></th>
                <td>
                    <input type="url" name="wpuf_settings[url]" value="<?php echo esc_attr( $url ); ?>">
                </td>
            </tr>

            <tr class="wpuf-submit-text">
                <th><?php _e( 'Submit Button text', 'wpuf' ); ?></th>
                <td>
                    <input type="text" name="wpuf_settings[submit_text]" value="<?php echo esc_attr( $submit_text ); ?>">
                </td>
            </tr>

            <tr class="wpuf-update-text">
                <th><?php _e( 'Update Button text', 'wpuf' ); ?></th>
                <td>
                    <input type="text" name="wpuf_settings[update_text]" value="<?php echo esc_attr( $update_text ); ?>">
                </td>
            </tr>
        </table>
        <?php
    }

    function form_elements() {
        ?>

        <div class="wpuf-loading hide"></div>

        <h2><?php _e( 'Post Fields', 'wpuf' ); ?></h2>
        <div class="wpuf-form-buttons">
            <button class="button" data-name="post_title" data-type="text"><?php _e( 'Post Title', 'wpuf' ); ?></button>
            <button class="button" data-name="post_content" data-type="textarea"><?php _e( 'Post Body', 'wpuf' ); ?></button>
            <button class="button" data-name="post_excerpt" data-type="textarea"><?php _e( 'Excerpt', 'wpuf' ); ?></button>
            <button class="button" data-name="tags" data-type="text"><?php _e( 'Tags', 'wpuf' ); ?></button>
            <button class="button" data-name="category" data-type="category"><?php _e( 'Category', 'wpuf' ); ?></button>
            <button class="button" data-name="featured_image" data-type="image"><?php _e( 'Featured Image', 'wpuf' ); ?></button>
        </div>

        <h2><?php _e( 'Custom Fields', 'wpuf' ); ?></h2>
        <div class="wpuf-form-buttons">
            <button class="button" data-name="custom_text" data-type="text"><?php _e( 'Text', 'wpuf' ); ?></button>
            <button class="button" data-name="custom_textarea" data-type="textarea"><?php _e( 'Textarea', 'wpuf' ); ?></button>
            <button class="button" data-name="custom_select" data-type="select"><?php _e( 'Dropdown', 'wpuf' ); ?></button>
            <button class="button" data-name="custom_multiselect" data-type="multiselect"><?php _e( 'Multi Select', 'wpuf' ); ?></button>
            <button class="button" data-name="custom_radio" data-type="radio"><?php _e( 'Radio', 'wpuf' ); ?></button>
            <button class="button" data-name="custom_checkbox" data-type="checkbox"><?php _e( 'Checkbox', 'wpuf' ); ?></button>
            <button class="button" data-name="custom_image" data-type="image"><?php _e( 'Image Upload', 'wpuf' ); ?></button>
            <button class="button" data-name="custom_file" data-type="file"><?php _e( 'File Upload', 'wpuf' ); ?></button>
            <button class="button" data-name="custom_url" data-type="url"><?php _e( 'URL', 'wpuf' ); ?></button>
            <button class="button" data-name="custom_email" data-type="email"><?php _e( 'Email', 'wpuf' ); ?></button>
            <button class="button" data-name="custom_repeater" data-type="repeat"><?php _e( 'Repeat Field', 'wpuf' ); ?></button>

        </div>

        <h2><?php _e( 'Custom Taxonomies', 'wpuf' ); ?></h2>
        <div class="wpuf-form-buttons">
            <?php
            $custom_taxonomies = get_taxonomies(array('_builtin' => false ) );
            if ( $custom_taxonomies ) {
                foreach ($custom_taxonomies as $tax) {
                    ?>
                    <button class="button" data-name="taxonomy" data-type="<?php echo $tax; ?>"><?php echo $tax; ?></button>
                    <?php
                }
            } else {
                _e('No custom taxonomies found', 'wpuf');
            }?>
        </div>

        <h2><?php _e( 'Others', 'wpuf' ); ?></h2>
        <div class="wpuf-form-buttons">
            <button class="button" data-name="recaptcha" data-type="captcha"><?php _e( 'reCaptcha', 'wpuf' ); ?></button>
            <button class="button" data-name="really_simple_captcha" data-type="rscaptcha"><?php _e( 'Really Simple Captcha', 'wpuf' ); ?></button>
            <button class="button" data-name="section_break" data-type="break"><?php _e( 'Section Break', 'wpuf' ); ?></button>
            <button class="button" data-name="custom_html" data-type="html"><?php _e( 'HTML', 'wpuf' ); ?></button>
            <button class="button" data-name="action_hook" data-type="action"><?php _e( 'Action Hook', 'wpuf' ); ?></button>
        </div>
        <?php
    }

    function save_meta( $post_id, $post ) {
        if ( !isset($_POST['wpuf_form_editor'])) {
            return $post->ID;
        }

        if ( !wp_verify_nonce( $_POST['wpuf_form_editor'], plugin_basename( __FILE__ ) ) ) {
            return $post->ID;
        }

        // Is the user allowed to edit the post or page?
        if ( !current_user_can( 'edit_post', $post->ID ) ) {
            return $post->ID;
        }

        // var_dump($_POST); die();
        update_post_meta( $post->ID, $this->meta_key, $_POST['wpuf_input'] );
        update_post_meta( $post->ID, 'wpuf_form_settings', $_POST['wpuf_settings'] );
    }

    function edit_form_area() {
        global $post, $pagenow;

        $form_inputs = get_post_meta( $post->ID, $this->meta_key, true );
        // var_dump($form_inputs);
        ?>

        <input type="hidden" name="wpuf_form_editor" id="wpuf_form_editor" value="<?php echo wp_create_nonce( plugin_basename( __FILE__ ) ); ?>" />

        <div style="margin-bottom: 10px">
            <button class="button wpuf-collapse">Collapse All</button>
        </div>

        <ul id="wpuf-form-editor" class="wpuf-form-editor unstyled">

        <?php
        require_once dirname( __FILE__ ) . '/form-template.php';

        if ($form_inputs) {
            $count = 0;
            foreach ($form_inputs as $order => $input_field) {
                $name = ucwords( str_replace( '_', ' ', $input_field['template'] ) );

                if ( $input_field['template'] == 'taxonomy') {
                    WPUF_Form_Template::$input_field['template']( $count, $name, $input_field['name'], $input_field );
                } else {
                    WPUF_Form_Template::$input_field['template']( $count, $name, $input_field );
                }

                $count++;
            }
        }
        ?>
        </ul>

        <?php if( $pagenow == 'post.php' ) { ?>
            <a class="button button-primary button-large" target="_blank" href="<?php printf('%s?action=wpuf_form_preview&form_id=%s', admin_url( 'admin-ajax.php' ), $post->ID ); ?>"><?php _e( 'Preview Form', 'wpuf' ); ?></a>
        <?php } ?>

        <?php
        // include dirname( __FILE__ ) . '/forms-edit.php';
    }

    function ajax_add_element() {
        require_once dirname( __FILE__ ) . '/form-template.php';

        // print_r( $_POST ); die();

        $name = $_POST['name'];
        $type = $_POST['type'];
        $field_id = $_POST['order'];

        switch ($name) {
            case 'post_title':
                WPUF_Form_Template::post_title( $field_id, 'Post Title');
                break;

            case 'post_content':
                WPUF_Form_Template::post_content( $field_id, 'Post Body');
                break;

            case 'post_excerpt':
                WPUF_Form_Template::post_excerpt( $field_id, 'Excerpt');
                break;

            case 'tags':
                WPUF_Form_Template::post_tags( $field_id, 'Tags');
                break;

            case 'featured_image':
                WPUF_Form_Template::featured_image( $field_id, 'Featured Image');
                break;

            case 'custom_text':
                WPUF_Form_Template::custom_text( $field_id, 'Custom field: Text');
                break;

            case 'custom_textarea':
                WPUF_Form_Template::custom_textarea( $field_id, 'Custom field: Textarea');
                break;

            case 'custom_select':
                WPUF_Form_Template::custom_select( $field_id, 'Custom field: Select');
                break;

            case 'custom_multiselect':
                WPUF_Form_Template::custom_multiselect( $field_id, 'Custom field: Multiselect');
                break;

            case 'custom_radio':
                WPUF_Form_Template::custom_radio( $field_id, 'Custom field: Radio');
                break;

            case 'custom_checkbox':
                WPUF_Form_Template::custom_checkbox( $field_id, 'Custom field: Checkbox');
                break;

            case 'custom_image':
                WPUF_Form_Template::custom_image( $field_id, 'Custom field: Image');
                break;

            case 'custom_file':
                WPUF_Form_Template::custom_file( $field_id, 'Custom field: File Upload');
                break;

            case 'custom_url':
                WPUF_Form_Template::custom_url( $field_id, 'Custom field: URL');
                break;

            case 'custom_email':
                WPUF_Form_Template::custom_email( $field_id, 'Custom field: E-Mail');
                break;

            case 'custom_repeater':
                WPUF_Form_Template::custom_repeater( $field_id, 'Custom field: Repeat Field');
                break;

            case 'custom_html':
                WPUF_Form_Template::custom_html( $field_id, 'HTML' );
                break;

            case 'category':
                WPUF_Form_Template::taxonomy( $field_id, 'Category', $type );
                break;

            case 'taxonomy':
                WPUF_Form_Template::taxonomy( $field_id, 'Taxonomy: ' . $type, $type );
                break;

            case 'section_break':
                WPUF_Form_Template::section_break( $field_id, 'Section Break' );
                break;

            case 'recaptcha':
                WPUF_Form_Template::recaptcha( $field_id, 'reCaptcha' );
                break;

            case 'action_hook':
                WPUF_Form_Template::action_hook( $field_id, 'Action Hook' );
                break;

            case 'really_simple_captcha':
                WPUF_Form_Template::really_simple_captcha( $field_id, 'Really Simple Captcha' );
                break;


            default:
                # code...
                break;
        }

        exit;
    }

}