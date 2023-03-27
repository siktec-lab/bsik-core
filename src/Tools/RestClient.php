<?php

namespace Siktec\Bsik\Tools;

use \Siktec\Bsik\Std;
use \GuzzleHttp\Client as GuzzleClient;

class RestClient {

    private ?string $base_url           = null; //base url for the request
    private ?string $auth_token         = null; //auth token to use
    private ?string $content_type       = null; //content type to use
    private array   $request_headers    = []; //headers to use
    private array   $request_params     = []; //params to use
    private string  $request_params_type = "query"; // [query, form_params, json, body]
    private string  $request_method     = "POST"; //[post, get]
    private ?GuzzleClient $client       = null; // GuzzleHttp\Client

    private array $result = [
        "code" => 200,
        "data" => "",
        "info" => [],
    ];
    
    /**
     * __construct
     * 
     * @param string $base_url - the base url to use for the request
     * @param string $token - the auth token to use (if any)
     * @param string $method - the request method to use (POST, GET)
     * @param string $content_type - the content type to use (application/json, application/x-www-form-urlencoded)
     * @return void
     */
    public function __construct(
        string $base_url     = "",  
        string $token        = null, 
        string $method       = "POST", 
        string $content_type = null
    ) {
        $this->set_base_url($base_url);
        $this->set_auth($token);
        $this->set_method( $method);
        if (!empty($content_type)) {
            $this->content_type = $content_type;
        }
    }
    
    /**
     * set_base_url
     * set the base url to use for the request
     * @param  string $url - the base url to use
     * @return RestClient
     */
    public function set_base_url(string $url) : RestClient {
        $this->base_url = $url;
        return $this;
    }
    
    /**
     * set_auth
     * set the auth token to use for the request
     * @param  string $token - the auth token to use
     * @return RestClient
     */
    public function set_auth(?string $token) : RestClient {
        $this->auth_token = $token;
        return $this;
    }
    
    /**
     * set_method
     * set the request method to use for the request
     * @param  string $method
     * @return RestClient
     */
    public function set_method(string $method) : RestClient {
        $this->request_method = trim(strtoupper($method));
        return $this;
    }
    
    /**
     * set_content_type
     * set the content type to use for the request (application/json, application/x-www-form-urlencoded)
     * @param  string $type
     * @return RestClient
     */
    public function set_content_type(string $type) : RestClient {
        $this->content_type = trim($type);
        return $this;
    }
    
    /**
     * set_params
     * Set the params to use for the request
     * @param  string $type - the type of params to use (query, form_params, json, body)
     * @param  array $params - the params to use
     * @return RestClient
     */
    public function set_params(string $type = "query", array $params = []) : RestClient {

        $this->request_params_type = trim(strtolower($type));
        if ($params === []) {
            $this->request_params = [];
        } else {
            $this->request_params = Std::$arr::merge_recursive_distinct(
                $this->request_params, 
                $params
            );
        }
        return $this;
    }
    
    /**
     * set_headers
     * Set the headers to use for the request
     * @param  array $headers - the headers to use
     * @return RestClient
     */
    public function set_headers(array $headers) : RestClient {
        $this->request_headers = Std::$arr::merge_recursive_distinct(
            $this->request_headers, 
            $headers
        );
        return $this;
    }
    
    /**
     * reset
     * Reset the request
     * @return RestClient
     */
    public function reset() : RestClient {
        $this->request_headers  = [];
        $this->request_params   = [];
        $this->client           = null;
        $this->result = [
            "code" => 200,
            "data" => "",
            "info" => [],
        ];
        return $this;
    }
    
    /**
     * prep_headers
     * Prepare the headers to use for the request
     * @return void
     */
    private function prep_headers() : array {
        $headers = [];

        // All the user defined headers:
        foreach ($this->request_headers as $key => $value) {
            if (is_string($key)) {
                $headers[$key] = $value;
            }
        }

        // The auth token:
        
        if (!empty($this->auth_token) && !array_key_exists("Authorization", $headers)) {
            $headers["Authorization"] = $this->auth_token;
        }
        // The content type:
        if (!empty($this->content_type) && !array_key_exists("Content-Type", $headers)) {
            $headers["Content-Type"] = $this->content_type;
        }
        
        return $headers;
    }

    /**
     * request
     * Make the request
     * @param  string $endpoint - the endpoint to use / append to the base url
     * @param  bool $json - if the response should be json decoded
     * @return int
     */
    public function request(string $endpoint = "", bool $json = false) : int {
        // The client:
        $this->client = new GuzzleClient([
            'base_uri' => $this->base_url
        ]);
        // The request headers:
        $opt = ['headers' => $this->prep_headers()];
        // The request params:
        if (!empty($this->request_params)) { 
            $opt[$this->request_params_type] = $this->request_params;
        }

        // The request:
        $response = $this->client->request($this->request_method, $endpoint, $opt);

        // The response:
        $this->result["code"] = $response->getStatusCode();
        if ($json) {
            $this->result["data"] = json_decode((string)$response->getBody(), true) ?? [];
        } else {
            $this->result["data"] = (string) $response->getBody()->getContents();
        }
        $this->result["info"] = $response->getHeaders();

        // Return the response code:
        return $this->result["code"];
    }
    
    /**
     * response
     * Get the response
     * @return array
     */
    public function response() : array {
        return $this->result;
    }
        
    /**
     * response_info
     * Get the response info
     * @return array
     */
    public function response_info() : array {
        return $this->result["info"];
    }

    /**
     * response_code
     * Get the response code
     * @return int
     */
    public function response_code() : int {
        return $this->result["code"];
    }

    /**
     * response_data
     * Get the response data
     * @return string|array
     */
    public function response_data() : string|array {
        return $this->result["data"];
    }
    
}