<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/soap/nusoap.php');
/**
 * Minnetonka AudioToolsServer를 연계하기 위한 soap 클래스
 * 특수한 케이스로 Nusoap_client를 상속받아서 해당 부분만 처리 - 2018.03.05 
 */
class nusoapATS_client extends nusoap_client{
    function call($operation, $params = array(), $namespace = 'http://tempuri.org', $soapAction = '', $headers = FALSE, $rpcParams = NULL, $style = 'rpc', $use = 'encoded')
    {
        $this->operation = $operation;
        $this->fault = FALSE;
        $this->setError('');
        $this->request = '';
        $this->response = '';
        $this->responseData = '';
        $this->faultstring = '';
        $this->faultcode = '';
        $this->opData = array();

        $this->debug("call: operation=$operation, namespace=$namespace, soapAction=$soapAction, rpcParams=$rpcParams, style=$style, use=$use, endpointType=$this->endpointType");
        $this->appendDebug('params=' . $this->varDump($params));
        $this->appendDebug('headers=' . $this->varDump($headers));
        if ($headers) {
            $this->requestHeaders = $headers;
        }
        if ($this->endpointType == 'wsdl' && is_null($this->wsdl)) {
            $this->loadWSDL();
            if ($this->getError())
                return FALSE;
        }
        // serialize parameters
        if ($this->endpointType == 'wsdl' && $opData = $this->getOperationData($operation)) {
            // use WSDL for operation
            $this->opData = $opData;
            $this->debug("found operation");
            $this->appendDebug('opData=' . $this->varDump($opData));
            if (isset($opData['soapAction'])) {
                $soapAction = $opData['soapAction'];
            }
            if (!$this->forceEndpoint) {
                $this->endpoint = $opData['endpoint'];
            } else {
                $this->endpoint = $this->forceEndpoint;
            }
            $namespace = isset($opData['input']['namespace']) ? $opData['input']['namespace'] : $namespace;
            $style = $opData['style'];
            $use = $opData['input']['use'];
            // add ns to ns array
            if ($namespace != '' && !isset($this->wsdl->namespaces[$namespace])) {
                $nsPrefix = 'ns' . rand(1000, 9999);
                $this->wsdl->namespaces[$nsPrefix] = $namespace;
            }
            $nsPrefix = $this->wsdl->getPrefixFromNamespace($namespace);
            // serialize payload
            if (is_string($params)) {
                $this->debug("serializing param string for WSDL operation $operation");
                $payload = $params;
            } elseif (is_array($params)) {
                $this->debug("serializing param array for WSDL operation $operation");
                $payload = $this->wsdl->serializeRPCParameters($operation, 'input', $params, $this->bindingType);
            } else {
                $this->debug('params must be array or string');
                $this->setError('params must be array or string');
                return FALSE;
            }
            $usedNamespaces = $this->wsdl->usedNamespaces;
            if (isset($opData['input']['encodingStyle'])) {
                $encodingStyle = $opData['input']['encodingStyle'];
            } else {
                $encodingStyle = '';
            }
            $this->appendDebug($this->wsdl->getDebug());
            $this->wsdl->clearDebug();
            if ($errstr = $this->wsdl->getError()) {
                $this->debug('got wsdl error: ' . $errstr);
                $this->setError('wsdl error: ' . $errstr);
                return FALSE;
            }
        } elseif ($this->endpointType == 'wsdl') {
            // operation not in WSDL
            $this->appendDebug($this->wsdl->getDebug());
            $this->wsdl->clearDebug();
            $this->setError('operation ' . $operation . ' not present in WSDL.');
            $this->debug("operation '$operation' not present in WSDL.");
            return FALSE;
        } else {
            // no WSDL
            //$this->namespaces['ns1'] = $namespace;
            $nsPrefix = 'ns' . rand(1000, 9999);
            // serialize 
            $payload = '';
            if (is_string($params)) {
                $this->debug("serializing param string for operation $operation");
                $payload = $params;
            } elseif (is_array($params)) {
                $this->debug("serializing param array for operation $operation");
                foreach ($params as $k => $v) {
                    $payload .= $this->serialize_val($v, $nsPrefix.':'.$k, FALSE, FALSE, FALSE, FALSE, $use);
                }
            } else {
                $this->debug('params must be array or string');
                $this->setError('params must be array or string');
                return FALSE;
            }
            $usedNamespaces = array();
            if ($use == 'encoded') {
                $encodingStyle = 'http://schemas.xmlsoap.org/soap/encoding/';
            } else {
                $encodingStyle = '';
            }
        }
        // wrap RPC calls with method element
        if ($style == 'rpc') {
            if ($use == 'literal') {
                $this->debug("wrapping RPC request with literal method element");
                if ($namespace) {
                    // http://www.ws-i.org/Profiles/BasicProfile-1.1-2004-08-24.html R2735 says rpc/literal accessor elements should not be in a namespace
                    $payload = "<$nsPrefix:$operation xmlns:$nsPrefix=\"$namespace\">" .
                        $payload .
                        "</$nsPrefix:$operation>";
                } else {
                    $payload = "<$operation>" . $payload . "</$operation>";
                }
            } else {
                $this->debug("wrapping RPC request with encoded method element");
                if ($namespace) {
                    $payload = "<$nsPrefix:$operation xmlns:$nsPrefix=\"$namespace\">" .
                        $payload .
                        "</$nsPrefix:$operation>";
                } else {
                    $payload = "<$operation>" .
                        $payload .
                        "</$operation>";
                }
            }
        }
        // serialize envelope
        $soapmsg = $this->serializeEnvelope($payload, $this->requestHeaders, $usedNamespaces, $style, $use, $encodingStyle);
        $this->debug("endpoint=$this->endpoint, soapAction=$soapAction, namespace=$namespace, style=$style, use=$use, encodingStyle=$encodingStyle");
        $this->debug('SOAP message length=' . strlen($soapmsg) . ' contents (max 1000 bytes)=' . substr($soapmsg, 0, 1000));
        // send
        $return = $this->send($this->getHTTPBody($soapmsg), $soapAction, $this->timeout, $this->response_timeout);
        if ($errstr = $this->getError()) {
            $this->debug('Error: ' . $errstr);
            return FALSE;
        } else {
            $this->return = $return;
            $this->debug('sent message successfully and got a(n) ' . gettype($return));
            $this->appendDebug('return=' . $this->varDump($return));

            // fault?
            if (is_array($return) && isset($return['faultcode'])) {
                $this->debug('got fault');
                $this->setError($return['faultcode'] . ': ' . $return['faultstring']);
                $this->fault = TRUE;
                foreach ($return as $k => $v) {
                    $this->$k = $v;
                    if (is_array($v)) {
                        $this->debug("$k = " . json_encode($v));
                    } else {
                        $this->debug("$k = $v<br>");
                    }
                }
                return $return;
            } elseif ($style == 'document') {
                // NOTE: if the response is defined to have multiple parts (i.e. unwrapped),
                // we are only going to return the first part here...sorry about that
                return $return;
            } else {
                // array of return values
                if (is_array($return)) {
                    // multiple 'out' parameters, which we return wrapped up
                    // in the array
                    if (sizeof($return) > 1) {
                        return $return;
                    }
                    // single 'out' parameter (normally the return value)
                    $return = array_shift($return);
                    $this->debug('return shifted value: ');
                    $this->appendDebug($this->varDump($return));
                    return $return;
                    // nothing returned (ie, echoVoid)
                } else {
                    return "";
                }
            }
        }
    }
}
?>