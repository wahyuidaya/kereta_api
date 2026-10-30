<?php
session_start();
require_once 'koneksi_db.php';

// Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login_admin.html");
    exit;
}

// Inisialisasi pesan alert
$pesan = '';
$tipe_pesan = '';

// Handle Tambah Kereta / Jadwal Manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tambah_jadwal') {
    $tujuan = trim($_POST['tujuan'] ?? '');
    $kelas  = trim($_POST['kelas'] ?? '');
    $jam    = trim($_POST['jam'] ?? '');

    if ($tujuan && $kelas && $jam) {
        $stmt = $conn->prepare("INSERT INTO Kereta (Tujuan, Kelas, Jam_Keberangkatan) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $tujuan, $kelas, $jam);
            if ($stmt->execute()) {
                $pesan = "Jadwal kereta berhasil ditambahkan!";
                $tipe_pesan = "sukses";
            } else {
                $pesan = "Gagal menambahkan jadwal: " . $stmt->error;
                $tipe_pesan = "gagal";
            }
            $stmt->close();
        }
    }
}

// Handle Hapus Pemesanan Tiket
if (isset($_GET['hapus_tiket'])) {
    $id_tiket = intval($_GET['hapus_tiket']);
    if ($id_tiket > 0) {
        $del = $conn->query("DELETE FROM Tiket WHERE ID_Tiket = $id_tiket");
        if ($del) {
            $pesan = "Tiket #$id_tiket berhasil dihapus dari sistem.";
            $tipe_pesan = "sukses";
        }
    }
}

// Handle Hapus Jadwal Kereta
if (isset($_GET['hapus_kereta'])) {
    $id_kereta = intval($_GET['hapus_kereta']);
    if ($id_kereta > 0) {
        // Hapus tiket terkait terlebih dahulu
        $conn->query("DELETE FROM Tiket WHERE ID_Kereta = $id_kereta");
        $delK = $conn->query("DELETE FROM Kereta WHERE ID_Kereta = $id_kereta");
        if ($delK) {
            $pesan = "Jadwal Kereta #$id_kereta berhasil dihapus.";
            $tipe_pesan = "sukses";
        }
    }
}

// Tab Aktif
$tab = $_GET['tab'] ?? 'ringkasan';

// Ambil Statistik
$total_tiket = 0;
$total_pendapatan = 0;
$total_penumpang = 0;
$total_kereta = 0;

$resT = $conn->query("SELECT COUNT(*) as tot, COALESCE(SUM(Harga_Tiket), 0) as omset FROM Tiket");
if ($resT && $rowT = $resT->fetch_assoc()) {
    $total_tiket = $rowT['tot'];
    $total_pendapatan = $rowT['omset'];
}

$resP = $conn->query("SELECT COUNT(*) as tot FROM Penumpang");
if ($resP && $rowP = $resP->fetch_assoc()) {
    $total_penumpang = $rowP['tot'];
}

$resK = $conn->query("SELECT COUNT(*) as tot FROM Kereta");
if ($resK && $rowK = $resK->fetch_assoc()) {
    $total_kereta = $rowK['tot'];
}

// Data Pemesanan Tiket Lengkap (JOIN)
$sqlTiket = "SELECT t.ID_Tiket, t.Tgl_Keberangkatan, t.Tempat_Duduk, t.Harga_Tiket,
                    u.Nama_Pemesan, u.Email,
                    p.No_Telepon, p.Alamat,
                    k.Tujuan, k.Kelas, k.Jam_Keberangkatan
             FROM Tiket t
             LEFT JOIN Login_Pengguna u ON t.ID_Pemesan = u.ID_Pengguna
             LEFT JOIN Penumpang p ON t.ID_Penumpang = p.ID_Penumpang
             LEFT JOIN Kereta k ON t.ID_Kereta = k.ID_Kereta
             ORDER BY t.ID_Tiket DESC";
$resTiket = $conn->query($sqlTiket);

// Data Jadwal Kereta
$resJadwal = $conn->query("SELECT * FROM Kereta ORDER BY ID_Kereta DESC");

// Data Pengguna Terdaftar
$resUser = $conn->query("SELECT * FROM Login_Pengguna ORDER BY ID_Pengguna DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - TiketKeretaApp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #F1F5F9;
            color: #1E293B;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        /* Top Navigation Header */
        .top-navbar {
            background-color: #0F172A;
            border-bottom: 1px solid #1E293B;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .nav-logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #2563EB, #1D4ED8);
            color: #fff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .nav-brand-text h1 {
            color: #F8FAFC;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .nav-brand-text span {
            color: #94A3B8;
            font-size: 11px;
            display: block;
        }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-portofolio {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #93C5FD;
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all .2s;
        }
        .btn-portofolio:hover {
            background: rgba(255, 255, 255, 0.16);
            color: #fff;
        }
        .btn-logout {
            background: #DC2626;
            color: #fff;
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            border: none;
            transition: background .2s;
        }
        .btn-logout:hover {
            background: #B91C1C;
        }

        /* Layout Container */
        .admin-layout {
            display: flex;
            flex: 1;
        }

        /* Sidebar */
        .admin-sidebar {
            width: 240px;
            background: #FFFFFF;
            border-right: 1px solid #E2E8F0;
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .sidebar-section-title {
            font-size: 11px;
            font-weight: 700;
            color: #94A3B8;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin: 12px 10px 6px;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: #475569;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 500;
            transition: all .15s;
        }
        .sidebar-link:hover {
            background: #F1F5F9;
            color: #0F172A;
        }
        .sidebar-link.active {
            background: #EFF6FF;
            color: #1D4ED8;
            font-weight: 600;
        }
        .sidebar-link i {
            font-size: 15px;
            width: 20px;
            text-align: center;
        }

        /* Content Area */
        .admin-content {
            flex: 1;
            padding: 28px 32px;
            overflow-x: auto;
        }
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .content-title h2 {
            font-size: 22px;
            font-weight: 700;
            color: #0F172A;
        }
        .content-title p {
            font-size: 13px;
            color: #64748B;
            margin-top: 2px;
        }

        /* Alert Notification */
        .alert-box {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-box.sukses {
            background: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }
        .alert-box.gagal {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        /* Stat Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .stat-info h4 {
            font-size: 12px;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .stat-info .stat-number {
            font-size: 22px;
            font-weight: 700;
            color: #0F172A;
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .stat-icon.blue { background: #EFF6FF; color: #2563EB; }
        .stat-icon.green { background: #ECFDF5; color: #059669; }
        .stat-icon.amber { background: #FFFBEB; color: #D97706; }
        .stat-icon.purple { background: #F5F3FF; color: #7C3AED; }

        /* Tables & Cards */
        .card-table {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .card-table-header {
            padding: 16px 20px;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #FAFAFA;
        }
        .card-table-header h3 {
            font-size: 15px;
            font-weight: 700;
            color: #0F172A;
        }
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }
        th {
            background: #F8FAFC;
            color: #475569;
            font-weight: 600;
            padding: 12px 16px;
            border-bottom: 1px solid #E2E8F0;
            white-space: nowrap;
        }
        td {
            padding: 12px 16px;
            border-bottom: 1px solid #F1F5F9;
            color: #334155;
            vertical-align: middle;
        }
        tr:hover td {
            background: #F8FAFC;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge.eksekutif { background: #FEF3C7; color: #B45309; }
        .badge.bisnis { background: #EFF6FF; color: #1D4ED8; }
        .badge.ekonomi { background: #F1F5F9; color: #475569; }

        /* Actions Button */
        .btn-action-del {
            background: #FEE2E2;
            color: #DC2626;
            border: 1px solid #FECACA;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            transition: all .15s;
        }
        .btn-action-del:hover {
            background: #DC2626;
            color: #fff;
        }

        /* Form Modal / Box */
        .form-box {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            max-width: 600px;
        }
        .form-box h3 {
            font-size: 16px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 16px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            font-size: 13px;
            color: #1E293B;
            outline: none;
            transition: border-color .15s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #2563EB;
        }
        .btn-submit-jadwal {
            background: #2563EB;
            color: #fff;
            padding: 10px 18px;
            border-radius: 8px;
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-submit-jadwal:hover {
            background: #1D4ED8;
        }

        @media (max-width: 768px) {
            .admin-layout {
                flex-direction: column;
            }
            .admin-sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #E2E8F0;
                padding: 12px;
                display: flex;
                flex-direction: row;
                overflow-x: auto;
            }
            .admin-content {
                padding: 18px 16px;
            }
        }
    </style>
</head>
<body>

    <!-- Top Navbar -->
    <header class="top-navbar">
        <div class="nav-brand">
            <div class="nav-logo-icon">
                <i class="fa-solid fa-train"></i>
            </div>
            <div class="nav-brand-text">
                <h1>TiketKeretaApp</h1>
                <span>Administrator Dashboard Panel</span>
            </div>
        </div>
        <div class="nav-actions">
            <a href="portal.html" class="btn-portofolio">
                <i class="fa-solid fa-layer-group"></i> Portal Peran
            </a>
            <a href="https://wahyuidaya.github.io" target="_blank" rel="noopener noreferrer" class="btn-portofolio">
                <i class="fa-solid fa-arrow-left"></i> Portofolio
            </a>
            <button onclick="logoutAdmin()" class="btn-logout">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </button>
        </div>
    </header>

    <div class="admin-layout">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <span class="sidebar-section-title">Menu Utama</span>
            <a href="dashboard_admin.php?tab=ringkasan" class="sidebar-link <?= $tab === 'ringkasan' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge"></i> Ringkasan &amp; Statistik
            </a>
            <a href="dashboard_admin.php?tab=tiket" class="sidebar-link <?= $tab === 'tiket' ? 'active' : '' ?>">
                <i class="fa-solid fa-ticket"></i> Pemesanan Tiket
            </a>
            <a href="dashboard_admin.php?tab=jadwal" class="sidebar-link <?= $tab === 'jadwal' ? 'active' : '' ?>">
                <i class="fa-solid fa-calendar-days"></i> Rute &amp; Jadwal Kereta
            </a>
            <a href="dashboard_admin.php?tab=pengguna" class="sidebar-link <?= $tab === 'pengguna' ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i> Akun Penumpang
            </a>

            <span class="sidebar-section-title">Akses Luar</span>
            <a href="pesan_tiket.html" target="_blank" class="sidebar-link">
                <i class="fa-solid fa-plus-circle"></i> Tes Pesan Tiket ↗
            </a>
            <a href="karcis_tiket.html" target="_blank" class="sidebar-link">
                <i class="fa-solid fa-print"></i> Karcis Tiket Demo ↗
            </a>
            <a href="javascript:void(0)" onclick="logoutAdmin()" class="sidebar-link" style="color: #DC2626;">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout Sistem
            </a>
        </aside>

        <!-- Main Content Area -->
        <main class="admin-content">
            <?php if ($pesan): ?>
                <div class="alert-box <?= $tipe_pesan ?>">
                    <i class="fa-solid <?= $tipe_pesan === 'sukses' ? 'fa-check-circle' : 'fa-triangle-exclamation' ?>"></i>
                    <span><?= htmlspecialchars($pesan) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'ringkasan' || $tab === ''): ?>
                <!-- Ringkasan Section -->
                <div class="content-header">
                    <div class="content-title">
                        <h2>Dashboard Operasional Kereta Api</h2>
                        <p>Pemantauan real-time pemesanan tiket, armada kereta, dan data penumpang terdaftar.</p>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h4>Total Tiket Dipesan</h4>
                            <div class="stat-number"><?= number_format($total_tiket) ?></div>
                        </div>
                        <div class="stat-icon blue">
                            <i class="fa-solid fa-ticket"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <h4>Total Pendapatan</h4>
                            <div class="stat-number">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></div>
                        </div>
                        <div class="stat-icon green">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <h4>Data Penumpang</h4>
                            <div class="stat-number"><?= number_format($total_penumpang) ?></div>
                        </div>
                        <div class="stat-icon amber">
                            <i class="fa-solid fa-user-check"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <h4>Rute &amp; Armada</h4>
                            <div class="stat-number"><?= number_format($total_kereta) ?></div>
                        </div>
                        <div class="stat-icon purple">
                            <i class="fa-solid fa-train-subway"></i>
                        </div>
                    </div>
                </div>

                <!-- Tabel Ringkasan Pesanan Terbaru -->
                <div class="card-table">
                    <div class="card-table-header">
                        <h3><i class="fa-solid fa-clock-rotate-left"></i> 5 Pemesanan Tiket Terakhir</h3>
                        <a href="dashboard_admin.php?tab=tiket" style="font-size: 12.5px; color: #2563EB; text-decoration: none; font-weight: 600;">Lihat Semua &rarr;</a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Tiket</th>
                                    <th>Nama Pemesan</th>
                                    <th>Rute / Tujuan</th>
                                    <th>Kelas</th>
                                    <th>Kursi</th>
                                    <th>Tgl Berangkat</th>
                                    <th>Tarif</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $count = 0;
                                if ($resTiket && $resTiket->num_rows > 0):
                                    $resTiket->data_seek(0);
                                    while ($t = $resTiket->fetch_assoc()):
                                        if ($count++ >= 5) break;
                                        $kelasBadge = strtolower($t['Kelas'] ?? 'ekonomi');
                                ?>
                                    <tr>
                                        <td><strong>#<?= $t['ID_Tiket'] ?></strong></td>
                                        <td>
                                            <strong><?= htmlspecialchars($t['Nama_Pemesan'] ?? '-') ?></strong><br>
                                            <small style="color: #64748B;"><?= htmlspecialchars($t['No_Telepon'] ?? $t['Email'] ?? '-') ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($t['Tujuan'] ?? '-') ?></td>
                                        <td><span class="badge <?= $kelasBadge ?>"><?= htmlspecialchars($t['Kelas'] ?? '-') ?></span></td>
                                        <td><strong><?= htmlspecialchars($t['Tempat_Duduk'] ?? '-') ?></strong></td>
                                        <td><?= htmlspecialchars($t['Tgl_Keberangkatan'] ?? '-') ?></td>
                                        <td><strong>Rp <?= number_format($t['Harga_Tiket'] ?? 0, 0, ',', '.') ?></strong></td>
                                        <td>
                                            <a href="dashboard_admin.php?hapus_tiket=<?= $t['ID_Tiket'] ?>&tab=ringkasan" onclick="return confirm('Hapus tiket #<?= $t['ID_Tiket'] ?>?')" class="btn-action-del">
                                                <i class="fa-solid fa-trash"></i> Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; color: #94A3B8; padding: 24px;">Belum ada riwayat pemesanan tiket di database.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($tab === 'tiket'): ?>
                <!-- Pemesanan Tiket Lengkap -->
                <div class="content-header">
                    <div class="content-title">
                        <h2>Data Pemesanan Seluruh Tiket</h2>
                        <p>Daftar transaksi reservasi tiket kereta api yang telah terdaftar di database.</p>
                    </div>
                </div>

                <div class="card-table">
                    <div class="card-table-header">
                        <h3><i class="fa-solid fa-list-check"></i> Seluruh Tiket Masuk (<?= $total_tiket ?> Reservasi)</h3>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Tiket</th>
                                    <th>Nama Pemesan</th>
                                    <th>Kontak &amp; Alamat</th>
                                    <th>Rute Tujuan</th>
                                    <th>Kelas</th>
                                    <th>Jam</th>
                                    <th>Kursi</th>
                                    <th>Tgl Berangkat</th>
                                    <th>Harga Tiket</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($resTiket && $resTiket->num_rows > 0):
                                    $resTiket->data_seek(0);
                                    while ($t = $resTiket->fetch_assoc()):
                                        $kelasBadge = strtolower($t['Kelas'] ?? 'ekonomi');
                                ?>
                                    <tr>
                                        <td><strong>#<?= $t['ID_Tiket'] ?></strong></td>
                                        <td>
                                            <strong><?= htmlspecialchars($t['Nama_Pemesan'] ?? '-') ?></strong><br>
                                            <small style="color: #64748B;"><?= htmlspecialchars($t['Email'] ?? '-') ?></small>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($t['No_Telepon'] ?? '-') ?><br>
                                            <small style="color: #64748B;"><?= htmlspecialchars($t['Alamat'] ?? '-') ?></small>
                                        </td>
                                        <td><strong><?= htmlspecialchars($t['Tujuan'] ?? '-') ?></strong></td>
                                        <td><span class="badge <?= $kelasBadge ?>"><?= htmlspecialchars($t['Kelas'] ?? '-') ?></span></td>
                                        <td><?= htmlspecialchars($t['Jam_Keberangkatan'] ?? '-') ?></td>
                                        <td><strong><?= htmlspecialchars($t['Tempat_Duduk'] ?? '-') ?></strong></td>
                                        <td><?= htmlspecialchars($t['Tgl_Keberangkatan'] ?? '-') ?></td>
                                        <td><strong>Rp <?= number_format($t['Harga_Tiket'] ?? 0, 0, ',', '.') ?></strong></td>
                                        <td>
                                            <a href="dashboard_admin.php?hapus_tiket=<?= $t['ID_Tiket'] ?>&tab=tiket" onclick="return confirm('Apakah Anda yakin ingin menghapus tiket #<?= $t['ID_Tiket'] ?>?')" class="btn-action-del">
                                                <i class="fa-solid fa-trash"></i> Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr>
                                        <td colspan="10" style="text-align: center; color: #94A3B8; padding: 24px;">Belum ada pemesanan tiket terdaftar.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($tab === 'jadwal'): ?>
                <!-- Rute & Jadwal Kereta -->
                <div class="content-header">
                    <div class="content-title">
                        <h2>Manajemen Jadwal Kereta</h2>
                        <p>Kelola daftar rute, jam keberangkatan, dan kelas armada kereta.</p>
                    </div>
                </div>

                <!-- Form Tambah Jadwal -->
                <div class="form-box">
                    <h3><i class="fa-solid fa-plus-circle"></i> Tambah Jadwal / Rute Kereta Baru</h3>
                    <form method="POST" action="dashboard_admin.php?tab=jadwal">
                        <input type="hidden" name="aksi" value="tambah_jadwal">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Rute / Tujuan:</label>
                                <select name="tujuan" required>
                                    <option value="" disabled selected>Pilih Rute</option>
                                    <option value="Jakarta - Bandung">Jakarta - Bandung</option>
                                    <option value="Jakarta - Yogyakarta">Jakarta - Yogyakarta</option>
                                    <option value="Jakarta - Semarang">Jakarta - Semarang</option>
                                    <option value="Jakarta - Surabaya">Jakarta - Surabaya</option>
                                    <option value="Bandung - Yogyakarta">Bandung - Yogyakarta</option>
                                    <option value="Surabaya - Jakarta">Surabaya - Jakarta</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Kelas Kereta:</label>
                                <select name="kelas" required>
                                    <option value="Eksekutif">Eksekutif</option>
                                    <option value="Bisnis">Bisnis</option>
                                    <option value="Ekonomi">Ekonomi</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Jam Keberangkatan:</label>
                                <input type="time" name="jam" required value="08:00">
                            </div>
                            <button type="submit" class="btn-submit-jadwal">
                                <i class="fa-solid fa-check"></i> Simpan Jadwal Kereta
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Daftar Kereta -->
                <div class="card-table">
                    <div class="card-table-header">
                        <h3><i class="fa-solid fa-train"></i> Daftar Rute &amp; Kereta Aktif (<?= $total_kereta ?> Armada)</h3>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Kereta</th>
                                    <th>Rute Tujuan</th>
                                    <th>Kelas</th>
                                    <th>Jam Keberangkatan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($resJadwal && $resJadwal->num_rows > 0):
                                    while ($k = $resJadwal->fetch_assoc()):
                                        $kelasBadge = strtolower($k['Kelas'] ?? 'ekonomi');
                                ?>
                                    <tr>
                                        <td><strong>#<?= $k['ID_Kereta'] ?></strong></td>
                                        <td><strong><?= htmlspecialchars($k['Tujuan'] ?? '-') ?></strong></td>
                                        <td><span class="badge <?= $kelasBadge ?>"><?= htmlspecialchars($k['Kelas'] ?? '-') ?></span></td>
                                        <td><i class="fa-regular fa-clock"></i> <?= htmlspecialchars($k['Jam_Keberangkatan'] ?? '-') ?></td>
                                        <td>
                                            <a href="dashboard_admin.php?hapus_kereta=<?= $k['ID_Kereta'] ?>&tab=jadwal" onclick="return confirm('Hapus jadwal kereta #<?= $k['ID_Kereta'] ?>? Peringatan: Seluruh tiket dengan kereta ini juga akan ikut terhapus!')" class="btn-action-del">
                                                <i class="fa-solid fa-trash"></i> Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: #94A3B8; padding: 24px;">Belum ada jadwal kereta.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($tab === 'pengguna'): ?>
                <!-- Akun Pengguna / Penumpang -->
                <div class="content-header">
                    <div class="content-title">
                        <h2>Daftar Akun Penumpang Terdaftar</h2>
                        <p>Data akun pengguna yang telah terdaftar pada database sistem TiketKeretaApp.</p>
                    </div>
                </div>

                <div class="card-table">
                    <div class="card-table-header">
                        <h3><i class="fa-solid fa-users"></i> Pengguna Login</h3>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Pengguna</th>
                                    <th>Nama Pemesan</th>
                                    <th>Email</th>
                                    <th>Status Akun</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($resUser && $resUser->num_rows > 0):
                                    while ($u = $resUser->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td><strong>#<?= $u['ID_Pengguna'] ?></strong></td>
                                        <td><strong><?= htmlspecialchars($u['Nama_Pemesan'] ?? '-') ?></strong></td>
                                        <td><?= htmlspecialchars($u['Email'] ?? '-') ?></td>
                                        <td><span class="badge bisnis"><i class="fa-solid fa-check"></i> Terdaftar</span></td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; color: #94A3B8; padding: 24px;">Belum ada akun pengguna.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
        function logoutAdmin() {
            if (confirm('Apakah Anda yakin ingin logout dari Dashboard Admin?')) {
                localStorage.removeItem('isLoggedIn');
                localStorage.removeItem('userRole');
                window.location.href = 'logout.php?to=portal.html';
            }
        }
    </script>
</body>
</html>