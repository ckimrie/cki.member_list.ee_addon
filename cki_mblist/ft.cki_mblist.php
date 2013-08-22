<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(PATH_THIRD . 'cki_mblist/config.php');
	
/**
* 
*/
class Cki_mblist_ft extends EE_Fieldtype
{
	//Needed in order to get the fieldtype to work as a single AND tag pair
	var $has_array_data = TRUE;
	
	var $info	=	array(
				'name'		=>	CKI_MBLIST_NAME,
				'version'	=>	CKI_MBLIST_VER
	);
	
	function Cki_mblist_ft()
	{
		parent::__construct();

		$this->EE->lang->loadfile(CKI_MBLIST_KEY);
	}

	/*
	 * param $data mixed	Previously saved cell data
	 */
	function display_field($data)
	{
		$text_direction = ($this->settings['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr';
		$member_list = array();
		$deleted_user_message = '';
		
		$this->EE->db->select('group_title, exp_members.member_id, screen_name');
		$this->EE->db->from('exp_members');
		$this->EE->db->join('exp_member_groups', 'exp_members.group_id = exp_member_groups.group_id');
		$this->EE->db->join('exp_member_data', 'exp_member_data.member_id = exp_members.member_id');
		$this->EE->db->order_by('exp_member_groups.group_id asc, exp_members.screen_name');
		if($this->settings[CKI_MBLIST_KEY]['group_ids']) {
			$this->EE->db->where_in('exp_members.group_id', explode('|', $this->settings[CKI_MBLIST_KEY]['group_ids'])); 
		}
		$q = $this->EE->db->get();
		
		//Create a blank option
		$member_list[''] = "None";
		
		//Setup the member list array to send to the form_dropdown function
		foreach($q->result_array() as $member)
		{
			$member_list[$member['group_title']][$member['member_id']] = $member['screen_name'];
			$member_id_array[$member['member_id']] = $member['screen_name'];
		}
		
		//Quickly check to see (if on the EDIT page) that the previously selected member still exists
		if(!array_key_exists($data, $member_id_array) && $data != '')
		{
			//If not, append a warning message
			$deleted_user_message = "&nbsp;<span class='notice'>Selected member no longer exists.</span>";
		}
		
		return form_dropdown($this->field_name, $member_list, $data, 'dir="'.$text_direction.'" id="'.$this->field_id.'"').$deleted_user_message;
	}
	//END
	
	
	// ====================================
	// = Get the data out of the database =
	// ====================================
	
	function pre_process($data)
	{
		$this->EE->db->select('*');
		$this->EE->db->from('exp_members');
		$this->EE->db->join('exp_member_groups', 'exp_members.group_id = exp_member_groups.group_id');
		$this->EE->db->join('exp_member_data', 'exp_member_data.member_id = exp_members.member_id');
		$this->EE->db->limit(1);
		$this->EE->db->where('exp_members.member_id', $data);
		$q = $this->EE->db->get();
		
		if($q->num_rows())
		{
			$qa = $q->result_array();
			return $qa[0];
		}else{
			return FALSE;
		}
		
	}
	
	
	// ============================
	// = Parse the front end data =
	// ============================
	
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		//Promote the use of the "show" parameter to select member data,
		//but keep backward compatibility by allowing "get" to still be used
		if (isset($params['show']))
		{
			$params['get'] = $params['show'];
		}
		
		//Check everything is in order and the requested array key exists
		if($data !== FALSE && isset($params['get']) && array_key_exists($params['get'], $data))
		{
			return $data[$params['get']];
		}else{
			if(is_array($data) && array_key_exists('member_id', $data))
			{
				return $data['screen_name'];
			}else{
				return FALSE;
			}
			
		}
	}
	
	
	// ====================================
	// = Validate the drop down selection =
	// ====================================
	
	function validate($data)
	{
		//Check that that a selection has been made
		if($data != '')
		{
			//Query the database to see if selected member exists
			$q = $this->EE->db->get_where('exp_members', array('member_id' => $data), 1);
			
			if($q->num_rows() ===  1)
			{
				return TRUE;
			}else{
				return "The Member you have selected does not exist";
			}
		}
	}
	//END

	function display_settings($data)
	{
		$this->EE->db->select('group_id, group_title');
		$this->EE->db->from('exp_member_groups');
		$this->EE->db->order_by('group_id asc');
		$q = $this->EE->db->get();
		
		$field_options = array();
		
		//Setup the member list array to send to the form_dropdown function
		foreach($q->result_array() as $group)
		{
			$field_options['group_ids'][$group['group_id']] = $group['group_title'];
		}
		
		// is this a new field?
		$field_values = (array_key_exists(CKI_MBLIST_KEY, $data)) ?
			$data[CKI_MBLIST_KEY] :
			$this->_normalise_settings()
		;
		
		$this->EE->table->add_row(
			'<strong>' . lang('group_ids_label') . '</strong><br />' . lang('group_ids_label_notes'),
			form_multiselect('cki_mblist[group_ids][]', $field_options['group_ids'], explode('|', $field_values['group_ids']))
		);

	}
	
	function save_settings($data)
	{
		return array(CKI_MBLIST_KEY => $this->_normalise_settings($_POST[CKI_MBLIST_KEY], TRUE));
	}
	//END
	
	function install()
	{
		//nothing	
	}
	
	function uninstall()
	{
		//nothing
	}

	/**
	 * Fetch from array
	 *
	 * This is a helper function to retrieve values from an array
	 * It has been borrowed, verbatim, from EE->input
	 *
	 * @access	private
	 * @param	array
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function _fetch_from_array(&$array = array(), $index = '', $xss_clean = FALSE)
	{
		if ( ! isset($array[$index]))
		{
			return FALSE;
		}

		if ($xss_clean === TRUE)
		{
			return $this->EE->security->xss_clean($array[$index]);
		}

		return $array[$index];
	}
	// --------------------------------------------------------------------

	/**
	 * Normalise Settings
	 * Ensures all setting values are acceptable formats/ranges before saving
	 * If passed array is empty, it returns an array of default settings
	 *
	 * @param	array
	 * @param	bool
	 * @return	array settings
	 */
	function _normalise_settings(&$array = array(), $xss_clean = FALSE)
	{
		return array(
			'group_ids'	=> ($this->_fetch_from_array($array, 'group_ids', $xss_clean)) ? implode('|', $this->_fetch_from_array($array, 'group_ids', $xss_clean)) : ''
		);
	}
}

	//END CLASS
?>