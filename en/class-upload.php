<?php
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Veign, LLC (http://www.veign.com)
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ** Copyright (C) 2007 - 2008 Veign, All rights reserved
// **		Authored By: Chris Hanscom
// **
// **		This library is copyrighted software by Veign; you can not
// **		redistribute it and/or modify it in any way without expressed written
// **		consent from Veign or Author.
// **
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// Modified from code provided by:
// 		PHP Class to handle file uploads.
// 		@author Mhyk C.D. - http://www.phphybrid.com
// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// File Size: Bytes
define('UPLOAD_SIZE_BYTES',1);

// File Size: Megabytes
define('UPLOAD_SIZE_MBYTES',2);

// Error Code: No file selected
define('UPLOAD_ERROR_NOFILE',1);

// Error Code: File exceeds the file size limit
define('UPLOAD_ERROR_SIZE',2);

// Error Code: File Type is invalid
define('UPLOAD_ERROR_TYPE',3);

// Error Code: Error moving file
define('UPLOAD_ERROR_MOVING',4);


class Upload
{
	// The directory where uploaded files will be placed (string)
	var $uploadDir;

	// Uploaded file's name (string)
	var $fileName;

	// Uploaded file's size (int)
	var $fileSize;

	// Uploaded file's type
	var $fileType;

	// The file's temporary location (string)
	var $tempLocation;
	
	// The maximum file size alloted for this file in bytes (int)
	var $maxFileSize;

	// The allowed file extensions (array)
	var $allowedTypes = array();

	// Container of the error message (string)
	var $errorMsg;
	
	// if this var is true the file copy get a new name (bol)
	var $renameFile; 

	// change the permission of the file
	var $newPermission; 

	/**
	 * Class constructor.
	 * @access public
	 * @param string $fieldName the name of the file input field
	 * @param string $uploadDir Directory to store uploaded files
	 */
	function Upload($fieldName, $uploadDir)
	{
		if ($this->getVersion() == 4) {
			if (! isset($_FILES[$fieldName])) {
				$this->setErrorMessage(UPLOAD_ERROR_NOFILE);
				return false;
			}
			$file = $_FILES[$fieldName];
		} else {
			if (! isset($HTTP_POST_FILES[$fieldName])) {
				$this->setErrorMessage(UPLOAD_ERROR_NOFILE);
				return false;
			}
			$file = $HTTP_POST_FILES[$fieldName];
		}
		
		$this->rename_file = false;
		$this->uploadDir = $uploadDir;
		$this->fileName = basename($file['name']);
		$this->fileSize = $file['size'];
		$this->tempLocation = $file['tmp_name'];
		$this->fileType = $file['type'];
	}
	
	/**
	 * Sets the acceptable maximum file size : Bytes of Megabytes<br />
	 * Use Constants UPLOAD_SIZE_BYTES || UPLOAD_SIZE_MBYTES
	 * @access public
	 * @param int $size
	 * @param int $sizeType
	 */
	function setMaxFileSize($size,$sizeType=UPLOAD_SIZE_MBYTES)
	{
		if ($sizeType == UPLOAD_SIZE_MBYTES) {
			$bytes = $this->toBytes($size);
			$this->maxFileSize = $bytes;
		} else {
			$this->maxFileSize = intval($size);
		}
	}
	
	/**
	 * Sets an array of acceptable file extensions
	 * @access public
	 * @param array $types
	 */
	function setTypes($types)
	{
		if (! is_array($types)) {
			trigger_error('Invalid parameter type. Expecting array ',E_USER_WARNING);
		}
		$this->allowedTypes = $types;
	}
	
	/**
	 * Checks if a file has been uploaded
	 * @access private
	 * @return boolean
	 */
	function isUploaded()
	{
		$temp = $this->tempLocation;
		if (@is_uploaded_file($temp)) {
			return true;
		} else {
			$this->setErrorMessage(UPLOAD_ERROR_NOFILE);
			return false;
		}
	}
	
	/**
	 * Attempts to move the file to the defined location
	 * @access private
	 * @return boolean
	 */
	function moveFile()
	{
		// Check for renaming to a unique name
		if ($this->renameFile) {
			// Set the filename to a unique id
			$newName= str_replace('-','',$this->uuid());
			sleep(1);
			$newName .= $this->getExtension($this->fileName);
			
			// Store the new name
			$this->fileName = $newName;
		} else {
			$newName = $this->fileName;
		}
		
		$destination = $this->uploadDir;
		$temp = $this->tempLocation;
		if (@move_uploaded_file($temp,$destination.$newName)) {
			// see if we should change permission
			if (strlen($this->newPermission) > 0) {
				chmod($destination.$newName, $this->newPermission);
			}
			return true;
		} else {
			$this->setErrorMessage(UPLOAD_ERROR_MOVING);
			return false;
		}
	}
	
	// Extract the extension from a passed filename
	function getExtension($from_file) {
		$ext = strtolower(strrchr($from_file,"."));
		return $ext;
	}
	
	/**
	 * Method to check for a valid file size
	 * @access private
	 * @return boolean
	 */
	function checkSize()
	{
		$maxFileSize = $this->maxFileSize;
		$fileSize = $this->fileSize;
		if ($fileSize > $maxFileSize) {
			$this->setErrorMessage(UPLOAD_ERROR_SIZE);
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * Checks for a valid file extension
	 * @access private
	 * @return boolean
	 */
	function checkType()
	{
		$types = $this->allowedTypes;
		$file = $this->fileName;
		
		if ($types[0] == '*') {
			// All types are permitted
			return true;
		}
		
		foreach ($types as $type) {
			if (ereg($type.'$',$file)) {
				return true;
			}
		}
		$this->setErrorMessage(UPLOAD_ERROR_TYPE);
		return false;
	}
	
	/**
	 * Method to get the file's name
	 * @access public
	 * @return string
	 */
	function getFileName()
	{
		return $this->fileName;
	}
	
	/**
	 * Method to get the file's size : Use constants UPLOAD_SIZE_BYTES || UPLOAD_SIZE_MBYTES(default)
	 * @access public
	 * @param int $sizeType
	 * @return int / float
	 */
	function getFileSize($sizeType=UPLOAD_SIZE_MBYTES)
	{
		$bytes = $this->fileSize;
		if ($sizeType == UPLOAD_SIZE_MBYTES) {
			return $this->toMBytes($bytes);
		} else {
			return $bytes;
		}
	}
	
	/**
	 * Method to convert byte to MegaBytes
	 * @access private
	 * @param int $bytes
	 * @param int [optional] $decimal Decimal places to show
	 * @return float
	 */
	function toMBytes($bytes,$decimal=2)
	{
		// KB
		$kb = ($bytes * 8) / 1024;
		// MB
		$mb = ($kb /1024) / 8;
	
		return round($mb,$decimal);
	}

	/**
	 * Method to convert MB to Bytes
	 * @access private
	 * @param float $mb
	 * @return int
	 */
	function toBytes($mb)
	{
		$bytes = ((($mb * 8) * 1024) * 1024) / 8;
		return $bytes;
	}
	
	/**
	 * Process the file
	 * @return boolean
	 */
	function process()
	{
		if (! $this->isUploaded())
			return false;
		if (! $this->checkSize())
			return false;
		if (! $this->checkType())
			return false;
		if (! $this->moveFile())
			return false;
		return true;
	}
	
	/**
	 * Method to set the error message
	 * @access private
	 * @param int $code The error code
	 */
	function setErrorMessage($code)
	{
		switch ($code)
		{
			case UPLOAD_ERROR_SIZE:
			$msg = 'File exceeded the maximum file size.';
			break;
			
			case UPLOAD_ERROR_TYPE:
			$msg = 'Invalid file type.';
			break;
			
			case UPLOAD_ERROR_NOFILE:
			$msg = 'No file selected or file is too big.';
			break;
			
			case UPLOAD_ERROR_MOVING:
			$msg = 'Error moving file.';
			break;
			
			default:
			$msg = 'Unknown Error.';
		}
		$this->errorMsg = $msg;
	}
	
	/**
	 * Method to get the error message
	 * @access public
	 * @return string The error message
	 */
	function getMessage()
	{
		return $this->errorMsg;
	}
	
	/**
	 * Method to get PHP's Version<br />
	 * If version is 4.1.0 and above, use $_FILES<br />
	 * If version is below 4.1.0, use $HTTP_POST_FILES
	 * @access private
	 * @return int Version
	 */
	function getVersion()
	{
		$version = phpversion();
		$version = explode('.',$version);
		
        if ( ($version[0]>4) ||($version[0] = 4 && $version[1] >= 1) ){
            return 4;
        } else {
            return 3;
        }
	}
	
	function uuid()	{
	   return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
	       mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
	       mt_rand( 0, 0x0fff ) | 0x4000,
	       mt_rand( 0, 0x3fff ) | 0x8000,
	       mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
	}

}
?>