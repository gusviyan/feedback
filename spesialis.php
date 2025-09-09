<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

include 'config.php';
include 'header.php';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Tambah spesialis
if (isset($_POST['add_spesialis'])) {
    $nama = $_POST['nama_spesialis'];
    $stmt = $conn->prepare("INSERT INTO spesialis (nama_spesialis) VALUES (?)");
    $stmt->bind_param("s", $nama);
    $stmt->execute();
    $stmt->close();
}

// Edit spesialis
if (isset($_POST['edit_spesialis'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama_spesialis'];

    $stmt = $conn->prepare("UPDATE spesialis SET nama_spesialis=? WHERE id=?");
    $stmt->bind_param("si", $nama, $id);
    $stmt->execute();
    $stmt->close();
}

// Hapus spesialis
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM spesialis WHERE id=$id");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Spesialis</title>
    <link rel="stylesheet" href="assets/style_admin.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">

    <!-- Form tambah spesialis -->
    <div class="card">
        <form method="POST">
            <h3>Tambah Spesialis</h3>
            <input type="text" name="nama_spesialis" placeholder="Nama Spesialis" required>
            <button type="submit" name="add_spesialis" class="btn btn-primary">Tambah</button>
        </form>
    </div>

    <!-- Tabel spesialis -->
    <table>
        <tr>
            <th>ID</th>
            <th>Nama Spesialis</th>
            <th>Aksi</th>
        </tr>
        <?php
        $result = $conn->query("SELECT * FROM spesialis ORDER BY id DESC");
        while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['nama_spesialis']) ?></td>
            <td>
                <button class="btn btn-warning"
                    onclick="openEditModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_spesialis'], ENT_QUOTES) ?>')">
                    Edit
                </button>
                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus spesialis ini?')" class="btn btn-danger">Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<!-- Modal Edit Spesialis -->
<div id="editModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Spesialis</h3>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="id" id="edit_id">
            <label>Nama Spesialis</label>
            <input type="text" name="nama_spesialis" id="edit_nama" required>
            <button type="submit" name="edit_spesialis" class="btn btn-success">Simpan</button>
        </form>
    </div>
</div>

<script>
function openEditModal(id, nama) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
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
