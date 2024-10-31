<?php

class PayallRestHttpCaller 
{
    private $curl;

    public static function payall_get($url)
    {
        $response = wp_remote_get( $url );
        $body     = wp_remote_retrieve_body( $response );
        return $body;
    }

    public static function payall_postWithAccessToken($url, $accessToken, $content)
    {
       
        $args = array(
            'method' => 'POST',
            'timeout' => 30,
            'sslverify'=> false,
            'sslcertificates' => false,
            'headers' => array(
                'Authorization'=> 'Bearer ' .$accessToken,
		        'Content-Type' => 'application/json'
            ),
            'body' => $content
        );

        $response = wp_remote_request( $url, $args );
        $body = wp_remote_retrieve_body( $response );

        return $body;
    }

    public static function payall_post($url, $content)
    {
        
        $args = array(
            'method' => 'POST',
            'timeout' => 30,
            'sslverify'=> false,
            'sslcertificates' => false,
            'headers' => array(
		        'Content-Type' => 'application/json'
            ),
            'body' => $content
        );

        $response = wp_remote_request( $url, $args );
        $body = wp_remote_retrieve_body( $response );

        return $body;
    }

    public static function payall_postFormData($url, $fields)
    {
        
        $args = array(
            'method' => 'POST',
            'timeout' => 30,
            'sslverify'=> false,
            'sslcertificates' => false,
            'body' => $fields
        );

        $response = wp_remote_request( $url, $args );
        $body = wp_remote_retrieve_body( $response );

        return $body;
    }
}