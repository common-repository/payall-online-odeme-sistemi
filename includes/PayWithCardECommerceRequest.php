<?php

class PayWithCardECommerceRequest
{
    public  $transactionId;
    public  $cardNum1;
	public  $cardNum2;
	public  $cardNum3;
	public  $cardNum4;
    public  $cardHolderName;
    public  $expireMonth;	
	public  $expireYear;
	public  $cvv;
	public  $installmentCount;
	public  $customerComment;
	public  $apiKey;

    public static function Execute(PayWithCardECommerceRequest $request)
    {     
        return  PayallRestHttpCaller::post("https://merchant.payall.com.tr/v2/api/payment/PayWithCardECommerceWithoutRedirect" , $request->apiKey, $request->toJsonString());
    }    
    
    //İstek sonucunda oluşan çıktının xml olarak gösterilmesini sağlar.
    public function toJsonString()
    {
		$jsonData = array(
			'TransactionId' => $this->transactionId,
			'CardNum1' => $this->cardNum1,
			'CardNum2' => $this->cardNum2,
			'CardNum3' => $this->cardNum3,
			'CardNum4' => $this->cardNum4,
			'CardHolderName' => $this->cardHolderName,
			'ExpireMonth' => $this->expireMonth,
			'ExpireYear' => $this->expireYear,
			'Cvv' => $this->cvv,
			'InstallmentCount' => $this->installmentCount,
			'CustomerComment' => $this->customerComment,
		);

		$jsonDataEncoded = json_encode($jsonData);
     
        return $jsonDataEncoded;
    }
}