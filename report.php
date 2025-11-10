<?php
include 'config.php';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$result = $conn->query("
    SELECT f.id, d.nama_dokter, f.jenis, f.created_at,
           GROUP_CONCAT(a.alasan SEPARATOR ', ') as alasan_list
    FROM feedback f
    JOIN doctors d ON f.doctor_id = d.id
    JOIN feedback_alasan fa ON f.id = fa.feedback_id
    JOIN alasan a ON fa.alasan_id = a.id
    GROUP BY f.id
    ORDER BY f.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penilaian Dokter</title>
    <link rel="stylesheet" href="assets/style_admin.css">
    <!-- <style>
        /* fallback styles if stye_admin.css is missing */
        body { font-family: Arial, sans-serif; background: #111; color: #eee; }
        table { width: 90%; margin: 20px auto; border-collapse: collapse; background: #222; }
        th, td { padding: 10px; border: 1px solid #555; text-align: center; }
        th { background: #333; }
    </style> -->
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <h2 style="text-align:center;">Laporan Kepuasan Dokter</h2>
    <table>
        <tr>
            <th>No</th>
            <th>Dokter</th>
            <th>Jenis</th>
            <th>Alasan</th>
            <th>Tanggal</th>
        </tr>
        <?php $no=1; while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= $row['nama_dokter'] ?></td>
            <td><?= ucfirst($row['jenis']) ?></td>
            <td><?= $row['alasan_list'] ?></td>
            <td><?= $row['created_at'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
