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
1.0.2:
    -> added update capabilities to merge definitions.
	-> added some rendering tags helper to definitions to give some easy dump for debugging.
	-> included core platform by definition -> this may change in the future:
1.0.3:
    -> now groups has metadat -> icon, description.
	-> entire groups are dynamically registered and evaluated.
	-> added support for updating and extending based on arrays and json
1.0.4:
	-> fixed bug and error in RegisteredPrivGroup - was accepting empty names and not checking correctly if was allready registered.
	-> Improved register - performance wise.
	-> group has improved to support null values of privileges tags.
	-> function names -> allowed() in groups is now is_allowed(). 
	-> function names -> groups() in definitions is now defined_groups(). 
	-> added group method called defined() that returns both true and false tags.
	-> all_granted() improved - now accepts a boolean to implode results, also filters out empty tags.
	-> fixed null overriding on update - now on update null tags will be ignored.
1.0.5
	-> intreduced in definition helper methods if() -> then() for quick inline checks.
    -> added a helper called can() that is shorter for if()->then() to get boolean response.
1.0.6
	-> fixed bug with god flags not set when serializing.
********************************************************************************/
namespace Siktec\Bsik\Privileges;

use \Exception;

/**
 * PrivGroup - defines a a group of privileges tags of some type
 */
abstract class PrivGroup {

	//The group name
	public const NAME  			= null;
	//The group meta
	public const ICON  			= null;
	public const DESCRIPTION  	= null;

	//Defind tags and their states:
	public array  $privileges;
		
	/**
	 * is
	 * checks what is this group name:
	 * @param  string $group_name
	 * @return bool
	 */
	public function is(string $group_name) : bool {
		return static::NAME === $group_name;
	}
		
	/**
	 * meta
	 * default return meta implementation - can and should be overide in groups that has more / less meta tags.
	 * @return array -> associative array meta - value.
	 */
	public static function meta() {
		return [
			"name" 			=> self::NAME,
			"icon" 			=> self::ICON,
			"description" 	=> self::DESCRIPTION
		];
	} 
	/**
	 * has
	 * check if the group has a specific tag defined
	 * that does no take in account the state of this tag
	 * @param  string $tag
	 * @return bool
	 */
	public function has(string $tag) : bool {
		return array_key_exists($tag, $this->privileges); // We use array_key_exists to allow null values.
	}

	/**
	 * isset
	 * check if the group has a specific tag defined
	 * AND that it has a state - which mean it is not null.
	 * @param  string $tag
	 * @return bool
	 */
	public function isset(string $tag) : bool {
		return array_key_exists($tag, $this->privileges) && !is_null($this->privileges[$tag]); // We use array_key_exists to allow null values.
	}

	/**
	 * set
	 * sets all those tags with a specific grant value
	 * @param  bool|string|int|null $grant - all tags will have this grant value
	 * @param  array $tags 
	 * @return void
	 * @throws Exception /E_PLAT_ERROR => when tag is not defined in this group
	 */
	public function set(bool|string|int|null $grant = null, string ...$tags) : void {
		foreach ($tags as $tag) {
			$this->set_priv($tag, $grant);
		}
	}
	
	/**
	 * set_from_array
	 * grant defined allowed tags from an associative array
	 * will ignore unknown tags
	 * @param  array $tags
	 * @return void
	 */
	public function set_from_array(array $tags) : void {
		foreach ($tags as $tag => $state) {
			if (is_string($tag) && $this->has($tag))
				$this->set_priv($tag, $state);
		}
	}
	
	/**
	 * set_from_json
	 * grant defined allowed tags from a json representation pf tags
	 * will ignore unknown tags 
	 * @param  string $json
	 * @return bool - false if decode failed.
	 */
	public function set_from_json(string $json) : bool {
		$tags = json_decode($json, true);
		if (is_array($tags)) {
			$this->set_from_array($tags);
			return true;
		}
		return false;
	}

	/**
	 * set_priv
	 * set a specific tag and its grant status
	 * @param  string $tag
	 * @param  bool|string|int $grant - ['true', 'TRUE', true, 1] are considered as boolean true ? We accept those true to support all the optional returned types when using remote data.
	 * @return void
	 * @throws Exception /E_PLAT_ERROR => when tag is not defined
	 */
	public function set_priv(string $tag, bool|string|int|null $grant) : void {
		if ($this->has($tag)) {
			//We accept those true to support all the optional returned types when using remote data.
			if (in_array($grant, ['true', 'TRUE', true, 1], true))
				$this->privileges[$tag] = true;
			elseif (in_array($grant, ['false', 'FALSE', false, 0], true))
				$this->privileges[$tag] = false;
			else 
				$this->privileges[$tag] = null;
		} else {
            throw new Exception("tried to grant an unknown privilege tag [{$tag}]", \E_PLAT_ERROR);
        }
	}
	
	/**
	 * tags
	 * get all defined tags - only names without values
	 * @return array
	 */
	public function tags() : array {
		return array_keys($this->privileges);
	}
	
	/**
	 * all
	 * get all tags with with there statuses - will include null tags also
	 * @return array
	 */
	public function all() : array {
		return $this->privileges;
	}

	/**
	 * all
	 * get all tags with with there statuses - only true false tags
	 * @return array
	 */
	public function defined() : array {
		return array_filter($this->privileges, fn($v) => !is_null($v));
	}

	/**
	 * granted
	 * get all defined tags that are granted
	 * @return array
	 */
	public function granted() : array {
		return array_keys($this->privileges, true);
	}

	/**
	 * denied
	 * get all defined tags that are denied
	 * @return array
	 */
	public function denied() : array {
		return array_keys($this->privileges, false);
	}

	/**
	 * allowed
	 * check if a specific tag is granted
	 * @param  string $tag
	 * @return bool
	 * @throws Exception /E_PLAT_WARNING => when tag is not defined
	 */
	public function is_allowed(string $tag) : bool {
		if ($this->has($tag)) {
			return $this->privileges[$tag] ? true : false; // we use this condition to force boolean return as null is avalid tag status.
		} 
		throw new Exception("tried to check an unknown privilege tag", \E_PLAT_WARNING);
		return false;
	}


}
