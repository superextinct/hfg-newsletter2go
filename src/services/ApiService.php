<?php
/**
 * Newsletter2Go plugin for Craft CMS 3.x
 *
 * Wrapper to integrate with Newsletter2Go API
 *
 * @link      https://niklassonnenschein.de
 * @copyright Copyright (c) 2020 Niklas Sonnenschein
 */

namespace hfg\newsletter2go\services;

use hfg\newsletter2go\Newsletter2Go;

use Craft;
use craft\base\Component;

class ApiService extends Component
{
    const GRANT_TYPE = "https://nl2go.com/jwt";
    const BASE_URL = "https://api.newsletter2go.com";

    const METHOD_GET = "GET";
    const METHOD_POST = "POST";
    const METHOD_PATCH = "PATCH";
    const METHOD_DELETE = "DELETE";

    private $user_email;
    private $user_pw;
    private $user_auth_key;

    private $access_token = "";
    private $refresh_token = "";

    private $timeout = 30;
    private $connectionTimeout = 2;
    private $allowRedirects = true;

    private $client;

    public function __construct()
    {
        $this->user_auth_key = Newsletter2Go::getInstance()->settings->getAuthKey();
        $this->user_email = Newsletter2Go::getInstance()->settings->getUserName();
        $this->user_pw = Newsletter2Go::getInstance()->settings->getUserPassword();
    }

    public function auth()
    {
        $this->getToken();
    }

    private function getToken()
    {
        $endpoint = "/oauth/v2/token";

        $data = array(
            "username"   => $this->user_email,
            "password"   => $this->user_pw,
            "grant_type" => static::GRANT_TYPE
        );

        $response = $this->_curl('Basic ' . base64_encode($this->user_auth_key), $endpoint, $data, "POST");
        if (isset($response->error)) {
            throw new \Exception("Authentication failed: " . $response->error);
        }

        $this->access_token = $response->access_token;
        $this->refresh_token = $response->refresh_token;
    }
    
    /**
     * @param $endpoint string the endpoint to call (see docs.newsletter2go.com)
     * @param $data array tha data to submit. In case of POST and PATCH its submitted as the body of the request. In case of GET and PATCH it is used as GET-Params. See docs.newsletter2go.com for supported parameters.
     * @param string $type GET,PATCH,POST,DELETE
     * @return \stdClass
     * @throws \Exception
     */
    public function curl($endpoint, $data, $type = "GET")
    {
        if (!isset($this->access_token) || strlen($this->access_token) == 0) {
            $this->getToken();
        }
        if (!isset($this->access_token) || strlen($this->access_token) == 0) {
            throw new \Exception("Authentication failed");
        }

        $apiReponse = $this->_curl('Bearer ' . $this->access_token, $endpoint, $data, $type);

        // check if token is expired
        if (isset($apiReponse->error) && $apiReponse->error == "invalid_grant") {
            $this->getToken();
            $apiReponse = $this->_curl('Bearer ' . $this->access_token, $endpoint, $data, $type);
        }

        return $apiReponse;
    }

    private function _curl($authorization, $endpoint, $data, $type = "GET")
    {
        $ch = curl_init();
        $data_string = json_encode($data);
        $get_params = "";
        if ($type == static::METHOD_POST || $type == static::METHOD_PATCH) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        if ($type == static::METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
        }
        } else {
            if ($type == static::METHOD_GET || $type == static::METHOD_DELETE) {
                $get_params = "?" . http_build_query($data);
            } else {
                throw new \Exception("Invalid HTTP method: " . $type);
            }
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($ch, CURLOPT_URL, static::BASE_URL . $endpoint . $get_params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: ' . $authorization,
            'Content-Length: ' . ($type == static::METHOD_GET || $type == static::METHOD_DELETE) ? 0 : strlen($data_string)
        ));
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response);
    }

    public function subscribe($contact) {
        $endpoint = "/forms/submit/" . Newsletter2Go::getInstance()->settings->getFormId();
        $data = [
            "recipient" => [
                "email" => $contact->email,
                "first_name" => $contact->name,
                "last_name" => ""
            ]
        ];

        return $this->curl($endpoint, $data, "POST");
    }
}