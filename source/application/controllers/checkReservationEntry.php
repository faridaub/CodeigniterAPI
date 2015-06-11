<?php
class CheckReservationEntry extends CI_Controller {
	
	function __construct(){
		parent::__construct();
		$this->load->library('xmlrpc');

		if(!$this->load->is_loaded('xmlrpc')){
			echo "Error";
		}
	}

	function index(){
		$host 		= "http://172.31.64.38/appbservice/services";
		$port 		= "8080";
		$method 	= "com.toyokoinn.api.service.SmartphoneApplicationReservationService.checkReservationEntry";
		$this->xmlrpc->server($host,$port);
		$this->xmlrpc->method($method);
		
		
		//3. connection way
		//-------------------------------------------------------------------------------------
		
		$request_data =array(
				array("applctnVrsnNmbr"=>array('applctnVrsnNmbr'=>"applctnVrsnNmbr"),'string'),
				array("lngg"=>array('lngg'=>"lngg"),'string'),
				array("nmbrRms"=>array('nmbrRms'=>"nmbrRms"),'string'),
				array("ttlPrc"=>array('ttlPrc'=>"ttlPrc"),'string'),
				array("ttlPrcIncldngTax"=>array('ttlPrcIncldngTax'=>"ttlPrcIncldngTax"),'string'),
				array('RoomReservationInformation'=>array(
						array(
							array("srlNmbr"=>array('srlNmbr'=>"srlNmbr"),'string'),
							array("htlCode"=>array('htlCode'=>"htlCode"),'string'),
							array("rsrvtnNmbr"=>array('rsrvtnNmbr'=>"rsrvtnNmbr"),'string'),
							array("roomType"=>array('roomType'=>"roomType"),'string'),
							array("chcknDate"=>array('chcknDate'=>"chcknDate"),'string'),
							array("chcknTime"=>array('chcknTime'=>"chcknTime"),'string'),
							array("nmbrNghts"=>array('nmbrNghts'=>"nmbrNghts"),'string'),
							array("nmberPpl"=>array('nmberPpl'=>"nmberPpl"),'string'),
							array("mmbrshpFlag"=>array('mmbrshpFlag'=>"mmbrshpFlag"),'string'),
							array("ecoFlag"=>array('ecoFlag'=>"ecoFlag"),'string'),
							array("vodFlag"=>array('vodFlag'=>"vodFlag"),'string'),
							array("bsnssPackFlag"=>array('bsnssPackFlag'=>"bsnssPackFlag"),'string'),
							array("bsnssPackType"=>array('bsnssPackType'=>"bsnssPackType"),'string'),
							array("fmlyName"=>array('fmlyName'=>"fmlyName"),'string'),
							array("frstName"=>array('frstName'=>"frstName"),'string'),
							array("ntnltyCode"=>array('ntnltyCode'=>"ntnltyCode"),'string'),
							array("phnNmbr"=>array('phnNmbr'=>"phnNmbr"),'string'),
							array("mmbrshpNmbr"=>array('mmbrshpNmbr'=>"mmbrshpNmbr"),'string'),
							array("ecoChckn"=>array('ecoChckn'=>"ecoChckn"),'string'),
							array("ttlPrc"=>array('ttlPrc'=>"ttlPrc"),'string'),
							array("ttlPrcIncldngTax"=>array('ttlPrcIncldngTax'=>"ttlPrcIncldngTax"),'string'),
							array('dlyInfrmtnList'=>array(
									array(
											array("trgtDate"=>array('trgtDate'=>"trgtDate"),'string'),
											array("prc"=>array('prc'=>"prc"),'string'),
											array("optionPrc"=>array('optionPrc'=>"optionPrc"),'string'),
											array("sbttlPrc"=>array('sbttlPrc'=>"sbttlPrc"),'string'),
											array("sbttlPrcIncldngTax"=>array('sbttlPrcIncldngTax'=>"sbttlPrcIncldngTax"),'string'),
											array("ecoFlag"=>array('ecoFlag'=>"ecoFlag"),'string'),
											array("class"=>array('class'=>"class"),'string'),
									),	array(
											array("trgtDate"=>array('trgtDate'=>"trgtDate"),'string'),
											array("prc"=>array('prc'=>"prc"),'string'),
											array("optionPrc"=>array('optionPrc'=>"optionPrc"),'string'),
											array("sbttlPrc"=>array('sbttlPrc'=>"sbttlPrc"),'string'),
											array("sbttlPrcIncldngTax"=>array('sbttlPrcIncldngTax'=>"sbttlPrcIncldngTax"),'string'),
											array("ecoFlag"=>array('ecoFlag'=>"ecoFlag"),'string'),
											array("class"=>array('class'=>"class"),'string'),
									),
									array(
											array("trgtDate"=>array('trgtDate'=>"trgtDate"),'string'),
											array("prc"=>array('prc'=>"prc"),'string'),
											array("optionPrc"=>array('optionPrc'=>"optionPrc"),'string'),
											array("sbttlPrc"=>array('sbttlPrc'=>"sbttlPrc"),'string'),
											array("sbttlPrcIncldngTax"=>array('sbttlPrcIncldngTax'=>"sbttlPrcIncldngTax"),'string'),
											array("ecoFlag"=>array('ecoFlag'=>"ecoFlag"),'string'),
											array("class"=>array('class'=>"class"),'string'),
									)
							),'struct'),
							array("class"=>array('class'=>"class"),'string')
						),	
						array(
							array("srlNmbr"=>array('srlNmbr'=>"srlNmbr"),'string'),
							array("htlCode"=>array('htlCode'=>"htlCode"),'string'),
							array("rsrvtnNmbr"=>array('rsrvtnNmbr'=>"rsrvtnNmbr"),'string'),
							array("roomType"=>array('roomType'=>"roomType"),'string'),
							array("chcknDate"=>array('chcknDate'=>"chcknDate"),'string'),
							array("chcknTime"=>array('chcknTime'=>"chcknTime"),'string'),
							array("nmbrNghts"=>array('nmbrNghts'=>"nmbrNghts"),'string'),
							array("nmberPpl"=>array('nmberPpl'=>"nmberPpl"),'string'),
							array("mmbrshpFlag"=>array('mmbrshpFlag'=>"mmbrshpFlag"),'string'),
							array("ecoFlag"=>array('ecoFlag'=>"ecoFlag"),'string'),
							array("vodFlag"=>array('vodFlag'=>"vodFlag"),'string'),
							array("bsnssPackFlag"=>array('bsnssPackFlag'=>"bsnssPackFlag"),'string'),
							array("bsnssPackType"=>array('bsnssPackType'=>"bsnssPackType"),'string'),
							array("fmlyName"=>array('fmlyName'=>"fmlyName"),'string'),
							array("frstName"=>array('frstName'=>"frstName"),'string'),
							array("ntnltyCode"=>array('ntnltyCode'=>"ntnltyCode"),'string'),
							array("phnNmbr"=>array('phnNmbr'=>"phnNmbr"),'string'),
							array("mmbrshpNmbr"=>array('mmbrshpNmbr'=>"mmbrshpNmbr"),'string'),
							array("ecoChckn"=>array('ecoChckn'=>"ecoChckn"),'string'),
							array("ttlPrc"=>array('ttlPrc'=>"ttlPrc"),'string'),
							array("ttlPrcIncldngTax"=>array('ttlPrcIncldngTax'=>"ttlPrcIncldngTax"),'string'),
							array('dlyInfrmtnList'=>array(
									array(
											array("trgtDate"=>array('trgtDate'=>"trgtDate"),'string'),
											array("prc"=>array('prc'=>"prc"),'string'),
											array("optionPrc"=>array('optionPrc'=>"optionPrc"),'string'),
											array("sbttlPrc"=>array('sbttlPrc'=>"sbttlPrc"),'string'),
											array("sbttlPrcIncldngTax"=>array('sbttlPrcIncldngTax'=>"sbttlPrcIncldngTax"),'string'),
											array("ecoFlag"=>array('ecoFlag'=>"ecoFlag"),'string'),
											array("class"=>array('class'=>"class"),'string'),
									),	array(
											array("trgtDate"=>array('trgtDate'=>"trgtDate"),'string'),
											array("prc"=>array('prc'=>"prc"),'string'),
											array("optionPrc"=>array('optionPrc'=>"optionPrc"),'string'),
											array("sbttlPrc"=>array('sbttlPrc'=>"sbttlPrc"),'string'),
											array("sbttlPrcIncldngTax"=>array('sbttlPrcIncldngTax'=>"sbttlPrcIncldngTax"),'string'),
											array("ecoFlag"=>array('ecoFlag'=>"ecoFlag"),'string'),
											array("class"=>array('class'=>"class"),'string'),
									),
									array(
											array("trgtDate"=>array('trgtDate'=>"trgtDate"),'string'),
											array("prc"=>array('prc'=>"prc"),'string'),
											array("optionPrc"=>array('optionPrc'=>"optionPrc"),'string'),
											array("sbttlPrc"=>array('sbttlPrc'=>"sbttlPrc"),'string'),
											array("sbttlPrcIncldngTax"=>array('sbttlPrcIncldngTax'=>"sbttlPrcIncldngTax"),'string'),
											array("ecoFlag"=>array('ecoFlag'=>"ecoFlag"),'string'),
											array("class"=>array('class'=>"class"),'string'),
									)
							),'struct'),
							array("class"=>array('class'=>"class"),'string')
						)
						
						
				),'struct')
					
		);
		
		
		$request_data1122 =array(
		 	array('applctnVrsnNmbr','string'),
			array('‚Œngg','string'),
			array('nmbrRms','string'),
			array('ttlPrc','string'),
			array('ttlPrcIncldngTax','string'),
			array('RoomReservationInformation'=>array(
							array('srlNmbr','string'),
							array('htlCode','string'),
							array('rsrvtnNmbr','string'),
							array('roomType','string'),
							array('chcknDate','string'),
							array('chcknTime','string'),
							array('nmbrNghts','string'),
							array('nmberPpl','string'),
							array('mmbrshpFlag','string'),
							array('ecoFlag','string'),
							array('vodFlag','string'),
							array('bsnssPackFlag','string'),
							array('bsnssPackType','string'),
							array('fmlyName','string'),
							array('frstName','string'),
							array('ntnltyCode','string'),
							array('phnNmbr','string'),
							array('mmbrshpNmbr','string'),
							array('ecoChckn','string'),
							array('ttlPrc','string'),
							array('ttlPrcIncldngTax','string'),
							array('dlyInfrmtnList'=>array(
												array(
												array('trgtDate','string'),
												array('prc','string'),
												array('optionPrc','string'),
												array('sbttlPrc','string'),
												array('sbttlPrcIncldngTax','string'),
												array('ecoFlag','string'),
												array('class','string')
												)
											),'struct'),
							array('class','string')
							),'struct')
							
		); 	
				

		$this->pr($request_data);		
		
		$this->xmlrpc->request($request_data);
		if ($this->xmlrpc->send_request()) {
			echo "Connected....Data Found";
			$this->pr($this->xmlrpc->display_response());
		}else{
			echo "Failed....";
			echo 'Error: '. $this->xmlrpc->display_error() . '<br/>';
			echo 'Response: '. print_r($this->xmlrpc->display_response(), true) . '<br/>';
			print_r($this->xmlrpc->display_error());
			$this->pr($this->xmlrpc->display_error());
		}
	}

	function php(){
		echo phpinfo();
	}
	
	function pr($data) {
		echo "<pre>";
		print_r ( $data );
		echo "</pre>";
	}

}
?>
