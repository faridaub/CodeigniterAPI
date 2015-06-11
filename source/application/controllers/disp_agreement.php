<?php
class Disp_agreement extends CI_Controller {

	var $FNCTIN			= 'disp_agreement';					// 約款ページ表示様


	/*
	 * 約款画面表示
	 *
	 */
	public function index() {
		$this->load->view('agreement');

	}
}