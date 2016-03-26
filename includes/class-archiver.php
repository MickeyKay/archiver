<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the dashboard.
 *
 * @link       http://wordpress.org/plugins/archiver
 * @since      1.0.0
 *
 * @package    Archiver
 * @subpackage Archiver/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Archiver
 * @subpackage Archiver/includes
 * @author     Mickey Kay mickey@mickeykaycreative.com
 */
class Archiver {

	/**
	 * The main plugin file.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_file    The main plugin file.
	 */
	protected $plugin_file;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $slug    The string used to uniquely identify this plugin.
	 */
	protected $slug;

	/**
	 * The display name of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $name    The plugin display name.
	 */
	protected $name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
     * Plugin options.
     *
     * @since  1.0.0
     *
     * @var    string
     */
    protected $options;

    /**
     * Wayback machine constants.
     *
     * @since  1.0.0
     *
     * @var    string
     */
    protected $wayback_machine_url_save = 'https://web.archive.org/save/';
    protected $wayback_machine_url_view = 'https://web.archive.org';

	/**
	 * The instance of this class.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Archiver    $instance    The instance of this class.
	 */
	private static $instance = null;

	/**
     * Creates or returns an instance of this class.
     *
     * @return    Archiver    A single instance of this class.
     */
    public static function get_instance( $args = array() ) {

        if ( null == self::$instance ) {
            self::$instance = new self( $args );
        }

        return self::$instance;

    }

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the Dashboard and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $args ) {

		$this->plugin_file = $args['plugin_file'];

		$this->slug = 'archiver';
		$this->name = __( 'Archiver', 'archiver' );
		$this->version = '1.0.0';
		$this->options = get_option( $this->slug );

		// Set up internationalization.
		add_action( 'plugins_loaded', array( $this, 'set_locale' ) );

		// Set up post save actions.
		add_action( 'save_post', array( $this, 'trigger_archive' ) );

		// Add metabox.
		add_action( 'add_meta_boxes', array( $this, 'add_post_metabox' ) );

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function set_locale() {

		load_plugin_textdomain(
			$this->slug,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}

	/**
	 * Trigger a post archive.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id ID of the post to archve.
	 */
	public function trigger_archive( $post_id ) {

		// Don't do anything if the post isn't published.
		if ( 'publish' != get_post_status( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$url = get_permalink( $post_id );
		$archive_link = $this->trigger_url_archive( $url );

		if ( $archive_link ) {
			$this->add_post_archive( $post_id, $archive_link );
		}

	}

	private function trigger_url_archive( $url ) {

		// Ping archive machine.
		$wayback_machine_save_url = $this->wayback_machine_url_save . 'http://mickeykay.me'; // $url goes here when ready
		$response = wp_remote_post( $wayback_machine_save_url );

		$archive_link = ( ! empty( $response['headers']['content-location'] ) ) ? $response['headers']['content-location'] : '';

		return $archive_link;

	}

	private function add_post_archive( $post_id, $archive_link ) {

		$current_date_time = current_time( 'mysql' );

		$new_archive = array(
			'url'       => $archive_link,
			'date_time' => $current_date_time,
		);

		$archives = maybe_unserialize( get_post_meta( $post_id, 'archiver_archive_links', true ) );

		$archives = $archives ? $archives : array();
		array_unshift( $archives, $new_archive );

		update_post_meta( $post_id, 'archiver_archive_links', $archives );
		error_log( print_r($archives, true) );

	}

	public function add_post_metabox() {
		add_meta_box(
			'archiver_post',
			__( 'Post Archives', 'archiver' ),
			array( $this, 'add_archive_metabox' ),
			array( 'post', 'page' ),
			'side',
			'default'
		);
	}

	public function add_archive_metabox() {

		global $post;

		$archives = maybe_unserialize( get_post_meta( $post->ID, 'archiver_archive_links', true ) );

		if ( $archives ) {

			echo '<ul>';

			foreach( $archives as $archive ) {

				$url = $this->wayback_machine_url_view . $archive['url'];
				$date_time = date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), strtotime( $archive['date_time'] ) );

				echo '<li><a href="' . $url . '" target="_blank">' . $date_time . '</a></li>';
			}

			echo '</ul>';

		}

	}

	function get_nice_date_time( ) {
		$time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
		if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
			$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
		}
		$time_string = sprintf( $time_string,
			esc_attr( get_the_date( 'c' ) ),
			esc_html( get_the_date() ),
			esc_attr( get_the_modified_date( 'c' ) ),
			esc_html( get_the_modified_date() )
		);
		$posted_on = sprintf(
			esc_html_x( 'Posted on %s', 'post date', '_s' ),
			'<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">' . $time_string . '</a>'
		);
		$byline = sprintf(
			esc_html_x( 'by %s', 'post author', '_s' ),
			'<span class="author vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a></span>'
		);
		echo '<span class="posted-on">' . $posted_on . '</span><span class="byline"> ' . $byline . '</span>'; // WPCS: XSS OK.
	}

}
