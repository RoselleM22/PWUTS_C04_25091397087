
<?php
require_once "koneksi.php";

header("Content-Type: application/json; charset=UTF-8");

function kirimRespons($success, $message, $status = 200) {
    http_response_code($status);
    echo json_encode([
        "success" => $success,
        "message" => $message
    ]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    kirimRespons(false, "Metode permintaan tidak valid.", 405);
}

$idTagihan = filter_input(INPUT_POST, "id_tagihan", FILTER_VALIDATE_INT);
$tanggal = $_POST["tanggal"] ?? "";
$metode = trim($_POST["metode"] ?? "");

if (!$idTagihan || $idTagihan < 1) {
    kirimRespons(false, "Pilih tagihan yang valid.", 400);
}

if (!in_array($metode, ["Transfer Bank", "E-Wallet", "Tunai"], true)) {
    kirimRespons(false, "Metode pembayaran tidak valid.", 400);
}

$date = DateTime::createFromFormat("!Y-m-d", $tanggal);

if (!$date || $date->format("Y-m-d") !== $tanggal) {
    kirimRespons(false, "Tanggal pembayaran tidak valid.", 400);
}

if ($tanggal > date("Y-m-d")) {
    kirimRespons(false, "Tanggal pembayaran tidak boleh di masa depan.", 400);
}

if (
    !isset($_FILES["bukti"]) ||
    $_FILES["bukti"]["error"] !== UPLOAD_ERR_OK
) {
    kirimRespons(false, "Bukti pembayaran wajib diunggah.", 400);
}

$file = $_FILES["bukti"];

if ($file["size"] > 2 * 1024 * 1024) {
    kirimRespons(false, "Ukuran bukti maksimal 2 MB.", 400);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file["tmp_name"]);

$tipeDiizinkan = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp"
];

if (!isset($tipeDiizinkan[$mime])) {
    kirimRespons(false, "Gunakan gambar JPG, PNG, atau WebP.", 400);
}

try {
    $conn->begin_transaction();

    // Kunci tagihan agar tidak diproses dua kali bersamaan.
    $stmt = $conn->prepare(
        "SELECT jumlah_tagihan, status_tagihan
         FROM tagihan
         WHERE id_tagihan = ?
         FOR UPDATE"
    );
    $stmt->bind_param("i", $idTagihan);
    $stmt->execute();
    $tagihan = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$tagihan) {
        $conn->rollback();
        kirimRespons(false, "Tagihan tidak ditemukan.", 404);
    }

    if ($tagihan["status_tagihan"] !== "belum_bayar") {
        $conn->rollback();
        kirimRespons(
            false,
            "Tagihan ini sudah diproses atau tidak lagi tersedia.",
            409
        );
    }

    // Nominal diambil dari database, bukan input browser.
    $jumlahBayar = (float) $tagihan["jumlah_tagihan"];

    $folder = __DIR__ . "/uploads/bukti-pembayaran/";

    if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
        $conn->rollback();
        kirimRespons(false, "Folder bukti pembayaran gagal dibuat.", 500);
    }

    $namaFile = bin2hex(random_bytes(16))
        . "." . $tipeDiizinkan[$mime];

    $tujuan = $folder . $namaFile;
    $lokasiDatabase = "uploads/bukti-pembayaran/" . $namaFile;

    if (!move_uploaded_file($file["tmp_name"], $tujuan)) {
        $conn->rollback();
        kirimRespons(false, "Bukti pembayaran gagal disimpan.", 500);
    }

    $keterangan = "Metode pembayaran: " . $metode;

    $stmt = $conn->prepare(
        "INSERT INTO pembayaran
         (id_tagihan, tanggal_pembayaran, jumlah_bayar,
          bukti_pembayaran, status_verifikasi, keterangan)
         VALUES (?, ?, ?, ?, 'menunggu', ?)"
    );

    $stmt->bind_param(
        "isdss",
        $idTagihan,
        $tanggal,
        $jumlahBayar,
        $lokasiDatabase,
        $keterangan
    );

    $stmt->execute();
    $stmt->close();

    // Menandai tagihan sedang menunggu verifikasi.
    $stmt = $conn->prepare(
        "UPDATE tagihan
         SET status_tagihan = 'menunggu_verifikasi'
         WHERE id_tagihan = ?"
    );
    $stmt->bind_param("i", $idTagihan);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    kirimRespons(
        true,
        "Pengajuan pembayaran berhasil disimpan dan menunggu verifikasi."
    );

} catch (Throwable $e) {
    if ($conn->errno === 0) {
        // Tidak perlu melakukan perubahan tambahan di sini.
    }

    try {
        $conn->rollback();
    } catch (Throwable $ignored) {
        // Abaikan jika transaksi sudah selesai.
    }

    if (isset($tujuan) && is_file($tujuan)) {
        unlink($tujuan);
    }

    error_log($e->getMessage());
    kirimRespons(false, "Terjadi kesalahan saat menyimpan pembayaran.", 500);
}

$conn->close();
?>
