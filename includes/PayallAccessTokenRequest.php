<?php

class PayallAccessTokenRequest
{
    public  $clientId;
	public  $clientSecret;
	public  $apiUrl;

    public static function Execute(PayallAccessTokenRequest $request)
    {     
		$accessTokenResponse =  PayallRestHttpCaller::payall_postFormData($request->apiUrl . "/token", $request->payallGetFields());
		
		$accessTokenJson = json_decode($accessTokenResponse, true);

		$accessToken = $accessTokenJson["access_token"];

		return $accessToken;
    }
        
    public function payallGetFields()
    {
		$fields = array(
			'grant_type' => 'client_credentials',
			'client_id' => $this->clientId,
			'client_secret' => $this->clientSecret
		);
        return $fields;
    }
}