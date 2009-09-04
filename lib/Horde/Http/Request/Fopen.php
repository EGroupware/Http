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
class Horde_Http_Request_Fopen extends Horde_Http_Request_Base
{
    public function __construct()
    {
        if (!ini_get('allow_url_fopen')) {
            throw new Horde_Http_Exception('allow_url_fopen must be enabled');
        }
    }

    /**
     * Send this HTTP request
     *
     * @return Horde_Http_Response_Base
     */
    public function send()
    {
        $method = $this->method;
        $uri = $this->uri;
        $headers = $this->headers;
        $data = $this->data;
        if (is_array($data)) {
            $data = http_build_query($data, '', '&');
        }

        $opts = array('http' => array());

        // Proxy settings - check first, so we can include the correct headers
        if ($this->proxyServer) {
            $opts['http']['proxy'] = 'tcp://' . $this->proxyServer;
            $opts['http']['request_fulluri'] = true;
            if ($this->proxyUser && $this->proxyPass) {
                $headers['Proxy-Authorization'] = 'Basic ' . base64_encode($this->proxyUser . ':' . $this->proxyPass);
            }
        }

        // Concatenate the headers
        $hdr = array();
        foreach ($headers as $header => $value) {
            $hdr[] = $header . ': ' . $value;
        }

        // Stream context config.
        $opts['http']['method'] = $method;
        $opts['http']['header'] = implode("\n", $hdr);
        $opts['http']['content'] = $data;
        $opts['http']['timeout'] = $this->timeout;

        $context = stream_context_create($opts);
        $stream = @fopen($uri, 'rb', false, $context);
        if (!$stream) {
            $error = error_get_last();
            if (preg_match('/HTTP\/(\d+\.\d+) (\d{3}) (.*)$/', $error['message'], $matches)) {
                // Create a Response for the HTTP error code
                return new Horde_Http_Response_Fopen($uri, null, $matches[0]);
            } else {
                throw new Horde_Http_Exception('Problem with ' . $uri . ': ', $error);
            }
        }

        $meta = stream_get_meta_data($stream);
        $headers = isset($meta['wrapper_data']) ? $meta['wrapper_data'] : array();

        return new Horde_Http_Response_Fopen($uri, $stream, $headers);
    }

}
