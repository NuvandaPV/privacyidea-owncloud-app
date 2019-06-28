<?php
/**
 * @author Cornelius Kölbel <cornelius.koelbel@netknights.it>
 *
 * @copyright Copyright (c) 2016, Cornelius Kölbel
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\TwoFactor_privacyIDEA\Provider;

use OCP\IUser;
use OCP\IGroupManager;
use OCP\Template;
use OCP\Http\Client\IClientService;
use OCP\ILogger;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use Exception;
// For OC < 9.2 the TwoFactorException does not exist. So we need to handle this in the method verifyChallenge
use OCP\Authentication\TwoFactorAuth\TwoFactorException;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\IL10N;

class AdminAuthException extends Exception
{

}

class TriggerChallengesException extends Exception
{

}

class TwoFactorPrivacyIDEAProvider implements IProvider
{

    private $httpClientService;
    private $config;
    private $logger;
    private $trans;
    private $session;

    public function __construct(IClientService $httpClientService,
                                IConfig $config,
                                ILogger $logger, IRequest $request,
                                IGroupManager $groupManager,
                                IL10N $trans,
                                ISession $session)
    {
        $this->httpClientService = $httpClientService;
        $this->config = $config;
        $this->logger = $logger;
        $this->trans = $trans;
        $this->request = $request;
        $this->detail = array();
        $this->u2fSignRequest = null;
        $this->groupManager = $groupManager;
        $this->session = $session;
    }

    /**
     * Get unique identifier of this 2FA provider
     *
     * @return string
     */
    public function getId()
    {
        return 'privacyidea';
    }

    /**
     * Get the display name for selecting the 2FA provider
     *
     * @return string
     */
    public function getDisplayName()
    {
        return 'privacyIDEA';
    }

    /**
     * Get the description for selecting the 2FA provider
     *
     * @return string
     */
    public function getDescription()
    {
        return 'privacyIDEA';
    }

    /**
     * Retrieve a value from the app's (twofactor_privacyidea) configuration store.
     *
     * @param string $key application config key
     * @return string
     */
    private function getAppValue($key, $default)
    {
        return $this->config->getAppValue('twofactor_privacyidea', $key, $default);
    }

    private function log($level, $message)
    {
    	$context = ["app" => "privacyIDEA"];
    	if($level === 'debug'){
    		return $this->logger->debug($message, $context);
	    }
	    if($level === 'info'){
    		return $this->logger->info($message, $context);
	    }
	    if($level === 'error'){
    		return $this->logger->error($message, $context);
	    }
    }

    /**
     * Retrieve the privacyIDEA instance base URL from the app configuration.
     * In case the stored URL ends with '/validate/check', this suffix is removed.
     * The returned URL always ends with a slash.
     *
     * @return string
     */
    private function getBaseUrl()
    {
        $url = $this->getAppValue('url', '');
        // Remove the "/validate/check" suffix of $url if it exists
        $suffix = "/validate/check";
        if (substr($url, -strlen($suffix)) === $suffix) {
            $url = substr($url, 0, -strlen($suffix));
        }
        // Ensure that $url ends with a slash
        if (substr($url, -1) !== "/") {
            $url .= "/";
        }
        return $url;
    }

    /**
     * Ask privacyIDEA to trigger all challenges for a given username via
     * the /validate/triggerchallenge API request.
     * If the request was successful, return a list of messages from privacyIDEA's response.
     * If the request failed for any reason, a TriggerChallengesException is raised.
     *
     * @param string $username user for which privacyIDEA should trigger challenges
     * @return string[]
     * @throws TriggerChallengesException
     */
    private function triggerChallenges($username)
    {
        $this->session->set("pi_hideOTPField", true);
        $error_message = "";
        $url = $this->getBaseUrl() . "validate/triggerchallenge";
        $options = $this->getClientOptions();
        $adminUser = $this->getAppValue('serviceaccount_user', '');
        $adminPassword = $this->getAppValue('serviceaccount_password', '');
        $realm = $this->getAppValue('realm', '');
        $result = 0;
        try {
            $token = $this->fetchAuthToken($adminUser, $adminPassword);
            $this->session->set("pi_authorization", $token);
            $client = $this->httpClientService->newClient();
            $options["body"] = ["user" => $username, "realm" => $realm];
            $options["headers"] = ["PI-Authorization" => $token];
            $result = $client->post($url, $options);
	        $body = json_decode($result->getBody());
            if ($result->getStatusCode() == 200) {
                if ($body->result->status === true) {
                    $detail = $body->detail;
                    $this->detail = $detail;
                    if (property_exists($detail, "transaction_id")) {
                        $this->session->set("pi_transaction_id", $detail->transaction_id);
                    }

                    if (property_exists($detail, "multi_challenge")) {
                        $multi_challenge = $detail->multi_challenge;
                        if (count($multi_challenge) === 0) {
                        	$this->session->set("pi_hideOTPField", false);
						} else {
							for ($i = 0; $i < count($multi_challenge); $i++) {
								if ($multi_challenge[$i]->type === "u2f") {
									$this->u2fSignRequest = $multi_challenge[$i]->attributes->u2fSignRequest;
								} elseif ($multi_challenge[$i]->type === "push") {
									$this->session->set("pi_PUSH_Response", true);
								} else {
									$this->session->set("pi_hideOTPField", false);
								}
							}
						}
                    }

                    return $detail->message;
                }
            } else {
                $error_message = $this->trans->t("Failed to trigger challenges. Wrong HTTP return code: " . $result->getStatusCode());
	            $this->log("error", "[triggerChallenges] privacyIDEA error code: " . $body->result->error->code);
	            $this->log("error", "[triggerChallenges] privacyIDEA error message: " . $body->result->error->message);
            }
        } catch (AdminAuthException $e) {
            $error_message = $e->getMessage();
        } catch (Exception $e) {
        	if($result !== 0) {
        		$this->log("error", "[triggerChallenges] HTTP return code: " . $result->getStatusCode());
	        }
	        $this->log("error", "[triggerChallenges] " . $e->getMessage());
        	$this->log("debug", $e);
            $error_message = $this->trans->t("Failed to trigger challenges.");
        }
        throw new TriggerChallengesException($error_message);
    }

    /**
     * Get the template for rending the 2FA provider view
     *
     * @param IUser $user
     * @return Template
     */
    public function getTemplate(IUser $user)
    {
    	$transactionId = $this->session->get("pi_transaction_id");

    	if (!$transactionId) {
			$message = null;
			if ($this->getAppValue('triggerchallenges', '') === '1') {
				try {
					$message = $this->triggerChallenges($user->getUID());
					$this->session->set("pi_message", $message);
				} catch (TriggerChallengesException $e) {
					$message = [$e->getMessage()];
				}
			}
		} else {
    		$message = $this->session->get("pi_message");
		}
        $template = new Template('twofactor_privacyidea', 'challenge');
        $template->assign("message", $message);
        $template->assign("hideOTPField", $this->session->get("pi_hideOTPField"));
        $template->assign("pushResponse", $this->session->get("pi_PUSH_Response"));
        $template->assign("pushResponseStatus", $this->session->get("pi_PUSH_Response_Status"));
        $template->assign("u2fSignRequest", $this->u2fSignRequest);
        $template->assign("detail", $this->detail);
        return $template;
    }

    /**
     * Return an associative array that contains the options that should be passed to
     * the HTTP client service when creating HTTP requests.
     * @return array
     */
    private function getClientOptions()
    {
        $checkssl = $this->getAppValue('checkssl', '');
        $noproxy = $this->getAppValue('noproxy', '');
        $timeout = $this->getAppValue('pitimeout', '5');
        $options = ['headers' => ['user-agent' => "ownCloud Plugin"],
            // NOTE: Here, we check for `!== '0'` instead of `=== '1'` in order to verify certificates
            // by default just after app installation.
            'verify' => $checkssl !== '0',
            'debug' => false,
            'exceptions' => false,
            'timeout' => $timeout];
        if ($noproxy === "1") {
            $options["proxy"] = ["https" => "", "http" => ""];
        }
        return $options;
    }

    /**
     * Verify the given challenge.
     * In fact it is not a challenge but the OTP value!
     *
     * @param IUser $user
     * @param string $challenge
     * @return Boolean, True in case of success
     */
    public function verifyChallenge(IUser $user, $challenge)
    {
        $this->log("debug", "privacyIDEA challenge= " . $challenge);
        return $this->authenticate($user->getUID(), $challenge);
    }

    /**
     * Try to authenticate the given username with the given password.
     *
     * @param string $username
     * @param string $password
     * @return Boolean, True in case of success. In case of failure, this raises
     *         a TwoFactorException (for OC >= 9.2) with a descriptive error message.
     *         If privacyIDEA returned a HTTP 200 and result->status=true, but
     *         result->value=false, the exception has a code 1 (i.e. if the user
     *         could be found, but the password was incorrect).
     * @throws TwoFactorException
     */
    public function authenticate($username, $password) {
        $error_message = "";

        // Read config
        $options = $this->getClientOptions();

        $signatureData = $this->request->getParam("signatureData");

        $pushResponse = $this->session->get("pi_PUSH_Response");
        if ($password || $signatureData) {
            $pushResponse = false;
        }
        if ($pushResponse) {
            $this->log("debug", "We are doing a PUSH response.");

            $url = $this->getBaseUrl() . "token/challenges/";
            $options["body"] = ["transaction_id" => $this->session->get("pi_transaction_id")];
            $options["headers"] = ["authorization" => $this->session->get("pi_authorization")];
        } else {
            $url = $this->getBaseUrl() . "validate/check";
            $realm = $this->getAppValue('realm', '');
            $options['body'] = ['user' => $username,
                'pass' => $password,
                'realm' => $realm];

            // The verifyChallenge is called with additional parameters in case of challenge response:
            $transaction_id = $this->session->get("pi_transaction_id");
            $clientData = $this->request->getParam("clientData");
            $this->log("debug", "transaction_id: " . $transaction_id);
            $this->log("debug", "signatureData: " . $signatureData);
            $this->log("debug", "clientData: " . $clientData);

            if ($transaction_id) {
                // add transaction ID in case of challenge response
                $options['body']["transaction_id"] = $transaction_id;
            }

            if ($signatureData) {
                $this->log("debug", "We are doing a U2F response.");
                // here we add the signatureData and the clientData in case of U2F
                $options['body']["signaturedata"] = $signatureData;
                $options['body']["clientdata"] = $clientData;
            }
        }

        $errorCode = 0;
        $res = 0;

        try {
            $client = $this->httpClientService->newClient();
            $res = $client->get($url, $options);
            $body = $res->getBody();
            $body = json_decode($body);

            if ($body->result->status === true) {
                if ($pushResponse) {
                    $error_message = $this->trans->t("The push token was not yet verified.");
                    $challenges = $body->result->value->challenges;
                    foreach ($challenges as $challenge) {
						if ($challenge->otp_received === true && $challenge->otp_valid === true) {
							$this->session->set("pi_PUSH_Response_Status", true);
							return true;
						}
                    }
                } else {
                    if ($body->result->value === true) {
                        return true;
                    } else {
                        $error_message = $this->trans->t("Failed to authenticate.");
                        $errorCode = 1;
                        $this->log("info", "User failed to authenticate. Wrong OTP value.");
                    }
                }
            } else {
                // status == false
                $this->log("error", "[authenticate] privacyIDEA error code: " . $body->result->error->code);
                $this->log("error", "[authenticate] privacyIDEA error message: " . $body->result->error->message);
            }
        } catch (Exception $e) {
            if ($res !== 0) {
                $this->log( "error", "[authenticate] HTTP return code: " . $res->getStatusCode() );
            }
            $this->log("error", "[authenticate] " . $e->getMessage());
            $this->log("debug", $e);
            $error_message = $this->trans->t("Failed to authenticate.") . " " . $e->getMessage();
        }
        if (class_exists('OCP\Authentication\TwoFactorAuth\TwoFactorException')) {
            // This is the behaviour for OC >= 9.2
            throw new TwoFactorException($error_message, $errorCode);
        } else {
            // This is the behaviour for OC == 9.1 and NC.
            return false;
        }
    }

	public function getClientIP(){
		if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)){
			return  $_SERVER["HTTP_X_FORWARDED_FOR"];
		}else if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
			return $_SERVER["REMOTE_ADDR"];
		}else if (array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
			return $_SERVER["HTTP_CLIENT_IP"];
		}

		return '';
	}

    /**
     * Decides whether 2FA is enabled for the given user
     * This method is called after the user has successfully finished the first
     * authentication step i.e.
     * He authenticated with username and password.
     *
     * @param IUser $user
     * @return boolean
     */
    public function isTwoFactorAuthEnabledForUser(IUser $user)
    {
        $piactive = $this->getAppValue('piactive', '');
        $piexcludegroups = $this->getAppValue('piexcludegroups', '');
        $piexclude= $this->getAppValue('piexclude', '1');
	    $piexcludeips = $this->getAppValue('piexcludeips', '');
        if ($piactive === "1") {
            // 2FA is basically enabled
	        if ($piexcludeips) {
	        	// We can exclude clients with specified ips from 2FA

		        $ipaddresses = explode(",", $piexcludeips);
		        $clientIP = ip2long($this->getClientIP());
				foreach ( $ipaddresses as $ipaddress ) {
					if (strpos($ipaddress, '-') !== false) {
						$range = explode('-', $ipaddress);
						$startIP = ip2long($range[0]);
						$endIP = ip2long($range[1]);
						if ($clientIP >= $startIP && $clientIP <= $endIP) {
							return false;
						}
					} else {
						if ($clientIP === ip2long($ipaddress)) {
							return false;
						}
					}
		     	}

	        }
            if ($piexcludegroups) {
                // We can exclude groups from the 2FA
                $piexcludegroupsCSV = str_replace("|", ",", $piexcludegroups);
                $groups = explode(",", $piexcludegroupsCSV);
                $checkEnabled;
                foreach($groups as $group) {
                	if($this->groupManager->isInGroup($user->getUID(), trim($group))) {
		                $this->log("debug", "[isTwoFactorEnabledForUser] The user " . $user->getUID() . " is in group " . $group . ".");
                		if($piexclude === "1"){
			                $this->log("debug", "[isTwoFactorEnabledForUser] The group " . $group . " is excluded (User does not need 2FA).");
                			return false;
		                }
		                if($piexclude === "0") {
			                $this->log("debug", "[isTwoFactorEnabledForUser] The group " . $group . " is included (User needs 2FA).");
			                return true;
		                }
	                }
	                $this->log("debug", "[isTwoFactorEnabledForUser] The user " . $user->getUID() . " is not in group " . $group . ".");
                	if($piexclude === "1"){
		                $this->log("debug", "[isTwoFactorEnabledForUser] The group " . $group . " is excluded (User may need 2FA).");
                	    $checkEnabled = true;
		            }
		            if($piexclude === "0"){
			            $this->log("debug", "[isTwoFactorEnabledForUser] The group " . $group . " is included (User may not need 2FA).");
                		$checkEnabled = false;
		            }
                };
                if(!$checkEnabled){
                	return false;
                }
            };
            $this->log("debug", "[isTwoFactorAuthEnabledForUser] User needs 2FA");
            return true;
        }
        $this->log("debug", "[isTwoFactorAuthEnabledForUser] privacyIDEA is not enabled.");
        return false;
    }

    /**
     * Authenticate the service account against the privacyIDEA instance and return the generated JWT token.
     * In case authentication fails, an AdminAuthException is thrown.
     *
     * @param string $username service account username
     * @param string $password service account password
     * @return string JWT token
     * @throws AdminAuthException
     */
    public function fetchAuthToken($username, $password)
    {
        $error_message = "";
        $url = $this->getBaseUrl() . "auth";
        $options = $this->getClientOptions();
        $result = 0;
        try {
            $client = $this->httpClientService->newClient();
            $options["body"] = ["username" => $username, "password" => $password];
            $result = $client->post($url, $options);
            $body = json_decode($result->getBody());
            if ($result->getStatusCode() === 200) {
                if ($body->result->status === true) {
                	if (in_array("triggerchallenge", $body->result->value->rights)){
		                return $body->result->value->token;
	                }else{
                		$error_message = $this->trans->t("Check if service account has correct permissions");
                		$this->log("error", "[fetchAuthToken} privacyIDEA error message: Missing permissions for service account");
	                }
                }
            }else {
	            $error_message = $this->trans->t("Failed to fetch authentication token. Wrong HTTP return code: " . $result->getStatusCode());
	            $this->log("error", "[fetchAuthToken] privacyIDEA error code: " . $body->result->error->code);
	            $this->log("error", "[fetchAuthToken] privacyIDEA error message: " . $body->result->error->message);

            }
        } catch (Exception $e) {
        	if($result !== 0){
		        $this->log( "error", "[fetchAuthToken] HTTP return code: " . $result->getStatusCode() );
	        }
	        $this->log("error", "[fetchAuthToken] " . $e->getMessage());
        	$this->log("debug", $e);
            $error_message = $this->trans->t("Failed to fetch authentication token.");
        }
        throw new AdminAuthException($error_message);
    }
}
