<?php
/**
 * BuddyBoss - Users Groups
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */
?>

<?php

switch ( bp_current_action() ) :

	// Home/My Groups
	case 'send-invites':
		bp_get_template_part( 'members/single/invites/send-invites' );
		break;

	// Group Invitations
	case 'sent-invites':
		bp_get_template_part( 'members/single/invites/sent-invites' );
		break;

	// Any other
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
