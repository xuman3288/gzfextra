<?php

namespace Gzfextra\Mvc\EasyMvc\Http;

use Zend\Http\Header\Cookie;
use Zend\Stdlib\Parameters;
use Zend\Stdlib\ParametersInterface;
use Zend\Uri\Http as HttpUri;

/**
 * Class Request
 *
 * @author  moln.xie@gmail.com
 */
class Request extends \Zend\Http\Request
{
    /**
     * Base URL of the application.
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Base Path of the application.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Actual request URI, independent of the platform.
     *
     * @var string
     */
    protected $requestUri;

    /**
     * PHP server params ($_SERVER)
     *
     * @var ParametersInterface
     */
    protected $serverParams = null;

    /**
     * PHP environment params ($_ENV)
     *
     * @var ParametersInterface
     */
    protected $envParams = null;

    /**
     * Construct
     * Instantiates request.
     */
    public function __construct()
    {
        $this->setEnv(new Parameters($_ENV));

        if ($_GET) {
            $this->setQuery(new Parameters($_GET));
        }
        if ($_POST) {
            $this->setPost(new Parameters($_POST));
        }
        if ($_COOKIE) {
            $this->setCookies(new Parameters($_COOKIE));
        }
        if ($_FILES) {
            // convert PHP $_FILES superglobal
            $files = $this->mapPhpFiles();
            $this->setFiles(new Parameters($files));
        }

        $this->setServer(new Parameters($_SERVER));
    }

    /**
     * Get raw request body
     *
     * @return string
     */
    public function getContent()
    {
        if (empty($this->content)) {
            $requestBody = file_get_contents('php://input');
            if (strlen($requestBody) > 0) {
                $this->content = $requestBody;
            }
        }

        return $this->content;
    }

    /**
     * Set cookies
     *
     * Instantiate and set cookies.
     *
     * @param $cookie
     * @return Request
     */
    public function setCookies($cookie)
    {
        $this->getHeaders()->addHeader(new Cookie((array)$cookie));
        return $this;
    }

    /**
     * Set the request URI.
     *
     * @param  string $requestUri
     * @return self
     */
    public function setRequestUri($requestUri)
    {
        $this->requestUri = $requestUri;
        return $this;
    }

    /**
     * Get the request URI.
     *
     * @return string
     */
    public function getRequestUri()
    {
        if ($this->requestUri === null) {
            $this->requestUri = $this->getServer('REQUEST_URI');
        }
        return $this->requestUri;
    }

    /**
     * Set the base URL.
     *
     * @param  string $baseUrl
     * @return self
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
    }

    /**
     * Get the base URL.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        if ($this->baseUrl === null) {
            $this->setBaseUrl($this->detectBaseUrl());
        }
        return $this->baseUrl;
    }

    /**
     * Set the base path.
     *
     * @param  string $basePath
     * @return self
     */
    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        return $this;
    }

    /**
     * Get the base path.
     *
     * @return string
     */
    public function getBasePath()
    {
        if ($this->basePath === null) {
            $this->setBasePath($this->detectBasePath());
        }

        return $this->basePath;
    }

    /**
     * Return the parameter container responsible for server parameters or a single parameter value.
     *
     * @param string|null $name   Parameter name to retrieve, or null to get the whole container.
     * @param mixed|null $default Default value to use when the parameter is missing.
     * @see http://www.faqs.org/rfcs/rfc3875.html
     * @return \Zend\Stdlib\ParametersInterface|mixed
     */
    public function getServer($name = null, $default = null)
    {
        if ($this->serverParams === null) {
            $this->serverParams = new Parameters();
        }

        if ($name === null) {
            return $this->serverParams;
        }

        return $this->serverParams->get($name, $default);
    }

    /**
     * Provide an alternate Parameter Container implementation for env parameters in this object,
     * (this is NOT the primary API for value setting, for that see env())
     *
     * @param  ParametersInterface $env
     * @return Request
     */
    public function setEnv(ParametersInterface $env)
    {
        $this->envParams = $env;
        return $this;
    }

    /**
     * Return the parameter container responsible for env parameters or a single parameter value.
     *
     * @param string|null $name   Parameter name to retrieve, or null to get the whole container.
     * @param mixed|null $default Default value to use when the parameter is missing.     * @return \Zend\Stdlib\ParametersInterface
     * @return \Zend\Stdlib\ParametersInterface|mixed
     */
    public function getEnv($name = null, $default = null)
    {
        if ($this->envParams === null) {
            $this->envParams = new Parameters();
        }

        if ($name === null) {
            return $this->envParams;
        }

        return $this->envParams->get($name, $default);
    }

    /**
     * Convert PHP superglobal $_FILES into more sane parameter=value structure
     * This handles form file input with brackets (name=files[])
     *
     * @return array
     */
    protected function mapPhpFiles()
    {
        $files = array();
        foreach ($_FILES as $fileName => $fileParams) {
            $files[$fileName] = array();
            foreach ($fileParams as $param => $data) {
                if (!is_array($data)) {
                    $files[$fileName][$param] = $data;
                } else {
                    foreach ($data as $i => $v) {
                        $this->mapPhpFileParam($files[$fileName], $param, $i, $v);
                    }
                }
            }
        }

        return $files;
    }

    /**
     * @param array $array
     * @param string $paramName
     * @param int|string $index
     * @param string|array $value
     */
    protected function mapPhpFileParam(&$array, $paramName, $index, $value)
    {
        if (!is_array($value)) {
            $array[$index][$paramName] = $value;
        } else {
            foreach ($value as $i => $v) {
                $this->mapPhpFileParam($array[$index], $paramName, $i, $v);
            }
        }
    }

    /**
     * Auto-detect the base path from the request environment
     *
     * Uses a variety of criteria in order to detect the base URL of the request
     * (i.e., anything additional to the document root).
     *
     *
     * @return string
     */
    protected function detectBaseUrl()
    {
        $filename       = $this->getServer()->get('SCRIPT_FILENAME', '');
        $scriptName     = $this->getServer()->get('SCRIPT_NAME');
        $phpSelf        = $this->getServer()->get('PHP_SELF');
        $origScriptName = $this->getServer()->get('ORIG_SCRIPT_NAME');

        if ($scriptName !== null && basename($scriptName) === $filename) {
            $baseUrl = $scriptName;
        } elseif ($phpSelf !== null && basename($phpSelf) === $filename) {
            $baseUrl = $phpSelf;
        } elseif ($origScriptName !== null && basename($origScriptName) === $filename) {
            // 1and1 shared hosting compatibility.
            $baseUrl = $origScriptName;
        } else {
            // Backtrack up the SCRIPT_FILENAME to find the portion
            // matching PHP_SELF.

            $baseUrl  = '/';
            $basename = basename($filename);
            if ($basename) {
                $path = ($phpSelf ? trim($phpSelf, '/') : '');
                $baseUrl .= substr($path, 0, strpos($path, $basename)) . $basename;
            }
        }

        // Does the base URL have anything in common with the request URI?
        $requestUri = $this->getRequestUri();

        // Full base URL matches.
        if (0 === strpos($requestUri, $baseUrl)) {
            return $baseUrl;
        }

        // Directory portion of base path matches.
        $baseDir = str_replace('\\', '/', dirname($baseUrl));
        if (0 === strpos($requestUri, $baseDir)) {
            return $baseDir;
        }

        $truncatedRequestUri = $requestUri;

        if (false !== ($pos = strpos($requestUri, '?'))) {
            $truncatedRequestUri = substr($requestUri, 0, $pos);
        }

        $basename = basename($baseUrl);

        // No match whatsoever
        if (empty($basename) || false === strpos($truncatedRequestUri, $basename)) {
            return '';
        }

        // If using mod_rewrite or ISAPI_Rewrite strip the script filename
        // out of the base path. $pos !== 0 makes sure it is not matching a
        // value from PATH_INFO or QUERY_STRING.
        if (strlen($requestUri) >= strlen($baseUrl)
            && (false !== ($pos = strpos($requestUri, $baseUrl)) && $pos !== 0)
        ) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return $baseUrl;
    }

    /**
     * Autodetect the base path of the request
     *
     * Uses several criteria to determine the base path of the request.
     *
     * @return string
     */
    protected function detectBasePath()
    {
        $filename = basename($this->getServer()->get('SCRIPT_FILENAME', ''));
        $baseUrl  = $this->getBaseUrl();

        // Empty base url detected
        if ($baseUrl === '') {
            return '';
        }

        // basename() matches the script filename; return the directory
        if (basename($baseUrl) === $filename) {
            return str_replace('\\', '/', dirname($baseUrl));
        }

        // Base path is identical to base URL
        return $baseUrl;
    }

    public function setServer(ParametersInterface $server)
    {
        $this->serverParams = $server;

        // This seems to be the only way to get the Authorization header on Apache
        if (function_exists('apache_request_headers')) {
            $apacheRequestHeaders = apache_request_headers();
            if (!isset($this->serverParams['HTTP_AUTHORIZATION'])) {
                if (isset($apacheRequestHeaders['Authorization'])) {
                    $this->serverParams->set('HTTP_AUTHORIZATION', $apacheRequestHeaders['Authorization']);
                } elseif (isset($apacheRequestHeaders['authorization'])) {
                    $this->serverParams->set('HTTP_AUTHORIZATION', $apacheRequestHeaders['authorization']);
                }
            }
        }

        // set headers
        $headers = array();

        foreach ($server as $key => $value) {
            if ($value && strpos($key, 'HTTP_') === 0) {
                if (strpos($key, 'HTTP_COOKIE') === 0) {
                    // Cookies are handled using the $_COOKIE superglobal
                    continue;
                }
                $name = strtr(substr($key, 5), '_', ' ');
                $name = strtr(ucwords(strtolower($name)), ' ', '-');
            } elseif ($value && strpos($key, 'CONTENT_') === 0) {
                $name = substr($key, 8); // Content-
                $name = 'Content-' . (($name == 'MD5') ? $name : ucfirst(strtolower($name)));
            } else {
                continue;
            }

            $headers[$name] = $value;
        }

        $this->getHeaders()->addHeaders($headers);

        // set method
        if (isset($this->serverParams['REQUEST_METHOD'])) {
            $this->setMethod($this->serverParams['REQUEST_METHOD']);
        }

        // set HTTP version
        if (isset($this->serverParams['SERVER_PROTOCOL'])
            && strpos($this->serverParams['SERVER_PROTOCOL'], self::VERSION_10) !== false
        ) {
            $this->setVersion(self::VERSION_10);
        }

        // set URI
        $uri = new HttpUri();

        // URI scheme
        if ((!empty($this->serverParams['HTTPS']) && $this->serverParams['HTTPS'] !== 'off')
            || (!empty($this->serverParams['HTTP_X_FORWARDED_PROTO'])
                && $this->serverParams['HTTP_X_FORWARDED_PROTO'] == 'https')
        ) {
            $scheme = 'https';
        } else {
            $scheme = 'http';
        }
        $uri->setScheme($scheme);

        // URI host & port
        $uri->setHost($this->serverParams['SERVER_NAME']);
        $uri->setPort($this->serverParams['SERVER_PORT']);

        // URI path
        if (isset($this->serverParams['REQUEST_URI'])) {
            $this->setRequestUri($this->serverParams['REQUEST_URI']);
        }

        $requestUri = $this->getRequestUri();
        if (($qpos = strpos($requestUri, '?')) !== false) {
            $requestUri = substr($requestUri, 0, $qpos);
        }

        $uri->setPath($requestUri);

        // URI query
        if (isset($this->serverParams['QUERY_STRING'])) {
            $uri->setQuery($this->serverParams['QUERY_STRING']);
        }

        $this->setUri($uri);
        return $this;
    }
}