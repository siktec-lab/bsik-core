<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.4
// Creation Date: 2021-03-18
// Copyright 2021, SIKTEC.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->initial
********************************************************************************/
namespace Siktec\Bsik\Privileges;

use \Exception;
use \Siktec\Bsik\Privileges\Default\PrivGod;

/**
 * PrivDefinition
 * a definition group which sets a bundle of privileges groups
 */
abstract class PrivDefinition {

	//Holds all the defined groups - group-name => PrivGroup (of some type)
	public array $groups = [];
	
	//Special flag that sets god-mode enabled 
	public ?bool $god = null;
	
	//Condition Flag:
	private bool $_allow = false;

	/**
	 * defined
	 * return all defined group names in this definition
	 * @return array
	 */
	public function defined_groups() : array {
		return array_keys($this->groups);
	}
	
	/**
	 * all_granted 
	 * returns an array of group with string of tags granted privileges 
	 * @example returns => ["users" => "edit,view"]
	 * @param bool $as_str => will implode tags as a list.
	 * @return array
	 */
	public function all_granted(bool $as_str = false) : array {
		$tags = []; 
		foreach ($this->groups as $name => $group) {
			$tags[$name] = $as_str ? implode(',', $group->granted()) : $group->granted();
		}
		return array_filter($tags);
	}

	/**
	 * all_defined 
	 * returns an array of all granted or declined tags
	 * @example returns => ["users" => ["edit" => true, "view" => false]
	 * @return array
	 */
	public function all_defined() : array {
		$tags = []; 
		foreach ($this->groups as $name => $group) {
			$tags[$name] = $group->defined();
		}
		return $tags;
	}
	
	/**
	 * all_privileges
	 * get an array of all defined groups and their tags + state - only tags that are not null
	 * @return array
	 */
	public function all_privileges() : array {
		$tags = []; 
		foreach ($this->groups as $name => $group) {
			$tags[$name] = $group->all();
		}
		return $tags;
	}
	
	/**
	 * all_meta
	 * get an array of all defined groups and their meta attributes
	 * @return array
	 */
	public function all_meta() : array {
		$groups = []; 
		foreach ($this->groups as $name => $group) {
			$groups[$name] = $group::meta();
		}
		return $groups;
	}

	/**
	 * update 
	 * $with definition overwrite this definition privileges.
	 * will also inherit new group if they are not defined
	 * @param  PrivDefinition|null $with
	 * @return void
	 */
	public function update(?PrivDefinition $with) {
		if (is_null($with))
			return;
		foreach ($with->groups as $name => $group) {
			if ($this->is_defined($name)) {
				$current = $this->group($name);
				foreach ($group->privileges as $tag => $state) {
					if (!is_null($state))
						$current->set_priv($tag, $state);
				} 
			} else {
				$this->define($group);
			}
		}
		if (!is_null($with->god))
			$this->god = $with->god;
	}

	/**
	 * extends 
	 * $defaults are inherited only if they are not allready defined.
	 * @param  PrivDefinition|null $defaults
	 * @return void
	 */
	public function extends(?PrivDefinition $defaults) {
		if (is_null($defaults))
			return;
		foreach ($defaults->groups as $name => $group) {
			if ($this->is_defined($name)) {
				$current = $this->group($name);
				foreach ($group->privileges as $tag => $state) {
					if (!is_null($state) && !$current->isset($tag))
						$current->set_priv($tag, $state);
				} 
			} else {
				$this->define($group);
			}
		}
		if (is_null($this->god))
			$this->god = $defaults->god;
	}
	/**
	 * update_from_arr
	 * take groups defined as array and updates this definition
	 * @param array $groups 
	 * @return void
	 */
	public function update_from_arr(array $groups) : void {
        foreach ($groups as $group => $tags) {
			if (!is_array($tags)) {
				continue;
			}
			if ($this->is_defined($group)) {
				$this->group($group)->set_from_array(is_array($tags) ? $tags : []);
			} elseif ($set = RegisteredPrivGroup::get_class($group)) {
				$new_group = new $set;
				$new_group->set_from_array($tags);
				$this->define($new_group);
			}
        }
    }
	
	/**
	 * update_from_json
	 * take groups defined in json and updates this definition
	 * @param string $json 
	 * @return bool - false if json is not valid
	 */
	public function update_from_json(string $json) : bool {
		$groups = json_decode($json, true);
		if (is_array($groups)) {
			$this->update_from_arr($groups);
			return true;
		}
		return false;
    }

	/**
	 * is_defined
	 * checks if a specific group is defined
	 * @param  string $group
	 * @return bool
	 */
	public function is_defined(string $group) : bool {
		return array_key_exists($group, $this->groups);
	}
	
	/**
	 * define
	 * stores a given set of groups in this definition:
	 * @param  array $groups - PrivGroup array
	 * @return $this
	 */
	public function define(PrivGroup ...$groups) {
		foreach($groups as $group) {
			$this->groups[$group::NAME] = $group;
			if ($group instanceof PrivGod) {
				$this->god = $group->is_allowed("grant");
			}
		}
		return $this;
	}
	
	/**
	 * group
	 * return a defined group object or null
	 * @param  string $name
	 * @return PrivGroup|null
	 */
	public function group(string $name) : PrivGroup|null {
		return $this->groups[$name] ?? null;
	}
	
	/**
	 * check
	 * internal check that checks a gate definition against an ask definition
	 * if ask has enough required privileges it will return true
	 * @param  PrivDefinition $gate
	 * @param  PrivDefinition $ask
	 * @param  array $messages - will be filled with messages of required privileges if they are missing
	 * @return bool
	 */
	protected static function check(PrivDefinition $gate, PrivDefinition $ask, array &$messages) : bool {

		$allowed = true;

		//First check god modes:
		if ($ask->god === true) {
			return true;
		} elseif ($gate->god === true) {
			$messages[] = "'god' privileges is required";
			return false;
		}
		//Iterate over privileges:
		foreach ($gate->groups as $group_name => $gate_group) {
			$gate_tags = $gate_group->granted();
			if (!empty($gate_tags)) {
				$ask_group = $ask->group($group_name);
				if (!is_null($ask_group)) {
					$ask_tags = $ask_group->granted();
					$needed   = implode(',',array_diff($gate_tags, $ask_tags));
					if (!empty($needed)) {
						$messages[] = "required '$needed' privileges tags of group '$group_name'";
						$allowed = false;
					}
				} else {
					$messages[] = "'$group_name' group privileges is required";
					$allowed = false;
				}
			}
		}
		return $allowed;
	}
	
	/**
	 * has_privileges
	 * @param  PrivDefinition $against
	 * @param  array $messages - will be filled with messages of required privileges if they are missing
	 * @return bool
	 */
	public function has_privileges(PrivDefinition $against, array &$messages = []) : bool {
		return false;
	}
		
	/**
	 * if
	 * sets and check the required tags - if ok the _allow flag will be raised
	 * @param  array $tags => ["group.tag1", "group.tag3"]
	 * @return PrivDefinition
	 */
	public function if(...$tags) : PrivDefinition {
		//Enforce god:
		if ($this->god === true) {
			$this->_allow = true;
		} else {
			$this->_allow = false; // reset check:
			foreach ($tags as $tagStr) {
				[$group_name, $tag] = explode(".", $tagStr);
				$group = $this->group($group_name);
				try {
					if (is_null($group) || !$group->is_allowed($tag)) {
						return $this;
					}
				} catch(Exception $t) {
					//This will prevent undefined tags to be thrown
					//and consider them as false e.g not met.
					return $this;
				}
			}
			$this->_allow = true;
		}
		return $this;
	}
		
	/**
	 * can
	 * shorter method to check if this definition has some required tags
	 * similar to -> $this->if(...$tags)->then(true, false);
	 * @param  array $tags => ["group.tag1", "group.tag3"]
	 * @return bool
	 */
	public function can(...$tags) : bool {
		return $this->if(...$tags)->then(true, false);
	}
	/**
	 * then
	 * an execution block to be called after if() that will check if _allow is true and 
	 * executes do otherwise else will be executed or returned
	 * @param  mixed $do    - a callable or some other type to be returned
	 * @param  mixed $else	- a callable or some other type to be returned
	 * @param  mixed $args  - arguments to be passed to the do and else blocks
	 * @return mixed
	 */
	public function then(mixed $do = null, mixed $else = null, array $args = []) : mixed {
		if ($this->_allow) {
			return is_callable($do) 	? call_user_func_array($do, $args) : $do;
		} else {
			return is_callable($else) 	? call_user_func_array($else, $args) : $else;
		}
	}

	/**
	 * safe_unserialize
	 * safely unserialize a blob (serialized) and return the Definition object or null
	 * @param  string|null $blob
	 * @return PrivDefinition|null
	 */
	public static function safe_unserialize(?string $blob) : PrivDefinition|null {
		$obj = null;
		try {
			if (!empty($blob))
				$obj = unserialize($blob);
		} catch (Exception $e) {
			return null;
		}
		if ( $obj instanceof PrivDefinition ) {
			//Set god flag if needed:
			if ($obj->can("god.grant")) {
				$obj->god = true;
			}
			return $obj;
		}
		return null;
	}
	
	/**
	 * str_tag_list
	 * handy method to print granted groups with corresponding granted tags:
	 * @param  PrivDefinition $definition
	 * @param  string $prefix
	 * @param  string $suffix
	 * @return string
	 */
	public static function str_tag_list(PrivDefinition $definition, string $prefix = "", string $suffix = "") {
		$group_tags = $definition->all_granted(true);
		array_walk($group_tags, function(&$tags, $group) use ($prefix, $suffix) { 
			$tags = $prefix.$group.' > '.$tags.$suffix;
		});
		return implode($group_tags);
	} 
}
