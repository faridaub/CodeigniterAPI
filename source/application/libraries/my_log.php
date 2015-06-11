<?php 

class My_log extends CI_Log {

	public function __construct(){
		parent::__construct();
	}

	public function write_log_batch($msg) {
		$filepath = $this->_log_path . 'BATCH-LOG-' . date ( 'Y-m-d' ) . '.php';
		$message = '';
		if (! file_exists ( $filepath )) {
			$message .= "<" . "?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?" . ">\n\n";
		}
		
		if (! $fp = @fopen( $filepath, FOPEN_WRITE_CREATE )) {
			return FALSE;
		}
		
		$message .= date ( $this->_date_fmt ) . ' --> ' . $msg . "\n";
		
		flock ( $fp, LOCK_EX );
		fwrite ( $fp, $message );
		flock ( $fp, LOCK_UN );
		fclose ( $fp );
		
		@chmod ( $filepath, FILE_WRITE_MODE );
		return TRUE;
	}
}
?>