<?php

interface FSB2_Auths
{
	/**
	 * Retourne un tableau multidimensionel
	 * clef 'data' contenant :
	 * - Au premier niveau en clef, l'ID d'un forum
	 * - Au second niveau en clef, l'ID d'un groupe
	 * - Au troisieme niveau, les clefs des droits avec true / false
	 * En clair, ce tableau permet de determiner les droits pour chaque groupe pour chaque forum.
	 * Une abscence de forum ou de groupe signifie aucun droit.
	 * 
	 * clef 'sql' :
	 * Requète sql spécifique, par exemple pour mettre à jour la couleurs des users
	 */
	protected function convert_auths();
}

# EOF
