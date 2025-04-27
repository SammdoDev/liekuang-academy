<?php
include '../koneksi.php';

// Validasi parameter
if (!isset($_GET['id_staff']) || !isset($_GET['divisi_id'])) {
    die('<div class="bg-red-100 p-4 my-4 rounded-lg text-red-800">
        ❌ Parameter tidak valid. Diperlukan id_staff dan divisi_id.
        <br><a href="javascript:history.back()" class="text-blue-600 hover:underline">⬅ Kembali</a>
    </div>');
}

$id_staff = intval($_GET['id_staff']);
$divisi_id = intval($_GET['divisi_id']);

// Ambil data staff
$staffQuery = $conn->prepare("SELECT s.*, d.nama_divisi, c.nama_cabang 
                             FROM staff s
                             JOIN divisi d ON s.id_divisi = d.id_divisi
                             JOIN cabang c ON s.id_cabang = c.id_cabang
                             WHERE s.id_staff = ?");
$staffQuery->bind_param("i", $id_staff);
$staffQuery->execute();
$result = $staffQuery->get_result();

if ($result->num_rows === 0) {
    die('<div class="bg-red-100 p-4 my-4 rounded-lg text-red-800">
        ❌ Staff tidak ditemukan.
        <br><a href="staff.php?divisi_id=' . $divisi_id . '" class="text-blue-600 hover:underline">⬅ Kembali ke Daftar Staff</a>
    </div>');
}

$staffData = $result->fetch_assoc();
$current_nama = $staffData['nama_staff'];
$current_divisi_id = $staffData['id_divisi'];
$current_cabang_id = $staffData['id_cabang'];
$current_divisi_nama = $staffData['nama_divisi'];
$current_cabang_nama = $staffData['nama_cabang'];

// Ambil daftar divisi untuk dropdown
$divisiQuery = $conn->prepare("SELECT d.id_divisi, d.nama_divisi, c.id_cabang, c.nama_cabang
                               FROM divisi d
                               JOIN cabang c ON d.id_cabang = c.id_cabang
                               ORDER BY c.nama_cabang, d.nama_divisi");
$divisiQuery->execute();
$divisiResult = $divisiQuery->get_result();

// Proses Update
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST['nama_staff'])) {
        $message = "⚠ Nama staff tidak boleh kosong!";
        $message_type = "warning";
    } else {
        $nama = sanitize_input($conn, $_POST['nama_staff']);
        $new_divisi_id = intval($_POST['id_divisi']);
        
        // Dapatkan cabang_id berdasarkan divisi yang dipilih
        $cabangQuery = $conn->prepare("SELECT id_cabang FROM divisi WHERE id_divisi = ?");
        $cabangQuery->bind_param("i", $new_divisi_id);
        $cabangQuery->execute();
        $cabangResult = $cabangQuery->get_result();
        
        if ($cabangResult->num_rows === 0) {
            $message = "❌ Divisi yang dipilih tidak valid!";
            $message_type = "error";
        } else {
            $cabangData = $cabangResult->fetch_assoc();
            $new_cabang_id = $cabangData['id_cabang'];
            
            // Update data staff
            $updateQuery = $conn->prepare("UPDATE staff SET nama_staff = ?, id_divisi = ?, id_cabang = ? WHERE id_staff = ?");
            $updateQuery->bind_param("siii", $nama, $new_divisi_id, $new_cabang_id, $id_staff);
            
            if ($updateQuery->execute()) {
                $message = "✅ Data staff berhasil diperbarui!";
                $message_type = "success";
                
                // Perbarui nilai current untuk ditampilkan di form
                $current_nama = $nama;
                $current_divisi_id = $new_divisi_id;
                $current_cabang_id = $new_cabang_id;
                
                // Ambil informasi terbaru tentang divisi dan cabang
                $infoQuery = $conn->prepare("SELECT d.nama_divisi, c.nama_cabang 
                                            FROM divisi d
                                            JOIN cabang c ON d.id_cabang = c.id_cabang
                                            WHERE d.id_divisi = ?");
                $infoQuery->bind_param("i", $new_divisi_id);
                $infoQuery->execute();
                $infoResult = $infoQuery->get_result();
                $infoData = $infoResult->fetch_assoc();
                
                $current_divisi_nama = $infoData['nama_divisi'];
                $current_cabang_nama = $infoData['nama_cabang'];
                
                // Refresh daftar divisi untuk dropdown
                $divisiQuery->execute();
                $divisiResult = $divisiQuery->get_result();
                
                // Redirect jika diminta
                if (isset($_POST['save_and_return']) && $_POST['save_and_return'] == '1') {
                    header("Location: staff.php?divisi_id=$new_divisi_id&updated=success&staff_name=" . urlencode($nama));
                    exit();
                }
            } else {
                $message = "❌ Gagal memperbarui data: " . $updateQuery->error;
                $message_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff - <?= htmlspecialchars($current_nama) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold text-center mb-4">Edit Staff</h1>
        
        <div class="mb-4 p-3 bg-blue-50 rounded-lg">
            <p class="text-blue-800"><strong>ID Staff:</strong> <?= $id_staff ?></p>
            <p class="text-blue-800"><strong>Divisi Saat Ini:</strong> <?= htmlspecialchars($current_divisi_nama) ?></p>
            <p class="text-blue-800"><strong>Cabang Saat Ini:</strong> <?= htmlspecialchars($current_cabang_nama) ?></p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="p-3 mb-4 rounded-lg <?= $message_type === 'success' ? 'bg-green-100 text-green-800' : ($message_type === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>?id_staff=<?= $id_staff ?>&divisi_id=<?= $divisi_id ?>">
            <div class="mb-4">
                <label class="block text-gray-700 mb-2" for="nama_staff">Nama Staff:</label>
                <input type="text" id="nama_staff" name="nama_staff" value="<?= htmlspecialchars($current_nama) ?>" 
                       required class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2" for="id_divisi">Divisi:</label>
                <select id="id_divisi" name="id_divisi" class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php
                    $current_cabang = '';
                    while ($divisi = $divisiResult->fetch_assoc()):
                        // Group divisi by cabang
                        if ($current_cabang != $divisi['nama_cabang']):
                            if ($current_cabang != '') echo '</optgroup>';
                            $current_cabang = $divisi['nama_cabang'];
                            echo '<optgroup label="' . htmlspecialchars($current_cabang) . '">';
                        endif;
                    ?>
                        <option value="<?= $divisi['id_divisi'] ?>" 
                                <?= ($divisi['id_divisi'] == $current_divisi_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($divisi['nama_divisi']) ?>
                        </option>
                    <?php
                    endwhile;
                    if ($current_cabang != '') echo '</optgroup>';
                    ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    Mengubah divisi akan otomatis mengubah cabang sesuai dengan divisi yang dipilih.
                </p>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <button type="submit" name="save" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                    Simpan Perubahan
                </button>
                
                <button type="submit" name="save_and_return" value="1" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">
                    Simpan & Kembali
                </button>
            </div>
        </form>
        
        <div class="mt-4 flex justify-between">
            <a href="staff.php?divisi_id=<?= $current_divisi_id ?>" class="text-blue-600 hover:underline">
                ⬅ Kembali ke Daftar Staff
            </a>
            
            <a href="hapus_staff.php?id_staff=<?= $id_staff ?>&divisi_id=<?= $current_divisi_id ?>" 
               class="text-red-600 hover:underline">
                🗑️ Hapus Staff Ini
            </a>
        </div>
    </div>
    
    <script>
    // Script untuk konfirmasi jika ada perubahan yang belum disimpan
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const initialValues = {
            nama: document.getElementById('nama_staff').value,
            divisi: document.getElementById('id_divisi').value
        };
        
        let formChanged = false;
        
        form.addEventListener('change', function() {
            const currentValues = {
                nama: document.getElementById('nama_staff').value,
                divisi: document.getElementById('id_divisi').value
            };
            
            formChanged = 
                currentValues.nama !== initialValues.nama || 
                currentValues.divisi !== initialValues.divisi;
        });
        
        // Untuk link kembali dan hapus
        const links = document.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                if (formChanged && !confirm('Ada perubahan yang belum disimpan. Yakin ingin meninggalkan halaman ini?')) {
                    e.preventDefault();
                }
            });
        });
    });
    </script>
</body>
</html>