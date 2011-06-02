<?php

interface FSB2_Ranks
{
	/**
	 * Renvoi un array :
	 * 	clef 'data' :
	 * 		'rank_id'
	 * 		'rank_name'
	 * 		'rank_img'
	 * 		'rank_special'
	 * 		'rank_quota'
	 * @return array
	 */
	protected function convert_ranks();
}

# EOF
