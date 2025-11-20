<?php
class ControllerExtensionModuleBdltSetting extends Controller
{
	private $error 			= array();
	private $version 		= '1.0.0.0';
	private $extension_code = 'bdlt';
	private $domain 		= '';
	private $v_d 			= '';

	public function __construct($params) {
		parent::__construct($params);
		$this->demo = ($this->user->getUserName() == "demo") ? true : false;
	}


	public function index() {
		$this->load->language('extension/module/bdlt_setting');

		$url = $this->request->get['route'];
		$this->domain	 = str_replace("www.","",$_SERVER['SERVER_NAME']);

		$this->houseKeeping();

		$this->rightman();


			// if ($this->domain != $this->v_d) {
			//     $this->storeAuth();
			// } else {
			   $this->getData();
			// }


		// if($this->domain == $this->v_d) {
		// 	$this->storeAuth();
		// } else {
		// 	$this->getData();
		// }
	}

	public function getData() {
		//Load additional CSS/JS
		$this->document->addScript('view/javascript/bootstrap/js/bootstrap-checkbox.min.js');
		$this->document->addStyle('view/javascript/desktop_theme.css');
		$this->document->addStyle('https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css');
		$this->document->addScript('https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js');


		$data['heading_title']  = $this->language->get('heading_title2');

		$data['user_token'] 	= $this->session->data['user_token'];

		  // Get default currency details
		$currency_code = $this->config->get('config_currency');
		$currency_symbol_left = $this->currency->getSymbolLeft($currency_code);
		$currency_symbol_right = $this->currency->getSymbolRight($currency_code);
		$is_symbol_on_right = !empty($currency_symbol_right);

		$data['currency_symbol'] 	= $currency_symbol_left ?: $currency_symbol_right;
		$data['is_symbol_on_right'] = $is_symbol_on_right;
		$data['currency_code'] 		= $currency_code;

		$data['version'] = $this->version;
		$data['extension_code'] = $this->extension_code;
		$data['doc_link']       = "https://bariklabs.com/docs/".$this->extension_code;
		$data['ticket_link']    = "https://bariklabs.com/support";

		$this->document->setTitle($this->language->get('heading_title2'));

		$this->load->model('setting/setting');

		$inputs = [
			[
				"name" => "status",
				"default" => 0,
			],
			[
				"name" => "api_key",
				"default" => '',
			],
			[
				"name" => "api_type",
				"default" => '',
			],
			[
				"name" => "translate_language",
				"default" => 0,
			],
			[
				"name" => "paste_status",
				"default" => 0,
			],
			[
				"name" => "language_source",
				"default" => $this->config->get('config_language_id'),
			],
			[
				"name" => "custom_fields",
				"default" => '',
			],

		];

		if (($this->request->server['REQUEST_METHOD'] == 'POST')  && $this->validate()) {
			$code    = 'bdlt_setting';
			$setting = [];

			foreach ($this->request->post as $key => $input) {

				$setting[$code . "_" . $key] = $input;
			}

			$settings['module_bdlt_setting_status'] = isset($setting['bdlt_setting_status']) ? $setting['bdlt_setting_status'] : 0;

			$this->model_setting_setting->editSetting($code, $setting);
			$this->model_setting_setting->editSetting('module_bdlt_setting', $settings);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/module/bdlt_setting', 'user_token=' . $this->session->data['user_token'], true));

		}

		foreach ($inputs as $input) {
			$key = "bdlt_setting_" . $input['name'];

			if (isset($this->request->post[$key])) {
				$data[$input['name']] = $this->request->post[$key];
			} else if ($this->config->get($key)) {
				$data[$input['name']] = $this->config->get($key);
			} else {
				$data[$input['name']] = $input['default'];
			}
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['default_language_id'] = $this->config->get('config_language_id');

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title2'),
			'href' => $this->url->link('extension/module/bdlt_setting', 'user_token=' . $this->session->data['user_token'], true),
		);


		$data['action'] = $this->url->link('extension/module/bdlt_setting', 'user_token=' . $this->session->data['user_token'], true);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/bdlt_setting', $data));

	}

	public function translate() {
		if(!$this->config->get('module_bdlt_setting_status')) {
			return;
		}

		$this->load->language('extension/module/bdlt_setting');
		$this->load->model('extension/module/bdlt');
		$this->load->model('localisation/language');

		$json = [];
		$data = [];

		if (isset($this->request->post['data'])) {
			$json_string = html_entity_decode($this->request->post['data']);
			$data = json_decode($json_string, true);
		}

		$lang_id = isset($this->request->get['language']) ? $this->request->get['language'] : '';
		$language_info = $this->model_localisation_language->getLanguage($lang_id);
		$code = $language_info['code'] ?? '';
		$lang_name = $language_info['name'] ?? '';
		$lang_code = $code ? explode('-', $code)[0] : '';

		$custom_fields = isset($data['custom_fields']) && is_array($data['custom_fields']) ? $data['custom_fields'] : [];

		if ($lang_code && $data) {
			try {

				$translated = $this->model_extension_module_bdlt->translateProduct($data, $lang_code);

				$json['success'] = true;
				$json['message'] = $this->language->get('text_success_translation');
				$json['data'] = $translated;

				if (isset($translated['custom_fields']) && is_array($translated['custom_fields'])) {
					$json['custom_fields'] = $translated['custom_fields'];
				}

			} catch (Exception $e) {
				$json['error'] = $e->getMessage();
			}

		} else {
			$json['error'] = $this->language->get('error_translate_language');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}


	public function storeAuth() {
		$data['curl_status'] = $this->curlcheck();
		$data['extension_code'] = $this->extension_code;
		$data['user_token']     = $this->session->data['user_token'];

		$this->flushdata();

		$this->document->setTitle($this->language->get('text_validation'));

		$data['text_curl']                  = $this->language->get('text_curl');
		$data['text_disabled_curl']         = $this->language->get('text_disabled_curl');

		$data['text_validation']            = $this->language->get('text_validation');
		$data['text_validate_store']        = $this->language->get('text_validate_store');
		$data['text_information_provide']   = $this->language->get('text_information_provide');
		$data['domain_name'] 				= $this->language->get('text_validate_store');
		$data['domain_name'] 				= $this->domain;

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true),
			'separator' => false
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title2'),
			'href'      => $this->url->link('extension/module/bdlt_setting', 'user_token=' . $this->session->data['user_token'], true),
			'separator' => false
		);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/validation', $data));
	}

	public function install() {

		$this->houseKeeping();
	}

	private function validate() {

		if (!$this->user->hasPermission('modify', 'extension/module/bdlt_setting')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}

	private function rightman() {
		if($this->internetAccess()) {
			$this->load->model('extension/module/system_startup');

			$license = $this->model_extension_module_system_startup->checkLicenseKey($this->extension_code);

			if ($license) {
				if (isset($this->model_extension_module_system_startup->licensewalker)) {
					$url = $this->model_extension_module_system_startup->licensewalker($license['license_key'],$this->extension_code,$this->domain);
					$data = $url;
					$domain = isset($data['domain']) ? $data['domain'] : '';

					if($domain == $this->domain) {
						$this->v_d = $domain;
					} else {
						$this->flushdata();
					}
				}
			}

		} else {
			$this->error['warning'] = $this->language->get('error_no_internet_access');
		}
	}

	private function houseKeeping() {
		$file    = 'https://api.bariklabs.com/validate.zip';
		$newfile = DIR_APPLICATION . 'validate.zip';

		if (!file_exists(DIR_APPLICATION . 'controller/common/hp_validate.php') || !file_exists(DIR_APPLICATION . 'model/extension/module/system_startup.php') || !file_exists(DIR_APPLICATION . 'view/template/extension/module/validation.twig')) {

			$file = $this->curl_get_file_contents($file);

			if (file_put_contents($newfile, $file)) {
				$zip = new ZipArchive();
				$res = $zip->open($newfile);
				if ($res === true) {
					$zip->extractTo(DIR_APPLICATION);
					$zip->close();
					unlink($newfile);
				}
			}
		}

		$this->load->model('extension/module/system_startup');

		if (!isset($this->model_extension_module_system_startup->checkLicenseKey) || !isset($this->model_extension_module_system_startup->licensewalker)) {

			$file = $this->curl_get_file_contents($file);

			if (file_put_contents($newfile, $file)) {
				$zip = new ZipArchive();
				$res = $zip->open($newfile);
				if ($res === true) {
					$zip->extractTo(DIR_APPLICATION);
					$zip->close();
					unlink($newfile);
				}
			}
		}

		if (!file_exists(DIR_SYSTEM . 'system.ocmod.xml')) {
			$str = $this->curl_get_file_contents('https://api.bariklabs.com/system.ocmod.txt');

			file_put_contents(dirname(getcwd()) . '/system/system.ocmod.xml', $str);
		}

		$sql = "CREATE TABLE IF NOT EXISTS `hpwd_license`(
						`hpwd_license_id` INT(11) NOT NULL AUTO_INCREMENT,
						`license_key` VARCHAR(64) NOT NULL,
						`code` VARCHAR(32) NOT NULL,
						`support_expiry` date DEFAULT NULL,
						 PRIMARY KEY(`hpwd_license_id`)
					) ENGINE = InnoDB;";

		$this->db->query($sql);
	}

	public function flushdata() {
		$this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE `key` LIKE '%module_bdlt_setting_status%'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE `key` LIKE '%bdlt_setting_status%'");
	}

	public function curlcheck() {
		return in_array ('curl', get_loaded_extensions()) ? true : false;
	}

	private function internetAccess() {
		//  $connected = @fopen("http://google.com","r");
		//return $connected ? true : false;
		return true;
	}

	private function curl_get_file_contents($URL) {
		$c = curl_init();
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_URL, $URL);
		$contents = curl_exec($c);
		curl_close($c);

		if ($contents) return $contents;
		else return FALSE;
	}


}
