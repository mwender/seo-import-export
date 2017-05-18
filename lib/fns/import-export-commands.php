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