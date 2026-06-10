<?php

namespace App\Http\Clients;

use App\Exceptions\ERPIntegrationException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;

class SAPClient
{
    private $http;
    private $cookies;
    private $config;
    private $authenticated = false;

    public function __construct()
    {
        $this->config = config('services.erp');
        $this->cookies = new CookieJar();
        $this->http = new Client([
            'base_uri' => rtrim((string) $this->config['base_url'], '/') . '/',
            'timeout' => (int) $this->config['timeout'],
            'verify' => (bool) $this->config['verify_ssl'],
            'cookies' => $this->cookies,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function getJson($path, array $query = [])
    {
        return $this->requestJson('GET', $path, ['query' => $query]);
    }

    private function requestJson($method, $path, array $options)
    {
        try {
            $this->authenticateIfConfigured();
            $response = $this->http->request($method, ltrim($path, '/'), $options);
            $payload = json_decode((string) $response->getBody(), true);

            if (!is_array($payload)) {
                throw new ERPIntegrationException('The ERP returned an invalid JSON response.');
            }

            return $payload;
        } catch (ERPIntegrationException $exception) {
            throw $exception;
        } catch (RequestException $exception) {
            throw new ERPIntegrationException(
                'The ERP/SAP Service Layer request could not be completed.',
                0,
                $exception
            );
        }
    }

    private function authenticateIfConfigured()
    {
        if ($this->authenticated || empty($this->config['username'])) {
            return;
        }

        try {
            $this->http->post(ltrim($this->config['login_path'], '/'), [
                'json' => [
                    'CompanyDB' => $this->config['company'],
                    'UserName' => $this->config['username'],
                    'Password' => $this->config['password'],
                ],
            ]);
            $this->authenticated = true;
        } catch (RequestException $exception) {
            throw new ERPIntegrationException(
                'Authentication with the ERP/SAP Service Layer failed.',
                0,
                $exception
            );
        }
    }
}
