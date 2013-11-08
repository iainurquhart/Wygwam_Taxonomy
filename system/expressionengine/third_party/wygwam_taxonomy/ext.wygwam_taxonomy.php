<?php if (! defined('APP_VER')) exit('No direct script access allowed');

if( !function_exists('ee') )
{
	function ee()
	{
		static $EE;
		if ( ! $EE) $EE = get_instance();
		return $EE;
	}
}


/**
 * Wygwam Taxonomy Links
 * 
 * @author    Iain Urquhart <shout@iain.co.nz>
 * @copyright Copyright (c) 2013 Iain Urquhart
 * @license   MIT - http://opensource.org/licenses/MIT 
 */
class Wygwam_taxonomy_ext {

	var $name           = 'Wygwam Taxonomy Links';
	var $version        = '1.0';
	var $description    = 'Adds a Taxonomy Link Type to Wygwam’s Link dialog';
	var $settings_exist = 'n';
	var $docs_url       = '';
	var $settings       = array();

	/**
	 * Class Constructor
	 */
	function __construct($settings = '')
    {
        $this->settings = $settings;
    }

	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 */
	function activate_extension()
	{
		$this->settings = array(
			'license_key'   => ''
		);
		// add the row to exp_extensions
		ee()->db->insert('extensions', array(
			'class'    => __CLASS__,
			'method'   => 'wygwam_config',
			'hook'     => 'wygwam_config',
			'settings' => serialize($this->settings),
			'priority' => 10,
			'version'  => $this->version,
			'enabled'  => 'y'
		));
	}

	/**
	 * Update Extension
	 */
	function update_extension($current = '')
	{
		// Nothing to change...
		return FALSE;
	}

	/**
	 * Disable Extension
	 */
	function disable_extension()
	{
		// Remove all Wygwam_template_links_ext rows from exp_extensions
		ee()->db->where('class', __CLASS__)
		             ->delete('extensions');
	}

	// --------------------------------------------------------------------

	/**
	 * wygwam_config hook
	 */
	function wygwam_config($config, $settings)
	{
		// If another extension shares the same hook,
		// we need to get the latest and greatest config
		if (ee()->extensions->last_call !== FALSE)
		{
			$config = ee()->extensions->last_call;
		}

		require_once PATH_THIRD .'taxonomy/models/taxonomy_model.php';
        ee()->wygwam_taxonomy = new Taxonomy_model;

        // fetch our trees
        $trees = ee()->db->get('taxonomy_trees');
        $index = ee()->functions->fetch_site_index();

        foreach($trees->result_array() as $tree)
        {	
        	// set the tree
        	ee()->wygwam_taxonomy->set_table($tree['id']);

        	// fetch our nodes
        	$nodes = ee()->wygwam_taxonomy->get_flat_tree();

        	foreach($nodes as $node)
        	{
        		// get the full url for the node
        		$url = ee()->wygwam_taxonomy->build_url($node);

        		// is the node an insite link
        		if (strpos($url, $index) !== FALSE)
        		{
        			// wrap in {path} for portability
        			$url = '{path='.str_replace($index, '', $url).'}';
        		}

        		$link = array(
					'label_depth' => $node['depth']+1,
					'label'       => $node['label'],
					'url'         => $url
				);

				$config['link_types'][$tree['label']][] = $link;
        	}

        }

		return $config;
	}
}
