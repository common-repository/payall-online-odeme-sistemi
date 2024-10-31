<?php

class PayallFindInstalmentsRequest
{
    public  $transactionLinkId;
    public  $cardBinNumber;
	public  $accessToken;
	public  $apiUrl;

    public static function Execute(PayallFindInstalmentsRequest $request)
    {     
        return  PayallRestHttpCaller::payall_postWithAccessToken($request->apiUrl . '/api/paymentlink/findinstallments', $request->accessToken, $request->PayalltoJsonString());
	}
	
    public function PayalltoJsonString()
    {
		$jsonData = array(
			'TransactionLinkId' => $this->transactionLinkId,
			'CardBinNumber' => $this->cardBinNumber
		);

		$jsonDataEncoded = json_encode($jsonData);     
        return $jsonDataEncoded;
    }
}