<?php
require_once __DIR__ . '/../Models/SuperAdminModel.php';
require_once __DIR__ . '/../../config/Database.php';
session_start();

class SuperAdminController
{
    private $model;

    public function __construct()
    {
        // Gunakan koneksi dari Database::connect()
        $dbConnection = Database::connect();
        $this->model = new SuperAdminModel($dbConnection);
    }


    // DONE
    public function dashboard()
    {

        try {
            // Ambil data dari model
            $mahasiswaCount = $this->model->getMahasiswaCount();
            $verifikatorCount = $this->model->getVerifikatorCount();
            $adminCount = $this->model->getAdminCount();
            // $documents = $this->model->getDocuments(); //MINUS INI
            // Validasi data sebelum dikirim ke view
            if ($mahasiswaCount === null || $verifikatorCount === null || $adminCount === null) {
                throw new Exception("Failed to fetch data from the database.");
            }

            // Kirim data ke view
            $viewPath = __DIR__ . '/../Views/Superadmin/admin_dashboard.php';
            if (file_exists($viewPath)) {
                require_once($viewPath);
            } else {
                throw new Exception("View file not found: $viewPath");
            }
        } catch (Exception $e) {
            die("Error loading dashboard: " . $e->getMessage());
        }
    }

    public function manageUsers()
    {
        try {
            // Ambil data mahasiswa dari model
            $dataMahasiswa = $this->model->getMahasiswaData();
            $prodiList = $this->model->getAllProdi();
            $jurusanList = $this->model->getAllJurusan();
            $angkatanList = $this->model->getAllAngkatan();

            // Kirim data ke view
            $viewPath = __DIR__ . '/../Views/Superadmin/manajemen_mahasiswa.php';
            if (file_exists($viewPath)) {
                require_once($viewPath);
            } else {
                throw new Exception("View file not found: $viewPath");
            }
        } catch (Exception $e) {
            die("Error loading user management: " . $e->getMessage());
        }
    }


    public function manageDocuments()
    {
        try {
            $documents = $this->model->getDocuments();
            $viewPath = __DIR__ . '/../Views/Superadmin/manajemen_dokumen.php';
            if (file_exists($viewPath)) {
                require_once($viewPath);
            } else {
                throw new Exception("View file not found: $viewPath");
            }
        } catch (Exception $e) {
            die("Error loading document management: " . $e->getMessage());
        }
    }

    public function addMahasiswa()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nim = $_POST['nim'];
            $password = $_POST['password'];
            $nama = $_POST['nama'];
            $no_telp = $_POST['no_telp'];
            $email = $_POST['email'];
            $kelas = $_POST['kelas'];
            $id_prodi = $_POST['id_prodi'];
            $id_jurusan = $_POST['id_jurusan'];
            $id_angkatan = $_POST['id_angkatan'];
            
            try {
                $this->model->addMahasiswa($nim, $nama, $password, $no_telp, $email, $kelas, $id_prodi, $id_jurusan, $id_angkatan);
                $_SESSION['status'] = 'success';
                $_SESSION['message'] = 'Mahasiswa berhasil ditambahkan!';
            } catch (Exception $e) {
                $_SESSION['status'] = 'error';
                $_SESSION['message'] = 'Terjadi kesalahan: ' . $e->getMessage();
            }
            header("Location: index.php?controller=superAdmin&action=manageUser");
            exit;
        } else {
            include __DIR__ . '/../../app/views/superadmin/addMahasiswa.php';
        }
    }
    
    public function deleteMahasiswa()
    {
        if (isset($_GET['nim'])) {
            $nim = $_GET['nim'];

            try {
                $this->model->deleteMahasiswa($nim);
                $_SESSION['status'] = 'success';
                $_SESSION['message'] = 'Mahasiswa berhasil dihapus!';
            } catch (Exception $e) {
                $_SESSION['status'] = 'error';
                $_SESSION['message'] = 'Terjadi kesalahan saat menghapus mahasiswa: ' . $e->getMessage();
            }

            // Redirect ke halaman daftar mahasiswa
            header("Location: index.php?controller=superAdmin&action=manageUser");
            exit();
        } else {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'NIM tidak ditemukan!';
            header("Location: index.php?controller=superAdmin&action=manageUser");
            exit();
        }
    }
}
