<?php

interface FSB2_Posts
{
	/**
	 * Retourne le nombre de messages que l'on va convertir
	 * @return int
	 */
	function count_convert_posts();

	/**
	 * Retourne un tableau contenant a chaque ligne les informations sur un message
	 * array :
	 * 	'data' : donée lié au message
	 *  'sql' : requêtes spécifiques
	 * @param int $offset enregistrement de départ
	 * @param int $step nombre d'enregistrement pris a chaque conversion
	 * @param int $state état lors du rafraichissement automatique
	 * @return array
	 */
	function convert_posts($offset, $step, $state);
}

# EOF
