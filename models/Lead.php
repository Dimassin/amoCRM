<?php

class Lead
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Db::getInstance()->connect();
    }

    public function getLead(int $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM leads WHERE `lead_id` = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addLead(int $leadId, string $name, int $price):void
    {
        $stmt = $this->db->prepare("INSERT INTO leads(`lead_id`, `name`, `price`) VALUES (:lead_id, :name, :price)");
        $stmt->execute(['lead_id' => $leadId, 'name' => $name, 'price' => $price]);
    }

    public function updateLead(int $leadId, string $price):void
    {
        $stmt = $this->db->prepare("UPDATE leads SET `price` = :price WHERE `lead_id` = :lead_id");
        $stmt->execute(['lead_id' => $leadId, 'price' => $price]);
    }
}