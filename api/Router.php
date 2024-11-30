<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    ob_start();
    
    // Allow requests from any origin
    header('Access-Control-Allow-Origin: *');
    
    // Allow specific HTTP methods
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
    
    // Allow specific headers
    header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Origin, Authorization');
    
    // Set Content-Type header to application/json for all responses
    header('Content-Type: application/json');
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    
        exit(0);
    }
    
    require_once('./config/database.php');
    require_once( './services/login.php');
    require_once('./services/register.php');
    require_once('./services/AdminMailer.php');
    require_once('./services/LandlordHandler.php');
    require_once('./services/TenantHandler.php');
    require_once('./services/AnalyticsHandler.php');

    

    
    $con = new DatabaseAccess();
    $pdo = $con->connect();
    
    
    $register = new RegisterUser($pdo);
    $login = new Login($pdo);
    $mail = new Mail($pdo);
    $landlord = new LandlordHandler($pdo);
    $tenant = new TenantHandler($pdo);
    $analytics = new AnalyticsHandler($pdo);
    
    
   
    
    // Check if 'request' parameter is set in the request
    if (isset($_REQUEST['request'])) {
        // Split the request into an array based on '/'
        $request = explode('/', $_REQUEST['request']);
    } else {
        // If 'request' parameter is not set, return a 404 response
        echo json_encode(["error" => "Not Found"]);
        http_response_code(404);
        exit();
    }
    
    // Handle requests based on HTTP method
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            $data = json_decode(file_get_contents("php://input"));
            switch ($request[0]) {
                case 'login':
                    if (isset($data->email) && isset($data->password)) {
                        echo json_encode($login->loginUser($data->email, $data->password));
                    } else {
                        echo json_encode([
                            'status' => 400,
                            'message' => 'Invalid input data'
                        ]);
                    }
                    break;
                    case 'logout':
                        echo json_encode($login->logoutUser($data));
                        break;
                    case 'register':
                        echo json_encode($register->registerUser($data));
                        break;
                    case 'mail':
                            echo json_encode($mail->sendEmail($data));
                            break;
                    case 'schedule':
                            echo json_encode($mail->scheduledSend($data));
                            break;
                    case 'announcement':
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            $data = $_POST; // Use $_POST to get form data
                            $data['image'] = $_FILES['image'] ?? null; // Add the uploaded file to the data array
                            echo json_encode($landlord->createAnnouncement($data));
                        } else {
                            echo "Method not allowed";
                            http_response_code(405);
                        }
                        break;
                    case 'apartment':
                        echo json_encode($landlord->createApartment($data));
                        break;
                    case 'assignTenant':
                        echo json_encode($landlord->assignTenantToApartment($data));
                        break;
                    case 'uploadLease':
                            if (isset($_FILES['image']) && isset($_POST['tenant_id']) && isset($_POST['room'])) {
                                // Get tenant ID and room from POST data
                                $tenantId = $_POST['tenant_id'];
                                $room = $_POST['room'];
                        
                                // Return JSON-encoded data for adding lease image
                                echo json_encode($landlord->addLease($_FILES['image'], $tenantId, $room));
                            } else {
                                echo json_encode(['error' => 'No file uploaded or missing tenant information.']);
                                http_response_code(400);
                            }
                            break;
                        
                    case 'payInvoice':
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            $tenantId = $_POST['tenantId'];
                            $amount = $_POST['amount'];
                            $referenceNumber = $_POST['referenceNumber'];
                            $proofOfPaymentFile = $_FILES['proofOfPayment'] ?? null;
                            $response = $tenant->payInvoice($tenantId, $amount, $referenceNumber, $proofOfPaymentFile);
                            echo json_encode($response);
                        } else {
                            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
                            http_response_code(405);
                        }
                        break;
                    case 'concern':
                            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                                $data = $_POST; // Use $_POST to get form data
                                $data['image'] = $_FILES['image'] ?? null; // Add the uploaded file to the data array
                                echo json_encode($tenant->createConcern($data));
                            } else {
                                echo "Method not allowed";
                                http_response_code(405);
                            }
                            break;
                    case 'uploadImage':
                                if (isset($_FILES['image'])) {
                                    // Return JSON-encoded data for adding image
                                    echo json_encode($landlord->addImage($_FILES['image']));
                                } else {
                                    echo json_encode(['error' => 'No file uploaded.']);
                                    http_response_code(400);
                                }
                                break;
                        
                    default:
                        echo "This is forbidden";
                        http_response_code(403);
                        break;
                    }
                        break;
                        case 'GET':
                            $data = json_decode(file_get_contents("php://input"));
                            switch ($request[0]) {
                                case 'getPosts':
                                    echo json_encode($landlord->getPosts());
                                    break;
                                case 'getApartments':
                                    echo json_encode($landlord->getApartments());
                                    break;
                                case 'getTenants':
                                    echo json_encode($landlord->getTenants());
                                    break;
                                default:
                                    echo "Method not available";
                                    http_response_code(404);
                                    break;
                                case 'loadLeases':
                                    echo json_encode($landlord->getLeases());
                                    break;
                                case 'getPaymentDetails':
                                        echo json_encode($tenant->getPaymemtDetails());
                                        break;
                                case 'getConcerns':
                                            echo json_encode($tenant->getConcern());
                                            break;
                                case 'getMonthlyIncome':
                                                $month = $_GET['month'] ?? null;
                                                $year = $_GET['year'] ?? null;
                                    
                                                if ($month && $year) {
                                                    echo json_encode($analytics->getMonthlyIncome($month, $year));
                                                } else {
                                                    echo json_encode(['status' => 'error', 'message' => 'Month and year parameters are required.']);
                                                    http_response_code(400);
                                                }
                                                break;
                                case 'loadImage':
                                            echo json_encode($landlord->getImage());
                                            break;
                            }
                            break;    
                            case 'PUT':
                                $data = json_decode(file_get_contents("php://input"));
                                switch ($request[0]) {
                                    case 'updateApartment':
                                        echo json_encode($landlord->updateApartmentAndTenant($data));
                                        break;
                                    default:
                                        echo "Method not available";
                                        http_response_code(404);
                                        break;
                                }
                                break;
                                                
            case 'DELETE':
                switch ($request[0]) {
                  
            default:
                echo "Method not available";
                http_response_code(404);
                break;
        }
    }
        
?>