<?php

require_once('database.php');
require_once('models/AccessToken.php');
require_once('models/Contact.php');
require_once('models/Lead.php');

$data = file_get_contents('php://input');
parse_str($data, $decodedData);
//file_put_contents('webhook_log.json', json_encode($decodedData, JSON_PRETTY_PRINT));
//die();

$accessToken = !empty((new AccessToken())->getToken()) ? (new AccessToken())->getToken() : (new AccessToken())->freshToken();
$headers = [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json'
];
$postData = [];
$url = '';

if (isset($decodedData['contacts'])) {
    $noteData = [];
    $url = "https://maksim0vd.amocrm.ru/api/v4/contacts/notes";
    $newPhone = '';
    $newEmail = '';
    $newPosition = '';
    if (isset($decodedData['contacts']['add'])) {
        $contactId = $decodedData['contacts']['add'][0]['id'];
        $contactName = $decodedData['contacts']['add'][0]['name'];
        $contactResponsible = $decodedData['contacts']['add'][0]['responsible_user_id'];
        $contactCreatedAt = date('Y-m-d H:i:s');

        $noteData = [
            [
                'entity_id' => (int)$contactId,
                'note_type' => 'common',
                'entity_type' => 'contacts',
                'params' => [
                    'text' => 'Имя: ' . $contactName . "\n" . 'Ответственный: ' . $contactResponsible . "\n" . 'Время добавления: ' . $contactCreatedAt
                ]
            ]
        ];
        $postData = $noteData;

        if (isset($decodedData['contacts']['add'][0]['custom_fields'])) {
            foreach ($decodedData['contacts']['add'][0]['custom_fields'] as $contact) {
                switch ($contact['code']) {
                    case 'POSITION':
                        $newPosition = $contact['values'][0]['value'];
                        break;
                    case 'PHONE':
                        $newPhone = $contact['values'][0]['value'];
                        break;
                    case 'EMAIL':
                        $newEmail = $contact['values'][0]['value'];
                        break;
                }
            }
        }

        (new Contact())->addContact($contactId, $contactName, $newPhone, $newEmail, $newPosition);
    }
    if (isset($decodedData['contacts']['update'])) {
        $contactId = $decodedData['contacts']['update'][0]['id'];
        $contactModel = (new Contact())->getContact((int)$contactId);
        $contactCreatedAt = date('Y-m-d H:i:s');
        $contactName = $decodedData['contacts']['update'][0]['name'];
        $text = 'Имя - ' . $contactName . "\n" . 'Изменённые поля';

        foreach ($decodedData['contacts']['update'][0]['custom_fields'] as $contact) {
            switch ($contact['code']) {
                case 'POSITION':
                    $newPosition = ($contact['values'][0]['value'] !== $contactModel[0]['position']) ? $contact['values'][0]['value'] : '';
                    break;
                case 'PHONE':
                    $newPhone = ($contact['values'][0]['value'] !== $contactModel[0]['phone']) ? $contact['values'][0]['value'] : '';
                    break;
                case 'EMAIL':
                    $newEmail = ($contact['values'][0]['value'] !== $contactModel[0]['email']) ? $contact['values'][0]['value'] : '';
                    break;
            }
        }

        $textPosition = !empty($newPosition) ? "\n" . 'Должность - ' . $newPosition : '';
        $textPhone = !empty($newPhone) ? "\n" . 'Телефон - ' . $newPhone : '';
        $textEmail = !empty($newEmail) ? "\n" . 'Email - ' . $newEmail : '';
        $textDate = "\n" . 'Дата изменения - ' . date('Y-m-d H:i:s');
        $text .= $textPosition . $textPhone . $textEmail . $textDate;

        $noteData = [
            [
                'entity_id' => (int)$contactId,
                'note_type' => 'common',
                'entity_type' => 'contacts',
                'params' => [
                    'text' => $text
                ]
            ]
        ];
        $postData = $noteData;

        $params = ['contact_id' => (int)$contactId];
        $setParts = [];
        $data = [
            'phone' => $newPhone,
            'email' => $newEmail,
            'position' => $newPosition
        ];
        foreach ($data as $key => $field) {
            if (!empty($field)) {
                $setParts[] = "`$key` = :$key";
                $params[$key] = $field;
            }
        }
        if (!empty($setParts)) {
            (new Contact())->updateContact($params, $setParts);
        }
    }
}
if (isset($decodedData['leads'])) {
    $leadData = [];
    $url = "https://maksim0vd.amocrm.ru/api/v4/leads/notes";
    $price = 0;

    if (isset($decodedData['leads']['add'])) {
        $leadId = $decodedData['leads']['add'][0]['id'];
        $leadName = $decodedData['leads']['add'][0]['name'];
        $leadResponsible = $decodedData['leads']['add'][0]['responsible_user_id'];
        $leadCreatedAt = date('Y-m-d H:i:s');

        if (isset($decodedData['leads']['add'][0]['custom_fields'][0]['price'])) {
            $price = $decodedData['leads']['add'][0]['custom_fields'][0]['price'];
        }

        $leadData = [
            [
                'entity_id' => (int)$leadId,
                'note_type' => 'common',
                'entity_type' => 'contacts',
                'params' => [
                    'text' => 'Название: ' . $leadName . "\n" . 'Ответственный: ' . $leadResponsible . "\n" . 'Время добавления: ' . $leadCreatedAt
                ]
            ]
        ];
        $postData = $leadData;

        (new Lead())->addLead((int)$leadId, $leadName, $price);
    }
    if (isset($decodedData['leads']['update'])) {
        $leadId = $decodedData['leads']['update'][0]['id'];
        $leadName = $decodedData['leads']['update'][0]['name'];
        $leadModel = (new Lead())->getLead((int)$leadId);
        $text = 'Название - ' . $leadName . "\n" . 'Изменённые поля';
        if (isset($decodedData['leads']['update'][0]['price']) && ($leadModel[0]['price'] !== $decodedData['leads']['update'][0]['price']))
        {
            $price = $decodedData['leads']['update'][0]['price'];
            $text .= 'Цена - ' . $decodedData['leads']['update'][0]['price'] .  "\n" . 'Дата изменения - ' . date('Y-m-d H:i:s');
        }
        $leadData = [
            [
                'entity_id' => (int)$leadId,
                'note_type' => 'common',
                'entity_type' => 'leads',
                'params' => [
                    'text' => $text
                ]
            ]
        ];
        $postData = $leadData;

        (new Lead())->updateLead((int)$leadId, $price);
    }
}

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HEADER, false);
$response = curl_exec($curl);
$status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
if ($status_code == 200 || $status_code == 201) {
    file_put_contents('webhook_log.json', 'Действие успешно выполнено');
} else {
    file_put_contents('webhook_log.json', $response);
}
