<?php 

class Batch_log_hotelinfo extends CI_Log {

	public function __construct(){
		parent::__construct();
	}

	public function write_log_batch($msg) {
		$filepath = $this->_log_path . 'hi_' . date ( 'Ymd' ) . '.log';
		$message = '';
		if (! $fp = @fopen( $filepath, FOPEN_WRITE_CREATE )) {
			return FALSE;
		}
		
		$message .= date ( 'Y-m-d H:i:s' ) . '　：　' . $msg . "\n";
		
		flock ( $fp, LOCK_EX );
		fwrite ( $fp, $message );
		flock ( $fp, LOCK_UN );
		fclose ( $fp );
		
		@chmod ( $filepath, FILE_WRITE_MODE );
		return TRUE;
	}
}
?>