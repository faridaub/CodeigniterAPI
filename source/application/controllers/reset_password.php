<?php
class Reset_password extends CI_Controller {

	var $FNCTIN			= 'reset_password';					// 機能名

	function __construct()
	{
		parent::__construct();
		$this->load->helper('form');
		$this->load->helper('log');
		$this->load->helper('date');
		$this->load->helper('url');
		$this->load->helper('cookie');
		$this->load->library('xmlrpc');
		$this->load->library('Api_const');
		$this->load->library('Api_date');
		$this->load->library('Api_com_util');
		$this->load->library('Api_util');
		$this->load->library('session');
		$this->lang->load('error', $this->api_util->getErrLang());

	}

	/*
	 * メソッド呼び出しの再マッピング
	 *
	 * コントローラに _remap() という名前のメソッドが含まれる場合、 それは、URI に何がかかれていようが 常に呼び出されます。
	 * それは、どのメソッドを呼ぶかを決めるという標準の振る舞いをオーバーライドするもので、
	 * 独自のメソッドルーティングルールを定義することができます。
	 *
	 *	$method : URIの第2セグメントの文字列
	 *	$params : URLの第2セグメント以降の文字列
	*
	*/
	public function _remap($method, $params = array()) {
		if ($method == "index") {
			$this->index($params);
		} else {
			$this->$method();
		}
	}

	/*
	 * パスワード再設定 初期画面
	 *
	 */
	public function index($params=NULL) {
		$result['mssg'] = '';
 		// 初期化
		$applctnVrsnNmbr = '';
		$lngg = '';
		$athnctnKey = '';
		$rsrvsPrsnUid = '';
		$lgnPsswd = '';
		
		// BIリクエストパラメータへの設定情報取得
		if (count($params) >= 1) {
			$applctnVrsnNmbr = $params[0];				// アプリケーションバージョン番号
		}
		if (count($params) >= 2) {
			$lngg = $params[1];							// 言語コード
		}
		if (count($params) >= 3) {
			$athnctnKey = base64_decode($params[2]);	// 認証キー
		}
		if (count($params) >= 4) {
			$rsrvsPrsnUid = base64_decode($params[3]);	// 予約者情報UID
		}
		if (count($params) >= 5) {
			$lgnPsswd = base64_decode($params[4]);		// ログインパスワード
		}

		// セッション解放
		$this->session->unset_userdata('lgnId');
		$this->session->unset_userdata('password');
		$this->session->unset_userdata('passwordConf');

		$this->session->set_userdata('applctnVrsnNmbr', $applctnVrsnNmbr);
		$this->session->set_userdata('lngg', $lngg);
		$this->session->set_userdata('athnctnKey', $athnctnKey);
		$this->session->set_userdata('rsrvsPrsnUid', $rsrvsPrsnUid);
		$this->lang->load('error', $this->api_util->getErrLangbyLngg($this->session->userdata('lngg')));
		// BIサービス呼び出し（S031：認証キーログイン）
		$host = $this->config->config['bi_service_host'];
		$port = $this->config->config['bi_service_port'];
		$method = "com.toyokoinn.api.service.SmartphoneApplicationCustomerService.loginAuthenticationkey";
		$this->xmlrpc->server($host, $port);
		$this->xmlrpc->method($method);
		$request = array(
			array($applctnVrsnNmbr, 'string'),
			array($lngg, 'string'),
			array($athnctnKey, 'string'),
			array($rsrvsPrsnUid, 'string')
		);
		$this->xmlrpc->request($request);
		// Call BService
		if (!$this->xmlrpc->send_request()) {
			log_error($this->FNCTIN, 'send_request_error : '.$this->xmlrpc->display_error());
			$b_result['errrCode'] = Api_const::BCMN0001;
			$b_result['errrMssg'] = $this->lang->line("ERR_MESSAGE_6");
			// エラー画面へ
			$this->error($b_result);
			return;
		} else {
			$b_result = $this->xmlrpc->display_response();

			if ($b_result['errrCode']==Api_const::BCMN0000) {
				$result['lgnId'] = $b_result['lgnId']; // ログインIDを取得
			} else {
				// エラー画面へ
				$this->error($b_result);
				return;
			}
		}
		$this->session->set_userdata('lgnId', $result['lgnId']);

		// ヘッダ部の表示
		$header['mode'] = "reset_password";
		$header['ex_flag'] = "index";
		$this->load->view('headder_v', $header);

		// ボディ部の表示
		$result['mode'] = "reset_password";
		$result['ex_flag'] = "index";
		$result['edit_mode'] = "entry";
 		$this->load->view('reset_password_v', $result);
		return null;
	}

	/*
	 * パスワード再設定 処理振り分け
	 *
	 */
	public function process() {
		$this->lang->load('error', $this->api_util->getErrLangbyLngg($this->session->userdata('lngg')));
		//-----------
		// 処理振り分け
		//-----------
		if($this->input->post('ex_flag')=='conf') {
			$this->modify_conf();
		} else if($this->input->post('ex_flag')=='comp') {
			if ($this->input->post('subBack') == $this->lang->line('UI_BTN_BACK')) {
				$this->modify_back();
			} else {
				$this->update();
			}
		}
		return null;
	}

	/*
	 * パスワード再設定 「設定」ボタンクリック時の処理
	 *
	 */
	private function modify_conf()
	{
		$result['mssg'] = '';

		$lgnId = $this->session->userdata('lgnId');
		//------------------
		// セッションに退避
		//------------------
		$rec['password']			= $this->input->post('password');
		$rec['passwordConf']		= $this->input->post('passwordConf');
		// セッションに設定
		$this->session->set_userdata($rec);
		//--------------
		// 入力チェック
		//--------------
		if ($this->api_com_util->isNull($rec['password'])) {

			// パスワード 必須チェック
			$result['mssg'] = $this->lang->line("ERR_MESSAGE_4");

		}elseif ($this->api_com_util->isNull($rec['passwordConf'])) {
			// 確認パスワード 必須チェック
			$result['mssg'] = $this->lang->line("ERR_MESSAGE_7");

		}else {

			if (!$this->api_util->chkPassword($rec['password'])) {
			// 半角英数字記号6文字～20文字チェック
				$result['mssg'] = $this->lang->line("ERR_MESSAGE_3");
			}elseif ($rec['password'] != $rec['passwordConf']) {
				// パスワード一致チェック
				$result['mssg'] = $this->lang->line("ERR_MESSAGE_5");
			}
		}

		$ex_flag = "conf";
		$edit_mode = "conf";
		if (!$this->api_com_util->isNull($result['mssg'])) {
			// エラーチェックの場合、indexに戻す
			$ex_flag = "index";
			$edit_mode = "error";
		}
		// ヘッダ部の表示
		$header['mode'] = "reset_password";
		$header['ex_flag'] = $ex_flag;
		$this->load->view('headder_v', $header);

		$result['lgnId'] = $lgnId;
		$result['ex_flag'] = $ex_flag;
		$result['edit_mode'] = $edit_mode;
		$this->load->view('reset_password_v', $result);

		return null;

	}

	/*
	 * パスワード再設定確認画面 「戻る」ボタンクリック時の処理
	 *
	 */
	private function modify_back()
	{
		$result['mssg'] = '';

		$lgnId = $this->session->userdata('lgnId');

		// ヘッダ部の表示
		$header['mode'] = "reset_password";
		$header['ex_flag'] = "back";
		$this->load->view('headder_v', $header);

		$result['lgnId'] = $lgnId;
		$result['mode'] = "reset_password";
		$result['ex_flag'] = "back";
		$result['edit_mode'] = "back";
		$this->load->view('reset_password_v', $result);
		return null;

	}

	/*
	 * パスワード再設定確認画面 「確認」ボタンクリック時の処理
	 *
	 */
	private function update()
	{
		$result['mssg'] = '';

		// BIリクエストパラメータへの設定情報取得
		$applctnVrsnNmbr = $this->session->userdata('applctnVrsnNmbr');
		$lngg = $this->session->userdata('lngg');
		$athnctnKey = $this->session->userdata('athnctnKey');
		$rsrvsPrsnUid = $this->session->userdata('rsrvsPrsnUid');
		$lgnId = $this->session->userdata('lgnId');
		$lgnPsswd = $this->session->userdata('password');

		// セッション解放
		$this->session->unset_userdata('applctnVrsnNmbr');
		$this->session->unset_userdata('lngg');
		$this->session->unset_userdata('athnctnKey');
		$this->session->unset_userdata('rsrvsPrsnUid');
		$this->session->unset_userdata('lgnId');
		$this->session->unset_userdata('password');
		$this->session->unset_userdata('passwordConf');
		// BIサービス呼び出し（S030：パスワード再設定）
		$host = $this->config->config['bi_service_host'];
		$port = $this->config->config['bi_service_port'];
		$method = "com.toyokoinn.api.service.SmartphoneApplicationCustomerService.resetPassword";
		$this->xmlrpc->server($host, $port);
		$this->xmlrpc->method($method);
		$request = array(
			array($applctnVrsnNmbr, 'string'),
			array($lngg, 'string'),
			array($athnctnKey, 'string'),
			array($rsrvsPrsnUid, 'string'),
			array($lgnId, 'string'),
			array($lgnPsswd, 'string')
		);
		$this->xmlrpc->request($request);
		// Call BService
		if (!$this->xmlrpc->send_request()) {
			log_error($this->FNCTIN, 'send_request_error : '.$this->xmlrpc->display_error());
			$b_result['errrCode'] = Api_const::BCMN0001;
			$b_result['errrMssg'] = $this->lang->line("ERR_MESSAGE_6");
			// エラー画面へ
			$this->error($b_result);
			return;
		} else {
			$b_result = $this->xmlrpc->display_response();
			if ($b_result['errrCode']!=Api_const::BCMN0000) {
				// エラー画面へ
				$this->error($b_result);
				return;
			}
		}

		// ヘッダ部の表示
		$header['mode'] = "reset_password";
		$header['ex_flag'] = "comp";
		$this->load->view('headder_v', $header);

		$result['lgnId'] = $lgnId;
		$result['mode'] = "reset_password";
		$result['ex_flag'] = "comp";
		$result['edit_mode'] = "comp";
		$this->load->view('reset_password_v', $result);

		return null;
	}

	/*
	 * エラー画面
	*
	*  $errMsg : エラーメッセージ
	*/
	private function error($b_result)
	{
		$errCd = $b_result['errrCode'];
		$result['mssg'] = $b_result['errrMssg'];

		// BGNL0006とBGNL0007の場合、error_langからメッセージを取得
		if ($errCd==Api_const::BGNL0006) {
			// 該当するお客様の情報はありません。
			$result['mssg'] = $this->lang->line("ERR_MESSAGE_1");
		} else if ($errCd==Api_const::BGNL0007) {
			// 認証キーを発行して２４時間を過ぎたためパスワード再設定はできません。もう一度はじめからやり直してください。
			$result['mssg'] = $this->lang->line("ERR_MESSAGE_2");
		} else {
			// システムエラーが発生しました。申しわけありませんが、初めからやり直してください。
			$result['mssg'] = $this->lang->line("ERR_MESSAGE_6");
		}

		// ヘッダ部の表示
		$header['mode'] = "reset_password";
		$header['ex_flag'] = "error";
		$this->load->view('headder_v', $header);

		$result['mode'] = "reset_password";
		$result['ex_flag'] = "error";
		$result['edit_mode'] = "error";
		$this->load->view('reset_password_v', $result);

		return null;
	}
}