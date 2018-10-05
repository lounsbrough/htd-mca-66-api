<?php
class Authentication 
{
    public function getRequestJSON($serverObject, $requestInput)
    {
        if (strcasecmp($serverObject['REQUEST_METHOD'], 'POST') != 0)
        {
            throw new Exception('Request method must be POST');
        }
    
        $contentType = isset($serverObject["CONTENT_TYPE"]) ? trim($serverObject["CONTENT_TYPE"]) : '';
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

    public function authenticateRequest($authenticationCode)
    {    
        if ($authenticationCode != getenv('HTTPS_AUTHENTICATION_SECRET')) 
        {
            throw new Exception('Auth Code is invalid');
        }
    }
}
?>