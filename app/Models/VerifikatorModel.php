<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/Model.php';
class VerifikatorModel extends Model {

    private $conn;

    public function __construct($dbConnection)
    {
        Model::__construct($dbConnection);
        $this->conn = $dbConnection;
    }


    public function getBelumDiverifikasiCount($jenisDokumen, $tahun) {
        $stmt = $this->conn->prepare ("
            SELECT COUNT(*) AS Terverifikasi
            FROM Verifikasi v
            JOIN Mahasiswa m ON v.nim = m.nim
            JOIN Angkatan a ON m.id_angkatan = a.id_angkatan
            JOIN Dokumen d ON v.id_dokumen = d.id_dokumen
            WHERE v.status_verifikasi = 'Menunggu Diverifikasi'
            AND a.role_angkatan = :tahun
            AND d.jenis_dokumen = :jenisDokumen  -- Filter berdasarkan jenis_dokumen
        ");
        $stmt->bindParam(':jenisDokumen', $jenisDokumen);
        $stmt->bindParam(':tahun', $tahun);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['Terverifikasi'];
    }

    public function getTerverifikasiCount($jenisDokumen, $tahun) {
        $stmt = $this->conn->prepare ("
            SELECT COUNT(*) AS Terverifikasi
            FROM Verifikasi v
            JOIN Mahasiswa m ON v.nim = m.nim
            JOIN Angkatan a ON m.id_angkatan = a.id_angkatan
            JOIN Dokumen d ON v.id_dokumen = d.id_dokumen
            WHERE v.status_verifikasi = 'Disetujui'
            AND a.role_angkatan = :tahun
            AND d.jenis_dokumen = :jenisDokumen  -- Filter berdasarkan jenis_dokumen
        ");
        $stmt->bindParam(':jenisDokumen', $jenisDokumen);
        $stmt->bindParam(':tahun', $tahun);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['Terverifikasi'];
    }

    
    public function getMahasiswaCount($tahun) {
        $stmt = $this->conn->prepare ("
            SELECT COUNT(*) AS jumlah_mahasiswa
            FROM [User] u
            JOIN Mahasiswa m ON u.id_user = m.id_user
            JOIN Angkatan a ON m.id_angkatan = a.id_angkatan
            WHERE u.role_user = 'mahasiswa'
            AND a.role_angkatan = :tahun
        ");
        
        $stmt->bindParam(':tahun', $tahun);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        return $result['jumlah_mahasiswa'];
    }
    
    public function getMhsWithDocumentCompleteJurusan() {
        $stmt = $this->conn->prepare("
            SELECT
            vm.nim,
            vm.nama,
            vm.no_telp,
            vm.role_prodi,
            vm.role_jurusan,
            vm.role_angkatan,
            vm.kelas, 
            vm.tgl_upload,
            COUNT(v.id_verifikasi) AS verifikasi_count,
            SUM(CASE WHEN v.status_verifikasi = 'Disetujui' THEN 1 ELSE 0 END) AS disetujui_count
        FROM Verif_Mhs_Jurusan vm
        JOIN Verifikasi v ON vm.nim = v.nim
        JOIN Dokumen d ON v.id_dokumen = d.id_dokumen
        WHERE d.jenis_dokumen = 'Jurusan'
        AND v.status_verifikasi IN ('Menunggu Diverifikasi', ' Tidak Disetujui', 'Disetujui')
        GROUP BY
            vm.nim, 
            vm.nama, 
            vm.no_telp, 
            vm.role_prodi, 
            vm.role_jurusan, 
            vm.role_angkatan, 
            vm.kelas, 
            vm.tgl_upload
        HAVING 
            SUM(CASE WHEN v.status_verifikasi = 'Disetujui' THEN 1 ELSE 0 END) < 7  -- Tampilkan data jika Disetujui < 7
        ORDER BY
            vm.tgl_upload ASC;
        ");
    

    
        // Execute the query
        $stmt->execute();
    
        // Fetch results
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $result; // Return all results
    }

    public function getMhsWithDocumentCompletePusat() {
        $stmt = $this->conn->prepare("
            SELECT
            vm.nim,
            vm.nama,
            vm.no_telp,
            vm.role_prodi,
            vm.role_jurusan,
            vm.role_angkatan,
            vm.kelas, 
            vm.tgl_upload,
            COUNT(v.id_verifikasi) AS verifikasi_count,
            SUM(CASE WHEN v.status_verifikasi = 'Disetujui' THEN 1 ELSE 0 END) AS disetujui_count
        FROM Verif_Mhs_Jurusan vm
        JOIN Verifikasi v ON vm.nim = v.nim
        JOIN Dokumen d ON v.id_dokumen = d.id_dokumen
        WHERE d.jenis_dokumen = 'Pusat'
        AND v.status_verifikasi IN ('Menunggu Diverifikasi', ' Tidak Disetujui', 'Disetujui')
        GROUP BY
            vm.nim, 
            vm.nama, 
            vm.no_telp, 
            vm.role_prodi, 
            vm.role_jurusan, 
            vm.role_angkatan, 
            vm.kelas, 
            vm.tgl_upload
        HAVING 
            SUM(CASE WHEN v.status_verifikasi = 'Disetujui' THEN 1 ELSE 0 END) < 6  -- Tampilkan data jika Disetujui < 7
        ORDER BY
            vm.tgl_upload ASC;
        ");
    
        // Execute the query
        $stmt->execute();
    
        // Fetch results
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $result; // Return all results
    }
    

    public function getDocument($jenisDokumen, $nim) {
        $stmt = $this->conn->prepare("
            SELECT v.path, d.nama_dokumen
            FROM Verifikasi v
            JOIN Mahasiswa m ON v.nim = m.nim
            JOIN Dokumen d ON v.id_dokumen = d.id_dokumen
            WHERE d.jenis_dokumen = :jenisDokumen
            AND m.nim = :nim
            ORDER BY d.id_dokumen ASC;
        ");
    
        $stmt->bindParam(':jenisDokumen', $jenisDokumen);
        $stmt->bindParam(':nim', $nim);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        return $result;  // Mengembalikan array asosiatif dari hasil query
    }
    
    
    public function updateStatusVerifikasi($id_dokumen, $statusVerifikasi) {
        // Query SQL untuk update status_verifikasi
        $stmt = $this->conn->prepare ("
            UPDATE Verifikasi
            SET status_verifikasi = :statusVerifikasi
            WHERE id_verifikasi = :idVerifikasi
        ");
        
        // Parameter untuk bind data
        $stmt->bindParam(':statusVerifikasi', $statusVerifikasi);
        $stmt->bindParam(':idVerifikasi', $id_dokumen);

        $allowedStatuses = [' Tidak Disetujui', 'Disetujui'];
        if (!in_array($statusVerifikasi, $allowedStatuses)) {
            throw new Exception("Status tidak valid.");
        }

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $result['Mhs_Dokumen_Lengkap'];
    }

    public function updateCatatanVerifikasi($id_dokumen, $catatan) {
        $query = "
            UPDATE Verifikasi
            SET catatan = :catatan
            WHERE id_verifikasi = :idVerifikasi
        ";
        
        $params = [
            ':catatan' => $catatan,
            ':idVerifikasi' => $id_dokumen
        ];
        
        $result = $this->executeUpdateQuery($query, $params);
    
        // Return hasil eksekusi
        return $result > 0; // Mengembalikan true jika berhasil, false jika gagal (jika baris terpengaruh lebih dari 0)
    }
    

    public function getMhsWithDocumentApproved($jenisDokumen) {
        $query = "
            SELECT
                vm.nim,
                vm.nama,
                vm.no_telp,
                vm.role_prodi,
                vm.role_jurusan,
                vm.role_angkatan,
                vm.kelas
            FROM Tabel_Verif_Mhs vm
            JOIN Verifikasi v ON vm.nim = v.nim
            JOIN Dokumen d ON v.id_dokumen = d.id_dokumen
            WHERE d.jenis_dokumen = :jenisDokumen  -- Filter berdasarkan jenis_dokumen (jenis dokumen Pusat/Jurusan)
              AND v.status_verifikasi = 'Disetujui' -- Filter hanya dokumen dengan status verifikasi 'Disetujui'
            GROUP BY 
                vm.nim, 
            HAVING COUNT(v.id_verifikasi) = 6  -- Filter mahasiswa yang memiliki 6 dokumen dengan jenis dokumen tertentu
            ORDER BY
                vm.role_angkatan DESC, -- Urutkan berdasarkan angkatan (terbaru ke yang lama)
                vm.kelas ASC; -- Jika angkatan sama, urutkan berdasarkan abjad kelas
        ";
        
        // Bind parameter jenis_dokumen
        $params = [':jenisDokumen' => $jenisDokumen];
        
        // Execute the query
        $result = $this->executeQueryFetchAll($query, $params);
        
        return $result;
    }
    
}    