<?php
require_once __DIR__ . '/../../config/database.php';


class SuperAdminModel
{
    private $conn;

    public function __construct($dbConnection)
    {
        $this->conn = $dbConnection;
    }

    public function getMahasiswaCount()
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM Mahasiswa");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }


    public function getVerifikatorCount()
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM [User] WHERE role_user IN ('admin jurusan', 'admin pusat')");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getAdminCount()
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM [User] WHERE role_user = 'superadmin'");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getDocuments()
    {
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
    public function getMahasiswaData()
    {
        $stmt = $this->conn->prepare("
            SELECT 
                m.nim, 
                u.nama AS nama_mahasiswa, 
                u.password AS password, 
                u.email AS email,
                p.role_prodi AS prodi,
                p.id_prodi AS id_prodi,
                j.role_jurusan AS jurusan, 
                j.id_jurusan AS id_jurusan, 
                a.role_angkatan AS angkatan, 
                a.id_angkatan AS id_angkatan, 
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


    public function updateMahasiswa($nim, $password, $nama, $no_telp, $email, $kelas, $id_prodi, $id_jurusan, $id_angkatan)
    {
        try {
            // Mulai transaksi
            $this->conn->beginTransaction();

            // Update data mahasiswa
            $stmt = $this->conn->prepare("UPDATE Mahasiswa SET 
                                            id_prodi = :id_prodi, 
                                            id_jurusan = :id_jurusan,
                                            id_angkatan = :id_angkatan,
                                            kelas = :kelas
                                          WHERE nim = :nim");
            $stmt->bindParam(':nim', $nim);
            $stmt->bindParam(':id_prodi', $id_prodi);
            $stmt->bindParam(':id_jurusan', $id_jurusan);
            $stmt->bindParam(':id_angkatan', $id_angkatan);
            $stmt->bindParam(':kelas', $kelas);
            $stmt->execute();

            // Update data user
            $stmt = $this->conn->prepare("UPDATE [User] SET 
                                            password = :password, 
                                            nama = :nama, 
                                            no_telp = :no_telp, 
                                            email = :email
                                          WHERE id_user = (SELECT id_user FROM Mahasiswa WHERE nim = :nim)");
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':nama', $nama);
            $stmt->bindParam(':no_telp', $no_telp);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':nim', $nim);
            $stmt->execute();

            // Commit transaksi
            $this->conn->commit();
            return true; // Menyatakan bahwa update berhasil
        } catch (PDOException $e) {
            // Rollback jika terjadi error
            $this->conn->rollBack();
            throw new Exception("Terjadi kesalahan: " . $e->getMessage());
        }
    }

    // Fungsi untuk mendapatkan id_user berdasarkan nim
    public function getIdUserByNim($nim)
    {
        $stmt = $this->conn->prepare("SELECT id_user FROM Mahasiswa WHERE nim = :nim");
        $stmt->bindParam(':nim', $nim);
        $stmt->execute();

        return $stmt->fetchColumn(); // Mengembalikan id_user atau null
    }

    // Fungsi untuk menghapus mahasiswa berdasarkan NIM
    public function deleteMahasiswa($nim)
    {
        $stmt = $this->conn->prepare("DELETE FROM Mahasiswa WHERE nim = :nim");
        $stmt->bindParam(':nim', $nim);
        return $stmt->execute(); // Menghapus mahasiswa
    }

    // Fungsi untuk menghapus user berdasarkan id_user
    public function deleteUser($id_user)
    {
        $stmt = $this->conn->prepare("DELETE FROM [User] WHERE id_user = :id_user");
        $stmt->bindParam(':id_user', $id_user);
        return $stmt->execute();
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
