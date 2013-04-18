<?php
/**
 * Post related form templates
 *
 * @package WP User Frontend
 */
class WPUF_Admin_Template_Post extends WPUF_Admin_Template {

    public static function post_title( $field_id, $label, $values = array() ) {
        ?>
        <li class="post_title">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'text' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'post_title' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, 'post_title', false, $values ); ?>
                <?php self::common_text( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function post_content( $field_id, $label, $values = array() ) {
        // var_dump($values);

        $image_insert_name = sprintf( '%s[%d][insert_image]', self::$input_name, $field_id );
        $image_insert_value = isset( $values['insert_image'] ) ? $values['insert_image'] : 'yes';
        ?>
        <li class="post_content">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'textarea' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'post_content' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, 'post_content', false, $values ); ?>
                <?php self::common_text( $field_id, $values ); ?>
                <?php self::common_textarea( $field_id, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Enable Image Insertion', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <label>
                            <?php self::hidden_field( "[$field_id][insert_image]", 'no' ); ?>
                            <input type="checkbox" name="<?php echo $image_insert_name ?>" value="yes"<?php checked( $image_insert_value, 'yes' ); ?> />
                            <?php _e( 'Enable image upload in post area', 'wpuf' ); ?>
                        </label>
                    </div>
                </div> <!-- .wpuf-form-rows -->
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function post_excerpt( $field_id, $label, $values = array() ) {
        ?>
        <li class="post_excerpt">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'textarea' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'post_excerpt' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, 'post_excerpt', false, $values ); ?>
                <?php self::common_text( $field_id, $values ); ?>
                <?php self::common_textarea( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function post_tags( $field_id, $label, $values = array() ) {
        ?>
        <li class="post_tags">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'text' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'post_tags' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, 'tags', false, $values ); ?>
                <?php self::common_text( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function featured_image( $field_id, $label, $values = array() ) {
        $max_file_name = sprintf( '%s[%d][max_size]', self::$input_name, $field_id );
        $max_file_value = $values ? $values['max_size'] : '1024';
        $help = esc_attr( __( 'Enter maximum upload size limit in KB', 'wpuf' ) );
        ?>
        <li class="featured_image">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'image_upload' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'featured_image' ); ?>
            <?php self::hidden_field( "[$field_id][count]", '1' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, 'featured_image', false, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Max. file size', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $max_file_name; ?>" value="<?php echo $max_file_value; ?>" title="<?php echo $help; ?>">
                </div> <!-- .wpuf-form-rows -->
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function post_category( $field_id, $label, $values = array() ) {
        ?>
        <li class="post_category">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'post_category' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, 'category', false, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function taxonomy( $field_id, $label, $taxonomy = '', $values = array() ) {
        $type_name = sprintf( '%s[%d][type]', self::$input_name, $field_id );
        $order_name = sprintf( '%s[%d][order]', self::$input_name, $field_id );
        $orderby_name = sprintf( '%s[%d][orderby]', self::$input_name, $field_id );
        $exclude_name = sprintf( '%s[%d][exclude]', self::$input_name, $field_id );

        $type_value = $values ? esc_attr( $values['type'] ) : 'select';
        $order_value = $values ? esc_attr( $values['order'] ) : 'ASC';
        $orderby_value = $values ? esc_attr( $values['orderby'] ) : 'name';
        $exclude_value = $values ? esc_attr( $values['exclude'] ) : '';
        ?>
        <li class="taxonomy <?php echo $taxonomy; ?>">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'taxonomy' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'taxonomy' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, $taxonomy, false, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Type', 'wpuf' ); ?></label>
                    <select name="<?php echo $type_name ?>">
                        <option value="select"<?php selected( $type_value, 'select' ); ?>><?php _e( 'Dropdown', 'wpuf' ); ?></option>
                        <option value="multiselect"<?php selected( $type_value, 'multiselect' ); ?>><?php _e( 'Multi Select', 'wpuf' ); ?></option>
                        <option value="checkbox"<?php selected( $type_value, 'checkbox' ); ?>><?php _e( 'Checkbox', 'wpuf' ); ?></option>
                    </select>
                </div> <!-- .wpuf-form-rows -->
                
                <div class="wpuf-form-rows">
                    <label><?php _e( 'Order By', 'wpuf' ); ?></label>
                    <select name="<?php echo $orderby_name ?>">
                        <option value="name"<?php selected( $orderby_value, 'name' ); ?>><?php _e( 'Name', 'wpuf' ); ?></option>
                        <option value="id"<?php selected( $orderby_value, 'id' ); ?>><?php _e( 'Term ID', 'wpuf' ); ?></option>
                        <option value="slug"<?php selected( $orderby_value, 'slug' ); ?>><?php _e( 'Slug', 'wpuf' ); ?></option>
                        <option value="count"<?php selected( $orderby_value, 'count' ); ?>><?php _e( 'Count', 'wpuf' ); ?></option>
                        <option value="term_group"<?php selected( $orderby_value, 'term_group' ); ?>><?php _e( 'Term Group', 'wpuf' ); ?></option>
                    </select>
                </div> <!-- .wpuf-form-rows -->
                
                <div class="wpuf-form-rows">
                    <label><?php _e( 'Order', 'wpuf' ); ?></label>
                    <select name="<?php echo $order_name ?>">
                        <option value="ASC"<?php selected( $order_value, 'ASC' ); ?>><?php _e( 'ASC', 'wpuf' ); ?></option>
                        <option value="DESC"<?php selected( $order_value, 'DESC' ); ?>><?php _e( 'DESC', 'wpuf' ); ?></option>
                    </select>
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Exclude terms', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $exclude_name; ?>" title="<?php _e( 'Enter the term IDs as comma separated (without space) to exclude in the form.', 'wpuf' ); ?>" value="<?php echo $exclude_value; ?>" />
                </div> <!-- .wpuf-form-rows -->

            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

}