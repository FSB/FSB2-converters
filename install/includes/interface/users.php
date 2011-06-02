<?php

interface FSB2_Users
{
	/**
	 * Retourne le nombre de membres qu'on va convertir
	 * @return int
	 */
	function count_convert_users();

	/**
	 * Retourne un tableau contenant a chaque ligne les informations sur un membre
	 * array :
	 * 	'data' : donée lié aux membres
	 *  'sql' : requêtes spécifiques
	 * @param int $offset enregistrement de départ
	 * @param int $step nombre d'enregistrement pris a chaque conversion
	 * @param int $state état lors du rafraichissement automatique
	 * @return array
	 */
	function convert_users($offset, $step, $state);
}

# EOF
