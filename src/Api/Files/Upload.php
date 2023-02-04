<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.0
// Creation Date: 2021-03-16
// Copyright 2021, SIKTEC.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.0:
    ->initial
*******************************************************************************/
namespace Siktec\Bsik\Api\Files;

class Upload {

	/**
	 * files array for multiple
	 * @var array
	 */
	public $files = array();

	/**
	 * file for single
	 * @var [type]
	 */
	public $file;

	/**
	 * raw data of the file upload
	 * @var [type]
	 */
	private $_raw;

	/**
	 * construct the class
	 * @param array $files_data  data from $_FILES['name']
	 * @param array  $validations validation array
	 */
	public function __construct($files_data, $validations = array()) {
		$this->_raw = $files_data;
		if ($this->_raw ) {
			// check if it's multiple or single file upload
			if ($this->_is_multiple()) {
				foreach ($this->_raw["error"] as $key => $error) {
			        $file_info = new \stdClass;
					$file_info->name = $this->_raw['name'][$key];
					$file_info->type = $this->_raw['type'][$key];
					$file_info->tmp_name = $this->_raw['tmp_name'][$key];
					$file_info->error = $error;
					$file_info->size = $this->_raw['size'][$key];

					$file = new File($file_info, $validations);
					$this->files[] = $file;
				}
				// let the single "file" property be the first file (index 0)
				if ($this->files) $this->file = $this->files[0];
			} else {
				$file_info = new \stdClass;
				foreach ($this->_raw as $key => $value) {
					$file_info->{$key} = $value;
				}
				$file = new File($file_info, $validations);
				$this->files[] = $file;
				$this->file = $this->files[0];
			}
			
		}
	}

	/**
	 * loop through each file
	 * @param  closure $callback callback function for each file
	 */
	public function each($callback) {
		if (!$this->_is_closure($callback)) return;
		foreach ($this->files as $file) {
			$callback($file);
		}
	}

	/**
	 * check if the upload data is multiple or not
	 * @return boolean true if multiple, otherwise false
	 */
	private function _is_multiple() {
		return is_array($this->_raw["name"]);
	}

	/**
	 * check if a value is a closure object
	 * @param  object  $obj object to test
	 * @return boolean      true if it's a closure object, otherwise false
	 */
	private function _is_closure($obj) {
		return (is_object($obj) && ($obj instanceof \Closure));   
	}
}

