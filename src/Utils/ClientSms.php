<?php


namespace App\Utils;


use GuzzleHttp\Client;

class ClientSms
{

    public $client;
    private const URL = "https://smsapi.smartworldgroup.net/";

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::URL,
            'verify'   => false,
        ]);
    }
    public function sendOne($data){
        $endpoint = "/api/smssend";
        $phone=$data['phone'];
        $dtanexah = [
            "phone" => $phone,
            "message" => $data['message'],
            "sender" => "Miss shine",
            "clientkey" => $data['clientkey'],
            "clientsecret" =>$data['clientsecret']
        ];
        $jsdata = json_encode($dtanexah);
        $bodyarray=[];
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ],
            'body' => $jsdata
        ];
        try {
            $response = $this->client->post($endpoint, $options);
            $body = $response->getBody();
            $bodyarray = json_decode($body->getContents(), true);

        } catch (\Exception $exception) {
            $res = [
                "status" => "FAILED",
                'code'=>500,
                "phone" => $phone,
                "message" => $exception->getMessage()
            ];
          //  return $res;
        }

        return $bodyarray;
    }
}
