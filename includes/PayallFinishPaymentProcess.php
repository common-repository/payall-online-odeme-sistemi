<?php

class PayallFinishPaymentProcess
{
    public  $transactionLinkId;
    public  $orderId;
    public  $clientIp;
    public  $clientUserAgent;
	public  $accessToken;
	public  $apiUrl;

    public static function Execute(PayallFinishPaymentProcess $request)
    {     
        return  PayallRestHttpCaller::payall_postWithAccessToken($request->apiUrl . '/api/paymentlink/FinishPaymentProcess', $request->accessToken, $request->PayalltoJsonString());
	}
	
    public function PayalltoJsonString()
    {
		$jsonData = array(
			'TransactionLinkId' => $this->transactionLinkId,
            'OrderId' => $this->orderId,
            'ClientIp' => $this->clientIp,
            'ClientUserAgent' => $this->clientUserAgent            
		);

		$jsonDataEncoded = json_encode($jsonData);     
        return $jsonDataEncoded;
    }
}