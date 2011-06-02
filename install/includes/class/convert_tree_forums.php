<?php



/**
 * Classe permettant la creation et la manipulation des forums sous forme d'arbre, afin de faciliter leur import dans FSB2
 *
 */
class Convert_tree_forums extends Tree
{
	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		$this->add_item(0, null, array());
	}

	/**
	 * Surcharge de la methode Tree::add_item() pour ajouter le niveau du sous forum
	 *
	 * @param int $id
	 * @param int $parent
	 * @param array $data
	 */
	public function add_item($id, $parent, $data)
	{
		parent::add_item($id, $parent, $data);

		$this->merge_item($id, array(
			'f_level' =>	count($this->getByID($id)->getParents()) - 2
		));
	}

	/**
	 * Ajoute les champs f_left et f_right aux forums
	 *
	 * @param unknown_type $node
	 * @param int $f_left
	 */
	public function create_interval($node = null, &$f_left = 0)
	{
		if (!$node)
		{
			$node = $this->document;
		}
		
		foreach ($node->children AS $child)
		{
			$f_left++;
			$child->set('f_left', $f_left);
			$child->set('f_right', $f_left + (2 * count($child->allChildren()) + 1));

			if ($child->children)
			{
				$this->create_interval($child, $f_left);
			}

			$f_left++;
		}
	}

	/**
	 * Retourne un tableau simple contenant les forums, au lieu d'un arbre
	 *
	 * @param unknown_type $node
	 * @return unknown
	 */
	public function plain_data($node = null)
	{
		if (!$node)
		{
			$node = $this->document->children[0];
		}

		$return = array();
		foreach ($node->children AS $child)
		{
			$return[$child->get('f_id')] = $child->data;
			if ($child->children)
			{
				$return = array_merge($return, $this->plain_data($child));
			}
		}

		return ($return);
	}
}

# EOF
