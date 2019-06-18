<?php
/**
 * Plugin Name: BuddyBoss Platform
 * Plugin URI:  https://buddyboss.com/
 * Description: The BuddyBoss Platform adds community features to WordPress. Member Profiles, Activity Feeds, Direct Messaging, Notifications, and more!
 * Author:      BuddyBoss
 * Author URI:  https://buddyboss.com/
 * Version:     1.0.4
 * Text Domain: buddyboss
 * Domain Path: /languages/
 * License:     GPLv2 or later (license.txt)
 */

/**
 * This files should always remain compatible with the minimum version of
 * PHP supported by WordPress.
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

global $incompatible_plugins;
global $is_bp_active;
global $bp_plugin_file;
global $is_bb_active;
global $bb_plugin_file;
global $sitewide_plugins;
global $plugins;
$incompatible_plugins = array();
$is_bp_active         = false;
$bp_plugin_file       = 'buddypress/bp-loader.php';

$is_bb_active     = false;
$bb_plugin_file   = 'bbpress/bbpress.php';
$sitewide_plugins = array();

if ( is_multisite() ) {
	// get network-activated plugins
	foreach ( get_site_option( 'active_sitewide_plugins', array() ) as $key => $value ) {
		$sitewide_plugins[] = $key;
	}

}

$plugins   = array_merge( $sitewide_plugins, get_option( 'active_plugins' ) );
$plugins[] = isset( $_GET['plugin'] ) ? $_GET['plugin'] : array();

//check if BuddyPress is activated
if ( in_array( $bp_plugin_file, $plugins ) ) {
	$is_bp_active = true;
}

//check if bbPress is activated
if ( in_array( $bb_plugin_file, $plugins ) ) {
	$is_bb_active = true;
}

/**
 * Prevent running BuddyBoss Platform if any incompatible plugins are active.
 * Show admin error message instead.
 */
$incompatible_plugins_list = array(
	'buddypress-global-search/buddypress-global-search.php' => __( 'The BuddyBoss Platform can\'t work while BuddyPress Global Search plugin is active. Global Search functionality is built into the platform. Please deactivate BuddyPress Global Search first, if you wish to activate BuddyBoss Platform.', 'buddyboss' ),
	'buddypress-followers/loader.php'                       => __( 'The BuddyBoss Platform can\'t work while BuddyPress Follow plugin is active. Following/followers functionality is built into the platform. Please deactivate BuddyPress Follow first, if you wish to activate BuddyBoss Platform.', 'buddyboss' ),
);

foreach ( $incompatible_plugins_list as $incompatible_plugin => $error_message ) {
	if ( in_array( $incompatible_plugin, $plugins ) ) {
		$incompatible_plugins[] = $error_message;
	}
}

if ( empty( $is_bp_active ) && empty( $is_bb_active ) && empty( $incompatible_plugins ) ) {


	// Required PHP version.
	define( 'BP_REQUIRED_PHP_VERSION', '5.3.0' );

	/**
	 * The main function responsible for returning the one true BuddyBoss Instance to functions everywhere.
	 *
	 * Use this function like you would a global variable, except without needing
	 * to declare the global.
	 *
	 * Example: <?php $bp = buddypress(); ?>
	 *
	 * @return BuddyPress|null The one true BuddyPress Instance.
	 */
	function buddypress() {
		return BuddyPress::instance();
	}

	/**
	 * Adds an admin notice to installations that don't meet minimum PHP requirement.
	 *
	 * @since BuddyPress 2.8.0
	 */
	function bp_php_requirements_notice() {
		if ( ! current_user_can( 'update_core' ) ) {
			return;
		}

		?>

        <div id="message" class="error notice">
            <p><strong><?php esc_html_e( 'Your site does not support BuddyBoss Platform.', 'buddyboss' ); ?></strong>
            </p>
			<?php /* translators: 1: current PHP version, 2: required PHP version */ ?>
            <p><?php printf( esc_html__( 'Your site is currently running PHP version %1$s, while BuddyBoss Platform requires version %2$s or greater.', 'buddyboss' ), esc_html( phpversion() ), esc_html( BP_REQUIRED_PHP_VERSION ) ); ?></p>
            <p><?php esc_html_e( 'Please update your server or deactivate BuddyBoss Platform.', 'buddyboss' ); ?></p>
        </div>

		<?php
	}

	if ( version_compare( phpversion(), BP_REQUIRED_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'bp_php_requirements_notice' );
		add_action( 'network_admin_notices', 'bp_php_requirements_notice' );

		return;
	} else {
		require dirname( __FILE__ ) . '/class-buddypress.php';

		// load the member switch class so all the hook prior to bp_init can be hook in
		require dirname( __FILE__ ) . '/bp-members/classes/class-bp-core-members-switching.php';

		/*
		 * Hook BuddyPress early onto the 'plugins_loaded' action.
		 *
		 * This gives all other plugins the chance to load before BuddyBoss Platform,
		 * to get their actions, filters, and overrides setup without
		 * BuddyBoss Platform being in the way.
		 */
		if ( defined( 'BUDDYPRESS_LATE_LOAD' ) ) {
			add_action( 'plugins_loaded', 'buddypress', (int) BUDDYPRESS_LATE_LOAD );

			$bp_forum_active = array_key_exists( 'forums', bp_get_option( 'bp-active-components', [] ) );

			// A lot of actions in bbpress require before component init,
			// hence we grab the pure db value and load the class
			// so all the hook prior to bp_init can be hook in
			if ( $bp_forum_active ) {
				require dirname( __FILE__ ) . '/bp-forums/classes/class-bbpress.php';
				add_action( 'plugins_loaded', 'bbpress', (int) BUDDYPRESS_LATE_LOAD );
			}

			// "And now here's something we hope you'll really like!"
		} else {
			$GLOBALS['bp'] = buddypress();

			$bp_forum_active = array_key_exists( 'forums', bp_get_option( 'bp-active-components', [] ) );
			if ( $bp_forum_active ) {
				require dirname( __FILE__ ) . '/bp-forums/classes/class-bbpress.php';
				$GLOBALS['bbp'] = bbpress();
			}
		}
	}

} else {
	/**
	 * Displays an admin notice when BuddyPress plugin is active.
	 *
	 * @since BuddyBoss 1.0.0
	 * @return void
	 */
	function bp_duplicate_notice() {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}


		global $incompatible_plugins;
		global $bp_plugin_file;
		global $bb_plugin_file;
		global $sitewide_plugins;
		global $is_bp_active;
		global $is_bb_active;
		global $plugins;

		// Disable BuddyPress message
		if ( $is_bp_active ) {
			if ( is_multisite() && ( is_network_admin() && ! in_array( $bp_plugin_file, $sitewide_plugins ) || in_array( $bp_plugin_file, $plugins ) ) ) {
				return;
			}
			$plugins_url  = is_network_admin() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );
			$link_plugins = sprintf( "<a href='%s'>%s</a>", $plugins_url, __( 'deactivate', 'buddyboss' ) );
			?>

            <div id="message" class="error notice">
                <p><strong><?php esc_html_e( 'BuddyBoss Platform is disabled.', 'buddyboss' ); ?></strong></p>
                <p><?php printf( esc_html__( 'The BuddyBoss Platform can\'t work while BuddyPress plugin is active. Please %s BuddyPress to re-enable BuddyBoss Platform.', 'buddyboss' ), $link_plugins ); ?></p>
            </div>

			<?php
		}

		// Disable bbPress message
		if ( $is_bb_active ) {

			if ( is_multisite() && ( is_network_admin() && ! in_array( $bb_plugin_file, $sitewide_plugins ) || in_array( $bb_plugin_file, $plugins ) ) ) {
				return;
			}
			$plugins_url  = is_network_admin() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );
			$link_plugins = sprintf( "<a href='%s'>%s</a>", $plugins_url, __( 'deactivate', 'buddyboss' ) );
			?>

            <div id="message" class="error notice">
                <p><strong><?php esc_html_e( 'BuddyBoss Platform is disabled.', 'buddyboss' ); ?></strong></p>
                <p><?php printf( esc_html__( 'The BuddyBoss Platform can\'t work while bbPress plugin is active. Please %s bbPress to re-enable BuddyBoss Platform.', 'buddyboss' ), $link_plugins ); ?></p>
            </div>

			<?php
		}

		if ( ! empty( $incompatible_plugins ) ) {
			foreach ( $incompatible_plugins as $incompatible_plugin_message ) {
				?>
                <div id="message" class="error notice">
                    <p><strong><?php esc_html_e( 'BuddyBoss Platform is disabled.', 'buddyboss' ); ?></strong></p>
					<?php
					printf( '<p>%s</p>', $incompatible_plugin_message ); ?>
                </div>
				<?php
			}
		}
	}

	/**
	 * You can't have bbPress and BuddyBoss Platform both active at the same time!
	 */
	add_action( 'admin_notices', 'bp_duplicate_notice' );
	add_action( 'network_admin_notices', 'bp_duplicate_notice' );
}