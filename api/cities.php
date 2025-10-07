<?php
require_once 'config.php';

// Get all cities
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $db = getDB();

    $action = $_GET['action'] ?? 'list';

    switch ($action) {
        case 'list':
            $stmt = $db->query("SELECT * FROM cities ORDER BY name ASC");
            $cities = $stmt->fetchAll();
            echo json_encode($cities, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;

        case 'get':
            $id = $_GET['id'] ?? 1;
            $stmt = $db->prepare("SELECT * FROM cities WHERE id = ?");
            $stmt->execute([$id]);
            $city = $stmt->fetch();

            if ($city) {
                echo json_encode($city, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } else {
                echo json_encode(['error' => 'City not found']);
            }
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}
?>
