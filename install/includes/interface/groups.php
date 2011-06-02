<?php

interface FSB2_Groups
{
	/**
	 * Retourne les informations sur les groupes du forum
	 * Renvoi un array :
	 * 	clef 'groups' : informations sur les groupes
	 *  clef 'groups_users' : les membres des groupes
	 *  clef 'sql' : requêtes éventuel spécifique
	 * @return array
	 */
	function convert_groups();
}

# EOF
