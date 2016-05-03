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
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $slug;

	/**
	 * The display name of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $name;

	/**
	 * Post types to archive.
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $post_types;

	/**
	 * Taxonomies to archive.
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $taxonomies;

    /**
     * Wayback machine constants.
     *
     * @since  1.0.0
     *
     * @see    See https://github.com/internetarchive/wayback/tree/master/wayback-cdx-server
     *
     * @var    string
     */
	protected $wayback_machine_url_save           = 'https://web.archive.org/save/';
	protected $wayback_machine_url_fetch_archives = 'https://web.archive.org/cdx/';
	protected $wayback_machine_url_view           = 'https://web.archive.org/web/';

	/**
	 * The instance of this class.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Archiver    $instance    The instance of this class.
	 */
	protected static $instance = null;

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

		// Set up base plugin configuration.
		add_action( 'init', array( $this, 'init' ) );

		// Set up archive trigger actions.
		add_action( 'save_post',      array( $this, 'trigger_post_snapshot' ) );
		add_action( 'created_term',   array( $this, 'trigger_term_snapshot' ), 10, 3 );
		add_action( 'edited_term',    array( $this, 'trigger_term_snapshot' ), 10, 3 );
		add_action( 'profile_update', array( $this, 'trigger_user_snapshot' ), 10, 3 );

		// Add Post Type metaboxes.
		add_action( 'add_meta_boxes', array( $this, 'add_post_meta_box' ) );

		// Add Term metaboxes.
		add_action( 'admin_init', array( $this, 'add_term_meta_box' ) );

		// Add User metabox.
		add_action( 'admin_init', array( $this, 'add_user_meta_box' ) );
		add_action( 'show_user_profile', array( $this, 'output_user_meta_box' ) );
		add_action( 'edit_user_profile', array( $this, 'output_user_meta_box' ) );

	}

	public function init() {

		// Set up internationalization.
		$this->set_locale();

		// Set up and filter content types to archive.
		$this->post_types = apply_filters( 'archive_post_types', get_post_types() );
		$this->taxonomies = apply_filters( 'archive_taxonomies', get_taxonomies() );

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since    1.0.0
	 */
	protected function set_locale() {

		load_plugin_textdomain(
			$this->slug,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}

	/**
	 * Trigger a post snapshot.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id ID of the post to archive.
	 */
	public function trigger_post_snapshot( $post_id ) {

		// Don't do anything if the post isn't published.
		if ( 'publish' != get_post_status( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$url = get_permalink( $post_id );
		$this->trigger_url_snapshot( $url );

	}

	/**
	 * Trigger a taxonomy term snapshot.
	 *
	 * @since 1.0.0
	 *
	 * @param int $term_id  ID of the taxonomy term to archive.
	 * @param int $taxonomy Taxonomy to which the current term belongs.
	 */
	public function trigger_term_snapshot( $term_id, $taxonomy_id, $taxonomy ) {

		$url = get_term_link( $term_id, $taxonomy );
		$this->trigger_url_snapshot( $url );

	}

	/**
	 * Trigger a user snapshot.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id  ID of the user to archive.
	 */
	public function trigger_user_snapshot( $user_id ) {

		$url = get_author_posts_url( $user_id );
		$this->trigger_url_snapshot( $url );

	}

	/**
	 * Trigger a URL to be archived on the Wayback Machine.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The URL to archive.
	 *
	 * @return string The link to the newly created archive, if it exists.
	 */
	protected function trigger_url_snapshot( $url ) {

		// Ping archive machine.
		$wayback_machine_save_url = $this->wayback_machine_url_save . $url;
		$response = wp_remote_get( $wayback_machine_save_url );

		$archive_link = '';

		if ( ! empty( $response['headers']['content-location'] ) ) {
			$archive_link = $response['headers']['content-location'];
		}

		return $archive_link;

	}

	/**
	 * Add the archive metabox to posts (and all post types).
	 *
	 * @since 1.0.0
	 */
	public function add_post_meta_box() {

		add_meta_box(
			'archiver_post',
			__( 'Archives', 'archiver' ),
			array( $this, 'add_archiver_metabox' ),
			$this->post_types,
			'side',
			'default'
		);

	}

	public function add_term_meta_box() {

		$archiver_taxonomy_slugs = array_map(
			create_function( '$taxonomy', 'return "archiver-" . $taxonomy;'),
			$this->taxonomies
		);

		add_meta_box(
			'archiver_terms',
			__( 'Archives', 'archiver' ),
			array( $this, 'add_archiver_metabox' ),
			$archiver_taxonomy_slugs,
			'side',
			'default'
		);

		foreach ( $this->taxonomies as $taxonomy ) {
			add_action( "{$taxonomy}_edit_form", array( $this, 'output_term_meta_box' ) );
		}

	}

	public function output_term_meta_box() {

		$object_type = get_current_screen()->taxonomy;
		$this->output_manual_meta_box( $object_type );


	}

	public function add_user_meta_box() {

		add_meta_box(
			'archiver_terms',
			__( 'Archives', 'archiver' ),
			array( $this, 'add_archiver_metabox' ),
			array( 'archiver-user' ),
			'side',
			'default'
		);

	}

	public function output_user_meta_box() {
		$this->output_manual_meta_box( 'user' );
	}

	public function output_manual_meta_box( $object_type ) {

		// Enqueue
		wp_enqueue_script( 'post' );

		echo '<div id="poststuff">';
		do_meta_boxes( 'archiver-' . $object_type, 'side', '' );
		echo '</div>';

	}

	public function add_archiver_metabox() {

		$snapshots = $this->get_post_snapshots();

		// If the snapshots fetch failed, just output the error.
		if ( is_wp_error( $snapshots ) ) {
			esc_html_e( $snapshots->get_error_message() );
			return;
		}

		if ( ! empty( $snapshots ) ) {

			$date_format = get_option( 'date_format' );
			$time_format = get_option( 'time_format' );
			$gmt_offset = get_option( 'gmt_offset' );

			echo '<ul>';

			foreach( $snapshots as $snapshot ) {

				// Convert to Y-m-d H:i:s format for get_date_from_gmt().
				$date_time = date( 'Y-m-d H:i:s', strtotime( $snapshot['timestamp'] ) );
				$adjusted_date = get_date_from_gmt( $date_time );

				$url = $this->wayback_machine_url_view . $snapshot['timestamp'] . '/' . $snapshot['original'];
				$date_time = date_i18n( $date_format . ' @ ' . $time_format, strtotime( $adjusted_date ) );

				echo '<li><a href="' . $url . '" target="_blank">' . $date_time . '</a></li>';

			}

			echo '</ul>';

			echo '<hr />';

			printf( '<a href="%s" target="_external">%s</a>',
				$this->wayback_machine_url_view . '*/' . $this->get_current_permalink_admin(),
				esc_html__( 'See all snapshots &rarr;', 'archiver' )
			);

		} else {
			esc_html_e( 'There are no archives of this URL.', 'archiver' );
		}

	}

	public function get_post_snapshots() {

		$permalink = $this->get_current_permalink();

		$url = add_query_arg( array(
			'url'    => $permalink,
			'output' => 'json',
			), $this->wayback_machine_url_fetch_archives );

		$response = wp_remote_get( $url );

		if ( 200 == wp_remote_retrieve_response_code( $response ) ) {

			$data = json_decode( $response['body'] );

			// Return empty array if no data exists for this url.
			if ( empty( $data ) ) {
				return array();
			}

			// Grab the first item, which is the map of field columns.
			$field_columns = $data[0];
			unset( $data[0] );

			// Reverse data since it comes in chronologically.
			$data = array_reverse( $data );

			// Set limit on how many snapshots to output.
			$data = array_slice( $data, 0, apply_filters( 'archiver_snapshot_count', 20 ) );

			// Set up snapshots.
			$snapshots = array();

			foreach( $data as $snapshot ) {

				$keyed_snapshot = array();

				foreach ( $snapshot as $i => $field ) {
					$keyed_snapshot[ $field_columns[ $i ] ] = $field;
				}

				$snapshots[] = $keyed_snapshot;

			}

			return $snapshots;

		} else {
			return new WP_Error( 'wp_remote_get_error', __( 'Attempt to fetch post snapshots failed.', 'archiver' ), $response );
		}

	}

	public function get_current_permalink() {

		$permalink = '';

		if ( is_admin() ) {
			$permalink = $this->get_current_permalink_admin();
		} else {
			$permalink = $this->get_current_permalink_public();
		}

		/**
		 * Filter the returned permalink.
		 *
		 * @since 1.0.0
		 *
		 * @param string $permalink The permalink to be filtered.
		 */
		return apply_filters( 'archiver_permalink', $permalink );

	}

	/**
	 * Get the permalink for the current object admin screen.
	 *
	 * @since 1.0.0
	 *
	 * @return string $permalink The permalink of the current admin object.
	 */
	public function get_current_permalink_admin() {

		$permalink = '';

		$current_screen = get_current_screen();
		$object_type = $current_screen->base;

		// Generate permalink based on current object type.
		switch( $object_type ) {

			// Post.
			case 'post':
				global $post;
				$permalink = get_permalink( $post->ID );
				break;

			// Taxonomy term.
			case 'term':
				global $taxnow, $tag;
				$taxonomy = $taxnow;
				$term_id = intval( $tag->term_id );
				$permalink = get_term_link( $term_id, $taxonomy );
				break;

			// User.
			case 'profile':
			case 'user-edit':

				$user_id = 0;

				/**
				 * Depending on whether the user we're editing is the
                 * currently logged in user, or another user, we see
                 * either the "profile" screen or the "user-edit"
                 * screen, each of which must be handled differently.
                 */
				if ( ! empty( $_GET['user_id'] ) ) {
					$user_id = intval( $_GET['user_id'] );
				} else {
					$user_id = get_current_user_id();
				}

				$permalink = get_author_posts_url( $user_id );
				break;
		}

		/**
		 * Filter the permalink generated for an admin screen.
		 *
		 * @since 1.0.0
		 *
		 * @param string $permalink The permalink to be filtered.
		 */
		return apply_filters( 'archive_permalink_admin', $permalink );

	}

}
