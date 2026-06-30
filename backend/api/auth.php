<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

$data = json_decode(file_get_contents("php://input"));

switch($action) {
    case 'login':
        if(isset($data->email) && isset($data->password)) {
            $query = "SELECT id, identification, first_name, last_name, password_hash, role FROM users WHERE email = :email LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":email", $data->email);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if(password_verify($data->password, $row['password_hash'])) {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['role'] = $row['role'];
                    $_SESSION['first_name'] = $row['first_name'];
                    
                    echo json_encode([
                        "success" => true,
                        "message" => "Login exitoso",
                        "user" => [
                            "id" => $row['id'],
                            "first_name" => $row['first_name'],
                            "role" => $row['role']
                        ]
                    ]);
                } else {
                    http_response_code(401);
                    echo json_encode(["success" => false, "message" => "Contraseña incorrecta."]);
                }
            } else {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Usuario no encontrado."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Datos incompletos."]);
        }
        break;

    case 'register':
        if(isset($data->identification) && isset($data->first_name) && isset($data->last_name) && isset($data->email) && isset($data->password)) {
            // Check if exists
            $check = "SELECT id FROM users WHERE email = :email OR identification = :ident LIMIT 1";
            $stmtCheck = $db->prepare($check);
            $stmtCheck->bindParam(":email", $data->email);
            $stmtCheck->bindParam(":ident", $data->identification);
            $stmtCheck->execute();

            if($stmtCheck->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "El correo o cédula ya están registrados."]);
                break;
            }

            $query = "INSERT INTO users (identification, first_name, last_name, email, password_hash, role) VALUES (:ident, :fname, :lname, :email, :pass, 'patient')";
            $stmt = $db->prepare($query);
            
            $password_hash = password_hash($data->password, PASSWORD_BCRYPT);
            
            $stmt->bindParam(":ident", $data->identification);
            $stmt->bindParam(":fname", $data->first_name);
            $stmt->bindParam(":lname", $data->last_name);
            $stmt->bindParam(":email", $data->email);
            $stmt->bindParam(":pass", $password_hash);
            
            if($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Usuario registrado correctamente."]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Error al registrar usuario."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Datos incompletos."]);
        }
        break;

    case 'logout':
        session_destroy();
        echo json_encode(["success" => true, "message" => "Sesión cerrada."]);
        break;

    case 'check':
        if(isset($_SESSION['user_id'])) {
            echo json_encode([
                "success" => true, 
                "user" => [
                    "id" => $_SESSION['user_id'],
                    "role" => $_SESSION['role'],
                    "first_name" => $_SESSION['first_name']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "No autorizado"]);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Acción no válida"]);
        break;
}
?>
