<?php

interface FSB2_Bans
{
	/**
	 * Retourne les informations sur le bannissement
	 * Renvoi un array :
	 * clef 'data' :
	 * 	Reprend les infos sur un ban, avec, par exemple :
	 *		'ban_type'
	 *		'ban_content'
	 *		'ban_length'
	 *		'ban_reason'
	 *		'ban_cookie'
	 * @return array
	 */
	function convert_bans();
}

# EOF
