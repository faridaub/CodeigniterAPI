<?php 

class Batch_log_vacancyinfo extends CI_Log {

	public function __construct(){
		parent::__construct();
	}

	public function write_log_batch($msg,$type) {
		$filepath = $this->_log_path . 'vi_all_' . date ( 'Ymd' ) . '.log';
		if($type != 0){
			$filepath = NULL;
			$filepath = $this->_log_path . 'vi_' . date ( 'Ymd' ) . '.log';
		}

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