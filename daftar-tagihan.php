
<?php
require_once "koneksi.php";

header("Content-Type: application/json; charset=UTF-8");

$sql = "SELECT
            t.id_tagihan,
            t.id_penghuni,
            p.nama_penghuni,
            t.periode_bulan,
            t.jumlah_tagihan,
            t.tanggal_jatuh_tempo,
            t.status_tagihan
        FROM tagihan t
        INNER JOIN penghuni p
            ON t.id_penghuni = p.id_penghuni
        WHERE t.status_tagihan = 'belum_bayar'
        ORDER BY t.periode_bulan DESC";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Gagal mengambil data tagihan."
    ]);
    exit;
}

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        "id_tagihan" => (int) $row["id_tagihan"],
        "nama_penghuni" => $row["nama_penghuni"],
        "periode" => date(
            "F Y",
            strtotime($row["periode_bulan"])
        ),
        "jumlah_tagihan" => (float) $row["jumlah_tagihan"],
        "tanggal_jatuh_tempo" =>
            $row["tanggal_jatuh_tempo"],
        "status_tagihan" => $row["status_tagihan"]
    ];
}

echo json_encode([
    "success" => true,
    "data" => $data
]);

$conn->close();
?>
