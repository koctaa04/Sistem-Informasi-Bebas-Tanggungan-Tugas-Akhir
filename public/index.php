<?php
require_once __DIR__ . '/../config/database.php';

// Mendapatkan controller dan action dari URL
$controller = isset($_GET['controller']) ? $_GET['controller'] : 'home';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

// Routing
switch ($controller) {
    case 'superAdmin':
        include_once __DIR__ . '/../app/controllers/SuperAdminController.php';
        $superAdminController = new SuperAdminController();
        if ($action === 'manageUser') {
            $superAdminController->manageUsers();
        } 
        // elseif ($action === 'ManageDocument') {
        //     $superAdminController->ManageDocument();
        // } 
        elseif ($action === 'addMahasiswa') {
            $superAdminController->addMahasiswa();
        } 
        elseif ($action === 'deleteMahasiswa') {
            $superAdminController->deleteMahasiswa();
        }
        
        // elseif ($action === 'editMahasiswa') {
        //     $id = isset($_GET['id']) ? $_GET['id'] : null;
        //     $superAdminController->editMahasiswa($id);
        // } elseif ($action === 'deleteMahasiswa') {
        //     $id = isset($_GET['id']) ? $_GET['id'] : null;
        //     $superAdminController->deleteMahasiswa($id);
        // } 
        else {
            $superAdminController->dashboard(); // Default action
        }
        break;

    default:
        // include_once __DIR__ . '/app/controllers/HomeController.php';
        // $homeController = new HomeController();
        // $homeController->renderHome();

        include_once __DIR__ . '/../app/controllers/SuperAdminController.php';
        $superAdminController = new SuperAdminController();
        $superAdminController->dashboard(); // Default action
        break;
}
