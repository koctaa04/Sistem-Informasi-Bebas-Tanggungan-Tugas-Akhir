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
        } elseif ($action === 'addMahasiswa') {
            $superAdminController->addMahasiswa();
        } elseif ($action === 'deleteMahasiswa') {
            // Pastikan parameter 'nim' ada di URL
            $nim = isset($_GET['nim']) ? $_GET['nim'] : null;
            $superAdminController->deleteMahasiswa($nim);
        } elseif ($action === 'editMahasiswa') {
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            $superAdminController->editMahasiswa($id);
        } else {
            $superAdminController->dashboard(); // Default action
        }
        break;

    default:
        // Default controller
        include_once __DIR__ . '/../app/controllers/SuperAdminController.php';
        $superAdminController = new SuperAdminController();
        $superAdminController->dashboard(); // Default action
        break;
}
