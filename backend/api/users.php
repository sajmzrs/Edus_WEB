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

    case 'save':
        if(isset($data->identification) && isset($data->first_name) && isset($data->last_name) && isset($data->email)) {
            $patientId = isset($data->id) && $data->id != '' ? $data->id : null;

            $check = "SELECT id FROM users
                      WHERE (email = :email OR identification = :ident)
                      AND role = 'patient'";
            if($patientId) {
                $check .= " AND id <> :id";
            }
            $stmtCheck = $db->prepare($check);
            $stmtCheck->bindParam(":email", $data->email);
            $stmtCheck->bindParam(":ident", $data->identification);
            if($patientId) {
                $stmtCheck->bindParam(":id", $patientId);
            }
            $stmtCheck->execute();

            if($stmtCheck->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "El correo o cédula ya están registrados."]);
                break;
            }

            if($patientId) {
                if(isset($data->password) && $data->password != '') {
                    $query = "UPDATE users
                              SET identification = :ident, first_name = :fname, last_name = :lname,
                                  email = :email, password_hash = :pass
                              WHERE id = :id AND role = 'patient'";
                    $stmt = $db->prepare($query);
                    $passwordHash = password_hash($data->password, PASSWORD_BCRYPT);
                    $stmt->bindParam(":pass", $passwordHash);
                } else {
                    $query = "UPDATE users
                              SET identification = :ident, first_name = :fname, last_name = :lname, email = :email
                              WHERE id = :id AND role = 'patient'";
                    $stmt = $db->prepare($query);
                }
                $stmt->bindParam(":id", $patientId);
            } else {
                if(!isset($data->password) || $data->password == '') {
                    http_response_code(400);
                    echo json_encode(["success" => false, "message" => "La contraseña es requerida para pacientes nuevos."]);
                    break;
                }

                $query = "INSERT INTO users (identification, first_name, last_name, email, password_hash, role)
                          VALUES (:ident, :fname, :lname, :email, :pass, 'patient')";
                $stmt = $db->prepare($query);
                $passwordHash = password_hash($data->password, PASSWORD_BCRYPT);
                $stmt->bindParam(":pass", $passwordHash);
            }

            $stmt->bindParam(":ident", $data->identification);
            $stmt->bindParam(":fname", $data->first_name);
            $stmt->bindParam(":lname", $data->last_name);
            $stmt->bindParam(":email", $data->email);

            if($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Paciente guardado correctamente."]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Error al guardar paciente."]);
            }
        }
        break;

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
