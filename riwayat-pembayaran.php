
<?php
require_once 'koneksi.php';

$sql = "SELECT
            p.id_pembayaran,
            p.tanggal_pembayaran,
            p.jumlah_bayar,
            p.bukti_pembayaran,
            p.status_verifikasi,
            p.keterangan,
            t.periode_bulan,
            h.nama_penghuni
        FROM pembayaran p
        JOIN tagihan t ON p.id_tagihan = t.id_tagihan
        JOIN penghuni h ON t.id_penghuni = h.id_penghuni
        ORDER BY p.id_pembayaran DESC";

$result = $conn->query($sql);

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka) {
    return 'Rp' . number_format((float) $angka, 0, ',', '.');
}

function periode($tanggal) {
    return date('F Y', strtotime($tanggal));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Riwayat Pembayaran | Kelola Kost</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #f3f6fc;
            color: #1e293b;
            font-family: Arial, sans-serif;
        }

        /* SIDEBAR */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 267px;
            height: 100vh;
            padding: 28px 24px;
            background: #192957;
            color: white;
            overflow-y: auto;
            z-index: 10;
        }

        .brand {
            margin-bottom: 6px;
            font-size: 25px;
            font-weight: bold;
        }

        .brand span {
            color: #93c5fd;
        }

        .subtitle-brand {
            margin-bottom: 40px;
            color: #e2e8f0;
            font-size: 13px;
        }

        .menu-title {
            margin: 24px 10px 12px;
            color: #aabbd9;
            font-size: 12px;
            text-transform: uppercase;
        }

        .menu a {
            display: block;
            margin-bottom: 8px;
            padding: 14px 12px;
            border-radius: 9px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
        }

        .menu a:hover,
        .menu a.active {
            background: #354e83;
        }

        .sidebar-info {
            padding: 10px;
            color: #cbd5e1;
            font-size: 13px;
            line-height: 1.8;
        }

        /* KONTEN UTAMA */
        .container {
            width: auto;
            margin-left: 267px;
            padding: 32px 44px;
        }

        .welcome {
            margin-bottom: 28px;
        }

        h1 {
            margin: 0 0 10px;
            font-size: 28px;
            color: #1e293b;
        }

        .subtitle {
            margin: 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.7;
        }

        .back {
            display: inline-block;
            margin-bottom: 24px;
            padding: 11px 16px;
            background: #2563eb;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
        }

        .back:hover {
            background: #1d4ed8;
            text-decoration: none;
        }

        /* TABEL RIWAYAT */
        .card {
            padding: 24px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow-x: auto;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.02);
        }

        table {
            width: 100%;
            min-width: 800px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e8edf5;
            font-size: 14px;
            white-space: nowrap;
        }

        th {
            background: #f8fafc;
            color: #64748b;
            font-weight: bold;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        /* STATUS PEMBAYARAN */
        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .menunggu {
            background: #fef3c7;
            color: #92400e;
        }

        .diterima {
            background: #d1fae5;
            color: #065f46;
        }

        .ditolak {
            background: #fee2e2;
            color: #991b1b;
        }

        .lainnya {
            background: #e2e8f0;
            color: #334155;
        }

        .proof-link {
            color: #2563eb;
            text-decoration: none;
        }

        .proof-link:hover {
            text-decoration: underline;
        }

        .empty {
            padding: 28px;
            text-align: center;
            color: #64748b;
            white-space: normal;
        }

        .error {
            padding: 14px;
            margin-bottom: 18px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 8px;
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .sidebar {
                width: 220px;
                padding: 24px 16px;
            }

            .container {
                margin-left: 220px;
                padding: 28px 20px;
            }
        }

        @media (max-width: 600px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 20px;
            }

            .subtitle-brand {
                margin-bottom: 20px;
            }

            .menu-title {
                margin-top: 18px;
            }

            .container {
                margin-left: 0;
                padding: 22px 14px;
            }

            h1 {
                font-size: 24px;
            }

            .card {
                padding: 12px;
            }
        }
    </style>
</head>

<body>

<!-- SIDEBAR KELOLA KOST -->
<aside class="sidebar">

    <div class="brand">
        Kelola<span>Kost.</span>
    </div>

    <div class="subtitle-brand">
        Portal Penghuni Kost
    </div>

    <div class="menu-title">Menu Utama</div>

    <nav class="menu">
        <a href="index.html">⌂ Dashboard</a>

        <a href="index.html">▤ Pembayaran</a>

        <a href="index.html">＋ Bayar Kost</a>

        <a href="riwayat-pembayaran.php" class="active">
            ▤ Riwayat Pembayaran
        </a>
    </nav>

    <div class="menu-title">Informasi</div>

    <div class="sidebar-info">
        Pantau tagihan dan pembayaran kost kamu dengan mudah.
    </div>

</aside>

<!-- KONTEN RIWAYAT -->
<main class="container">

    <a class="back" href="index.html">
        &larr; Kembali ke Dashboard
    </a>

    <section class="welcome">
        <h1>Riwayat Pembayaran</h1>

        <p class="subtitle">
            Lihat transaksi pembayaran kost dan status verifikasinya.
        </p>
    </section>

    <?php if (!$result): ?>

        <div class="error">
            Data riwayat gagal dimuat.
            Periksa kembali koneksi database dan query SQL.
        </div>

    <?php else: ?>

        <section class="card">

            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Penghuni</th>
                        <th>Periode Tagihan</th>
                        <th>Tanggal Bayar</th>
                        <th>Nominal</th>
                        <th>Status</th>
                        <th>Bukti</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($result->num_rows > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <?php
                        $status = strtolower(
                            (string) $row['status_verifikasi']
                        );

                        $kelas = in_array(
                            $status,
                            ['menunggu', 'diterima', 'ditolak'],
                            true
                        ) ? $status : 'lainnya';

                        $tanggal = strtotime(
                            $row['tanggal_pembayaran']
                        );

                        $bulan = strtotime(
                            $row['periode_bulan']
                        );
                        ?>

                        <tr>
                            <td><?= $no++ ?></td>

                            <td>
                                <?= e($row['nama_penghuni']) ?>
                            </td>

                            <td>
                                <?= $bulan !== false
                                    ? e(date('F Y', $bulan))
                                    : e($row['periode_bulan']) ?>
                            </td>

                            <td>
                                <?= $tanggal !== false
                                    ? e(date('d-m-Y', $tanggal))
                                    : '-' ?>
                            </td>

                            <td>
                                <?= e(rupiah($row['jumlah_bayar'])) ?>
                            </td>

                            <td>
                                <span class="status <?= e($kelas) ?>">
                                    <?= e(ucfirst($status)) ?>
                                </span>
                            </td>

                            <td>
                                <?php
                                $bukti = trim(
                                    (string) $row['bukti_pembayaran']
                                );
                                ?>

                                <?php if ($bukti !== ''): ?>
                                    <a
                                        class="proof-link"
                                        href="<?= e($bukti) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Lihat bukti
                                    </a>
                                <?php else: ?>
                                    Belum tersedia
                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="empty">
                            Belum ada riwayat pembayaran.
                        </td>
                    </tr>

                <?php endif; ?>
                </tbody>
            </table>

        </section>

    <?php endif; ?>

</main>

</body>
</html>
