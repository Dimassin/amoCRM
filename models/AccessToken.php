<?php

class AccessToken
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Db::getInstance()->connect();
    }

    public function getToken(): ?string
    {
        $stmt = $this->db->prepare("SELECT `value` FROM `access_token` WHERE `created_at` > (NOW() - INTERVAL 20 HOUR)");
        $stmt->execute();
        return $stmt->fetchColumn() ?? null;
    }

    public function freshToken(): string
    {
        file_put_contents('webhook.json', 12345, FILE_APPEND);

        $stmt = $this->db->prepare("SELECT `refresh_token` FROM `access_token`");
        $stmt->execute();
        $refreshToken = $stmt->fetchColumn();

        $link = 'https://maksim0vd.amocrm.ru/oauth2/access_token';
        $data = [
            'client_id' => '55194da8-884f-49bc-86e2-eb9de4bafbf8',
            'client_secret' => 'OZN8svtqB2WOJQIgxmQuiR2StSBeEB4zUGWbMKLXlnN3VsHjcJ0rUSWQa0Pci5xA',
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'redirect_uri' => 'https://cz28393.tw1.ru',
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $link);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl);
        file_put_contents('webhook.json', 'ответ' . $out);

        curl_close($curl);

        $response = json_decode($out, true);

        $stmt = $this->db->prepare("UPDATE access_token SET `value` = :accessToken, `refresh_token` = :refreshToken, `created_at` = :created_at");
        $stmt->execute([':accessToken' => $response['access_token'], ':refreshToken' => $response['refresh_token'], ':created_at' => date('Y-m-d H:i:s')]);

        return $response['access_token'];
    }
}