<?php
include 'config.php';
include 'header.php';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Ambil daftar dokter dengan spesialis (JOIN)
$dokter = $conn->query("
    SELECT d.id, d.nama_dokter, d.foto, s.nama_spesialis 
    FROM doctors d 
    LEFT JOIN spesialis s ON d.spesialis_id = s.id 
    ORDER BY d.nama_dokter
");

// Ambil alasan
$alasan_like = $conn->query("SELECT * FROM alasan WHERE kategori='like' ORDER BY alasan");
$alasan_dislike = $conn->query("SELECT * FROM alasan WHERE kategori='dislike' ORDER BY alasan");

// Simpan feedback
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_id = $_POST['doctor_id'];
    $jenis = $_POST['jenis'];
    $alasan = isset($_POST['alasan']) ? $_POST['alasan'] : [];

    if ($doctor_id && $jenis && count($alasan) > 0) {
        $stmt = $conn->prepare("INSERT INTO feedback (doctor_id, jenis) VALUES (?, ?)");
        $stmt->bind_param("is", $doctor_id, $jenis);
        $stmt->execute();
        $feedback_id = $stmt->insert_id;
        $stmt->close();

        foreach ($alasan as $a) {
            $stmt2 = $conn->prepare("INSERT INTO feedback_alasan (feedback_id, alasan_id) VALUES (?, ?)");
            $stmt2->bind_param("ii", $feedback_id, $a);
            $stmt2->execute();
            $stmt2->close();
        }

        echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Buat elemen notifikasi
        let notif = document.createElement('div');
        notif.innerText = 'Terima kasih atas penilaian Anda!';
        notif.style.position = 'fixed';
        notif.style.top = '20px';
        notif.style.right = '20px';
        notif.style.background = '#28a745';
        notif.style.color = '#fff';
        notif.style.padding = '12px 20px';
        notif.style.borderRadius = '6px';
        notif.style.boxShadow = '0 3px 6px rgba(0,0,0,0.2)';
        notif.style.zIndex = '9999';
        document.body.appendChild(notif);

        // Hilangkan setelah 2 detik
        setTimeout(() => {
            notif.remove();
            window.location = 'index.php?doctor_id=$doctor_id';
        }, 2000);
    });
</script>";

    } else {
        echo "<script>alert('Mohon pilih dokter dan minimal satu alasan.');</script>";
    }
}

$selectedDoctor = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : "";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Survey Kepuasan Dokter</title>
    <link rel="stylesheet" href="assets/style.css">
 

    <script>
function tampilFoto() {
    let select = document.getElementById("doctorSelect");
    let foto = select.options[select.selectedIndex].dataset.foto;
    let nama = select.options[select.selectedIndex].dataset.nama;
    let spesialis = select.options[select.selectedIndex].dataset.spesialis;
    let imgBox = document.getElementById("doctorPhoto");
    let nameBox = document.getElementById("doctorName");
    let specBox = document.getElementById("doctorSpecialist");
    let box = document.getElementById("doctorBox");
    let smileyBox = document.querySelector(".smiley-group");

    if (foto) {
        imgBox.src = "uploads/" + foto;
        imgBox.style.display = "block";
        nameBox.innerText = nama;
        specBox.innerText = spesialis ? spesialis : "-";
        box.style.display = "flex";
        smileyBox.style.display = "flex";

        document.querySelector(".smiley.like").style.display = "block";
        document.querySelector(".smiley.dislike").style.display = "block";
        document.getElementById("feedbackForm").style.display = "none";
    } else {
        box.style.display = "none";
        smileyBox.style.display = "none";
        document.getElementById("feedbackForm").style.display = "none";
    }
}

function showOptions(type) {
    document.getElementById("feedbackForm").style.display = "block";
    document.getElementById("jenis").value = type;

    let likeOptions = document.getElementById("likeOptions");
    let dislikeOptions = document.getElementById("dislikeOptions");
    let smileyLike = document.querySelector(".smiley.like");
    let smileyDislike = document.querySelector(".smiley.dislike");

    if (type === "like") {
        likeOptions.style.display = "block";
        dislikeOptions.style.display = "none";
        smileyDislike.style.display = "none"; 
    } else {
        likeOptions.style.display = "none";
        dislikeOptions.style.display = "block";
        smileyLike.style.display = "none"; 
    }
}

window.onload = function() {
    let select = document.getElementById("doctorSelect");
    if (select.value !== "") {
        tampilFoto();
    }
}
    </script>
</head>
<body>
<div class="container">
    <form method="POST">
        <!-- Pilih Dokter -->
<div class="container">
    <form method="POST">
        <!-- Pilih Dokter -->
        <div class="doctor-select-wrapper">
            <label for="doctorSelect" class="doctor-select-label">Dokter Praktek</label>

            <div class="doctor-select-box">
                <select name="doctor_id" id="doctorSelect" onchange="tampilFoto()" required>
                    <option value="">-- Dokter --</option>
                    <?php while($row = $dokter->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" 
                                data-foto="<?= $row['foto'] ?>" 
                                data-nama="<?= htmlspecialchars($row['nama_dokter']) ?>"
                                data-spesialis="<?= htmlspecialchars($row['nama_spesialis']) ?>"
                                <?= ($selectedDoctor == $row['id']) ? "selected" : "" ?>>
                            <?= htmlspecialchars($row['nama_dokter']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </form>
</div>


        <!-- Foto + Nama Dokter + Spesialis -->
        <div class="doctor-box" id="doctorBox">
            <img id="doctorPhoto" src="" alt="Foto Dokter" class="doctor-photo">
            <h3 id="doctorName" class="doctor-name"></h3>
            <p id="doctorSpecialist" class="doctor-specialist"></p>
        </div>

        <!-- Smiley -->
        <div class="smiley-group">
    <div class="smiley-card" onclick="selectSmiley('like')">
        <div class="smiley like">😊</div>
        <div class="smiley-label"></div>
    </div>
    <div class="smiley-card" onclick="selectSmiley('dislike')">
        <div class="smiley dislike">☹️</div>
        <div class="smiley-label"></div>
    </div>
</div>

        <!-- Form Feedback -->
        <div id="feedbackForm" style="display:none;" class="feedback-card">
            <input type="hidden" name="jenis" id="jenis">

            <div class="options-group" id="likeOptions" style="display:none;">
                <div class="option-group">
                    <?php mysqli_data_seek($alasan_like, 0); while($row = $alasan_like->fetch_assoc()): ?>
                        <label>
                            <input type="checkbox" name="alasan[]" value="<?= $row['id'] ?>">
                            <span><?= htmlspecialchars($row['alasan']) ?></span>
                        </label>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="options" id="dislikeOptions" style="display:none;">
                <div class="option-group">
                    <?php mysqli_data_seek($alasan_dislike, 0); while($row = $alasan_dislike->fetch_assoc()): ?>
                        <label>
                            <input type="checkbox" name="alasan[]" value="<?= $row['id'] ?>">
                            <span><?= htmlspecialchars($row['alasan']) ?></span>
                        </label>
                    <?php endwhile; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Kirim</button>
        </div>
    </form>
</div>
<?php include 'footer.php'; ?>
</body>
</html>
