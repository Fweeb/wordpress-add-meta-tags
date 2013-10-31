<?php
/**
 * Dublin Core metadata on posts, pages and attachments.
 *
 *  * http://dublincore.org/documents/dcmi-terms/
 *  * http://dublincore.org/documents/dces/
 *  * Examples: http://www.metatags.org/dublin_core_metadata_element_set
 *
 *  * Generic Examples: http://dublincore.org/documents/2001/04/12/usageguide/generic.shtml
 *  * XML examples: http://dublincore.org/documents/dc-xml-guidelines/
 *
 * Module containing functions related to Dublin Core
 */


function amt_add_dublin_core_metadata_head( $post, $attachments, $embedded_media ) {

    if ( !is_singular() || is_front_page() ) {  // is_front_page() is used for the case in which a static page is used as the front page.
        // Dublin Core metadata has a meaning for content only.
        return array();
    }

    // Get the options the DB
    $options = get_option("add_meta_tags_opts");
    $do_auto_dublincore = (($options["auto_dublincore"] == "1") ? true : false );
    if (!$do_auto_dublincore) {
        return array();
    }

    $metadata_arr = array();

    // Title
    // Note: Contains multipage information through amt_process_paged()
    $metadata_arr[] = '<meta name="dcterms.title" content="' . esc_attr( amt_process_paged( get_the_title($post->ID) ) ) . '" />';

    // Resource identifier
    // TODO: In case of paginated content, get_permalink() still returns the link to the main mage. FIX (#1025)
    $metadata_arr[] = '<meta name="dcterms.identifier" scheme="dcterms.URI" content="' . esc_url_raw( get_permalink($post->ID) ) . '" />';

    $metadata_arr[] = '<meta name="dcterms.creator" content="' . esc_attr( amt_get_dublin_core_author_notation($post) ) . '" />';
    //$metadata_arr[] = '<meta name="dcterms.date" scheme="dcterms.W3CDTF" content="' . esc_attr( amt_iso8601_date($post->post_date) ) . '" />';
    $metadata_arr[] = '<meta name="dcterms.created" scheme="dcterms.W3CDTF" content="' . esc_attr( amt_iso8601_date($post->post_date) ) . '" />';
    $metadata_arr[] = '<meta name="dcterms.available" scheme="dcterms.W3CDTF" content="' . esc_attr( amt_iso8601_date($post->post_date) ) . '" />';
    //$metadata_arr[] = '<meta name="dcterms.issued" scheme="dcterms.W3CDTF" content="' . esc_attr( amt_iso8601_date($post->post_date) ) . '" />';
    $metadata_arr[] = '<meta name="dcterms.modified" scheme="dcterms.W3CDTF" content="' . esc_attr( amt_iso8601_date($post->post_modified) ) . '" />';
 
    // Description
    // We use the same description as the ``description`` meta tag.
    // Note: Contains multipage information through amt_process_paged()
    $content_desc = amt_get_content_description($post);
    if ( !empty($content_desc) ) {
        $metadata_arr[] = '<meta name="dcterms.description" content="' . esc_attr( amt_process_paged( $content_desc ) ) . '" />';
    }

    // Keywords
    if ( ! is_attachment() ) {  // Attachments do not support keywords
        // dcterms.subject - one for each keyword.
        $keywords = explode(',', amt_get_content_keywords($post));
        foreach ( $keywords as $subject ) {
            $subject = trim( $subject );
            if ( ! empty($subject) ) {
                $metadata_arr[] = '<meta name="dcterms.subject" content="' . esc_attr( $subject ) . '" />';
            }
        }
    }

    $metadata_arr[] = '<meta name="dcterms.language" scheme="dcterms.RFC4646" content="' . esc_attr( get_bloginfo('language') ) . '" />';
    $metadata_arr[] = '<meta name="dcterms.publisher" scheme="dcterms.URI" content="' . esc_url_raw( get_bloginfo('url') ) . '" />';

    // Copyright page
    if (!empty($options["copyright_url"])) {
        $metadata_arr[] = '<meta name="dcterms.rights" scheme="dcterms.URI" content="' . esc_url_raw( get_bloginfo('url') ) . '" />';
    }
    // The following requires creative commons configurator
    if (function_exists('bccl_get_license_url')) {
        $metadata_arr[] = '<meta name="dcterms.license" scheme="dcterms.URI" content="' . esc_url_raw( bccl_get_license_url() ) . '" />';
    }

    $metadata_arr[] = '<meta name="dcterms.coverage" content="World" />';

    if ( is_attachment() ) {

        $mime_type = get_post_mime_type( $post->ID );
        //$attachment_type = strstr( $mime_type, '/', true );
        // See why we do not use strstr(): http://www.codetrax.org/issues/1091
        $attachment_type = preg_replace( '#\/[^\/]*$#', '', $mime_type );

        $metadata_arr[] = '<meta name="dcterms.isPartOf" scheme="dcterms.URI" content="' . esc_url_raw( get_permalink( $post->post_parent ) ) . '" />';

        if ( 'image' == $attachment_type ) {
            $metadata_arr[] = '<meta name="dcterms.type" scheme="dcterms.DCMIType" content="Image" />';
            $metadata_arr[] = '<meta name="dcterms.format" scheme="dcterms.IMT" content="' . $mime_type . '" />';
        } elseif ( 'video' == $attachment_type ) {
            $metadata_arr[] = '<meta name="dcterms.type" scheme="dcterms.DCMIType" content="MovingImage" />';
            $metadata_arr[] = '<meta name="dcterms.format" scheme="dcterms.IMT" content="' . $mime_type . '" />';
        } elseif ( 'audio' == $attachment_type ) {
            $metadata_arr[] = '<meta name="dcterms.type" scheme="dcterms.DCMIType" content="Sound" />';
            $metadata_arr[] = '<meta name="dcterms.format" scheme="dcterms.IMT" content="' . $mime_type . '" />';
        }

    } else {    // Default: Text
        $metadata_arr[] = '<meta name="dcterms.type" scheme="dcterms.DCMIType" content="Text" />';
        $metadata_arr[] = '<meta name="dcterms.format" scheme="dcterms.IMT" content="text/html" />';

        // List attachments
        foreach( $attachments as $attachment ) {
            $metadata_arr[] = '<meta name="dcterms.hasPart" scheme="dcterms.URI" content="' . esc_url_raw( get_permalink( $attachment->ID ) ) . '" />';
        }

        // Embedded Media
        foreach( $embedded_media['images'] as $embedded_item ) {
            $metadata_arr[] = '<meta name="dcterms.hasPart" scheme="dcterms.URI" content="' . esc_url_raw( $embedded_item['page'] ) . '" />';
        }
        foreach( $embedded_media['videos'] as $embedded_item ) {
            $metadata_arr[] = '<meta name="dcterms.hasPart" scheme="dcterms.URI" content="' . esc_url_raw( $embedded_item['page'] ) . '" />';
        }
        foreach( $embedded_media['sounds'] as $embedded_item ) {
            $metadata_arr[] = '<meta name="dcterms.hasPart" scheme="dcterms.URI" content="' . esc_url_raw( $embedded_item['page'] ) . '" />';
        }
    }


    /**
     * WordPress Post Formats: http://codex.wordpress.org/Post_Formats
     * Dublin Core Format: http://dublincore.org/documents/dcmi-terms/#terms-format
     * Dublin Core DCMIType: http://dublincore.org/documents/dcmi-type-vocabulary/
     */
    /**
     * TREAT ALL POST FORMATS AS TEXT (for now)
     */
    /**
    $format = get_post_format( $post->id );
    if ( empty($format) || $format=="aside" || $format=="link" || $format=="quote" || $format=="status" || $format=="chat") {
        // Default format
        $metadata_arr[] = '<meta name="dcterms.type" scheme="DCMIType" content="Text" />';
        $metadata_arr[] = '<meta name="dcterms.format" scheme="dcterms.imt" content="text/html" />';
    } elseif ($format=="gallery") {
        $metadata_arr[] = '<meta name="dcterms.type" scheme="DCMIType" content="Collection" />';
        // $metadata_arr[] = '<meta name="dcterms.format" scheme="dcterms.imt" content="image" />';
    } elseif ($format=="image") {
        $metadata_arr[] = '<meta name="dcterms.type" scheme="DCMIType" content="Image" />';
        // $metadata_arr[] = '<meta name="dcterms.format" scheme="dcterms.imt" content="image/png" />';
    } elseif ($format=="video") {
        $metadata_arr[] = '<meta name="dcterms.type" scheme="DCMIType" content="Moving Image" />';
        $metadata_arr[] = '<meta name="dcterms.format" scheme="dcterms.imt" content="application/x-shockwave-flash" />';
    } elseif ($format=="audio") {
        $metadata_arr[] = '<meta name="dcterms.type" scheme="DCMIType" content="Sound" />';
        $metadata_arr[] = '<meta name="dcterms.format" scheme="dcterms.imt" content="audio/mpeg" />';
    }
    */

    // Filtering of the generated Dublin Core metadata
    $metadata_arr = apply_filters( 'amt_dublin_core_metadata_head', $metadata_arr );

    return $metadata_arr;
}


