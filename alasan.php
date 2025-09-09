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

// Tambah alasan
if (isset($_POST['add_reason'])) {
    $kategori = $_POST['kategori'];
    $alasan = $_POST['alasan'];

    if (!empty($alasan)) {
        $stmt = $conn->prepare("INSERT INTO alasan (kategori, alasan) VALUES (?, ?)");
        $stmt->bind_param("ss", $kategori, $alasan);
        $stmt->execute();
        $stmt->close();
    }
}

// Edit alasan
if (isset($_POST['edit_reason'])) {
    $id = $_POST['id'];
    $kategori = $_POST['kategori'];
    $alasan = $_POST['alasan'];

    $stmt = $conn->prepare("UPDATE alasan SET kategori=?, alasan=? WHERE id=?");
    $stmt->bind_param("ssi", $kategori, $alasan, $id);
    $stmt->execute();
    $stmt->close();
}

// Hapus alasan
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM alasan WHERE id=$id");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Feedback</title>
    <link rel="stylesheet" href="assets/style_admin.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">

    <!-- Form tambah alasan -->
    <div class="card">
        <form method="POST">
            <h3>Tambah Alasan</h3>
            <select name="kategori" required>
                <option value="like">Like</option>
                <option value="dislike">Dislike</option>
            </select>
            <input type="text" name="alasan" placeholder="Tuliskan alasan" required>
            <button type="submit" name="add_reason" class="btn btn-primary">Tambah</button>
        </form>
    </div>

    <!-- Tabel alasan -->
    <table>
        <tr>
            <th>ID</th>
            <th>Kategori</th>
            <th>Alasan</th>
            <th>Aksi</th>
        </tr>
        <?php
        $result = $conn->query("SELECT * FROM alasan ORDER BY id DESC");
        while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['kategori']) ?></td>
            <td><?= htmlspecialchars($row['alasan'] ?? '-') ?></td>
            <td>
                <button class="btn btn-warning" 
                        onclick="openEditModal(
                            <?= $row['id'] ?>, 
                            '<?= htmlspecialchars($row['kategori'], ENT_QUOTES) ?>', 
                            '<?= htmlspecialchars($row['alasan'] ?? '', ENT_QUOTES) ?>'
                        )">Edit</button>
                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus alasan ini?')" class="btn btn-danger">Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<!-- Modal Edit Alasan -->
<div id="editModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Alasan</h3>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="id" id="edit_id">
            <label>Kategori</label>
            <select name="kategori" id="edit_kategori" required>
                <option value="like">Like</option>
                <option value="dislike">Dislike</option>
            </select>
            <label>Alasan</label>
            <input type="text" name="alasan" id="edit_alasan" required>
            <div class="modal-footer">
                <button type="submit" name="edit_reason" class="btn btn-success">✔ Simpan</button>
                <button type="button" onclick="closeModal()" class="btn btn-secondary">✖ Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, kategori, alasan) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_kategori').value = kategori;
    document.getElementById('edit_alasan').value = alasan;
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
