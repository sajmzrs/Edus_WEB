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
        $query = "SELECT id, identification, first_name, last_name, specialty, status FROM doctors ORDER BY last_name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $doctors]);
        break;

    case 'save':
        if(isset($data->identification) && isset($data->first_name) && isset($data->last_name) && isset($data->specialty)) {
            if(isset($data->id) && $data->id != '') {
                // Update
                $query = "UPDATE doctors SET identification = :ident, first_name = :fname, last_name = :lname, specialty = :spec, status = :stat WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $data->id);
            } else {
                // Insert
                $query = "INSERT INTO doctors (identification, first_name, last_name, specialty, status) VALUES (:ident, :fname, :lname, :spec, :stat)";
                $stmt = $db->prepare($query);
            }
            
            $stmt->bindParam(":ident", $data->identification);
            $stmt->bindParam(":fname", $data->first_name);
            $stmt->bindParam(":lname", $data->last_name);
            $stmt->bindParam(":spec", $data->specialty);
            $stmt->bindParam(":stat", $data->status);
            
            if($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Médico guardado correctamente."]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Error al guardar."]);
            }
        }
        break;

    case 'delete':
        if(isset($data->id)) {
            $query = "DELETE FROM doctors WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $data->id);
            if($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Médico eliminado."]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Error al eliminar (puede tener citas/horarios asociados)."]);
            }
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Acción no válida"]);
        break;
}
?>
