<?php
class CRMSystem {
    private $mysqli;
    private $currentEntity;
    private $currentItemId;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->currentEntity = $_GET['entity'] ?? 'deals';
        $this->currentItemId = $_GET['item_id'] ?? null;
    }
    
    public function getEntities() {
        return ['deals' => 'Сделки', 'contacts' => 'Контакты'];
    }
    
    public function getItems($entity) {
        $items = [];
        
        if ($entity === 'deals') {
            $result = $this->mysqli->query("
                SELECT d.*, GROUP_CONCAT(c.id) as contact_ids
                FROM deals d
                LEFT JOIN deal_contact dc ON d.id = dc.deal_id
                LEFT JOIN contacts c ON dc.contact_id = c.id
                GROUP BY d.id
                ORDER BY d.name
            ");
        } else {
            $result = $this->mysqli->query("
                SELECT c.*, GROUP_CONCAT(d.id) as deal_ids
                FROM contacts c
                LEFT JOIN deal_contact dc ON c.id = dc.contact_id
                LEFT JOIN deals d ON dc.deal_id = d.id
                GROUP BY c.id
                ORDER BY c.last_name, c.first_name
            ");
        }
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        
        return $items;
    }
    
    public function getItemDetails($entity, $itemId) {
        if (!$itemId) return null;
        
        if ($entity === 'deals') {
            $stmt = $this->mysqli->prepare("
                SELECT d.* FROM deals d WHERE d.id = ?
            ");
        } else {
            $stmt = $this->mysqli->prepare("
                SELECT c.* FROM contacts c WHERE c.id = ?
            ");
        }
        
        $stmt->bind_param('i', $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    public function getRelatedItems($entity, $itemId) {
        if (!$itemId) return [];
        
        if ($entity === 'deals') {
            $stmt = $this->mysqli->prepare("
                SELECT c.id, c.first_name, c.last_name 
                FROM contacts c
                JOIN deal_contact dc ON c.id = dc.contact_id
                WHERE dc.deal_id = ?
                ORDER BY c.last_name, c.first_name
            ");
        } else {
            $stmt = $this->mysqli->prepare("
                SELECT d.id, d.name, d.amount
                FROM deals d
                JOIN deal_contact dc ON d.id = dc.deal_id
                WHERE dc.contact_id = ?
                ORDER BY d.name
            ");
        }
        
        $stmt->bind_param('i', $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        return $items;
    }
    
    public function createItem($entity, $data) {
        if ($entity === 'deals') {
            $stmt = $this->mysqli->prepare("INSERT INTO deals (name, amount) VALUES (?, ?)");
            $stmt->bind_param('sd', $data['name'], $data['amount']);
        } else {
            $stmt = $this->mysqli->prepare("INSERT INTO contacts (first_name, last_name) VALUES (?, ?)");
            $stmt->bind_param('ss', $data['first_name'], $data['last_name']);
        }
        
        return $stmt->execute();
    }
    
    public function updateItem($entity, $itemId, $data) {
        if ($entity === 'deals') {
            $stmt = $this->mysqli->prepare("UPDATE deals SET name = ?, amount = ? WHERE id = ?");
            $stmt->bind_param('sdi', $data['name'], $data['amount'], $itemId);
        } else {
            $stmt = $this->mysqli->prepare("UPDATE contacts SET first_name = ?, last_name = ? WHERE id = ?");
            $stmt->bind_param('ssi', $data['first_name'], $data['last_name'], $itemId);
        }
        
        return $stmt->execute();
    }
    
    public function deleteItem($entity, $itemId) {
        if ($entity === 'deals') {
            $stmt = $this->mysqli->prepare("DELETE FROM deals WHERE id = ?");
        } else {
            $stmt = $this->mysqli->prepare("DELETE FROM contacts WHERE id = ?");
        }
        
        $stmt->bind_param('i', $itemId);
        return $stmt->execute();
    }
    
    public function updateRelations($entity, $itemId, $relatedIds) {
        if ($entity === 'deals') {
            // Удаляем старые связи
            $stmt = $this->mysqli->prepare("DELETE FROM deal_contact WHERE deal_id = ?");
            $stmt->bind_param('i', $itemId);
            $stmt->execute();
            
            // Добавляем новые связи
            foreach ($relatedIds as $contactId) {
                $stmt = $this->mysqli->prepare("INSERT INTO deal_contact (deal_id, contact_id) VALUES (?, ?)");
                $stmt->bind_param('ii', $itemId, $contactId);
                $stmt->execute();
            }
        } else {
            // Аналогично для контактов
            $stmt = $this->mysqli->prepare("DELETE FROM deal_contact WHERE contact_id = ?");
            $stmt->bind_param('i', $itemId);
            $stmt->execute();
            
            foreach ($relatedIds as $dealId) {
                $stmt = $this->mysqli->prepare("INSERT INTO deal_contact (deal_id, contact_id) VALUES (?, ?)");
                $stmt->bind_param('ii', $dealId, $itemId);
                $stmt->execute();
            }
        }
    }
    
    public function getAllItemsForRelation($entity) {
        if ($entity === 'deals') {
            $result = $this->mysqli->query("SELECT * FROM contacts ORDER BY last_name, first_name");
        } else {
            $result = $this->mysqli->query("SELECT * FROM deals ORDER BY name");
        }
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        return $items;
    }
    
    // Геттеры
    public function getCurrentEntity() { return $this->currentEntity; }
    public function getCurrentItemId() { return $this->currentItemId; }
    public function isCurrentEntity($entity) { return $this->currentEntity === $entity; }
}
?>