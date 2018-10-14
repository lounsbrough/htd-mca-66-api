<?php
class Authentication 
{
    public function getRequestJSON($requestInput)
    {
        if (strcasecmp(filter_input(INPUT_SERVER, 'REQUEST_METHOD'), 'POST') != 0)
        {
            throw new Exception('Request method must be POST');
        }
    
        $contentType = filter_input(INPUT_SERVER, 'CONTENT_TYPE') !== null ? trim(filter_input(INPUT_SERVER, 'CONTENT_TYPE')) : '';
        if (strcasecmp($contentType, 'application/json') != 0)
        {
            throw new Exception('Content type must be: application/json');
        }
        
        $postBody = trim($requestInput);
        $jsonBody = json_decode($postBody, true);
    
        if (!is_array($jsonBody))
        {
            throw new Exception('Received content contained invalid JSON');
        }

        return $jsonBody;
    }

    public function authenticateRequest($jsonBody)
    {    
        if (!isset($jsonBody['authCode']) || $jsonBody['authCode'] != getenv('HTTPS_AUTHENTICATION_SECRET')) 
        {
            throw new Exception('Auth Code is invalid');
        }
    }
}
?>