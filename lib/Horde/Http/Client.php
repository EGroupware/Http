<?php
/**
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Http
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Http
 */
class Horde_Http_Client
{
    /**
     * The current HTTP request
     * @var Horde_Http_Request_Base
     */
    protected $_request;

    /**
     * The previous HTTP request
     * @var Horde_Http_Request_Base
     */
    protected $_lastRequest;

    /**
     * The most recent HTTP response
     * @var Horde_Http_Response_Base
     */
    protected $_lastResponse;

    /**
     * Horde_Http_Client constructor.
     *
     * @param array $args Any Http_Client settings to initialize in the
     * constructor. Available settings are:
     *     client.proxyServer
     *     client.proxyUser
     *     client.proxyPass
     *     client.timeout
     *     request
     *     request.uri
     *     request.headers
     *     request.method
     *     request.data
     */
    public function __construct($args = array())
    {
        // Set or create request object
        if (isset($args['request'])) {
            $this->_request = $args['request'];
            unset($args['request']);
        } else {
            $this->_request = $this->_createBestAvailableRequest();
        }

        foreach ($args as $key => $val) {
            list($object, $objectkey) = explode('.', $key, 2);
            $this->$object->$objectkey = $val;
        }
    }

    /**
     * Send a GET request
     *
     * @return Horde_Http_Response_Base
     */
    public function get($uri = null, $headers = array())
    {
        return $this->request('GET', $uri, null, $headers);
    }

    /**
     * Send a POST request
     *
     * @return Horde_Http_Response_Base
     */
    public function post($uri = null, $data = null, $headers = array())
    {
        return $this->request('POST', $uri, $data, $headers);
    }

    /**
     * Send a PUT request
     *
     * @return Horde_Http_Response_Base
     */
    public function put($uri = null, $data = null, $headers = array())
    {
        /* @TODO suport method override (X-Method-Override: PUT). */
        return $this->request('PUT', $uri, $data, $headers);
    }

    /**
     * Send a DELETE request
     *
     * @return Horde_Http_Response_Base
     */
    public function delete($uri = null, $headers = array())
    {
        /* @TODO suport method override (X-Method-Override: DELETE). */
        return $this->request('DELETE', $uri, null, $headers);
    }

    /**
     * Send a HEAD request
     * @TODO
     *
     * @return  ? Probably just the status
     */
    public function head($uri = null, $headers = array())
    {
        return $this->request('HEAD', $uri, null, $headers);
    }

    /**
     * Send an HTTP request
     *
     * @param string $method HTTP request method (GET, PUT, etc.)
     * @param string $uri URI to request, if different from $this->uri
     * @param mixed $data Request data. Can be an array of form data that will be
     *                    encoded automatically, or a raw string.
     * @param array $headers Any headers specific to this request. They will
     *                       be combined with $this->_headers, and override
     *                       headers of the same name for this request only.
     *
     * @return Horde_Http_Response_Base
     */
    public function request($method, $uri = null, $data = null, $headers = array())
    {
        if ($method !== null) {
            $this->request->method = $method;
        }
        if ($uri !== null) {
            $this->request->uri = $uri;
        }
        if ($data !== null) {
            $this->request->data = $data;
        }
        if (count($headers)) {
            $this->request->setHeaders($headers);
        }

        $this->_lastRequest = $this->_request;
        $this->_lastResponse = $this->_request->send();
        return $this->_lastResponse;
    }

    /**
     * Get a client parameter
     *
     * @param string $name  The parameter to get.
     * @return mixed        Parameter value.
     */
    public function __get($name)
    {
        return isset($this->{'_' . $name}) ? $this->{'_' . $name} : null;
    }

    /**
     * Set a client parameter
     *
     * @param string $name   The parameter to set.
     * @param mixed  $value  Parameter value.
     */
    public function __set($name, $value)
    {
        $this->{'_' . $name} = $value;
    }

    /**
     * Find the best available request backend
     *
     * @return Horde_Http_Request_Base
     */
    public function _createBestAvailableRequest()
    {
        if (class_exists('HttpRequest', false)) {
            return new Horde_Http_Request_Peclhttp();
        } elseif (extension_loaded('curl')) {
            return new Horde_Http_Request_Curl();
        } elseif (ini_get('allow_url_fopen')) {
            return new Horde_Http_Request_Fopen();
        } else {
            throw new Horde_Http_Exception('No HTTP request backends are available. You must install pecl_http, curl, or enable allow_url_fopen.');
        }
    }
}
