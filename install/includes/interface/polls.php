<?php

interface FSB2_Polls
{
	/**
	 * Retourne un tableau contenant les sondages, avec un sondage par ligne. Chaque ligne de sondage
	 * doit contenir un sous tableau "options" avec le tableau d'options, ainsi qu'un tableau "voters"
	 * contenant les ID des membres qui ont vote.
	 * array :
	 * 	'data' : donnée du sondage : ['options', 'voters']
	 * 	'sql' : requête spécifique
	 * @return array
	 */
	protected function convert_polls();
}

# EOF
