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
        $query = "SELECT a.id, a.schedule_id, a.status, s.doctor_id, s.schedule_date, s.start_time, s.end_time, d.first_name, d.last_name, d.specialty
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
                
                // Verifica que el horario siga libre.
                $check = "SELECT is_available FROM schedules WHERE id = :sid FOR UPDATE";
                $stmtCheck = $db->prepare($check);
                $stmtCheck->bindParam(":sid", $data->schedule_id);
                $stmtCheck->execute();
                $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                
                if($row && $row['is_available'] == 1) {
                    $query = "INSERT INTO appointments (patient_id, schedule_id, status) VALUES (:pid, :sid, 'scheduled')";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":pid", $patient_id);
                    $stmt->bindParam(":sid", $data->schedule_id);
                    $stmt->execute();
                    
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
                
                // Verifica que la cita le pertenezca al paciente.
                $check = "SELECT schedule_id FROM appointments WHERE id = :aid AND patient_id = :pid AND status = 'scheduled'";
                $stmtCheck = $db->prepare($check);
                $stmtCheck->bindParam(":aid", $data->appointment_id);
                $stmtCheck->bindParam(":pid", $patient_id);
                $stmtCheck->execute();
                
                if($stmtCheck->rowCount() > 0) {
                    $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                    
                    $updateApp = "UPDATE appointments SET status = 'cancelled' WHERE id = :aid";
                    $stmtUpdateApp = $db->prepare($updateApp);
                    $stmtUpdateApp->bindParam(":aid", $data->appointment_id);
                    $stmtUpdateApp->execute();
                    
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

    case 'modify':
        if(isset($data->appointment_id) && isset($data->schedule_id)) {
            try {
                $db->beginTransaction();

                $current = "SELECT a.schedule_id, s.doctor_id
                            FROM appointments a
                            JOIN schedules s ON a.schedule_id = s.id
                            WHERE a.id = :aid AND a.patient_id = :pid AND a.status = 'scheduled'
                            FOR UPDATE";
                $stmtCurrent = $db->prepare($current);
                $stmtCurrent->bindParam(":aid", $data->appointment_id);
                $stmtCurrent->bindParam(":pid", $patient_id);
                $stmtCurrent->execute();
                $appointment = $stmtCurrent->fetch(PDO::FETCH_ASSOC);

                if(!$appointment) {
                    $db->rollBack();
                    http_response_code(403);
                    echo json_encode(["success" => false, "message" => "No se pudo modificar la cita."]);
                    break;
                }

                $available = "SELECT id FROM schedules
                              WHERE id = :sid AND doctor_id = :did AND is_available = 1 AND schedule_date >= CURDATE()
                              FOR UPDATE";
                $stmtAvailable = $db->prepare($available);
                $stmtAvailable->bindParam(":sid", $data->schedule_id);
                $stmtAvailable->bindParam(":did", $appointment['doctor_id']);
                $stmtAvailable->execute();

                if($stmtAvailable->rowCount() === 0) {
                    $db->rollBack();
                    http_response_code(400);
                    echo json_encode(["success" => false, "message" => "El nuevo horario no está disponible."]);
                    break;
                }

                $updateAppointment = "UPDATE appointments SET schedule_id = :new_sid WHERE id = :aid";
                $stmtUpdateAppointment = $db->prepare($updateAppointment);
                $stmtUpdateAppointment->bindParam(":new_sid", $data->schedule_id);
                $stmtUpdateAppointment->bindParam(":aid", $data->appointment_id);
                $stmtUpdateAppointment->execute();

                $freeOld = "UPDATE schedules SET is_available = 1 WHERE id = :old_sid";
                $stmtFreeOld = $db->prepare($freeOld);
                $stmtFreeOld->bindParam(":old_sid", $appointment['schedule_id']);
                $stmtFreeOld->execute();

                $takeNew = "UPDATE schedules SET is_available = 0 WHERE id = :new_sid";
                $stmtTakeNew = $db->prepare($takeNew);
                $stmtTakeNew->bindParam(":new_sid", $data->schedule_id);
                $stmtTakeNew->execute();

                $db->commit();
                echo json_encode(["success" => true, "message" => "Cita modificada correctamente."]);
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Error al modificar la cita."]);
            }
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Acción no válida"]);
        break;
}
?>
