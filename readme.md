# Archiver #
**Contributors:** [McGuive7](https://profiles.wordpress.org/McGuive7)  
**Donate link:**       http://wordpress.org/plugins/archiver  
**Tags:**              archive, post, content, wayback, machine  
**Requires at least:** 3.5  
**Tested up to:**      4.1  
**Stable tag:**        1.0.4  
**License:**           GPLv2 or later  
**License URI:**       http://www.gnu.org/licenses/gpl-2.0.html  

Automatically create Wayback Machine snapshots of your site when you update your content.

## Description ##

**Like this plugin? Please consider [leaving a 5-star review](https://wordpress.org/support/view/plugin-reviews/archiver).**

Archiver integrates your website with the [Wayback Machine](https://archive.org/web/) to create easy-to-view snapshots of your site over time, giving you a fully navigable visual history of the changes you've made.

The plugin gives you some handy tools to easily trigger and view snapshots:

* Automatically creates a Wayback Machine snapshot when you update your content.
* Allows you to manually trigger a Wayback Machine snapshot of any page on your site using the admin.
* Allows you to easily view your site's Wayback Machine archives (all snapshots) for any page on your site.
* Adds an "Archives" metabox to the admin edit screen of specific content types (see below) that can be used to easily view existing snapshots.

Archiver makes it easy to do all of these things whether you're editing a post in the admin or viewing it on the front-end. Currently, Archiver's automated functionality works for the following content types:

* Posts
* Pages
* Users
* Custom Post Types
* Categories
* Tags
* Custom Taxonomies

This means that whenever you edit/save one of these content types, a snapshot of the corresponding front-end page will be auto-generated and archived via the Wayback Machine. As you update your content, the Wayback Machine will automatically keep a visual history of your changes. To view these archives, use the handy admin bar link, or navigate check the Archiver metabox when editing content.

If you have content that Archiver doesn't know how to automatically handle, you can use the admin bar links to automatically trigger a snapshot from any page on your site. Also, let us know and we'll do our best to add any needed automatic functionality.

Also available via Github: https://github.com/MickeyKay/archiver


## Installation ##

1. Install the plugin.
1. Use the "Archives" metabox to view snapshots of content you are editing.
1. Use the admin bar links to view and trigger snapshots.


## Screenshots ##

1. Admin bar links to view and manually trigger snapshots.
2. "Archives" metabox that shows all existing snapshots when editing a post/page/term/etc.

## Changelog ##

### 1.0.4 ###
* Fix error 500 error due to calling method on non-object.

### 1.0.3 ###
* Remove unreliable localhost detection.

### 1.0.2 ###
* Fix: fix issue in which directly referencing array index on function call caused issues in PHP < 5.4.

### 1.0.1 ###
* Add max archive display count.

### 1.0.0 ###
* First release.

## Upgrade Notice ##

### 1.0.4 ###
* Fix error 500 error due to calling method on non-object.

### 1.0.3 ###
* Remove unreliable localhost detection.

### 1.0.2 ###
* Fix: fix issue in which directly referencing array index on function call caused issues in PHP < 5.4.

### 1.0.1 ###
* Add max archive display count.

### 1.0.0 ###
* First release.