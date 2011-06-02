<?php

interface FSB2_Converter
{
	/**
	 * Récupère les informations utiles aux convertisseurs, peut-être vide
	 */
	protected function forum_information();

	/**
	 * Retourne la liste des conversions implementées.
	 * Apelée dans get_implement()
	 * @return array
	 */
	protected function _get_implement();
}

# EOF
