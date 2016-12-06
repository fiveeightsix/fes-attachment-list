<?php
/**
 * Plugin Name: Attachment list
 * Plugin URI: https://github.com/fiveeightsix/fes-attachment-list
 * Description: Provides shortcode for displaying links to and descriptions of attachment posts.
 * Author: Karl Inglis
 * Author URI: http://web.karlinglis.net
 * Version: 1.0.0
*/

// If this file is called directly, die.
if ( ! defined( 'WPINC' ) ) {
  die( 'Nope.' );
}

/**
 * Get human-readable file size.
 * 
 * @param string $file_path Path to file.
 * @return string Formatted file size.
 */
function fes_file_size( $file_path ) {
  return size_format( filesize( $file_path ) );
}

/**
 * Get the file type extension.
 *
 * @param string $url URL to file.
 * @return sting File extension
 */
function fes_get_file_type( $file_url ) {
  $file_type = wp_check_filetype( $file_url );
  return $file_type['ext'];
}

/**
 * Builds the attachment list shortcode output.
 *
 * Attachment title description are filtered through 'the_title' and 'the_content' respectively.
 *
 * @param array $attr {
 *     Attributes of the attachment list shortcode.
 *
 *     @type string       $order           Order of the images in the gallery. Default 'ASC'. Accepts 'ASC', 'DESC'.
 *     @type string       $orderby         The field to use when ordering the images. Default 'title ID'.
 *                                         Accepts any valid SQL ORDERBY statement.
 *     @type string       $ids             A comma-separated list of IDs of attachments to display. Default empty.
 *     @type string       $id              ID of the parent page to display the attachments of. Default current page.
 *                                         Not used if $ids argument is present.
 *     @type string       $idattribute     ID attribute for the attachment list. Default empty.
 *     @type string       $caption         Include the caption (summary) text if this is 'true'. Default 'false'.
 * }
 * @return string HTML content to display attachment list.
 */
function fes_attachment_list_shortcode_handler( $atts, $content = null ) {
  $post = get_post();

  // Merge shorcut attributes with default values.
  $atts = shortcode_atts( array(
    'order'       => 'ASC',
    'orderby'     => 'title ID',
    'id'          => $post ? $post->ID : 0,
    'ids'         => '',
    'idattribute' => '',
    'caption'     => false
  ), $atts );

  $id = intval( $atts['id'] );

  // Default arguments for the attachment post query.
  $args = array(
    'order'       => $atts['order'],
    'orderby'     => $atts['orderby'],
    'post_status' => 'inherit',
    'post_type'   => 'attachment'
  );

  // Use the list of IDs if provided, otherwise use all children of the current post.
  if ( ! empty( $atts['ids'] ) ) {
    $attachments = get_posts( array_merge( $args, array(
      'include' => $atts['ids'],
    ) ) );    
  } else {
    $attachments = get_posts( array_merge ( $args, array(
      'post_parent' => $id,
    ) ) );
  }

  // Exit early if there is nothing to display.
  if ( empty( $attachments ) ) {
    return '';
  }

  $showCaption = ( $atts['caption'] ) ? true : false;
  
  $idAtt = $atts['idattribute'];

  if ( ! empty( $idAtt ) ) {
    $idAtt = " id='{$idAtt}'";
  }

  // Generate the output.
  $output = "<div{$idAtt} class='attachment-list'>";

  foreach ( $attachments as $attachment ) {

    $title = get_the_title( $attachment );
    $excerpt = get_the_excerpt( $attachment );
    $url = wp_get_attachment_url( $attachment->ID );
    $size = fes_file_size( get_attached_file ( $attachment->ID ) );
    $type = fes_get_file_type( $url );

    $link_text = "<span class='file-title'>{$title}</span> (<span class='file-type'>{$type}</span>, <span class='file-size'>{$size}</span>)";
    
    $link = wp_get_attachment_link(
      $attachment->ID, /* ID */
      '',              /* No image size */
      false,           /* false = link to file */
      false,           /* false = dont' use an icon */
      $link_text       /* Use a text link */
    );
    
    $output .= "<section class='attachment-entry'>\n";
    $output .= "<header class='attachment-header'>\n";
    $output .= "<h2 class='attachment-title'>" . $title . "</h2>\n";
    $output .= "</header>\n";

    if ( $excerpt && $showCaption ) {
      $output .= "<div class='attachment-summary'>\n{$excerpt}\n</div>\n";
    }
    
    $output .= "<div class='attachment-content'>\n";
    $output .= "<p class='attachment'>" . $link . "</p>\n";
    $output .= apply_filters( "the_content", $attachment->post_content );
    $output .= "</div>\n";
    $output .= "</section>\n";
  }
  
  $output .= '</div>';
  
  return $output;
}
add_shortcode( 'attachment-list', 'fes_attachment_list_shortcode_handler' );
?>
