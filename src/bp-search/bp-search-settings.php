<?php
/**
 * Search Settings
 *
 * @package BuddyBoss
 * @subpackage Search
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the Search settings sections.
 *
 * @since BuddyBoss 1.0.0
 * @return array
 */
function bp_search_get_settings_sections() {
	return (array) apply_filters( 'bp_search_get_settings_sections', array(
		'bp_search_settings_community'  => array(
			'page' => 'search'
		),
		'bp_search_settings_post_types' => array(
			'page' => 'search'
		),
		'bp_search_settings_general'    => array(
			'page' => 'search'
		),
	) );
}

/**
 * Get all of the settings fields.
 *
 * @since BuddyBoss 1.0.0
 * @return array
 */
function bp_search_get_settings_fields() {

	$fields = [];

	/** General Section ******************************************************/
	$fields['bp_search_settings_general'] = [

		'bp_search_autocomplete' => [
			'title'             => __( 'Enable Autocomplete', 'buddyboss' ),
			'callback'          => 'bp_search_settings_callback_autocomplete',
			'sanitize_callback' => 'intval',
			'args'              => []
		],

		'bp_search_number_of_results' => [
			'title'             => __( 'Number of Results', 'buddyboss' ),
			'callback'          => 'bp_search_settings_callback_number_of_results',
			'sanitize_callback' => 'intval',
			'args'              => []
		],
	];

	$fields['bp_search_settings_community'] = [
		'bp_search_members' => [
			'title'             => __( 'Community Network', 'buddyboss' ),
			'callback'          => 'bp_search_settings_callback_members',
			'sanitize_callback' => 'intval',
			'args'              => [
				'class' => 'bp-search-parent-field'
			]
		],
	];

	if ( bp_is_search_members_enable() ) {
		$groups = bp_xprofile_get_groups( array(
			'fetch_fields' => true
		) );


		if ( ! empty( $groups ) ) {
			foreach ( $groups as $group ) {
				if ( ! empty( $group->fields ) ) {

					$fields['bp_search_settings_community']["bp_search_xprofile_group_{$group->id}"] = [
						'title'    => '&#65279;',
						'callback' => 'bp_search_settings_callback_xprofile_group',
						'args'     => [
							'group' => $group,
							'class' => 'bp-search-child-field bp-search-subgroup-heading'
						]
					];

					foreach ( $group->fields as $field ) {
						$fields['bp_search_settings_community']["bp_search_xprofile_{$field->id}"] = [
							'title'             => '&#65279;',
							'callback'          => 'bp_search_settings_callback_xprofile',
							'sanitize_callback' => 'intval',
							'args'              => [
								'field' => $field,
								'class' => 'bp-search-child-field'
							]
						];
					}
				}
			}
		}
	}

	if ( bp_is_active( 'forums' ) ) {
		$fields['bp_search_settings_community']["bp_search_post_type_forum"] = [
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_post_type',
			'sanitize_callback' => 'intval',
			'args'              => [
				'post_type' => 'forum',
				'class'     => 'bp-search-parent-field'
			]
		];

		$fields['bp_search_settings_community']["bp_search_post_type_topic"] = [
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_post_type',
			'sanitize_callback' => 'intval',
			'args'              => [
				'post_type' => 'topic',
				'class'     => 'bp-search-child-field'
			]
		];

		$fields['bp_search_settings_community']["bp_search_post_type_reply"] = [
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_post_type',
			'sanitize_callback' => 'intval',
			'args'              => [
				'post_type' => 'reply',
				'class'     => 'bp-search-child-field'
			]
		];
	}

	if ( bp_is_active( 'groups' ) ) {
		$fields['bp_search_settings_community']["bp_search_groups"] = [
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_groups',
			'sanitize_callback' => 'intval'
		];
	}

	return (array) apply_filters( 'bp_search_get_settings_fields', $fields );
}

/** General Section **************************************************************/

/**
 * Get settings fields by section.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $section_id
 *
 * @return mixed False if section is invalid, array of fields otherwise.
 */
function bp_search_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bp_search_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	return (array) apply_filters( 'bp_search_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Output settings API option
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $option
 * @param string $default
 * @param bool $slug
 */
function bp_search_form_option( $option, $default = '', $slug = false ) {
	echo bp_search_get_form_option( $option, $default, $slug );
}

/**
 * Return settings API option
 *
 * @since BuddyBoss 1.0.0
 *
 * @uses get_option()
 * @uses esc_attr()
 * @uses apply_filters()
 *
 * @param string $option
 * @param string $default
 * @param bool $slug
 *
 * @return mixed
 */
function bp_search_get_form_option( $option, $default = '', $slug = false ) {

	// Get the option and sanitize it
	$value = get_option( $option, $default );

	// Slug?
	if ( true === $slug ) {
		$value = esc_attr( apply_filters( 'editable_slug', $value ) );

		// Not a slug
	} else {
		$value = esc_attr( $value );
	}

	// Fallback to default
	if ( empty( $value ) ) {
		$value = $default;
	}

	// Allow plugins to further filter the output
	return apply_filters( 'bp_search_get_form_option', $value, $option );
}

/**
 * Search autocomplete setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_autocomplete() {
	?>

	<input name="bp_search_autocomplete" id="bp_search_autocomplete" type="checkbox" value="1"
		<?php checked( bp_is_search_autotcomplete_enable( true ) ) ?> />
	<label
		for="bp_search_autocomplete"><?php esc_html_e( 'Enable autocomplete dropdown when typing into search inputs.', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Checks if search autocomplete feature is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default bool Optional.Default value true
 *
 * @uses get_option() To get the bp_search_autocomplete option
 * @return bool Is search autocomplete enabled or not
 */
function bp_is_search_autotcomplete_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_autotcomplete_enable', (bool) get_option( 'bp_search_autocomplete', $default ) );
}

/**
 * Number of results setting field
 *
 * @since BuddyBoss 1.0.0
 */
function bp_search_settings_callback_number_of_results() {
	?>

	<input name="bp_search_number_of_results" id="bp_search_number_of_results" type="number" min="1" step="1"
	       value="<?php bp_search_form_option( 'bp_search_number_of_results', '5' ); ?>" class="small-text"/>
	<label for="bp_search_number_of_results"><?php esc_html_e( 'results', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Allow Members search setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_members() {
	?>

	<input name="bp_search_members" id="bp_search_members" type="checkbox" value="1"
		<?php checked( bp_is_search_members_enable( true ) ) ?> />
	<label
		for="bp_search_members"><?php esc_html_e( 'Members', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Checks if members search feature is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default bool Optional.Default value true
 *
 * @uses get_option() To get the bp_search_members option
 * @return bool Is members search enabled or not
 */
function bp_is_search_members_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_members_enable', (bool) get_option( 'bp_search_members', $default ) );
}

/**
 * Output Field Group name
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args array
 *
 * @param $group
 */
function bp_search_settings_callback_xprofile_group( $args ) {
	$group = $args['group'];
	?>
	<strong><?php echo $group->name ?></strong>
	<?php
}

/**
 * Allow xProfile field search setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args array
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_xprofile( $args ) {
	$field       = $args['field'];
	$id          = $field->id;
	$option_name = 'bp_search_xprofile_' . $id;
	?>

	<input name="<?php echo $option_name ?>" id="<?php echo $option_name ?>" type="checkbox" value="1"
		<?php checked( bp_is_search_xprofile_enable( $id ) ) ?> />
	<label
		for="<?php echo $option_name ?>"><?php echo $field->name ?></label>

	<?php
}

/**
 * Checks if xprofile field search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $id integer
 *
 * @uses get_option() To get the bp_search_members option
 * @return bool Is members search enabled or not
 */
function bp_is_search_xprofile_enable( $id ) {
	return (bool) apply_filters( 'bp_is_search_xprofile_enable', (bool) get_option( "bp_search_xprofile_$id" ) );
}

/**
 * Allow Post Type search setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args array
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_post_type( $args ) {

	$post_type   = $args['post_type'];
	$option_name = 'bp_search_post_type_' . $post_type;

	$post_type_obj = get_post_type_object( $post_type );
	?>
	<input
		name="<?php echo $option_name ?>"
		id="<?php echo $option_name ?>"
		type="checkbox"
		value="1"
		<?php checked( bp_is_search_post_type_enable( $post_type ) ) ?>
	/>
	<label for="<?php echo $option_name ?>">
		<?php echo $post_type_obj->labels->name ?>
	</label>
	<?php
}

/**
 * Checks if post type search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post_type string
 *
 * @return bool Is members search enabled or not
 */
function bp_is_search_post_type_enable( $post_type ) {
	return (bool) apply_filters( 'bp_is_search_post_type_enable', (bool) get_option( "bp_search_post_type_$post_type" ) );
}

/**
 * Allow Post Type search setting field
 *
 * @since BuddyBoss 1.0.0
 *
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_groups() { ?>
	<input
		name="bp_search_groups"
		id="bp_search_groups"
		type="checkbox"
		value="1"
		<?php checked( bp_is_search_groups_enable( true ) ) ?>
	/>
	<label for="bp_search_groups">
		<?php esc_html_e( 'Groups', 'buddyboss' ) ?>
	</label>
	<?php
}

/**
 * Checks if groups search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is groups search enabled or not
 */
function bp_is_search_groups_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_groups_enable', (bool) get_option( 'bp_search_groups', $default ) );
}