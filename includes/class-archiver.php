<?php
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
	 * Minification prefix.
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $min_suffix = '';

	/**
	 * The max number of snapshots to retrieve.
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $snapshot_max_count;

	/**
	 * Whether to enable Archiver for localhost.
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $enable_for_localhost;

	/**
	 * IP's to check against for localhost detection..
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $localhost_ips;

	/**
     * Wayback machine constants.
     *
     * @since  1.0.0
     *
     * @see    See https://github.com/internetarchive/wayback/tree/master/wayback-cdx-server
     *
     * @var    string
     */
	protected $wayback_machine_url_save;
	protected $wayback_machine_url_fetch_archives;
	protected $wayback_machine_url_view;

	/**
	 * Permalink for the current screen.
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $current_permalink = '';

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

		$this->slug = 'archiver';
		$this->name = __( 'Archiver', 'archiver' );

	}

	/**
	 * Set up base plugin functionality.
	 *
	 * @since 1.0.0
	 */
	public function run() {

		// Set up base plugin configuration - run late to ensure post types are already registered.
		add_action( 'init', array( $this, 'init' ), 999 );

	}

	/**
	 * Check whether or not Archiver can run.
	 *
	 * This function is used to determine whether or not Archiver can run, based
	 * on things like localhost vs production server, etc.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether or not Archiver's base functionality should run.
	 */
	public function can_run() {

		// Check if we're working local, and if that's allowed.
		if ( ! $this->enable_for_localhost && in_array( $_SERVER['REMOTE_ADDR'], $this->localhost_ips ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Initialize basic plugin.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Set up internationalization.
		$this->set_locale();

		// Set up Wayback Machine API endpoints.
		$this->wayback_machine_url_save           = 'https://web.archive.org/save/';
		$this->wayback_machine_url_fetch_archives = 'https://web.archive.org/cdx/';
		$this->wayback_machine_url_view           = 'https://web.archive.org/web/';

		/**
		 * Filter default snapshot max count.
		 *
		 * Default: 20
		 *
		 * @filter archiver_snapshot_max_count
		 */
		$this->snapshot_max_count = apply_filters( 'archiver_snapshot_max_count', 20 );

		/**
		 * Filter whether to enable on localhost.
		 *
		 * Default: FALSE
		 *
		 * @filter archiver_enable_for_local_host
		 */
		$this->enable_for_localhost = apply_filters( 'archiver_enable_for_local_host', __return_false() );

		/**
		 * Filter IP's to check against for determining localhost.
		 *
		 * @filter archiver_enable_for_local_host
		 */
		$localhost_ips = array(
			'127.0.0.1',
			'::1',
		);
		$this->localhost_ips = apply_filters( 'archiver_localhost_ips', $localhost_ips );

		// Set up minification prefix.
		$this->min_suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		// Set up manual archive trigger actions.
		add_action( 'wp_ajax_archiver_trigger_archive', array( $this, 'ajax_trigger_snapshot' ) );

		// Set up dismiss notice functionality.
		add_action( 'wp_ajax_archiver_dismiss_notice', array( $this, 'ajax_dismiss_notice' ) );

		// Register scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts_and_styles' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts_and_styles' ), 5 );

		// Enqueue scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// Set up functionality that should only run if can_run() == true.
		if ( $this->can_run() ) {

			// Set up automated archive trigger actions.
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

			// Add menu bar links.
			add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_links' ), 999 );

		} else {
			add_action( 'admin_notices', array( $this, 'do_admin_notice_localhost' ) );
		}

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
	 * Trigger a snapshot via Ajax.
	 *
	 * @since 1.0.0
	 */
	public function ajax_trigger_snapshot() {

		$nonce_check = check_ajax_referer( 'archiver_ajax_nonce', 'archiver_ajax_nonce', false );

		if ( ! $nonce_check ) {
			wp_send_json_error( __( 'The Ajax nonce check failed.', 'archiver' ) );
		}

		$url = $_REQUEST['url'];
		$response = $this->trigger_url_snapshot( $url );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_codes()[0] . ': ' . $response->get_error_messages()[0] );
		} else {
			wp_send_json_success();
		}

		// End Ajax nicely.
		wp_die();

	}

	/**
	 * Log dismissed admin notices per-user.
	 *
	 * @since 1.0.0
	 */
	public function ajax_dismiss_notice() {

		$nonce_check = check_ajax_referer( 'archiver_ajax_nonce', 'archiver_ajax_nonce', false );

		if ( ! $nonce_check ) {
			wp_send_json_error( __( 'The Ajax nonce check failed', 'archiver' ) );
		}

		$notice_key = 'archiver_dismiss_notice_' . $_REQUEST['notice_id'];
		$current_user_id = get_current_user_id();

		if ( ! $current_user_id ) {
			wp_send_json_error( __( 'There is no current user.', 'archiver' ) );
		} else {
			update_user_meta( $current_user_id, $notice_key, true );
			wp_send_json_success( __( 'User meta updated to dismiss notice.', 'archiver' ) );
		}

		// End Ajax nicely.
		wp_die();

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

		if ( is_wp_error( $response ) ) {
			return $response;
		} elseif ( ! empty( $response['headers']['x-archive-wayback-runtime-error'] ) ) {
			return new WP_Error( 'wayback_machine_error', $response['headers']['x-archive-wayback-runtime-error'], $response );
		} elseif ( ! empty( $response['headers']['content-location'] ) ) {
			return $response['headers']['content-location'];
		}

	}

	/**
	 * Add the archive metabox to posts (and all post types).
	 *
	 * @since 1.0.0
	 */
	public function add_post_meta_box() {

		/**
		 * Filter post types.
		 */
		$post_types = apply_filters( 'archiver_post_types', get_post_types() );

		add_meta_box(
			'archiver_post',
			__( 'Archives', 'archiver' ),
			array( $this, 'output_archiver_metabox' ),
			$post_types,
			'side',
			'default'
		);

	}

	/**
	 * Add the archive metabox to taxonomy terms.
	 *
	 * @since 1.0.0
	 */
	public function add_term_meta_box() {

		/**
		 * Filter taxonomies.
		 */
		$taxonomies = apply_filters( 'archiver_taxonomies', get_taxonomies() );

		$archiver_taxonomy_slugs = array_map(
			create_function( '$taxonomy', 'return "archiver-" . $taxonomy;'),
			$taxonomies
		);

		add_meta_box(
			'archiver_terms',
			__( 'Archives', 'archiver' ),
			array( $this, 'output_archiver_metabox' ),
			$archiver_taxonomy_slugs,
			'side',
			'default'
		);

		foreach ( $taxonomies as $taxonomy ) {
			add_action( "{$taxonomy}_edit_form", array( $this, 'output_term_meta_box' ) );
		}

	}

	/**
	 * Output the archive metabox on taxonomy term screens.
	 *
	 * @since 1.0.0
	 */
	public function output_term_meta_box() {

		$object_type = get_current_screen()->taxonomy;
		$this->output_manual_meta_box( $object_type );


	}

	/**
	 * Add the archive metabox to users.
	 *
	 * @since 1.0.0
	 */
	public function add_user_meta_box() {

		add_meta_box(
			'archiver_terms',
			__( 'Archives', 'archiver' ),
			array( $this, 'output_archiver_metabox' ),
			array( 'archiver-user' ),
			'side',
			'default'
		);

	}

	/**
	 * Output the archive metabox on user screens.
	 *
	 * @since 1.0.0
	 */
	public function output_user_meta_box() {
		$this->output_manual_meta_box( 'user' );
	}

	/**
	 * Output a manually created archive metabox (e.g. for terms and users).
	 *
	 * @since 1.0.0
	 *
	 * @param string $object_type Object type for which to output the metabox.
	 */
	public function output_manual_meta_box( $object_type ) {

		// Enqueue
		wp_enqueue_script( 'post' );

		echo '<div id="poststuff">';
		do_meta_boxes( 'archiver-' . $object_type, 'side', '' );
		echo '</div>';

	}

	/**
	 * Output the actual archive metabox.
	 *
	 * @since 1.0.0
	 */
	public function output_archiver_metabox() {

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
				$this->wayback_machine_url_view . '*/' . $this->get_current_permalink(),
				esc_html__( 'See all snapshots &rarr;', 'archiver' )
			);

		} else {
			esc_html_e( 'There are no archives of this URL.', 'archiver' );
		}

	}

	/**
	 * Add archive link(s) to the admin bar.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Original admin bar object.
	 */
	public function add_admin_bar_links( $wp_admin_bar ) {

		$url = $this->get_current_permalink();

		// Only proceed if we can generate a URL for this page.
		if ( ! $url ) {
			return;
		}

		$archive_link = $this->wayback_machine_url_view . '*/' . $url;

		$snapshots = $this->get_post_snapshots();
		$snapshot_count = count( $snapshots );
		if ( $snapshot_count >= $this->snapshot_max_count ) {
			$snapshot_count .= '+';
		}

		$wp_admin_bar->add_menu( array(
			'id'    => 'archiver',
			'title' => __( 'Archiver', 'achiver' ),
			'href'  => $archive_link,
			'meta'   => array(
				'target' => '_blank',
			)
		) );

		$wp_admin_bar->add_node( array(
			'parent' => 'archiver',
			'id'     => 'archiver-snapshots',
			'title'  => __( 'Snapshots', 'archiver' ) . " ({$snapshot_count})",
			'href'   => $archive_link,
			'meta'   => array(
				'target' => '_blank',
			)
		) );

		$wp_admin_bar->add_node( array(
			'parent' => 'archiver',
			'id'     => 'archiver-trigger',
			'title'  => sprintf( '%s <span class="ab-icon dashicons dashicons-update"></span>', __( 'Trigger Snapshot', 'achiver' ) ),
			'href'   => '#',
		) );

	}

	/**
	 * Get Wayback Machine snapshots for a url.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL for which to query snapshots.
	 *
	 * @return array An array of snapshots for the given URL.
	 */
	public function get_post_snapshots( $url = '' ) {

		$url = $url ? $url : $this->get_current_permalink();

		$fetch_url = add_query_arg( array(
			'url'    => $url,
			'output' => 'json',
			), $this->wayback_machine_url_fetch_archives );

		$response = wp_remote_get( $fetch_url );

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
			$data = array_slice( $data, 0, $this->snapshot_count );

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

	/**
	 * Get the permalink of the current screen/page.
	 *
	 * @since 1.0.0
	 *
	 * @return string The public URL of the current page, or an empty string if no public URL exists.
	 */
	public function get_current_permalink() {

		// Attempt to fetch the current permalink if it is not already set.
		if ( empty( $this->current_permalink ) ) {

			if ( is_admin() ) {
				$this->current_permalink = $this->get_current_permalink_admin();
			} else {
				$this->current_permalink = $this->get_current_permalink_public();
			}

		}

		/**
		 * Filter the returned permalink.
		 *
		 * @since 1.0.0
		 *
		 * @param string $permalink The permalink to be filtered.
		 */
		return apply_filters( 'archiver_permalink', $this->current_permalink );

	}

	/**
	 * Get the permalink for the current object from the admin.
	 *
	 * @since 1.0.0
	 *
	 * @return string $permalink The permalink of the current object.
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
		return apply_filters( 'archiver_permalink_admin', $permalink );

	}

	/**
	 * Get the permalink for the current object from the front-end.
	 *
	 * @since 1.0.0
	 *
	 * @return string $permalink The permalink of the current object.
	 */
	public function get_current_permalink_public() {

		global $wp;
  		$permalink = add_query_arg( $_SERVER['QUERY_STRING'], '', home_url( $wp->request ) );

		/**
		 * Filter the permalink generated for an public screen.
		 *
		 * @since 1.0.0
		 *
		 * @param string $permalink The permalink to be filtered.
		 */
		return apply_filters( 'archiver_permalink_public', $permalink );

	}

	/**
	 * Register scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public function register_scripts_and_styles() {

		wp_register_script(
			'archiver',
			ARCHIVER_PLUGIN_DIR_URL . 'js/archiver' . $this->min_suffix . '.js',
			array( 'jquery' ),
			true
		);

		wp_register_style(
			'archiver',
			ARCHIVER_PLUGIN_DIR_URL . 'css/archiver' . $this->min_suffix . '.css',
			array( 'dashicons' )
		);

		// Include JS vars.
		$archiver_vars = array(
			'ajax_url'            => admin_url( 'admin-ajax.php' ),
			'archiver_ajax_nonce' => wp_create_nonce( 'archiver_ajax_nonce' ),
			'url'                 => $this->get_current_permalink(),
		);
		wp_localize_script( 'archiver', 'archiver', $archiver_vars );

	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		$url = $this->get_current_permalink();

		// Only proceed if we can generate a URL for this page.
		if ( ! $url ) {
			return;
		}

		wp_enqueue_script( 'archiver' );
		wp_enqueue_style( 'archiver' );
		wp_enqueue_style( 'dashicons' );

	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 1.0.0
	 *
	 * @param strong $hook Current screen hook.
	 */
	public function admin_enqueue_scripts( $hook ) {
		wp_enqueue_script( 'archiver' );
		wp_enqueue_style( 'archiver' );
	}

	/**
	 * Output admin notice to indicate localhost.
	 *
	 * @since 1.0.0
	 */
	public function do_admin_notice_localhost() {

		$id = 'archiver-notice-localhost';

		$dismiss_notice_key = 'archiver_dismiss_notice_' . $id;
		if ( get_user_meta( get_current_user_id(), $dismiss_notice_key ) ) {
			return;
		}

		$class = 'archiver-notice notice notice-error is-dismissible';
		$message = __( "Archiver is disabled while you are working locally.", 'archiver' );

		printf( '<div id="%s" class="%s"><p>%s</p></div>', $id, $class, $message );

	}

}
