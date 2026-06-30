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
        $query = "SELECT s.id, s.schedule_date, s.start_time, s.end_time, s.is_available, d.first_name, d.last_name, d.id as doctor_id 
                  FROM schedules s
                  JOIN doctors d ON s.doctor_id = d.id";
        
        if(isset($_GET['doctor_id']) && $_GET['doctor_id'] != '') {
            $query .= " WHERE d.id = :did";
        }
        $query .= " ORDER BY s.schedule_date DESC, s.start_time DESC";
        
        $stmt = $db->prepare($query);
        if(isset($_GET['doctor_id']) && $_GET['doctor_id'] != '') {
            $stmt->bindParam(":did", $_GET['doctor_id']);
        }
        $stmt->execute();
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $schedules]);
        break;

    case 'save':
        if(isset($data->doctor_id) && isset($data->schedule_date) && isset($data->start_time) && isset($data->end_time)) {
            $is_avail = isset($data->is_available) && $data->is_available ? 1 : 0;
            
            if(isset($data->id) && $data->id != '') {
                // Update
                $query = "UPDATE schedules SET doctor_id = :did, schedule_date = :sdate, start_time = :stime, end_time = :etime, is_available = :avail WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $data->id);
            } else {
                // Insert
                $query = "INSERT INTO schedules (doctor_id, schedule_date, start_time, end_time, is_available) VALUES (:did, :sdate, :stime, :etime, :avail)";
                $stmt = $db->prepare($query);
            }
            
            $stmt->bindParam(":did", $data->doctor_id);
            $stmt->bindParam(":sdate", $data->schedule_date);
            $stmt->bindParam(":stime", $data->start_time);
            $stmt->bindParam(":etime", $data->end_time);
            $stmt->bindParam(":avail", $is_avail);
            
            if($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Horario guardado correctamente."]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Error al guardar."]);
            }
        }
        break;

    case 'delete':
        if(isset($data->id)) {
            $query = "DELETE FROM schedules WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $data->id);
            if($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Horario eliminado."]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Error al eliminar."]);
            }
        }
        break;

    case 'reports':
        // Citas reservadas para el reporte
        $query = "SELECT a.id, a.status, a.created_at, u.identification as patient_id, u.first_name as pf, u.last_name as pl,
                  s.schedule_date, s.start_time, d.first_name as df, d.last_name as dl
                  FROM appointments a
                  JOIN users u ON a.patient_id = u.id
                  JOIN schedules s ON a.schedule_id = s.id
                  JOIN doctors d ON s.doctor_id = d.id
                  ORDER BY s.schedule_date DESC, s.start_time DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $reports]);
        break;

    default:
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Acción no válida"]);
        break;
}
?>
