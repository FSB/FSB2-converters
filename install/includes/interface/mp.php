<?php

interface FSB2_Mp
{
	/**
	 * Retourne un tableau contenant a chaque ligne les informations sur un message prive
	 * array :
	 * 	'data' : donée lié au mps
	 *  'sql' : requêtes spécifiques
	 * @param int $offset enregistrement de départ
	 * @param int $step nombre d'enregistrement pris a chaque conversion
	 * @param int $state état lors du rafraichissement automatique
	 * @return array
	 */
	function convert_mp($offset, $step, $state);

	/**
	 * Retourne le nombre de MP a convertir
	 * @return int
	 */
	function count_convert_mp();
}

# EOF
