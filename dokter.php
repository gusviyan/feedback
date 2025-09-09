<?php
include 'header.php';
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

include 'config.php';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// === Filter ===
$filter_spesialis = isset($_GET['spesialis_id']) ? intval($_GET['spesialis_id']) : "";
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Tambah dokter
if (isset($_POST['add_doctor'])) {
    $nama = $_POST['nama_dokter'];
    $spesialis_id = $_POST['spesialis_id'];
    $foto = null;

    if (!empty($_FILES['foto']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir);
        $foto = time() . "_" . basename($_FILES['foto']['name']);
        $targetFile = $targetDir . $foto;
        move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile);
    }

    $stmt = $conn->prepare("INSERT INTO doctors (nama_dokter, spesialis_id, foto) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $nama, $spesialis_id, $foto);
    $stmt->execute();
    $stmt->close();
}

// Edit dokter
if (isset($_POST['edit_doctor'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama_dokter'];
    $spesialis_id = $_POST['spesialis_id'];

    if (!empty($_FILES['foto']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir);
        $foto = time() . "_" . basename($_FILES['foto']['name']);
        $targetFile = $targetDir . $foto;
        move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile);

        $stmt = $conn->prepare("UPDATE doctors SET nama_dokter=?, spesialis_id=?, foto=? WHERE id=?");
        $stmt->bind_param("sisi", $nama, $spesialis_id, $foto, $id);
    } else {
        $stmt = $conn->prepare("UPDATE doctors SET nama_dokter=?, spesialis_id=? WHERE id=?");
        $stmt->bind_param("sii", $nama, $spesialis_id, $id);
    }
    $stmt->execute();
    $stmt->close();
}

// Hapus dokter
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $res = $conn->query("SELECT foto FROM doctors WHERE id=$id");
    if ($res && $row = $res->fetch_assoc()) {
        if ($row['foto'] && file_exists("uploads/" . $row['foto'])) {
            unlink("uploads/" . $row['foto']);
        }
    }
    $conn->query("DELETE FROM doctors WHERE id=$id");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Dokter</title>
    <link rel="stylesheet" href="assets/style_admin.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">

    <!-- Form tambah dokter -->
    <div class="card">
        <form method="POST" enctype="multipart/form-data">
            <h3>Tambah Dokter</h3>
            <input type="text" name="nama_dokter" placeholder="Nama Dokter" required>

            <!-- Dropdown Spesialis -->
            <select name="spesialis_id" required>
                <option value="">-- Pilih Spesialis --</option>
                <?php
                $spesialis = $conn->query("SELECT * FROM spesialis ORDER BY nama_spesialis");
                while ($s = $spesialis->fetch_assoc()):
                ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_spesialis']) ?></option>
                <?php endwhile; ?>
            </select>

            <input type="file" name="foto" accept="image/*">
            <button type="submit" name="add_doctor" class="btn btn-primary">Tambah</button>
        </form>
    </div>

    <!-- Filter & Search -->
    <div class="card">
        <form method="GET" class="filter-form">
            <select name="spesialis_id">
                <option value="">-- Semua Spesialis --</option>
                <?php
                $spesialis2 = $conn->query("SELECT * FROM spesialis ORDER BY nama_spesialis");
                while ($s2 = $spesialis2->fetch_assoc()):
                ?>
                    <option value="<?= $s2['id'] ?>" <?= ($filter_spesialis == $s2['id']) ? "selected" : "" ?>>
                        <?= htmlspecialchars($s2['nama_spesialis']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <input type="text" name="search" placeholder="Cari nama dokter..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="admin.php" class="btn btn-warning">Reset</a>
        </form>
    </div>

    <!-- Tabel dokter -->
    <table>
        <tr>
            <th>ID</th>
            <th>Nama Dokter</th>
            <th>Spesialis</th>
            <th>Foto</th>
            <th>Aksi</th>
        </tr>
        <?php
        $sql = "SELECT d.*, s.nama_spesialis 
                FROM doctors d 
                LEFT JOIN spesialis s ON d.spesialis_id=s.id 
                WHERE 1=1";

        if ($filter_spesialis) {
            $sql .= " AND d.spesialis_id=" . intval($filter_spesialis);
        }
        if ($search) {
            $sql .= " AND d.nama_dokter LIKE '%" . $conn->real_escape_string($search) . "%'";
        }

        $sql .= " ORDER BY d.id DESC";

        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['nama_dokter']) ?></td>
            <td><?= htmlspecialchars($row['nama_spesialis']) ?></td>
            <td>
                <?php if ($row['foto']): ?>
                    <img src="uploads/<?= $row['foto'] ?>" alt="Foto Dokter" class="doctor-thumb">
                <?php else: ?>
                    <span style="color:#888;">Tidak ada foto</span>
                <?php endif; ?>
            </td>
            <td>
                <button class="btn btn-warning"
                    onclick="openEditModal(
                        <?= $row['id'] ?>, 
                        '<?= htmlspecialchars($row['nama_dokter'], ENT_QUOTES) ?>',
                        <?= $row['spesialis_id'] ?>
                    )">Edit</button>
                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus dokter ini?')" class="btn btn-danger">Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<!-- Modal Edit Dokter -->
<div id="editModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Dokter</h3>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="edit_id">

            <label>Nama Dokter</label>
            <input type="text" name="nama_dokter" id="edit_nama" required>

            <label>Spesialis</label>
            <select name="spesialis_id" id="edit_spesialis" required>
                <?php
                $spesialis3 = $conn->query("SELECT * FROM spesialis ORDER BY nama_spesialis");
                while ($s3 = $spesialis3->fetch_assoc()):
                ?>
                    <option value="<?= $s3['id'] ?>"><?= htmlspecialchars($s3['nama_spesialis']) ?></option>
                <?php endwhile; ?>
            </select>

            <label>Foto (kosongkan jika tidak diganti)</label>
            <input type="file" name="foto" accept="image/*">

            <button type="submit" name="edit_doctor" class="btn btn-success">Simpan</button>
        </form>
    </div>
</div>

<script>
function openEditModal(id, nama, spesialisId) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;

    let spesialisSelect = document.getElementById('edit_spesialis');
    for (let i = 0; i < spesialisSelect.options.length; i++) {
        if (spesialisSelect.options[i].value == spesialisId) {
            spesialisSelect.options[i].selected = true;
        }
    }

    document.getElementById('editModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}
window.onclick = function(event) {
    let modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>
<?php include 'footer.php'; ?>
</body>
</html>
