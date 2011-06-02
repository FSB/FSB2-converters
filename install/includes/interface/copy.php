<?php

interface FSB2_Copy
{
	/**
	 * Retourne les dossiers pour la copie d'image
	 * array :
	 * 	clef 'avatars' : contient l'ensemble des chemins vers les avatars des membres
	 * 	clef 'ranks' : contient l'ensemble des  chemins vers les rangs des membres
	 * @return array
	 */
	function convert_copy();
}

# EOF
