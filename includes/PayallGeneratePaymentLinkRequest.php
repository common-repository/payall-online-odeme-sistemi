<?php

class PayallGeneratePaymentLinkRequest
{
    public  $amount; 
    public  $expireDate; 
    public  $successUrl; 
    public  $errorUrl;	
	public  $orderId;
	public  $orderInfo;	
	public  $instalmentCount;
	public  $accessToken;
	public  $apiUrl;

    public static function Execute(PayallGeneratePaymentLinkRequest $request)
    {     
        return  PayallRestHttpCaller::payall_postWithAccessToken($request->apiUrl . '/api/paymentlink/generate', $request->accessToken, $request->PayalltoJsonString());
	}
	
    public function PayalltoJsonString()
    {
		$jsonData = array(
			'amount' => $this->amount,
			'expireDate' => $this->expireDate,
			'successUrl' => $this->successUrl,
			'errorUrl' => $this->errorUrl,
			'orderId' => $this->orderId,
			'extraParams' => $this->orderInfo,
			'installmentCount' => $this->instalmentCount
		);

		$jsonDataEncoded = json_encode($jsonData);     
        return $jsonDataEncoded;
    }
}