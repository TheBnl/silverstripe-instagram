<?php
/**
 * Created by PhpStorm.
 * User: Bram de Leeuw
 * Date: 20/02/16
 * Time: 19:47
 */

class InstagramAuthenticator extends DataExtension {

	/**
	 * The API OAuth URL
	 */
	const API_OAUTH_URL = 'https://api.instagram.com/oauth/authorize';
	
	/**
	 * The OAuth token URL
	 */
	const API_OAUTH_TOKEN_URL = 'https://api.instagram.com/oauth/access_token';

	/**
	 * The redirect URL
	 */
	const REDIRECT_PATH = '/instagram/authenticate';
	
	/**
	 * @config
	 */
	private static $client_id = '';

	/**
	 * @config
	 */
	private static $client_secret = '';


	/**
	 * Database fields
	 * @var array
	 */
	private static $db = array(
			"AccessToken" => "Varchar(255)",
			"ClientID" => "Varchar(255)",
			"ClientSecret" => "Varchar(255)",
			"InstagramID" => "Varchar(255)",
			"InstagramUserName" => "Varchar(255)",
			"InstagramProfilePicture" => "Varchar(255)",
			"InstagramFullName" => "Varchar(255)",
	);

	public function updateCMSFields(FieldList $fields) {
		$clientID = Config::inst()->get("InstagramAuthenticator", "client_id");
		$clientSecret = Config::inst()->get("InstagramAuthenticator", "client_secret");
		// FIXME: how to get url params.. in something els than a controller..
		$authenticated = false;//$this->getRequest()->getVar('authenticated');
		$fields->removeByName(array("AccessToken"));


		/**
		 * FIXME: handle authentication failures and successes by giving proper feedback
		 */
		if ($authenticated) {
			$authenticatedNotice = new LiteralField("AuthenticatedNotice",
					"<div class='message good'>
						<p>You are successfully authenticated!</p>
					</div>");
			$fields->addFieldToTab("Root.Instagram", $authenticatedNotice);
		}

		/**
		 * If no client ID or client secret are specified in the config.yml show a notice were a user can get the id and secret
		 */
		if (!$clientID || !$clientSecret) {
			$clientManagerNotice = new LiteralField("ClientManagerNotice",
					"<div class='message notice'>
						<p>Clients can be managed <a href='https://www.instagram.com/developer/clients/manage/' title='Manage Clients'>here</a></p>
					</div>");
			$fields->addFieldToTab("Root.Instagram", $clientManagerNotice);
		}

		/**
		 * If no client ID is specified in the config.yml let the user save there own
		 */
		if (!$clientID) {
			$clientIDTextField = new TextField("ClientID", "Instagram Client ID");
			$fields->addFieldToTab("Root.Instagram", $clientIDTextField);
		}

		/**
		 * If no client secret is specified in the config.yml let the user save there own
		 */
		if (!$clientSecret) {
			$clientSecretTextField = new TextField("ClientSecret", "Instagram Secret");
			$fields->addFieldToTab("Root.Instagram", $clientSecretTextField);
		}

		/**
		 * If no access token is saved in the database, show a button with the authentication url
		 * TODO: button needs to trigger a window.open event with the instagram authentication url
		 */
		if ($this->owner->getField("AccessToken") == null) {
			$authenticateButton = new LiteralField("InstagramAuthentication",
					"<button type='submit'
								 name='Instagram_Authentication_Button'
								 value='Authenticate Instagram'
								 class='action ss-ui-action-constructive ss-ui-button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary'
								 id='Instagram_Authentication_Button'
								 role='button'
								 aria-disabled='false'
								 onclick='window.open(\"{$this->getAuthenticationURL()}\",\"Authenticate Instagram\",\"width = 600,height = 516\")'>
							<span class='ui-button-text'>Authenticate Instagram</span>
					</button>");
			$fields->addFieldToTab("Root.Instagram", $authenticateButton);
		}

		/**
		 * Show the account info of the logged in user
		 * TODO: make the template and render
		 * TODO: add a revoke access button
		 * TODO: add a get new access token button
		 */
		if ($this->owner->getField("AccessToken") != null) {
			$accountInformationField = new LiteralField('InstagramAccountInformation', $this->owner->RenderWith('InstagramAccountInformation'));
			$fields->addFieldToTab("Root.Instagram", $accountInformationField);

			$revokeButton = new LiteralField("InstagramAuthentication",
					"<button type='submit'
								 name='Instagram_Revoke_Button'
								 value='Revoke Access'
								 class='action ss-ui-button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only'
								 id='Instagram_Revoke_Button'
								 role='button'
								 aria-disabled='false'>
							<span class='ui-button-text'>Revoke Access</span>
					</button>");
			$fields->addFieldToTab("Root.Instagram", $revokeButton);
		}
	}

	/**
	 * Get the client id from config or database
	 * @return string
	 */
	public static function getClientID() {
		$siteConfig = SiteConfig::current_site_config();
		$clientID = Config::inst()->get("InstagramAuthenticator", "client_id");
		return $clientID ? $clientID : $siteConfig->getField("ClientID");
	}

	/**
	 * Get the client secret from config or database
	 * @return string
	 */
	public static function getClientSecret() {
		$siteConfig = SiteConfig::current_site_config();
		$clientSecret = Config::inst()->get("InstagramAuthenticator", "client_secret");
		return $clientSecret ? $clientSecret : $siteConfig->getField("ClientSecret");
	}

	/**
	 * Build the redirect URL
	 * @return string
	 */
	public static function getRedirectURL() {
		return Director::absoluteURL(self::REDIRECT_PATH);
	}

	/**
	 * Create the authentication URL
	 * @return string
	 */
	public function getAuthenticationURL() {
		return self::API_OAUTH_URL . "?client_id=" . self::getClientID() . "&redirect_uri=" . urlencode(self::getRedirectURL()) . "&response_type=code&scope=public_content";
	}

	/**
	 * Return the access token
	 * @return string
	 */
	public function getAccessToken() {
		return $this->owner->getField("AccessToken");
	}

}

/**
 * Controller that handles the authentication redirect and saves the access token in the database
 */
class InstagramAuthenticator_Controller extends ContentController {

	private static $allowed_actions = array("authenticate");

	public function init() {
		parent::init();
	}

	/**
	 * Handle authentication and save the access token
	 * finally redirect the user to the cms tab instagram
	 * FIXME: cms is not opened at tab #Root_Instagram
	 * FIXME: find a way to handle page params in the cms
	 */
	public function authenticate() {
		$code = $this->getRequest()->getVar('code');
		$error = $this->getRequest()->getVar('error');
		$error_reason = $this->getRequest()->getVar('error_reason');
		$error_description = $this->getRequest()->getVar('error_description');

		if ($code) {
			$url = InstagramAuthenticator::API_OAUTH_TOKEN_URL;
			$fields = array(
					"client_id" => InstagramAuthenticator::getClientID(),
					"client_secret" => InstagramAuthenticator::getClientSecret(),
					"grant_type" => "authorization_code",
					"redirect_uri" => urlencode(InstagramAuthenticator::getRedirectURL()),
					"code" => $code
			);

			$fields_string = "";
			foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
			rtrim($fields_string, '&');

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, count($fields));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
			$response = json_decode(curl_exec($ch));
			curl_close($ch);


			if(array_key_exists("error_message", $response)) {
				$this->redirect(Director::absoluteURL("admin/settings/?authenticated=false&error_description=$error_description#Root_Instagram"));
			} else {
				$siteConfig = SiteConfig::current_site_config();
				$siteConfig->AccessToken = $response->access_token;
				$siteConfig->InstagramID = $response->user->id;
				$siteConfig->InstagramUserName = $response->user->username;
				$siteConfig->InstagramProfilePicture = $response->user->profile_picture;
				$siteConfig->InstagramFullName = $response->user->full_name;
				$siteConfig->write();

				$this->redirect(Director::absoluteURL('admin/settings/?authenticated=true#Root_Instagram'));
			}

		} else if ($error) {
			$this->redirect(Director::absoluteURL("admin/settings/?authenticated=false&error=$error&error_reason=$error_reason&error_description=$error_description#Root_Instagram"));
		} else {
			$this->redirect(Director::absoluteURL("admin/settings/?authenticated=false"));
		}
	}
}