<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
	
	class Cki_mblist_ft extends EE_Fieldtype 
	{
		
		var $info = array(
			'name'		=> 'CKI Member List',
			'version'	=> '1.0'
		);
		
		var $has_array_data = TRUE;
		
		function Cki_mblist_ft()
		{
			parent::EE_Fieldtype();
		}
		//END
		
		
		
		function display_field($data)
		{
			$text_direction = ($this->settings['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr';
			$member_list = array();
			$deleted_user_message = '';
			
			$this->EE->db->select('member_id, screen_name');
			$q = $this->EE->db->get('exp_members');
			
			//Add a blank option as default
			$member_list[''] = "-"; 
			
			//Setup the member list array to send to the form_dropdwon function
			foreach($q->result_array() as $member)
			{
				$member_list[$member['member_id']] = $member['screen_name'];
			}
			
			//Quickly check to see (if on the EDIT page) that the previously selected member still exists
			if(!array_key_exists($data, $member_list) && $data != '')
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
			$tags = array();
			
			//Double tag 
			if($tagdata !== FALSE)
			{
				if($data)
				{
					$tagdata = $this->EE->functions->prep_conditionals($tagdata, $data);
					$tagdata = $this->EE->functions->var_swap($tagdata, $data);
				}
				
				return $tagdata;

			//Single tags
			}else{
				//Check everything is in order and the requested array key exists
				if($data !== FALSE && isset($params['get']) && array_key_exists($params['get'], $data))
				{
					return $data[$params['get']];
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
				$this->EE->db->select('member_id');
				$this->EE->db->limit(1);
				$q = $this->EE->db->get('exp_members');
				
				if($q->num_rows() == 0)
				{
					return "The Member you have selected does not exist";
				}else{
					return TRUE;
				}
			}
		}
		//END
		
		function save_settings($data)
		{
			// nothing
		}
		//END
	}
	//END CLASS
?>