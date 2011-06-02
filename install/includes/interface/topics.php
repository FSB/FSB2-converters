<?php

interface FSB2_Topics
{
	/**
	 * Retourne le nombre de sujets qu'on va convertir
	 * @return int
	 */
	function count_convert_topics();

	/**
	 * Retourne un tableau contenant a chaque ligne les informations sur un sujet
	 * array :
	 * 	'data' : donée lié aux sujets
	 *  'sql' : requêtes spécifiques
	 * @param int $offset enregistrement de départ
	 * @param int $step nombre d'enregistrement pris a chaque conversion
	 * @param int $state état lors du rafraichissement automatique
	 * @return array
	 */
	function convert_topics($offset, $step, $state);
}

# EOF
