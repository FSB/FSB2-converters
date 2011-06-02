<?php

interface FSB2_Converter
{
	/**
	 * Récupère les informations utiles aux convertisseurs, peut-être vide
	 */
	function forum_information();

	/**
	 * Retourne la liste des conversions implementées.
	 * Apelée dans get_implement()
	 * @see Converter#_get_implement()
	 * @return array
	 */
	function _get_implement();
}

# EOF
