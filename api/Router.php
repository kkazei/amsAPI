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
    require_once('./services/LandlordHandler.php');
    require_once('./services/TenantHandler.php');
    require_once('./services/AnalyticsHandler.php');

    

    
    $con = new DatabaseAccess();
    $pdo = $con->connect();
    
    
    $register = new RegisterUser($pdo);
    $login = new Login($pdo);
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
                case 'loginLandlord':
                    if (isset($data->email) && isset($data->password)) {
                        echo json_encode($login->loginUserAsLandlord($data->email, $data->password));
                    } else {
                        echo json_encode([
                            'status' => 400,
                            'message' => 'Invalid input data'
                        ]);
                    }
                    break;
                case 'loginTenant':
                    if (isset($data->email) && isset($data->password)) {
                        echo json_encode($login->loginUserAsTenant($data->email, $data->password));
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
                    case 'createMaintenance':
                        echo json_encode($landlord->addMaintenance($data));
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
                                $tenantId = $_POST['tenantId'];
                                $amount = $_POST['amount'];
                                $referenceNumber = $_POST['referenceNumber'];
                                $proofOfPaymentFile = $_FILES['proofOfPayment'] ?? null;
                                $response = $tenant->payInvoice($tenantId, $amount, $referenceNumber, $proofOfPaymentFile);
                                echo json_encode($response);
                                break;
                    case 'concern':
                                $data = $_POST; // Use $_POST to get form data
                                $data['image'] = $_FILES['image'] ?? null; // Add the uploaded file to the data array
                                echo json_encode($tenant->createConcern($data));
                                break;
                    case 'uploadImage':
                                if (isset($_FILES['image'])) {
                                    // Check if 'description' and 'landlord_id' are passed
                                    $description = isset($_POST['description']) ? $_POST['description'] : '';
                                    $landlord_id = isset($_POST['landlord_id']) ? $_POST['landlord_id'] : null;
                            
                                    // Call the addImage method, passing the description and landlord_id
                                    $result = $landlord->addImage($_FILES['image'], $description, $landlord_id);
                            
                                    // Return JSON-encoded data for adding image
                                    echo json_encode($result);
                                } else {
                                    echo json_encode(['error' => 'No file uploaded.']);
                                    http_response_code(400);
                                }
                                break;
                    case 'updatePaymentVisibility':
                                    if (isset($data->invoice_id)) {
                                        echo json_encode($tenant->updatePaymentVisibility($data->invoice_id));
                                    } else {
                                        echo json_encode(['status' => 'error', 'message' => 'Invoice ID not provided']);
                                        http_response_code(400);
                                    }
                                    break;
                    case 'restorePaymentVisibility':
                                        if (isset($data->invoice_id)) {
                                            echo json_encode($tenant->restorePaymentVisibility($data->invoice_id));
                                        } else {
                                            echo json_encode(['status' => 'error', 'message' => 'Invoice ID not provided']);
                                            http_response_code(400);
                                        }
                                        break;
                    case 'archiveMaintenance':
                                            if (isset($data->maintenance_id)) {
                                                echo json_encode($landlord->archiveMaintenance($data->maintenance_id));
                                            } else {
                                                echo json_encode(['status' => 'error', 'message' => 'Maintenance ID not provided']);
                                                http_response_code(400);
                                            }
                                            break;
                    case 'restoreMaintenance':
                                            if (isset($data->maintenance_id)) {
                                                echo json_encode($landlord->restoreMaintenance($data->maintenance_id));
                                            } else {
                                                echo json_encode(['status' => 'error', 'message' => 'Maintenance ID not provided']);
                                                http_response_code(400);
                                            }
                                            break;
                                            case 'importPayments':
                                                if (isset($_FILES['file'])) {
                                                    $file = $_FILES['file']['tmp_name'];
                                                    $data = array_map('str_getcsv', file($file));
                                                    
                                                    // Process the CSV data
                                                    $payments = [];
                                                    foreach ($data as $index => $row) {
                                                        if ($index === 0) continue; // Skip header row
                                                        if (count($row) < 5) continue; // Skip rows with insufficient columns
                                            
                                                        // Validate and sanitize data
                                                        $tenant_id = $row[0] ?? null;
                                                        $tenant_fullname = $row[1] ?? null;
                                                        $room = $row[2] ?? null;
                                                        $amount = $row[3] ?? null;
                                                        $payment_date = $row[4] ?? null;
                                            
                                                        // Ensure the payment date is in the correct format
                                                        if ($payment_date) {
                                                            $payment_date = date('Y-m-d', strtotime($payment_date));
                                                            if ($payment_date === '1970-01-01') {
                                                                $payment_date = null; // Set to null if the date is invalid
                                                            }
                                                        } else {
                                                            $payment_date = null; // Set to null if the date is empty
                                                        }
                                            
                                                        $payments[] = [
                                                            'tenant_id' => $tenant_id,
                                                            'tenant_fullname' => $tenant_fullname,
                                                            'room' => $room,
                                                            'amount' => $amount,
                                                            'payment_date' => $payment_date
                                                        ];
                                                    }
                                            
                                                    echo json_encode($tenant->importPayments($payments));
                                                } else {
                                                    echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
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
                                        echo json_encode($tenant->getPaymentDetails());
                                        break;
                                case 'getArchivedPayments':
                                            echo json_encode($tenant->getArchivedPayments());
                                            break;
                                case 'getMaintenance':
                                            echo json_encode($landlord->getMaintenance());
                                            break;
                                case 'getArchivedMaintenance':
                                                echo json_encode($landlord->getArchivedMaintenance());
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
                                    case 'updateMaintenance':
                                            echo json_encode($landlord->updateMaintenance($data));
                                            break;
                                    case 'updateConcern':
                                                echo json_encode($landlord->updateConcern($data));
                                                break;
                                    default:
                                        echo "Method not available";
                                        http_response_code(404);
                                        break;
                                }
                                break;
                                                
                                case 'DELETE':
                                    $data = json_decode(file_get_contents("php://input"));
                                    switch ($request[0]) {
                                        case 'deleteTenant':
                                            if (isset($data->tenantId)) {
                                                echo json_encode($tenant->deleteTenant($data->tenantId));
                                            } else {
                                                echo json_encode(['error' => 'Tenant ID not provided.']);
                                                http_response_code(400);
                                            }
                                            break;
                                        case 'deleteImage':
                                                if (isset($data->landlordId)) {
                                                    echo json_encode($landlord->deleteImage($data->landlordId));
                                                } else {
                                                    echo json_encode(['code' => 400, 'errmsg' => 'Landlord ID is required']);
                                                    http_response_code(400);
                                                }
                                                break;
                                        case 'deleteInvoice':
                                                    if (isset($data->invoice_id)) {
                                                        echo json_encode($tenant->deleteInvoice($data->invoice_id));
                                                    } else {
                                                        echo json_encode(['error' => 'Invoice ID not provided.']);
                                                        http_response_code(400);
                                                    }
                                                    break;
                                        case 'deleteMaintenance':
                                                        if (isset($data->maintenance_id)) {
                                                            echo json_encode($landlord->deleteMaintenance($data->maintenance_id));
                                                        } else {
                                                            echo json_encode(['error' => 'Maintenance ID not provided.']);
                                                            http_response_code(400);
                                                        }
                                                        break;
                                        case 'deletePost':
                                                            if (isset($data->post_id)) {
                                                                echo json_encode($landlord->deletePost($data->post_id));
                                                            } else {
                                                                echo json_encode(['error' => 'Post ID not provided.']);
                                                                http_response_code(400);
                                                            }
                                                            break;
                                        // Add other DELETE cases here
                                        default:
                                            echo "This is forbidden";
                                            http_response_code(403);
                                            break;
                                    }
                                    break;
                                }
        
?>