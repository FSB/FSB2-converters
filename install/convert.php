<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

if (!defined('ROOT'))
{
	die('This file must be included.<hr />Ce fichier doit etre inclus');
}

include_once('includes/class/convert_tree_forums.php');
include_once('includes/interface/converter.php');
include_once('includes/interface/users.php');
include_once('includes/interface/config.php');
include_once('includes/interface/groups.php');
include_once('includes/interface/forums.php');
include_once('includes/interface/auths.php');
include_once('includes/interface/topics.php');
include_once('includes/interface/posts.php');
include_once('includes/interface/mp.php');
include_once('includes/interface/polls.php');
include_once('includes/interface/bans.php');
include_once('includes/interface/ranks.php');
include_once('includes/interface/copy.php');

/**
 * Classe de gestion des convertisseurs
 */
class Convert
{
	/**
	 * Liste des conversions implementees
	 *
	 * @var array
	 */
	protected $implement = array();

	/**
	 * Page actuelle de conversion
	 *
	 * @var string
	 */
	protected $page = 'index';

	/**
	 * Offset de depart pour la conversion
	 *
	 * @var int
	 */
	protected $offset = 0;

	/**
	 * URL du script
	 *
	 * @var string
	 */
	protected $url = '';

	/**
	 * Configuration du convertisseur
	 *
	 * @var unknown_type
	 */
	protected $config;

	/**
	 * Export dans un fichier, ou injection dans la base de donnee ?
	 *
	 */
	const OUTPUT_FILE = 0;
	const OUTPUT_DB = 1;
	const OUTPUT_PRINT = 2;

	/**
	 * Etat lors du rafraichissement automatique
	 *
	 */
	const STATE_BEGIN = 2;
	const STATE_MIDDLE = 4;
	const STATE_END = 8;

	const EXPORT_FILENAME = 'export.sql';

	/**
	 * Rafraichissement offset ?
	 *
	 * @var bool
	 */
	protected $refresh_offset = false;

	/**
	 * Constructeur
	 *
	 * @param string $converter
	 */
	public function __construct($converter)
	{
		@set_time_limit(0);

		// Informations sur la page
		$this->page = Http::request('p');
		$this->offset = intval(Http::request('offset'));
		$this->url = 'index.' . PHPEXT . '?convert=' . $converter . '&amp;p=';

		// Configuration du convertisseur
		$this->get_config();

		// Page de soumission
		if (Http::method() == Http::POST)
		{
			if (method_exists($this, 'submit_' . $this->page))
			{
				$this->{'submit_' . $this->page}();
			}
		}

		// Gestion de la connexion a la base de donnee ?
		$this->database_connexion();

		$this->forum_information();

		// Liste des conversions implementees
		$this->get_implement();

		// Verification de la page
		if (!in_array($this->page, $this->implement))
		{
			$this->page = 'index';
		}

		$this->page_header();
		$this->{'page_' . $this->page}();
		$this->page_footer();
	}

	/**
	 * Recupere la configuration du convertisseur (stoquee dans la table fsb2_cache de FSB2)
	 *
	 */
	private function get_config()
	{
		Fsb::$db = Dbal::factory();
		$cache = Cache::factory('converter', 'sql');
		$this->config = $cache->get('config');
		Fsb::$db->close();

		// Config par defaut
		if (!isset($this->config['output']))
		{
			$this->config['output'] = self::OUTPUT_FILE;
		}

		if (!isset($this->config['step_users']))
		{
			$this->config['step_users'] = 2000;
		}

		if (!isset($this->config['step_topics']))
		{
			$this->config['step_topics'] = 2000;
		}

		if (!isset($this->config['step_posts']))
		{
			$this->config['step_posts'] = 5000;
		}
	}

	/**
	 * Recupere une clef de configuration
	 *
	 * @param string $key
	 * @return unknown
	 */
	protected function config($key)
	{
		return ((isset($this->config[$key])) ? $this->config[$key] : null);
	}

	/**
	 * Retourne la liste des conversions implementées.
	 * Apelée dans get_implement()
	 * @return array
	 */
	protected function _get_implement()
	{
		$implemented = class_implements($this);
		$implemented_interfaces = array(
			'FSB2_Config' 	=> 'config',
			'FSB2_Users'	=> 'users',
			'FSB2_Groups'	=> 'groups',
			'FSB2_Forums'	=> 'forums',
			'FSB2_Auths'	=> 'auths',
			'FSB2_Topics'	=> 'topics',
			'FSB2_Posts'	=> 'posts',
			'FSB2_Mp'		=> 'mp',
			'FSB2_Polls'	=> 'polls',
			'FSB2_Bans'		=> 'bans',
			'FSB2_Ranks'	=> 'ranks',
			'FSB2_Copy'		=> 'copy'
		);

		$implements = array();

		foreach($implemented as $interface)
		{
			if (!empty($implemented_interfaces[ $interface ]))
			{
				$implements[] = $implemented_interfaces[ $interface ];
			} 
		}

		return $implements;
	}

	/**
	 * Liste des conversions que le script implemente
	 */
	private function get_implement()
	{
		$this->implement = array(
			'config',
			'users',
			'groups',
			'forums',
			'auths',
			'topics',
			'posts',
			'mp',
			'polls',
			'bans',
			'ranks',
			'copy'
		);

		$this->implement = array_intersect($this->implement, $this->_get_implement());
	}

	/**
	 * Gestion de la connexion a la base de donnee
	 *
	 */
	private function database_connexion()
	{
		Fsb::$db = Dbal::factory($this->config('sql_server'), $this->config('sql_login'), $this->config('sql_password'), $this->config('sql_dbname'), $this->config('sql_port'), false);
		if (!Fsb::$db->_get_id())
		{
			$this->error(Fsb::$session->lang('convert_sql_connexion_error'));
		}
	}

	/**
	 * Generation du haut de la page
	 *
	 */
	private function page_header()
	{
		if (!defined('CONVERTER_PAGE_HEADER'))
		{
			define('CONVERTER_PAGE_HEADER', true);
		}

		Fsb::$tpl->set_file('convert.html');
		Fsb::$tpl->set_vars(array(
			'CONVERT_WELCOME' =>	sprintf(Fsb::$session->lang('convert_welcome'), $this->forum_type),
			'U_ACTION' =>			$this->url . $this->page,
		));

		$this->implement = array_merge(array('index'), $this->implement);

		foreach ($this->implement AS $menu)
		{
			Fsb::$tpl->set_blocks('menu', array(
				'URL' =>		$this->url . $menu,
				'NAME' =>		Fsb::$session->lang('convert_menu_' . $menu),
				'SELECTED' =>	($this->page == $menu) ? true : false,
			));
		}
	}

	/**
	 * Generation du pied de la page
	 *
	 */
	private function page_footer()
	{
		if (!$this->refresh_offset && $this->page != 'index')
		{
			$i = array_flip($this->implement);
			$pos = $i[$this->page];
			if (isset($this->implement[$pos + 1]))
			{
				$next_page = $this->implement[$pos + 1];
				Fsb::$tpl->set_vars(array(
					'NEXT_PAGE' =>		$this->url . $next_page,
					'REFRESH_URL' =>	$this->url . $next_page,
					'REFRESH_AUTO' =>	($this->config('output') != self::OUTPUT_PRINT) ? true : false,
				));
			}
			else
			{
				Fsb::$tpl->set_vars(array(
					'CONVERT_DONE' =>		true,
				));
			}
		}
		Fsb::$tpl->parse();
	}

	/**
	 * Affichage d'une erreur
	 *
	 * @param string $str
	 */
	protected function error($str)
	{
		if ($this->page == 'index')
		{
			return ;
		}

		if (!defined('CONVERTER_PAGE_HEADER'))
		{
			$this->page_header();
		}

		Fsb::$tpl->set_switch('error');
		Fsb::$tpl->set_vars(array(
			'ERRSTR' =>	$str,
		));

		$this->page_footer();

		exit;
	}

	/**
	 * Lance un rafraichissement automatique de la page
	 *
	 * @param int $total
	 */
	protected function refresh_with_offset($total)
	{
		$this->refresh_offset = true;

		Fsb::$tpl->set_vars(array(
			'REFRESH_URL' =>	$this->url . $this->page . '&amp;offset=' . $this->offset,
			'REFRESH_AUTO' =>	($this->config('output') != self::OUTPUT_PRINT) ? true : false,
			'PROGRESS' =>		sprintf(Fsb::$session->lang('convert_progress'), $this->offset, $total, round($this->offset * 100 / $total, 2)),
		));
	}

	/**
	 * Gestion de la sortie des requetes
	 *
	 * @param array $ary
	 */
	protected function output(&$ary)
	{
		if (!$this->use_utf8)
		{
			$ary = array_map('utf8_encode', $ary);

			if ($this->config('output') == self::OUTPUT_FILE || $this->config('output') == self::OUTPUT_PRINT)
			{
				$ary = array_map('utf8_encode', $ary);
			}
		}

		switch ($this->config('output'))
		{
			case self::OUTPUT_FILE :
				$fd = fopen(self::EXPORT_FILENAME, 'a');
				foreach ($ary AS $line)
				{
					fwrite($fd, $line . ";\n");
				}
				fclose($fd);
			break;

			case self::OUTPUT_DB :
				Fsb::$db->close();

				Fsb::$db = Dbal::factory();
				foreach ($ary AS $line)
				{
					Fsb::$db->query($line);
				}
			break;

			case self::OUTPUT_PRINT :
			default :
				Fsb::$tpl->set_switch('output_print');
				Fsb::$tpl->set_vars(array(
					'OUTPUT_QUERIES' =>		htmlspecialchars(implode(";\n", $ary)) . ";\n",
				));
			break;
		}
	}

	/**
	 * Index du convertisseur
	 *
	 */
	private function page_index()
	{
		Fsb::$tpl->set_switch('page_index');
		Fsb::$tpl->set_vars(array(
			'OUTPUT' =>				$this->config('output'),
			'STEP_USERS' =>			$this->config('step_users'),
			'STEP_TOPICS' =>		$this->config('step_topics'),
			'STEP_POSTS' =>			$this->config('step_posts'),
			'SQL_PREFIX' =>			$this->config('sql_prefix'),
			'SQL_SERVER' =>			$this->config('sql_server'),
			'SQL_LOGIN' =>			$this->config('sql_login'),
			'SQL_PASSWORD' =>		$this->config('sql_password'),
			'SQL_DBNAME' =>			$this->config('sql_dbname'),
			'SQL_PORT' =>			$this->config('sql_port')
		));

		// Configuration additionelle
		foreach ($this->additional_conf AS $key => $value)
		{
			Fsb::$tpl->set_blocks('conf', array(
				'LANG' =>		Fsb::$session->lang('convert_conf_' . $key),
				'EXPLAIN' =>	Fsb::$session->lang('convert_conf_' . $key . '_explain'),
				'HTML' =>		str_replace('{VALUE}', $this->config($key), $value),
			));
		}

		if (!is_writable(self::EXPORT_FILENAME))
		{
			chmod(self::EXPORT_FILENAME, 'ugo+w');
		}
	}

	/**
	 * Sauvegarde de la configuration
	 *
	 */
	private function submit_index()
	{
		$this->config['output'] =			intval(Http::request('output', 'post'));
		$this->config['step_users'] =		intval(Http::request('step_users', 'post'));
		$this->config['step_topics'] =		intval(Http::request('step_topics', 'post'));
		$this->config['step_posts'] =		intval(Http::request('step_posts', 'post'));
		$this->config['sql_server'] =		trim(Http::request('sql_server', 'post'));
		$this->config['sql_login'] =		trim(Http::request('sql_login', 'post'));
		$this->config['sql_password'] =		trim(Http::request('sql_password', 'post'));
		$this->config['sql_dbname'] =		trim(Http::request('sql_dbname', 'post'));
		$this->config['sql_prefix'] =		trim(Http::request('sql_prefix', 'post'));
		$this->config['sql_port'] =			intval(Http::request('sql_port', 'post'));

		// Configuration additionelle
		foreach ($this->additional_conf AS $key => $value)
		{
			$this->config[$key] = Http::request($key, 'post');
		}

		// Sauvegarde de la configuration
		Fsb::$db = Dbal::factory();
		$cache = Cache::factory('converter', 'sql');
		$cache->put('config', $this->config);
		Fsb::$db->close();
	}

	/**
	 * Configuration du forum
	 */
	private function page_config()
	{
		$data = $this->convert_config();

		$query = array();
		foreach ($data['data'] AS $key => $value)
		{
			$query[] = 'UPDATE ' . SQL_PREFIX . 'config SET cfg_value = \'' . Fsb::$db->escape($value) . '\' WHERE cfg_name = \'' . Fsb::$db->escape($key) . '\'';
		}

		$this->_push_manual_queries($query, $data['sql']);

		$this->output($query);
	}

	/**
	 * Liste des membres
	 *
	 */
	private function page_users()
	{
		$step = $this->config('step_users');

		$total = $this->count_convert_users();
		$state = 0;
		if ($this->offset == 0)
		{
			$state |= self::STATE_BEGIN;
		}

		if ($this->offset + $step < $total)
		{
			$state |= self::STATE_MIDDLE;
		}
		else
		{
			$state |= self::STATE_END;
		}

		$queries = array();
		if ($state & self::STATE_BEGIN)
		{
			$queries[] = 'DELETE FROM ' . SQL_PREFIX . 'users WHERE u_id <> 1';
			$queries[] = 'DELETE FROM ' . SQL_PREFIX . 'users_password';
			$queries[] = 'DELETE FROM ' . SQL_PREFIX . 'groups WHERE g_type <> ' . GROUP_SPECIAL;
			$queries[] = 'DELETE FROM ' . SQL_PREFIX . 'groups_users WHERE g_id <> ' . GROUP_SPECIAL_VISITOR;
		}

		// Informations par defaut
		$def = array(
			'u_language' =>				'fr',
			'u_tpl' =>					'WhiteSummer',
			'u_activated' =>			true,
			'u_birthday' =>				'00/00/00',
			'u_activate_avatar' =>		true,
			'u_activate_fscode' =>		6,
			'u_activate_email' =>		4,
			'u_activate_hidden' =>		false,
			'u_activate_sig' =>			true,
			'u_activate_img' =>			6,
		);

		// Creation des requetes
		$users = $this->convert_users($this->offset, $step, $state);
		foreach ($users AS $data)
		{
			// ID unique du groupe du membre
			$group_id = $data['u_id'] + 10;
			$data['u_single_group_id'] = $group_id;

			// Derniere lecture des messages
			if (!isset($data['u_last_read']))
			{
				$data['u_last_read'] = $data['u_last_visit'];
				$data['u_last_read_flag'] = 1;
			}

			// Creation du groupe unique
			$queries[] = 'INSERT INTO ' . SQL_PREFIX . 'groups (g_id, g_type) VALUES (' . $group_id . ', ' . GROUP_SINGLE . ')';
			$queries[] = 'INSERT INTO ' . SQL_PREFIX . 'groups_users (g_id, u_id, gu_status) VALUES (' . $group_id . ', ' . $data['u_id'] . ', ' . GROUP_USER . ')';

			// Insertion dans les groupes speciaux
			$queries[] = 'INSERT INTO ' . SQL_PREFIX . 'groups_users VALUES (' . GROUP_SPECIAL_USER . ', ' . $data['u_id'] . ', ' . GROUP_USER . ')';
			$group_special_id = null;
			switch ($data['u_auth'])
			{
				case MODO :
					$group_special_id = GROUP_SPECIAL_MODO;
					$default_group_id =	GROUP_SPECIAL_MODO;
					$color = 'class="modo"';
				break;

				case MODOSUP :
					$group_special_id = GROUP_SPECIAL_MODOSUP;
					$default_group_id =	GROUP_SPECIAL_MODOSUP;
					$color = 'class="modosup"';
				break;

				case ADMIN :
					$group_special_id = GROUP_SPECIAL_ADMIN;
					$default_group_id =	GROUP_SPECIAL_ADMIN;
					$color = 'class="admin"';
				break;

				default :
					$default_group_id = GROUP_SPECIAL_USER;
					$color = 'class="user"';
				break;
			}
			$data['u_default_group_id'] = (!isset($data['u_default_group_id'])) ? $default_group_id : $data['u_default_group_id'];
			$data['u_color'] = (!isset($data['u_color'])) ? $color : $data['u_color'];

			if ($group_special_id)
			{
				$queries[] = 'INSERT INTO ' . SQL_PREFIX . 'groups_users VALUES (' . $group_special_id . ', ' . $data['u_id'] . ', ' . GROUP_USER . ')';
			}

			// Mot de passe, login, etc ..
			$password = $data['password'];
			$password = array_map(array(Fsb::$db, 'escape'), $password);
			unset($data['password']);
			$password['u_id'] = $data['u_id'];
			$password['u_autologin_key'] = md5($password['u_id'] . $password['u_login'] . $password['u_password']);
			$queries[] = 'INSERT INTO ' . SQL_PREFIX . 'users_password (' . implode(', ', array_keys($password)) . ') VALUES (\'' . implode('\', \'', $password) . '\')';

			// Insertion du membre
			$data = array_merge($def, array_map(array(Fsb::$db, 'escape'), $data));
			$queries[] = 'INSERT INTO ' . SQL_PREFIX . 'users (' . implode(', ', array_keys($data)) . ') VALUES (\'' . implode('\', \'', $data) . '\')';
		}

		$this->output($queries);

		if (!($state & self::STATE_END))
		{
			$this->offset += $step;
			$this->refresh_with_offset($total);
		}
	}

	/**
	 * Groupes d'utilisateurs
	 *
	 */
	private function page_groups()
	{
		$queries = array();

		$groups = $this->convert_groups();

		$this->_push_data_queries($queries, $groups['groups'], 'groups');
		unset($groups['groups']);

		$this->_push_data_queries($queries, $groups['groups_users'], 'groups_users');
		unset($groups['groups_users']);

		$this->_push_manual_queries($queries, $groups['sql']);

		$this->output($queries);
	}

	/**
	 * Liste des forums
	 *
	 */
	private function page_forums()
	{
		$tree = $this->convert_forums();
		$tree->create_interval();

		$queries = array();
		$queries[] = 'TRUNCATE ' . SQL_PREFIX . 'forums';
		foreach ($tree->plain_data() AS $data)
		{
			$data = array_map(array(Fsb::$db, 'escape'), $data);
			$queries[] = 'INSERT INTO ' . SQL_PREFIX . 'forums (' . implode(', ', array_keys($data)) . ') VALUES (\'' . implode('\', \'', $data) . '\')';
		}

		$this->output($queries);
	}

	/**
	 * Autorisations des forums
	 *
	 */
	private function page_auths()
	{
		$queries = array();
		$queries[] = 'TRUNCATE ' . SQL_PREFIX . 'groups_auth';

		$auths_list = $this->convert_auths();
		foreach ($auths_list['data'] AS $forum_id => $groups)
		{
			foreach ($groups AS $group_id => $auths)
			{
				$auths['f_id'] = $forum_id;
				$auths['g_id'] = $group_id;
				$auths = array_map(array(Fsb::$db, 'escape'), $auths);
				$queries[] = 'INSERT INTO ' . SQL_PREFIX . 'groups_auth (' . implode(', ', array_keys($auths)) . ') VALUES (\'' . implode('\', \'', $auths) . '\')';
			}
		}

		// Requetes manuelles
		$this->_push_manual_queries($queries, $auths_list['sql']);

		$this->output($queries);
	}

	/**
	 * Liste des sujets
	 *
	 */
	private function page_topics()
	{
		$step = $this->config('step_topics');

		$total = $this->count_convert_topics();
		$state = 0;
		if ($this->offset == 0)
		{
			$state |= self::STATE_BEGIN;
		}

		if ($this->offset + $step < $total)
		{
			$state |= self::STATE_MIDDLE;
		}
		else
		{
			$state |= self::STATE_END;
		}

		if ($state & self::STATE_BEGIN)
		{
			$query[] = 'TRUNCATE ' . SQL_PREFIX . 'topics';
		}

		$topics = $this->convert_topics($this->offset, $step, $state);
		$this->_push_convert_queries($query, $topics, 'topics');

		$this->output($query);

		if (!($state & self::STATE_END))
		{
			$this->offset += $step;
			$this->refresh_with_offset($total);
		}
	}

	/**
	 * Liste des messages
	 *
	 */
	private function page_posts()
	{
		$step = $this->config('step_posts');

		$total = $this->count_convert_posts();
		$state = 0;
		if ($this->offset == 0)
		{
			$state |= self::STATE_BEGIN;
		}

		if ($this->offset + $step < $total)
		{
			$state |= self::STATE_MIDDLE;
		}
		else
		{
			$state |= self::STATE_END;
		}

		if ($state & self::STATE_BEGIN)
		{
			$query[] = 'TRUNCATE ' . SQL_PREFIX . 'posts';
		}

		$posts = $this->convert_posts($this->offset, $step, $state);
		$this->_push_convert_queries($query, $posts, 'posts');

		$this->output($query);

		if (!($state & self::STATE_END))
		{
			$this->offset += $step;
			$this->refresh_with_offset($total);
		}
	}

	/**
	 * Liste des messages prives
	 *
	 */
	private function page_mp()
	{
		$step = $this->config('step_posts');

		$total = $this->count_convert_mp();
		$state = 0;
		if ($this->offset == 0)
		{
			$state |= self::STATE_BEGIN;
		}

		if ($this->offset + $step < $total)
		{
			$state |= self::STATE_MIDDLE;
		}
		else
		{
			$state |= self::STATE_END;
		}

		if ($state & self::STATE_BEGIN)
		{
			$query[] = 'TRUNCATE ' . SQL_PREFIX . 'mp';
		}

		$data = $this->convert_mp($this->offset, $step, $state);
		$this->_push_convert_queries($query, $data, 'mp');

		$this->output($query);

		if (!($state & self::STATE_END))
		{
			$this->offset += $step;
			$this->refresh_with_offset($total);
		}
	}

	/**
	 * Sondages du forum
	 *
	 */
	private function page_polls()
	{
		$queries = array();
		$queries[] = 'TRUNCATE ' . SQL_PREFIX . 'poll';
		$queries[] = 'TRUNCATE ' . SQL_PREFIX . 'poll_options';
		$queries[] = 'TRUNCATE ' . SQL_PREFIX . 'poll_result';

		$polls = $this->convert_polls();

		foreach ($polls['data'] AS $poll)
		{
			// Liste des options
			foreach ($poll['options'] AS $opt)
			{
				$opt['t_id'] = $poll['t_id'];
				$opt = array_map(array(Fsb::$db, 'escape'), $opt);
				$queries[] = 'INSERT INTO ' . SQL_PREFIX . 'poll_options (' . implode(', ', array_keys($opt)) . ') VALUES (\'' . implode('\', \'', $opt) . '\')';
			}
			unset($poll['options']);

			// Liste des voters
			foreach ($poll['voters'] AS $vot)
			{
				$row = array(
					'poll_result_u_id' =>	$vot,
					't_id' =>				$poll['t_id'],
				);

				$row = array_map(array(Fsb::$db, 'escape'), $row);
				$queries[] = 'INSERT INTO ' . SQL_PREFIX . 'poll_result (' . implode(', ', array_keys($row)) . ') VALUES (\'' . implode('\', \'', $row) . '\')';
			}
			unset($poll['voters']);

			// Insertion du sondage
			$poll = array_map(array(Fsb::$db, 'escape'), $poll);
			$queries[] = 'INSERT INTO ' . SQL_PREFIX . 'poll (' . implode(', ', array_keys($poll)) . ') VALUES (\'' . implode('\', \'', $poll) . '\')';

			$queries[] = 'UPDATE ' . SQL_PREFIX . 'topics SET t_poll = 1 WHERE t_id = ' . $poll['t_id'];
		}

		// Requetes manuelles
		$this->_push_manual_queries($queries, $polls['sql']);

		$this->output($queries);
	}

	/**
	 * Membres bannis du forum
	 *
	 */
	private function page_bans()
	{
		$queries = array();
		$queries[] = 'TRUNCATE ' . SQL_PREFIX . 'ban';

		$bans = $this->convert_bans();

		$this->_push_convert_queries($queries, $bans, 'ban');

		$this->output($queries);
	}

	/**
	 * Liste des rangs
	 */
	private function page_ranks()
	{
		$queries = array();
		$queries[] = 'TRUNCATE ' . SQL_PREFIX . 'ranks';

		$ranks = $this->convert_ranks();

		$this->_push_convert_queries($queries, $ranks, 'ranks');

		$this->output($queries);
	}

	/**
	 * Copie des images
	 *
	 */
	private function page_copy()
	{
		$dirs = $this->convert_copy();

		$copy = array(
			'ranks' =>		ROOT . 'images/ranks/',
			'avatars' =>	ROOT . 'images/avatars/',
		);

		foreach ($copy AS $type => $dst)
		{
			if (isset($dirs[$type]))
			{
				foreach ($dirs[$type] AS $src)
				{
					if (is_dir($src))
					{
						$fd = opendir($src);
						while ($file = readdir($fd))
						{
							if ($file != '.' && $file != '..' && is_file($src . '/' . $file) && preg_match('#\.(gif|jpg|jpeg|bmp|png)$#i', $file))
							{
								@copy($src . '/' . $file, $dst . '/' . $file);
							}
						}
						closedir($fd);
					}
				}
			}
		}
	}

	/**
	 * Met les queries specifique et automatique dans le tableau des requêtes
	 * @param array $queries ['data', 'sql']
	 * @param array $datas tableau de requetes
	 * @param string $table nom de la table sql
	 */
	private function _push_convert_queries(&$queries, $datas, $table)
	{
		$this->_push_data_queries(&$queries, $datas['data'], $table);
		$this->_push_manual_queries(&$queries, $datas['sql']);
	}

	/**
	 * Met les queries automatique a exécuter dans le tableau des requêtes
	 * @param array $queries
	 * @param array $datas
	 * @param string $table nom de la table sql
	 */
	private function _push_data_queries(&$queries, $datas, $table)
	{
		foreach ($datas AS $data)
		{
			$data = array_map(array(Fsb::$db, 'escape'), $data);
			$queries[] = 'INSERT INTO ' . SQL_PREFIX . $table . ' (' . implode(', ', array_keys($data)) . ') VALUES (\'' . implode('\', \'', $data) . '\')';
		}
	}

	/**
	 * Met les queries specifique a exécuter dans le tableau des requêtes
	 * @param array $queries
	 * @param array $data tableau de requetes
	 */
	private function _push_manual_queries(&$queries, $data)
	{
		// Requetes manuelles
		foreach ($data AS $query)
		{
			$queries[] = $query;
		}
	}
}

/* EOF */
