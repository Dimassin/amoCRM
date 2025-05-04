<?php

class Contact
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Db::getInstance()->connect();
    }

    public function getContact(int $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM contacts WHERE `contact_id` = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addContact(int $contactId, string $name, string $phone, string $email, string $position):void
    {
        $stmt = $this->db->prepare("INSERT INTO contacts(`contact_id`, `name`, `phone`, `email`, `position`) VALUES (:contact_id, :name, :phone, :email, :position)");
        $stmt->execute(['contact_id' => $contactId, 'name' => $name, 'phone' => $phone, 'email' => $email, 'position' => $position]);
    }

    public function updateContact(array $params, array $setParts):void
    {
        $stmt = $this->db->prepare("UPDATE contacts SET " . implode(', ', $setParts) . " WHERE `contact_id` = :contact_id");
        $stmt->execute($params);
    }
}