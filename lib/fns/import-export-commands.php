<?php
namespace SEOImportExport\wpcli;

/**
 * Export page\post SEO meta data.
 */
class SEO_Command extends \WP_CLI_Command
{
    /**
     * Exports SEO meta data from posts and pages.
     *
     * ## OPTIONS
     *
     * [--filename=<file>]
     * :Specify the name of the export file saved in the uploads root directory.
     *
     * ## EXAMPLES
     *
     * wp seo export
     * wp seo export --filename=my_seo_export.json
     *
     * @subcommand export
     */
    function export( $args, $assoc_args )
    {
        $args = [
            'posts_per_page' => -1,
            'post_type' => ['post','page'],
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_yoast_wpseo_title',
                    'value' => '',
                    'compare' => '!='
                ],
                [
                    'key' => '_yoast_wpseo_metadesc',
                    'value' => '',
                    'compare' => '!='
                ],
            ],
        ];
        $posts = get_posts( $args );
        if( ! $posts )
            \WP_CLI::error( 'No pages/posts with SEO meta data found!' );

        $seo_data = [];

        foreach ($posts as $post) {
            $title = get_post_meta( $post->ID, '_yoast_wpseo_title', true );
            $desc = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
            $post_array = [
                'ID' => $post->ID,
                'post_title' => $post->post_title,
                'seo_title' => $title,
                'seo_desc' => $desc,
                'post_type' => $post->post_type,
            ];
            $seo_data[] = $post_array;
        }
        $seo_json_export = json_encode( $seo_data );

        $upload_dir = wp_upload_dir();
        if( ! $upload_dir['basedir'] )
            \WP_CLI::error( 'No uploads basedir found! Unable to write our export file.' );

        // Build the filename
        $filename = 'seo_' . current_time( 'Y-m-d_Hi' );
        if( isset( $assoc_args['filename'] ) && ! empty( $assoc_args['filename'] ) )
            $filename = $assoc_args['filename'];
        if( '.json' != substr( $filename, - 5 ) )
            $filename.= '.json';

        // Write the JSON to a file
        $fp = fopen( trailingslashit( $upload_dir['basedir'] ) . $filename, 'w' );
        if( ! $fp )
            \WP_CLI::error( 'Unable to write to ' . trailingslashit( $upload_dir['basedir'] ) . $filename . '. Do you have write permissions?' );
        fwrite( $fp, $seo_json_export );
        fclose( $fp );
        \WP_CLI::success( 'SEO data written to ' . trailingslashit( $upload_dir['basedir'] ) . $filename );
    }

    /**
     * Inserts the default description into the Yoast SEO meta description field.
     *
     * ## EXAMPLES
     *
     * wp seo filldesc
     *
     * @subcommand filldesc
     */
    function filldesc( $args, $assoc_args )
    {
        $totals = array();
        $post_types = ['post','page'];
        foreach ( $post_types as $type ) {
            $args = [
                'post_type' => $type,
                'numberposts' => -1
            ];
            \WP_CLI::log( str_repeat( '-', 80 ) . "\n" . 'Analyzing ' . ucfirst( $type ) . 's' . "\n" . str_repeat( '-', 80 ) );
            $posts = \get_posts( $args );
            if( $posts ){
                $count = 0;
                foreach ( $posts as $post ) {
                    $meta_desc = \get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
                    if( empty( $meta_desc ) ){
                        \WP_CLI::log( ucfirst( $type ) . ' (#' . $post->ID . '): `' . get_the_title( $post->ID ) . '`');
                        // Get the post's excerpt
                        if ( $post->post_excerpt !== '' ) {
                            $excerpt = strip_tags( $post->post_excerpt );
                        }
                        elseif ( $post->post_content !== '' ) {
                            $excerpt = strip_shortcodes( $post->post_content );
                        }
                        // Remove Visual Composer Shortcodes
                        $patterns = "/\[[\/]?vc_[^\]]*\]/";
                        $replacements = "";
                        $excerpt = preg_replace( $patterns, $replacements, $excerpt );
                        //$excerpt = SEO_Command::remove_vc_from_excerpt( $excerpt );

                        $excerpt = strip_tags( $excerpt );
                        $excerpt = trim( $excerpt );
                        $excerpt = str_replace( "\n", ' ', $excerpt );

                        // Remove tabs and newlines plus all other non-printable chars
                        $excerpt = preg_replace('/[\x00-\x1F\x7F]/u', '', $excerpt);

                        $excerpt = substr( $excerpt, 0, 160 );

                        if( ! empty( $excerpt ) ){
                            \WP_CLI::log( 'Generated excerpt: ' . $excerpt );
                            $success = update_post_meta( $post->ID, '_yoast_wpseo_metadesc', $excerpt );
                            if( true == $success )
                                \WP_CLI::success('Updated ' . ucfirst( $type ) . ' #' . $post->ID );
                        }
                        $count++;
                    }
                }
                $totals[$type] = $count;
            }
        }
        if( 0 < count( $totals ) ){
            foreach( $totals as $type => $count ){
                \WP_CLI::success( $count . ' ' . $type . 's updated with default descriptions.');
            }
        } else {
            \WP_CLI::log( 'No posts/pages found with empty Yoast Meta Description fields.' );
        }
    }

    /**
     * Imports SEO meta data to posts and pages.
     *
     * ## OPTIONS
     *
     * <filename>
     * : The file containing the JSON formatted SEO data to import.
     *
     * ## EXAMPLES
     *
     * wp seo import wp-content/uploads/seo.json
     *
     * @subcommand import
     */
    function import( $args, $assoc_args )
    {
        list( $filename ) = $args;

        if( empty( $filename ) )
            \WP_CLI::error( 'You must specify a JSON file for import (e.g. \'wp seo import wp-content/uploads/seo.json\'' );

        if( ! file_exists( $filename ) )
            \WP_CLI::error( 'File \'' . $filename . '\' not found.' );

        $json_file = file_get_contents( $filename );
        $seo_data = json_decode( $json_file );
        if( ! is_array( $seo_data ) || 0 == count( $seo_data) )
            \WP_CLI::error( 'Unable to load SEO data from ' . basename( $filename ) . '.' );

        $count = 0;
        foreach( $seo_data as $post_data ){
            $post = get_page_by_title( $post_data->post_title, OBJECT, $post_data->post_type );
            if( ! $post ){
                \WP_CLI::error( 'Unable to locate ' . $post_data->post_type . ' with title \'' . $post_data->post_title . '\'', false );
                continue;
            }

            update_post_meta( $post->ID, '_yoast_wpseo_title', $post_data->seo_title );
            update_post_meta( $post->ID, '_yoast_wpseo_metadesc', $post_data->seo_desc );
            \WP_CLI::success( 'Updated \'' . $post_data->post_title . '\', ID: ' . $post->ID . ' (Original ID: ' . $post_data->ID . ').' );
            $count++;
        }

        \WP_CLI::success( 'Finished import. ' . $count . ' posts/pages updated.' );
    }
}
\WP_CLI::add_command( 'seo', __NAMESPACE__ . '\\SEO_Command' );