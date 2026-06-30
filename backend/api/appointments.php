<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$patient_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"));

switch($action) {
    case 'list':
        $query = "SELECT a.id, a.status, s.schedule_date, s.start_time, d.first_name, d.last_name, d.specialty 
                  FROM appointments a
                  JOIN schedules s ON a.schedule_id = s.id
                  JOIN doctors d ON s.doctor_id = d.id
                  WHERE a.patient_id = :pid
                  ORDER BY s.schedule_date DESC, s.start_time DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":pid", $patient_id);
        $stmt->execute();
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $appointments]);
        break;

    case 'specialties':
        $query = "SELECT DISTINCT specialty FROM doctors WHERE status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $specialties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $specialties]);
        break;

    case 'doctors':
        if(isset($_GET['specialty'])) {
            $query = "SELECT id, first_name, last_name FROM doctors WHERE specialty = :spec AND status = 'active'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":spec", $_GET['specialty']);
            $stmt->execute();
            $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $doctors]);
        }
        break;

    case 'schedules':
        if(isset($_GET['doctor_id'])) {
            $query = "SELECT id, schedule_date, start_time, end_time FROM schedules 
                      WHERE doctor_id = :did AND is_available = 1 AND schedule_date >= CURDATE()
                      ORDER BY schedule_date ASC, start_time ASC";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":did", $_GET['doctor_id']);
            $stmt->execute();
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $schedules]);
        }
        break;

    case 'book':
        if(isset($data->schedule_id)) {
            try {
                $db->beginTransaction();
                
                // Verify availability
                $check = "SELECT is_available FROM schedules WHERE id = :sid FOR UPDATE";
                $stmtCheck = $db->prepare($check);
                $stmtCheck->bindParam(":sid", $data->schedule_id);
                $stmtCheck->execute();
                $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                
                if($row && $row['is_available'] == 1) {
                    // Create appointment
                    $query = "INSERT INTO appointments (patient_id, schedule_id, status) VALUES (:pid, :sid, 'scheduled')";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":pid", $patient_id);
                    $stmt->bindParam(":sid", $data->schedule_id);
                    $stmt->execute();
                    
                    // Update schedule
                    $update = "UPDATE schedules SET is_available = 0 WHERE id = :sid";
                    $stmtUpdate = $db->prepare($update);
                    $stmtUpdate->bindParam(":sid", $data->schedule_id);
                    $stmtUpdate->execute();
                    
                    $db->commit();
                    echo json_encode(["success" => true, "message" => "Cita agendada correctamente."]);
                } else {
                    $db->rollBack();
                    http_response_code(400);
                    echo json_encode(["success" => false, "message" => "El horario seleccionado ya no está disponible."]);
                }
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Error al procesar la solicitud."]);
            }
        }
        break;

    case 'cancel':
        if(isset($data->appointment_id)) {
            try {
                $db->beginTransaction();
                
                // Verify ownership
                $check = "SELECT schedule_id FROM appointments WHERE id = :aid AND patient_id = :pid AND status = 'scheduled'";
                $stmtCheck = $db->prepare($check);
                $stmtCheck->bindParam(":aid", $data->appointment_id);
                $stmtCheck->bindParam(":pid", $patient_id);
                $stmtCheck->execute();
                
                if($stmtCheck->rowCount() > 0) {
                    $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                    
                    // Cancel appointment
                    $updateApp = "UPDATE appointments SET status = 'cancelled' WHERE id = :aid";
                    $stmtUpdateApp = $db->prepare($updateApp);
                    $stmtUpdateApp->bindParam(":aid", $data->appointment_id);
                    $stmtUpdateApp->execute();
                    
                    // Free schedule
                    $updateSched = "UPDATE schedules SET is_available = 1 WHERE id = :sid";
                    $stmtUpdateSched = $db->prepare($updateSched);
                    $stmtUpdateSched->bindParam(":sid", $row['schedule_id']);
                    $stmtUpdateSched->execute();
                    
                    $db->commit();
                    echo json_encode(["success" => true, "message" => "Cita cancelada correctamente."]);
                } else {
                    $db->rollBack();
                    http_response_code(403);
                    echo json_encode(["success" => false, "message" => "No se pudo cancelar la cita."]);
                }
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Error al cancelar."]);
            }
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Acción no válida"]);
        break;
}
?>
