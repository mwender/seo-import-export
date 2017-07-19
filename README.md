# SEO Import/Export #
**Contributors:** thewebist  
**Tags:** seo, import, export  
**Requires at least:** 3.7  
**Tested up to:** 4.7.5  
**Stable tag:** 1.1.1  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Provides WP-CLI commands for importing and exporting page/post SEO meta data.

## Description ##

This plugin provides a `wp seo` command with two subcommands:

- `wp seo export` - exports your page and post SEO title and meta description data to a JSON file
- `wp seo filldesc` - updates the Yoast meta description custom field with the post's or page's default excerpt
- `wp seo import <filename>` - imports your SEO data

Currently this plugin works with [Yoast SEO](https://wordpress.org/plugins/wordpress-seo/).

## Installation ##

1. Upload this plugin to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

## Changelog ##

### 1.1.1 ###
* Don't run `update_post_meta()` on `wp seo import` for empty title/desc values.

### 1.1.0 ###
* Adding `wp seo filldesc`.

### 1.0.1 ###
* Adding check for file write permissions to `wp seo export`.

### 1.0.0 ###
* Initial release.