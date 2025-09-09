<?php
include 'config.php';
include 'sidebar.php';
include 'header.php';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Ambil filter periode & spesialis
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$spesialis_id = isset($_GET['spesialis_id']) ? intval($_GET['spesialis_id']) : "";

// Buat WHERE clause
$where = "WHERE 1=1";
if ($start_date && $end_date) {
    $where .= " AND f.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
}
if ($spesialis_id) {
    $where .= " AND d.spesialis_id = $spesialis_id";
}

// Query jumlah like & dislike per dokter
$sql = "
    SELECT d.nama_dokter,
           SUM(CASE WHEN f.jenis = 'like' THEN 1 ELSE 0 END) as total_like,
           SUM(CASE WHEN f.jenis = 'dislike' THEN 1 ELSE 0 END) as total_dislike
    FROM doctors d
    LEFT JOIN feedback f ON d.id = f.doctor_id
    $where
    GROUP BY d.id, d.nama_dokter
    ORDER BY d.nama_dokter
";
$result = $conn->query($sql);

$labels = [];
$likeData = [];
$dislikeData = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['nama_dokter'];
    $likeData[] = $row['total_like'];
    $dislikeData[] = $row['total_dislike'];
}

// Ambil daftar spesialis untuk dropdown
$spesialis = $conn->query("SELECT * FROM spesialis ORDER BY nama_spesialis");

// Ambil total keseluruhan
$totalQuery = "
    SELECT 
        SUM(CASE WHEN f.jenis='like' THEN 1 ELSE 0 END) AS total_like,
        SUM(CASE WHEN f.jenis='dislike' THEN 1 ELSE 0 END) AS total_dislike
    FROM doctors d
    LEFT JOIN feedback f ON d.id=f.doctor_id
    $where
";
$total = $conn->query($totalQuery)->fetch_assoc();

$total_like = $total['total_like'] ?? 0;
$total_dislike = $total['total_dislike'] ?? 0;
$total_all = $total_like + $total_dislike;
$percent_like = $total_all > 0 ? round(($total_like / $total_all) * 100) : 0;
$percent_dislike = $total_all > 0 ? round(($total_dislike / $total_all) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Feedback Dokter</title>
    <link rel="stylesheet" href="assets/style_admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .filter-box {
            margin: 20px auto;
            text-align: center;
        }
        .filter-box input, .filter-box select {
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .filter-box button {
            padding: 6px 14px;
            border: none;
            border-radius: 6px;
            background: #007bff;
            color: #fff;
            cursor: pointer;
        }
        .filter-box button:hover {
            background: #0056b3;
        }
        .summary-box {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 20px auto;
        }
        .summary-card {
            background: #fff;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            text-align: center;
            min-width: 150px;
        }
        .summary-card h3 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .summary-card p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #666;
        }
        .summary-like { color: #28a745; }
        .summary-dislike { color: #dc3545; }

        /* Progress bar */
        .progress-container {
            width: 80%;
            max-width: 600px;
            margin: 20px auto;
            background: #eee;
            border-radius: 20px;
            overflow: hidden;
            height: 25px;
            display: flex;
        }
        .progress-like {
            background: #28a745;
            height: 100%;
            text-align: center;
            color: #fff;
            font-size: 13px;
            line-height: 25px;
        }
        .progress-dislike {
            background: #dc3545;
            height: 100%;
            text-align: center;
            color: #fff;
            font-size: 13px;
            line-height: 25px;
        }
        .chart-container {
            width: 95%;
            max-width: 1000px;
            margin: 30px auto;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<div class="container">

    <!-- Filter Periode & Spesialis -->
    <div class="filter-box">
        <form method="GET">
            <label>Dari: </label>
            <input type="date" name="start_date" value="<?= $start_date ?>">
            <label>Sampai: </label>
            <input type="date" name="end_date" value="<?= $end_date ?>">

            <label>Spesialis: </label>
            <select name="spesialis_id">
                <option value="">-- Semua Spesialis --</option>
                <?php while ($s = $spesialis->fetch_assoc()): ?>
                    <option value="<?= $s['id'] ?>" <?= ($spesialis_id == $s['id']) ? "selected" : "" ?>>
                        <?= htmlspecialchars($s['nama_spesialis']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <button type="submit">Terapkan</button>
        </form>
    </div>

    <!-- Ringkasan total -->
    <div class="summary-box">
        <div class="summary-card summary-like">
            <h3><?= $total_like ?></h3>
            <p>Total Like</p>
        </div>
        <div class="summary-card summary-dislike">
            <h3><?= $total_dislike ?></h3>
            <p>Total Dislike</p>
        </div>
    </div>

    <!-- Progress bar persentase -->
    <div class="progress-container">
        <div class="progress-like" style="width: <?= $percent_like ?>%">
            <?= $percent_like ?>%
        </div>
        <div class="progress-dislike" style="width: <?= $percent_dislike ?>%">
            <?= $percent_dislike ?>%
        </div>
    </div>

    <!-- Chart -->
    <div class="chart-container">
        <canvas id="feedbackChart"></canvas>
    </div>
</div>

<!-- Tombol Export -->
<div class="filter-box" style="margin-top:10px;">
    <a href="export_excel.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&spesialis_id=<?= $spesialis_id ?>" 
       class="btn btn-success">Export Excel</a>

    <a href="export_pdf.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&spesialis_id=<?= $spesialis_id ?>" 
       class="btn btn-danger">Export PDF</a>
</div>


<script>
const ctx = document.getElementById('feedbackChart').getContext('2d');
const feedbackChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [
            {
                label: 'Like',
                data: <?= json_encode($likeData) ?>,
                backgroundColor: '#28a745'
            },
            {
                label: 'Dislike',
                data: <?= json_encode($dislikeData) ?>,
                backgroundColor: '#dc3545'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            title: {
                display: true,
                text: 'Jumlah Like & Dislike per Dokter'
            }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
<?php include 'footer.php'; ?>
</body>
</html>
