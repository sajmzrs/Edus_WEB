<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$data = json_decode(file_get_contents("php://input"));

switch($action) {
    case 'list':
        $query = "SELECT id, identification, first_name, last_name, email FROM users WHERE role = 'patient' ORDER BY last_name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $patients]);
        break;

    // A complete CRUD could include add/update/delete here, 
    // but patients self-register, so listing and deleting is primary for MVP.
    case 'delete':
        if(isset($data->id)) {
            $query = "DELETE FROM users WHERE id = :id AND role = 'patient'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $data->id);
            if($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Paciente eliminado."]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Error al eliminar."]);
            }
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Acción no válida"]);
        break;
}
?>
