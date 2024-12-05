<?php
require_once __DIR__ . '/../../config/database.php';


class SuperAdminModel {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    public function getMahasiswaCount() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM Mahasiswa");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    

    public function getVerifikatorCount() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM [User] WHERE role_user IN ('admin jurusan', 'admin pusat')");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getAdminCount() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM [User] WHERE role_user = 'superadmin'");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getDocuments() {
        $stmt = $this->conn->prepare("
        SELECT 
    u.nama AS nama_mahasiswa,
    v.tgl_verifikasi,
    v.status_verifikasi,
    d.nama_dokumen
FROM 
    [dbo].[Verifikasi] v
JOIN 
    [dbo].[Mahasiswa] m ON v.nim = m.nim
JOIN 
    [dbo].[User] u ON m.id_user = u.id_user
JOIN 
    [dbo].[Dokumen] d ON v.id_dokumen = d.id_dokumen;

        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fungsi untuk mengambil data mahasiswa
    public function getMahasiswaData() {
        $stmt = $this->conn->prepare("
            SELECT 
                m.nim, 
                u.nama AS nama_mahasiswa, 
                u.password AS password, 
                p.role_prodi AS prodi,
                j.role_jurusan AS jurusan, 
                a.role_angkatan AS angkatan, 
                m.kelas, 
                u.no_telp 
            FROM Mahasiswa m
            JOIN [User] u ON m.id_user = u.id_user
            JOIN Prodi p ON m.id_prodi = p.id_prodi
            JOIN Jurusan j ON m.id_jurusan = j.id_jurusan
            JOIN Angkatan a ON m.id_angkatan = a.id_angkatan
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fungsi untuk menambah mahasiswa
    public function addMahasiswa($nim, $nama, $password, $no_telp, $email, $kelas, $id_prodi, $id_jurusan, $id_angkatan)
    {
        try {
            // Tambah data ke tabel User
            $stmtUser = $this->conn->prepare("
                INSERT INTO [User] (role_user, username, password, nama, no_telp, email)
                VALUES ('mahasiswa', :username, :password, :nama, :no_telp, :email)
            ");
            $stmtUser->execute([
                ':username' => $nim, // Username sama dengan NIM
                ':password' => $password, 
                ':nama' => $nama,
                ':no_telp' => $no_telp,
                ':email' => $email
            ]);
    
            // Ambil ID User yang baru saja dimasukkan
            $id_user = $this->conn->lastInsertId();
    
            // Tambah data ke tabel Mahasiswa
            $stmtMahasiswa = $this->conn->prepare("
                INSERT INTO Mahasiswa (nim, id_user, kelas, id_prodi, id_jurusan, id_angkatan)
                VALUES (:nim, :id_user, :kelas, :id_prodi, :id_jurusan, :id_angkatan)
            ");
            $stmtMahasiswa->execute([
                ':nim' => $nim,
                ':id_user' => $id_user,
                ':kelas' => $kelas,
                ':id_prodi' => $id_prodi,
                ':id_jurusan' => $id_jurusan,
                ':id_angkatan' => $id_angkatan
            ]);
    
            return true;
        } catch (Exception $e) {
            throw new Exception("Gagal menambahkan mahasiswa: " . $e->getMessage());
        }
    }
    

    // Fungsi untuk mengedit data mahasiswa
public function editMahasiswa($id_mahasiswa, $nim, $prodi, $jurusan, $angkatan, $kelas, $nama, $no_telp) {
    $stmt = $this->conn->prepare("
        UPDATE Mahasiswa 
        SET 
            nim = :nim, 
            id_prodi = (SELECT id_prodi FROM Prodi WHERE role_prodi = :prodi), 
            id_jurusan = (SELECT id_jurusan FROM Jurusan WHERE role_jurusan = :jurusan),
            id_angkatan = (SELECT id_angkatan FROM Angkatan WHERE role_angkatan = :angkatan),
            kelas = :kelas, 
            id_user = (SELECT id_user FROM [User] WHERE nama = :nama AND no_telp = :no_telp)
        WHERE id_mahasiswa = :id_mahasiswa
    ");
    // Bind parameter
    $stmt->bindParam(':id_mahasiswa', $id_mahasiswa);
    $stmt->bindParam(':nim', $nim);
    $stmt->bindParam(':prodi', $prodi);
    $stmt->bindParam(':jurusan', $jurusan);
    $stmt->bindParam(':angkatan', $angkatan);
    $stmt->bindParam(':kelas', $kelas);
    $stmt->bindParam(':nama', $nama);
    $stmt->bindParam(':no_telp', $no_telp);
    
    // Eksekusi query
    $stmt->execute();
}

    // Fungsi untuk menghapus mahasiswa
    public function deleteMahasiswa($nim) {
        $stmt = $this->conn->prepare("DELETE FROM Mahasiswa WHERE nim = :nim");
        $stmt->bindParam(':nim', $nim);
        $stmt->execute();
    }

    // Ambil semua data Prodi
    public function getAllProdi()
    {
        $stmt = $this->conn->prepare("SELECT id_prodi, role_prodi FROM Prodi");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil semua data Jurusan
    public function getAllJurusan()
    {
        $stmt = $this->conn->prepare("SELECT id_jurusan, role_jurusan FROM Jurusan");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil semua data Angkatan
    public function getAllAngkatan()
    {
        $stmt = $this->conn->prepare("SELECT id_angkatan, role_angkatan FROM Angkatan");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
