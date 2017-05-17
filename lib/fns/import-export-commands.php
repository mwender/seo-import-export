<?php
namespace SEOImportExport\wpcli;

/**
 * Export page\post SEO meta data.
 */
function export_seo()
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
    if( ! $posts ){
        \WP_CLI::error( 'No pages/posts with SEO meta data found!' );
        exit();
    }

    $seo_data = [];

    foreach ($posts as $post) {
        $title = get_post_meta( $post->ID, '_yoast_wpseo_title', true );
        $desc = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
        $post_array = [
            'ID' => $post->ID,
            'post_title' => $post->post_title,
            'seo_title' => $title,
            'seo_desc' => $desc,
        ];
        $seo_data[] = $post_array;
    }
    $seo_json_export = json_encode( $seo_data );

    $upload_dir = wp_upload_dir();
    if( ! $upload_dir['basedir'] ){
        \WP_CLI::error( 'No uploads basedir found!' );
        exit();
    }

    // Write the JSON to a file
    $fp = fopen( $upload_dir['basedir'] . '/seo.json', 'w' );
    fwrite( $fp, $seo_json_export );
    fclose();
    \WP_CLI::success( 'SEO data written to ' . $upload_dir['basedir'] . '/seo.json' );
};
\WP_CLI::add_command( 'seo export', __NAMESPACE__ . '\\export_seo' );