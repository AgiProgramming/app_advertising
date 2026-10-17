<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sistem Payroll & SPK - Mann Plus Advertising</title>

    <!-- PWA Meta Tags & Manifest -->
    <link rel="manifest" href="/manifest.json?v=2">
    <meta name="theme-color" content="#4f46e5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Mann Plus">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png?v=2">
    <link rel="icon" type="image/png" href="/icons/icon-192x192.png?v=2">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @media print {
            body * { visibility: hidden; }
            #printable-slip, #printable-slip * { visibility: visible; }
            #printable-slip { position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex flex-col font-sans">

    <!-- 1. AUTH SCREEN (LOGIN) -->
    <div id="auth-screen" class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl p-8 space-y-6 border border-slate-100">
            <div class="text-center space-y-2">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white p-2 shadow-md border border-slate-100 overflow-hidden">
                    <img src="/images/logo.png" alt="Logo Mann Plus Advertising" class="w-full h-full object-contain">
                </div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Mann Plus Advertising</h1>
                <p class="text-sm text-slate-500">Sistem Manajemen SPK & Slip Gaji Karyawan</p>
            </div>

            <!-- Login Form -->
            <form id="login-form" onsubmit="handleLogin(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="fa-regular fa-user"></i>
                        </span>
                        <input type="text" id="login-username" required placeholder="Masukkan username" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" id="login-password" required placeholder="••••••••" class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                        <button type="button" onclick="togglePasswordVisibility('login-password', 'eye-login')" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 transition focus:outline-none" title="Lihat/Sembunyikan Password">
                            <i id="eye-login" class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" id="btn-login-submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-lg shadow-indigo-500/25 transition duration-150 flex items-center justify-center space-x-2">
                    <span>Masuk ke Sistem</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </form>

            <div class="pt-4 border-t border-slate-100">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider text-center mb-3">⚡ Quick Login (Pilih Akun)</p>
                <div class="grid grid-cols-3 gap-2">
                    <button onclick="quickLogin('admin_utama', 'password123')" class="p-2.5 rounded-xl border border-indigo-100 bg-indigo-50/50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition flex items-center justify-center space-x-1.5">
                        <i class="fa-solid fa-user-shield text-sm"></i>
                        <span>Admin</span>
                    </button>
                    <button onclick="quickLogin('bapak_dollop', 'dollop123')" class="p-2.5 rounded-xl border border-amber-100 bg-amber-50/50 hover:bg-amber-100 text-amber-700 text-xs font-bold transition flex items-center justify-center space-x-1.5">
                        <i class="fa-solid fa-user-tie text-sm"></i>
                        <span>Owner</span>
                    </button>
                    <button onclick="quickLogin('PEDRO', 'PEDRO123')" class="p-2.5 rounded-xl border border-emerald-100 bg-emerald-50/50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold transition flex items-center justify-center space-x-1.5">
                        <i class="fa-solid fa-user-gear text-sm"></i>
                        <span>Karyawan</span>
                    </button>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 text-center">
                <button type="button" onclick="installPwa()" class="w-full py-2.5 px-3 rounded-xl border border-indigo-200 bg-indigo-50/70 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-mobile-screen-button text-sm"></i>
                    <span>Pasang Aplikasi di HP / Laptop</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. MAIN APP SCREEN -->
    <div id="main-screen" class="hidden min-h-screen flex bg-slate-50 relative overflow-x-hidden">
        <!-- Mobile Sidebar Backdrop Overlay -->
        <div id="sidebar-backdrop" onclick="toggleSidebarMobile(false)" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-40 hidden transition-opacity duration-300 md:hidden"></div>

        <!-- Sidebar (Off-canvas on mobile, fixed width static on desktop) -->
        <aside id="app-sidebar" class="fixed inset-y-0 left-0 z-50 w-72 bg-slate-900 text-slate-300 flex flex-col shadow-2xl flex-shrink-0 transform -translate-x-full transition-transform duration-300 ease-in-out md:static md:w-64 md:translate-x-0 md:shadow-xl">
            <!-- Brand -->
            <div class="p-4 sm:p-5 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-white p-1 flex items-center justify-center shadow-md shadow-slate-900/40 overflow-hidden">
                        <img src="/images/logo.png" alt="Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <h2 class="font-black text-white text-base leading-tight">ADV PAYROLL</h2>
                        <p class="text-xs text-indigo-400 font-medium">Mann Plus Advertising</p>
                    </div>
                </div>
                <!-- Close Button (Mobile Only) -->
                <button onclick="toggleSidebarMobile(false)" class="md:hidden w-8 h-8 rounded-lg bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition" title="Tutup Menu">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <!-- Profile Info -->
            <div class="p-4 mx-3 my-3 rounded-xl bg-slate-800/60 border border-slate-700/50 flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-sm">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="overflow-hidden">
                    <p id="user-display-name" class="text-xs font-bold text-white truncate">Administrator</p>
                    <span id="user-display-role" class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/20 text-indigo-300 uppercase">Admin</span>
                </div>
            </div>

            <!-- Nav Links -->
            <nav class="flex-1 px-3 space-y-1 overflow-y-auto">
                <button onclick="switchTab('dashboard')" id="nav-dashboard" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 shadow-sm transition">
                    <i class="fa-solid fa-chart-pie w-5 text-center"></i>
                    <span>Dashboard</span>
                </button>

                <!-- Admin Only Menus -->
                <div id="menu-admin-group" class="space-y-1 pt-2">
                    <div class="px-3 text-[10px] font-extrabold uppercase text-slate-500 tracking-wider">Manajemen Data</div>
                    <button onclick="switchTab('shops')" id="nav-shops" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <i class="fa-solid fa-store w-5 text-center"></i>
                        <span>Data Toko Pelanggan</span>
                    </button>
                    <button onclick="switchTab('employees')" id="nav-employees" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <i class="fa-solid fa-users w-5 text-center"></i>
                        <span>Data Karyawan</span>
                    </button>
                    <button onclick="switchTab('spks')" id="nav-spks" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <i class="fa-solid fa-file-contract w-5 text-center"></i>
                        <span>SPK (Perintah Kerja)</span>
                    </button>
                    <button onclick="switchTab('cash-advances')" id="nav-cash-advances" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <i class="fa-solid fa-hand-holding-dollar w-5 text-center"></i>
                        <span>Kasbon Karyawan</span>
                    </button>
                    <button onclick="switchTab('payrolls')" id="nav-payrolls" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <i class="fa-solid fa-receipt w-5 text-center"></i>
                        <span>Slip Gaji / Payroll</span>
                    </button>
                    <button onclick="switchTab('reports')" id="nav-reports" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <i class="fa-solid fa-file-invoice-dollar w-5 text-center"></i>
                        <span>Laporan Rekapitulasi</span>
                    </button>
                </div>

                <!-- Owner Menus -->
                <div id="menu-owner-group" class="hidden space-y-1 pt-2">
                    <div class="px-3 text-[10px] font-extrabold uppercase text-slate-500 tracking-wider">Owner Menus</div>
                    <button onclick="switchTab('spks')" id="nav-owner-spks" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <i class="fa-solid fa-file-contract w-5 text-center"></i>
                        <span>Daftar Order SPK</span>
                    </button>
                    <button onclick="switchTab('payrolls')" id="nav-owner-payrolls" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <i class="fa-solid fa-receipt w-5 text-center"></i>
                        <span>Monitoring Slip Gaji</span>
                    </button>
                    <button onclick="switchTab('reports')" id="nav-owner-reports" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <i class="fa-solid fa-file-invoice-dollar w-5 text-center"></i>
                        <span>Laporan Rekapitulasi</span>
                    </button>
                </div>

                <!-- Karyawan Menus -->
                <div id="menu-karyawan-group" class="hidden space-y-1 pt-2">
                    <div class="px-3 text-[10px] font-extrabold uppercase text-slate-500 tracking-wider">Karyawan Menus</div>
                    <button onclick="switchTab('spks')" id="nav-karyawan-spks" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <i class="fa-solid fa-file-contract w-5 text-center"></i>
                        <span>Tugas SPK Saya</span>
                    </button>
                    <button onclick="switchTab('cash-advances')" id="nav-karyawan-cash-advances" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <i class="fa-solid fa-hand-holding-dollar w-5 text-center"></i>
                        <span>Kasbon Saya</span>
                    </button>
                    <button onclick="switchTab('payrolls')" id="nav-karyawan-payrolls" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <i class="fa-solid fa-receipt w-5 text-center"></i>
                        <span>Slip Gaji Saya</span>
                    </button>
                </div>
            </nav>

            <!-- Logout -->
            <div class="p-3 border-t border-slate-800">
                <button onclick="handleLogout()" class="w-full flex items-center justify-center space-x-2 py-2.5 rounded-xl text-xs font-bold text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 transition">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Keluar Sistem</span>
                </button>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            <!-- Navbar -->
            <header class="bg-white border-b border-slate-200 px-4 sm:px-6 md:px-8 py-3.5 flex items-center justify-between sticky top-0 z-20 shadow-xs">
                <div class="flex items-center space-x-3">
                    <button onclick="toggleSidebarMobile(true)" class="md:hidden w-9 h-9 rounded-xl border border-slate-200 text-slate-700 bg-slate-50 hover:bg-slate-100 flex items-center justify-center transition shadow-xs" title="Buka Menu">
                        <i class="fa-solid fa-bars text-sm"></i>
                    </button>
                    <div>
                        <h1 id="page-title" class="text-base sm:text-xl font-black text-slate-900 leading-tight">Dashboard</h1>
                        <p id="page-subtitle" class="text-[11px] sm:text-xs text-slate-500 hidden sm:block">Ringkasan aktivitas operasional dan finansial</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <button onclick="installPwa()" title="Instal Aplikasi di HP/Laptop" class="px-2.5 sm:px-3 py-1.5 rounded-xl border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center space-x-1.5 transition">
                        <i class="fa-solid fa-mobile-screen-button text-xs sm:text-sm"></i>
                        <span class="hidden sm:inline">Pasang App</span>
                    </button>
                    <span id="status-live-indicator" class="flex items-center space-x-1.5 px-2.5 sm:px-3 py-1 rounded-full text-[11px] sm:text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="hidden sm:inline">API Terhubung</span>
                        <span class="sm:hidden">Online</span>
                    </span>
                    <button onclick="refreshCurrentTab()" title="Muat ulang data" class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 flex items-center justify-center transition">
                        <i class="fa-solid fa-rotate text-xs sm:text-sm"></i>
                    </button>
                </div>
            </header>

            <!-- Container Konten Dinamis -->
            <main class="p-3.5 sm:p-6 md:p-8 flex-1 w-full max-w-full overflow-x-hidden">
                <!-- TAB 1: DASHBOARD -->
                <div id="tab-dashboard" class="tab-pane space-y-6">
                    <!-- Cards Grid -->
                    <div id="dashboard-metrics" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                        <!-- Populated by JS -->
                    </div>

                    <!-- Additional Details / Tables -->
                    <div id="dashboard-extra" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <!-- TAB 2: DATA TOKO -->
                <div id="tab-shops" class="tab-pane hidden space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base sm:text-lg font-bold text-slate-900">Daftar Toko Pelanggan</h2>
                            <p class="text-xs text-slate-500">Kelola master data toko rekanan bengkel advertising</p>
                        </div>
                        <button onclick="openModalShop()" class="w-full sm:w-auto justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-600/20 flex items-center space-x-2 transition">
                            <i class="fa-solid fa-plus"></i>
                            <span>Tambah Toko Baru</span>
                        </button>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-600">
                                <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-[11px] border-b border-slate-200">
                                    <tr>
                                        <th class="px-6 py-4">Kode Toko</th>
                                        <th class="px-6 py-4">Nama Toko</th>
                                        <th class="px-6 py-4">Pemilik</th>
                                        <th class="px-6 py-4">No. Telepon</th>
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="table-shops-body" class="divide-y divide-slate-100 font-medium">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: DATA KARYAWAN -->
                <div id="tab-employees" class="tab-pane hidden space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base sm:text-lg font-bold text-slate-900">Data Karyawan & Teknisi</h2>
                            <p class="text-xs text-slate-500">Kelola profil karyawan dan akun login aplikasi</p>
                        </div>
                        <button onclick="openModalEmployee()" class="w-full sm:w-auto justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-600/20 flex items-center space-x-2 transition">
                            <i class="fa-solid fa-user-plus"></i>
                            <span>Tambah Karyawan Baru</span>
                        </button>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-600">
                                <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-[11px] border-b border-slate-200">
                                    <tr>
                                        <th class="px-6 py-4">NIK</th>
                                        <th class="px-6 py-4">Nama Karyawan</th>
                                        <th class="px-6 py-4">Jabatan</th>
                                        <th class="px-6 py-4">Username Login</th>
                                        <th class="px-6 py-4">No. Telepon</th>
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="table-employees-body" class="divide-y divide-slate-100 font-medium">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: SPK -->
                <div id="tab-spks" class="tab-pane hidden space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base sm:text-lg font-bold text-slate-900">Surat Perintah Kerja (SPK)</h2>
                            <p class="text-xs text-slate-500">Kelola order advertising, teknisi penanggung jawab, dan nilai SPK</p>
                        </div>
                        <button onclick="openModalSpk()" class="w-full sm:w-auto justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-600/20 flex items-center space-x-2 transition">
                            <i class="fa-solid fa-plus"></i>
                            <span>Buat SPK Baru</span>
                        </button>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-600">
                                <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-[11px] border-b border-slate-200">
                                    <tr>
                                        <th class="px-6 py-4">No. SPK</th>
                                        <th class="px-6 py-4">Tanggal</th>
                                        <th class="px-6 py-4">Toko Pelanggan</th>
                                        <th class="px-6 py-4">Pekerjaan</th>
                                        <th class="px-6 py-4">Teknisi</th>
                                        <th class="px-6 py-4">Nilai SPK</th>
                                        <th class="px-6 py-4">Status Pengerjaan</th>
                                        <th class="px-6 py-4">Pembayaran</th>
                                        <th class="px-6 py-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="table-spks-body" class="divide-y divide-slate-100 font-medium">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: KASBON -->
                <div id="tab-cash-advances" class="tab-pane hidden space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base sm:text-lg font-bold text-slate-900">Kasbon Karyawan (Cash Advance)</h2>
                            <p class="text-xs text-slate-500">Pencatatan pinjaman kasbon yang dipotong otomatis saat penggajian</p>
                        </div>
                        <button id="btn-add-cash-advance" onclick="openModalCashAdvance()" class="w-full sm:w-auto justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-600/20 flex items-center space-x-2 transition">
                            <i class="fa-solid fa-plus"></i>
                            <span>Input Kasbon Baru</span>
                        </button>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-600">
                                <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-[11px] border-b border-slate-200">
                                    <tr>
                                        <th class="px-6 py-4">Tanggal</th>
                                        <th class="px-6 py-4">Karyawan</th>
                                        <th class="px-6 py-4">Nominal Kasbon</th>
                                        <th class="px-6 py-4">Keterangan</th>
                                        <th class="px-6 py-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="table-cash-advances-body" class="divide-y divide-slate-100 font-medium">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 6: PAYROLL / SLIP GAJI -->
                <div id="tab-payrolls" class="tab-pane hidden space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base sm:text-lg font-bold text-slate-900">Penggajian & Slip Gaji Karyawan</h2>
                            <p class="text-xs text-slate-500">Kalkulasi otomatis (PPN 3%, Komisi 22%, Uang Makan, Potong Kasbon)</p>
                        </div>
                        <button id="btn-create-payroll-modal" onclick="openModalCreatePayroll()" class="w-full sm:w-auto justify-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-600/20 flex items-center space-x-2 transition">
                            <i class="fa-solid fa-calculator"></i>
                            <span>Buat Slip Gaji Baru</span>
                        </button>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-600">
                                <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-[11px] border-b border-slate-200">
                                    <tr>
                                        <th class="px-6 py-4">No. Slip</th>
                                        <th class="px-6 py-4">Periode</th>
                                        <th class="px-6 py-4">Karyawan</th>
                                        <th class="px-6 py-4">No. SPK</th>
                                        <th class="px-6 py-4">Nilai SPK</th>
                                        <th class="px-6 py-4">Hak Gaji (22%)</th>
                                        <th class="px-6 py-4">Pot. Uang Makan</th>
                                        <th class="px-6 py-4">Pot. Kasbon</th>
                                        <th class="px-6 py-4">Gaji Bersih</th>
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="table-payrolls-body" class="divide-y divide-slate-100 font-medium">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 7: LAPORAN -->
                <div id="tab-reports" class="tab-pane hidden space-y-6">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900">Laporan Rekapitulasi</h2>
                        <p class="text-xs text-slate-500">Pantau performa omset SPK, pengeluaran gaji, dan pinjaman kasbon</p>
                    </div>

                    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4 sm:space-y-6">
                        <div class="flex space-x-2 border-b border-slate-200 pb-3 overflow-x-auto whitespace-nowrap">
                            <button onclick="switchReportSubTab('spk')" id="subtab-btn-spk" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white flex-shrink-0 transition">Laporan SPK</button>
                            <button onclick="switchReportSubTab('payroll')" id="subtab-btn-payroll" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 flex-shrink-0 transition">Laporan Penggajian</button>
                            <button onclick="switchReportSubTab('cash-advance')" id="subtab-btn-cash-advance" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 flex-shrink-0 transition">Laporan Kasbon</button>
                        </div>

                        <!-- Report Content Container -->
                        <div id="report-content-container" class="space-y-4">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ==================== MODALS ==================== -->

    <!-- Modal Form Toko -->
    <div id="modal-shop" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-4 sm:p-6 shadow-2xl border border-slate-100 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b pb-3">
                <h3 id="modal-shop-title" class="font-bold text-slate-900 text-sm">Tambah Toko Pelanggan</h3>
                <button onclick="closeModal('modal-shop')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="form-shop" onsubmit="handleSaveShop(event)" class="space-y-3 text-xs">
                <input type="hidden" id="shop-id">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kode Toko</label>
                    <input type="text" id="shop-code" required placeholder="Contoh: TK-002" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Toko</label>
                    <input type="text" id="shop-name" required placeholder="Nama toko pelanggan" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Pemilik</label>
                    <input type="text" id="shop-owner" placeholder="Nama pemilik toko" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">No. Telepon</label>
                    <input type="text" id="shop-phone" placeholder="08xxxxxxxx" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Alamat</label>
                    <textarea id="shop-address" rows="2" placeholder="Alamat toko..." class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                </div>
                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('modal-shop')" class="px-4 py-2 text-slate-600 bg-slate-100 rounded-xl font-bold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold">Simpan Toko</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Form Karyawan -->
    <div id="modal-employee" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-4 sm:p-6 shadow-2xl border border-slate-100 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b pb-3">
                <h3 class="font-bold text-slate-900 text-sm">Tambah Karyawan Baru</h3>
                <button onclick="closeModal('modal-employee')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="form-employee" onsubmit="handleSaveEmployee(event)" class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">NIK (Nomor Induk Karyawan)</label>
                    <input type="text" id="emp-nik" required placeholder="EMP-003" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Lengkap</label>
                    <input type="text" id="emp-name" required placeholder="Nama karyawan" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Jabatan / Posisi</label>
                    <input type="text" id="emp-position" required placeholder="Contoh: Teknisi Huruf Timbul" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Username Login</label>
                        <input type="text" id="emp-username" required placeholder="username_login" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Password</label>
                        <div class="relative">
                            <input type="password" id="emp-password" required placeholder="Minimal 6 karakter" class="w-full pl-3 pr-8 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                            <button type="button" onclick="togglePasswordVisibility('emp-password', 'eye-emp')" class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 transition focus:outline-none" title="Lihat/Sembunyikan Password">
                                <i id="eye-emp" class="fa-regular fa-eye text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">No. Telepon</label>
                    <input type="text" id="emp-phone" placeholder="08xxxxxxxx" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('modal-employee')" class="px-4 py-2 text-slate-600 bg-slate-100 rounded-xl font-bold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold">Simpan Karyawan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Form SPK -->
    <div id="modal-spk" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-4 sm:p-6 shadow-2xl border border-slate-100 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b pb-3">
                <h3 class="font-bold text-slate-900 text-sm">Buat SPK Baru (Auto Nomor SPK)</h3>
                <button onclick="closeModal('modal-spk')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="form-spk" onsubmit="handleSaveSpk(event)" class="space-y-3 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tanggal SPK</label>
                        <input type="date" id="spk-date" required class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pilih Toko Pelanggan</label>
                        <select id="spk-shop-id" required class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                            <!-- Filled dynamically -->
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Teknisi Penanggung Jawab</label>
                    <select id="spk-employee-id" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                        <!-- Filled dynamically -->
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Pekerjaan</label>
                    <input type="text" id="spk-job-name" required placeholder="Contoh: Pemasangan Neon Box 3x1m" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nilai SPK (Rp)</label>
                        <input type="number" id="spk-value" required min="0" placeholder="5000000" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Status Pengerjaan</label>
                        <select id="spk-status" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                            <option value="Sedang Dikerjakan">Sedang Dikerjakan</option>
                            <option value="Selesai">Selesai</option>
                            <option value="Belum Dikerjakan">Belum Dikerjakan</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                    <textarea id="spk-notes" rows="2" placeholder="Spesifikasi / catatan lokasi..." class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                </div>
                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('modal-spk')" class="px-4 py-2 text-slate-600 bg-slate-100 rounded-xl font-bold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold">Terbitkan SPK</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Form Kasbon -->
    <div id="modal-cash-advance" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-4 sm:p-6 shadow-2xl border border-slate-100 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b pb-3">
                <h3 class="font-bold text-slate-900 text-sm">Input Pinjaman Kasbon Karyawan</h3>
                <button onclick="closeModal('modal-cash-advance')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="form-cash-advance" onsubmit="handleSaveCashAdvance(event)" class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Karyawan</label>
                    <select id="ca-employee-id" required class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                        <!-- Filled dynamically -->
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tanggal</label>
                        <input type="date" id="ca-date" required class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nominal (Rp)</label>
                        <input type="number" id="ca-amount" min="1" required placeholder="300000" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Keterangan / Keperluan</label>
                    <textarea id="ca-desc" rows="2" placeholder="Alasan pengajuan kasbon..." class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                </div>
                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('modal-cash-advance')" class="px-4 py-2 text-slate-600 bg-slate-100 rounded-xl font-bold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold">Simpan Kasbon</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Buat Slip Gaji / Payroll dengan Live Calculation Preview -->
    <div id="modal-create-payroll" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full p-4 sm:p-6 shadow-2xl border border-slate-100 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b pb-3">
                <div class="flex items-center space-x-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-600 flex items-center justify-center font-bold text-xs">
                        <i class="fa-solid fa-calculator"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 text-sm">Buat Slip Gaji & Hitung Otomatis</h3>
                </div>
                <button onclick="closeModal('modal-create-payroll')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form id="form-payroll" onsubmit="handleSavePayroll(event)" class="space-y-4 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pilih SPK (Selesai/Dikerjakan)</label>
                        <select id="pr-spk-id" onchange="triggerPayrollPreview()" required class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none bg-white">
                            <!-- Filled dynamically -->
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Periode Slip (YYYY-MM)</label>
                        <input type="text" id="pr-period" required value="2026-10" placeholder="2026-10" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Komisi Gaji (%)</label>
                        <input type="number" id="pr-salary-percentage" oninput="triggerPayrollPreview()" value="22" step="0.1" min="1" max="100" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pot. Uang Makan (Rp)</label>
                        <input type="number" id="pr-meal-allowance" oninput="triggerPayrollPreview()" value="0" min="0" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pot. Kasbon (Rp)</label>
                        <input type="number" id="pr-cash-advance-deduction" oninput="triggerPayrollPreview()" placeholder="Otomatis" min="0" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none">
                    </div>
                </div>

                <!-- Live Preview Result Box -->
                <div id="payroll-preview-box" class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                    <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Simulasi Rincian Slip Gaji</p>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-slate-600">
                        <div>Nilai Asli SPK:</div>
                        <div id="prev-spk-val" class="font-bold text-right text-slate-800">Rp 0</div>
                        <div>Pajak PPN (3%):</div>
                        <div id="prev-ppn-val" class="font-bold text-right text-rose-500">- Rp 0</div>
                        <div>Nilai Bersih SPK:</div>
                        <div id="prev-net-spk" class="font-bold text-right text-slate-800">Rp 0</div>
                        <div>Hak Gaji Karyawan (22%):</div>
                        <div id="prev-gross-salary" class="font-bold text-right text-indigo-600">Rp 0</div>
                        <div>Potongan Uang Makan:</div>
                        <div id="prev-meal" class="font-bold text-right text-rose-500">- Rp 0</div>
                        <div>Sisa Kasbon Tersedia:</div>
                        <div id="prev-rem-cash" class="font-bold text-right text-amber-600">Rp 0</div>
                        <div>Potongan Kasbon:</div>
                        <div id="prev-deduct-cash" class="font-bold text-right text-rose-500">- Rp 0</div>
                    </div>
                    <div class="pt-2 border-t border-slate-200 flex justify-between items-center">
                        <span class="font-black text-slate-800 text-sm">TOTAL GAJI BERSIH:</span>
                        <span id="prev-net-salary" class="font-black text-emerald-600 text-base">Rp 0</span>
                    </div>
                </div>

                <div class="pt-2 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-2">
                    <button type="button" onclick="closeModal('modal-create-payroll')" class="w-full sm:w-auto px-4 py-2 text-slate-600 bg-slate-100 rounded-xl font-bold">Batal</button>
                    <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow-md shadow-emerald-600/20">Terbitkan Slip Gaji Resmi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Cetak Slip Gaji (Printable Modal) -->
    <div id="modal-view-slip" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-4 sm:p-6 shadow-2xl border border-slate-100 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b pb-3 no-print">
                <h3 class="font-bold text-slate-900 text-sm">Lembar Slip Gaji Karyawan</h3>
                <div class="flex items-center space-x-2">
                    <button onclick="window.print()" class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg font-bold text-xs flex items-center space-x-1.5">
                        <i class="fa-solid fa-print"></i>
                        <span>Cetak / PDF</span>
                    </button>
                    <button onclick="closeModal('modal-view-slip')" class="text-slate-400 hover:text-slate-600 p-1"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>

            <!-- Content Area of Slip -->
            <div id="printable-slip" class="p-6 bg-white border border-slate-200 rounded-xl text-slate-800 space-y-4 text-xs font-mono">
                <!-- Populated dynamically -->
            </div>
        </div>
    </div>

    <!-- ==================== CORE JAVASCRIPT ==================== -->
    <script>
        const API_URL = '/api';
        let currentTab = 'dashboard';
        let currentUser = null;
        let token = localStorage.getItem('token');

        // Number Formatter
        function formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        }

        // Toggle Password Visibility (Show / Hide Password)
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (!input || !icon) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Centralized API Fetcher with Auto-401 Logout
        async function fetchApi(url, options = {}) {
            options.headers = options.headers || {};
            if (token) {
                options.headers['Authorization'] = `Bearer ${token}`;
            }
            options.headers['Accept'] = 'application/json';

            const res = await fetch(url, options);
            if (res.status === 401) {
                localStorage.removeItem('token');
                token = null;
                currentUser = null;
                showAuthScreen();
                Swal.fire({
                    icon: 'warning',
                    title: 'Sesi Login Berakhir',
                    text: 'Akun atau sesi Anda telah direset. Silakan login kembali.',
                    timer: 2000,
                    showConfirmButton: false
                });
                throw new Error('Unauthenticated');
            }
            return res;
        }

        // ==================== PWA INSTALLATION & SERVICE WORKER ====================
        let deferredPrompt = null;

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js?v=2')
                    .then(reg => {
                        reg.update();
                        console.log('Service Worker terdaftar:', reg.scope);
                    })
                    .catch(err => console.log('Service Worker gagal:', err));
            });
        }

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
        });

        async function installPwa() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Aplikasi Terpasang!',
                        text: 'Aplikasi ADV Payroll telah ditambahkan ke layar utama.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
                deferredPrompt = null;
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'Cara Pasang di HP & Laptop',
                    html: `
                        <div class="text-left text-xs space-y-3 p-1">
                            <div class="p-3 bg-indigo-50 rounded-xl border border-indigo-100">
                                <p class="font-bold text-indigo-900 mb-1"><i class="fa-brands fa-android text-emerald-600 text-sm"></i> Di HP Android (Chrome):</p>
                                <p class="text-slate-600">1. Buka website ini di Google Chrome HP Anda.</p>
                                <p class="text-slate-600">2. Ketuk ikon titik tiga (<strong>⋮</strong>) di pojok kanan atas.</p>
                                <p class="text-slate-600">3. Pilih <strong>"Tambahkan ke Layar Utama"</strong> atau <strong>"Instal Aplikasi"</strong>.</p>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                                <p class="font-bold text-slate-900 mb-1"><i class="fa-solid fa-laptop text-indigo-600 text-sm"></i> Di Laptop / Komputer (Chrome/Edge):</p>
                                <p class="text-slate-600">1. Lihat di sisi kanan kolom alamat URL browser.</p>
                                <p class="text-slate-600">2. Klik ikon komputer/panah <strong>"Instal Aplikasi ADV Payroll"</strong>.</p>
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: '#4f46e5'
                });
            }
        }

        // Init App on Load
        window.addEventListener('DOMContentLoaded', async () => {
            if (token) {
                await checkProfile();
            } else {
                showAuthScreen();
            }
        });

        function showAuthScreen() {
            document.getElementById('auth-screen').classList.remove('hidden');
            document.getElementById('main-screen').classList.add('hidden');
        }

        function showMainScreen() {
            document.getElementById('auth-screen').classList.add('hidden');
            document.getElementById('main-screen').classList.remove('hidden');
            setupRoleViews();
            loadTabData(currentTab);
        }

        function getUserRole() {
            if (!currentUser) return null;
            if (typeof currentUser.role === 'string') return currentUser.role;
            if (currentUser.role && typeof currentUser.role.name === 'string') return currentUser.role.name;
            return null;
        }

        // Setup views based on role
        function setupRoleViews() {
            const role = getUserRole();
            document.getElementById('user-display-name').innerText = currentUser?.employee?.name || currentUser?.username;
            document.getElementById('user-display-role').innerText = role || 'User';

            // Hide all role groups
            document.getElementById('menu-admin-group').classList.add('hidden');
            document.getElementById('menu-owner-group').classList.add('hidden');
            document.getElementById('menu-karyawan-group').classList.add('hidden');

            // Button controls
            const btnCreatePayroll = document.getElementById('btn-create-payroll-modal');
            const btnAddCashAdv = document.getElementById('btn-add-cash-advance');

            if (role === 'Admin') {
                document.getElementById('menu-admin-group').classList.remove('hidden');
                if (btnCreatePayroll) btnCreatePayroll.classList.remove('hidden');
                if (btnAddCashAdv) btnAddCashAdv.classList.remove('hidden');
            } else if (role === 'Owner') {
                document.getElementById('menu-owner-group').classList.remove('hidden');
                if (btnCreatePayroll) btnCreatePayroll.classList.add('hidden');
                if (btnAddCashAdv) btnAddCashAdv.classList.add('hidden');
            } else if (role === 'Karyawan') {
                document.getElementById('menu-karyawan-group').classList.remove('hidden');
                if (btnCreatePayroll) btnCreatePayroll.classList.add('hidden');
                if (btnAddCashAdv) btnAddCashAdv.classList.add('hidden');
            }
        }

        // Fetch user profile
        async function checkProfile() {
            try {
                const res = await fetchApi(`${API_URL}/profile`);
                if (res.ok) {
                    const data = await res.json();
                    currentUser = data.user;
                    showMainScreen();
                } else {
                    handleLogout();
                }
            } catch (err) {
                showAuthScreen();
            }
        }

        // Quick Login helper
        async function quickLogin(username, password = 'password123') {
            document.getElementById('login-username').value = username;
            document.getElementById('login-password').value = password;
            await handleLogin(new Event('submit'));
        }

        // Handle Login
        async function handleLogin(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-login-submit');
            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner animate-spin"></i> <span>Memproses...</span>`;

            const username = document.getElementById('login-username').value;
            const password = document.getElementById('login-password').value;

            try {
                const res = await fetch(`${API_URL}/login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ username, password })
                });
                const data = await res.json();

                if (res.ok && data.access_token) {
                    token = data.access_token;
                    localStorage.setItem('token', token);
                    currentUser = data.user;
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Berhasil!',
                        text: `Selamat datang, ${data.user.username} (${data.role})`,
                        timer: 1200,
                        showConfirmButton: false
                    });
                    showMainScreen();
                } else {
                    Swal.fire({ icon: 'error', title: 'Login Gagal', text: data.message || 'Periksa kembali username dan password Anda.' });
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: 'Tidak dapat menghubungi server API.' });
            } finally {
                btn.disabled = false;
                btn.innerHTML = `<span>Masuk ke Sistem</span> <i class="fa-solid fa-arrow-right text-xs"></i>`;
            }
        }

        // Logout
        async function handleLogout() {
            try {
                if (token) {
                    await fetch(`${API_URL}/logout`, {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                }
            } catch (err) {}
            localStorage.removeItem('token');
            token = null;
            currentUser = null;
            showAuthScreen();
        }

        // Mobile Sidebar Drawer Toggle
        function toggleSidebarMobile(show) {
            const sidebar = document.getElementById('app-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            if (!sidebar || !backdrop) return;
            if (show === undefined) {
                show = sidebar.classList.contains('-translate-x-full');
            }
            if (show) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        }

        // Switch Tab UI
        function switchTab(tab) {
            currentTab = tab;
            toggleSidebarMobile(false); // Close sidebar drawer on mobile upon tab selection

            document.querySelectorAll('.tab-pane').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.nav-item').forEach(el => {
                el.classList.remove('bg-indigo-600', 'text-white');
                el.classList.add('text-slate-400');
            });

            const target = document.getElementById(`tab-${tab}`);
            if (target) target.classList.remove('hidden');

            const activeBtn = document.getElementById(`nav-${tab}`) || document.getElementById(`nav-owner-${tab}`) || document.getElementById(`nav-karyawan-${tab}`);
            if (activeBtn) {
                activeBtn.classList.add('bg-indigo-600', 'text-white');
                activeBtn.classList.remove('text-slate-400');
            }

            // Update Header Title
            const titles = {
                'dashboard': ['Dashboard', 'Ringkasan operasional dan finansial terkini'],
                'shops': ['Data Toko Pelanggan', 'Kelola data master toko dan nomor kontak rekanan'],
                'employees': ['Data Karyawan & Teknisi', 'Kelola profil tim dan kredensial login'],
                'spks': ['Surat Perintah Kerja (SPK)', 'Pencatatan order pengerjaan advertising'],
                'cash-advances': ['Kasbon Karyawan', 'Riwayat pinjaman dana operasional/pribadi'],
                'payrolls': ['Slip Gaji & Payroll', 'Kalkulasi slip gaji dan pelunasan hak karyawan'],
                'reports': ['Laporan Rekapitulasi', 'Pusat ekspor dan analisa data bisnis']
            };
            if (titles[tab]) {
                document.getElementById('page-title').innerText = titles[tab][0];
                document.getElementById('page-subtitle').innerText = titles[tab][1];
            }

            loadTabData(tab);
        }

        function refreshCurrentTab() {
            loadTabData(currentTab);
        }

        // Modal Helpers
        function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
        function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

        // ==================== DATA LOADERS ====================

        async function loadTabData(tab) {
            if (tab === 'dashboard') loadDashboardData();
            if (tab === 'shops') loadShops();
            if (tab === 'employees') loadEmployees();
            if (tab === 'spks') loadSpks();
            if (tab === 'cash-advances') loadCashAdvances();
            if (tab === 'payrolls') loadPayrolls();
            if (tab === 'reports') switchReportSubTab('spk');
        }

        // 1. DASHBOARD
        async function loadDashboardData() {
            const role = getUserRole();
            const container = document.getElementById('dashboard-metrics');
            const extraContainer = document.getElementById('dashboard-extra');
            container.innerHTML = `<div class="col-span-1 sm:col-span-2 lg:col-span-4 p-8 text-center text-slate-400"><i class="fa-solid fa-spinner animate-spin text-2xl"></i> Memuat dashboard...</div>`;

            let url = `${API_URL}/admin/dashboard`;
            if (role === 'Owner') url = `${API_URL}/owner/dashboard`;
            if (role === 'Karyawan') url = `${API_URL}/karyawan/dashboard`;

            try {
                const res = await fetchApi(url);
                if (!res.ok) {
                    const err = await res.json();
                    container.innerHTML = `<div class="col-span-1 sm:col-span-2 lg:col-span-4 p-6 text-center text-rose-500 font-bold bg-rose-50 rounded-2xl border border-rose-200">${err.message || 'Gagal memuat data dashboard.'}</div>`;
                    return;
                }
                const json = await res.json();
                const d = json.data;

                if (role === 'Admin') {
                    container.innerHTML = `
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase">Toko Rekanan</p>
                                <h3 class="text-2xl font-black text-slate-800 mt-1">${d.overview.total_shops}</h3>
                                <p class="text-[11px] text-emerald-600 font-bold mt-1">${d.overview.active_shops} Toko Aktif</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold"><i class="fa-solid fa-store"></i></div>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase">Total Karyawan</p>
                                <h3 class="text-2xl font-black text-slate-800 mt-1">${d.overview.total_employees}</h3>
                                <p class="text-[11px] text-emerald-600 font-bold mt-1">${d.overview.active_employees} Teknisi Aktif</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl font-bold"><i class="fa-solid fa-users"></i></div>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase">Total SPK Order</p>
                                <h3 class="text-2xl font-black text-slate-800 mt-1">${d.spk.total_spk}</h3>
                                <p class="text-[11px] text-slate-500 font-bold mt-1">${formatRupiah(d.spk.total_spk_value)}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold"><i class="fa-solid fa-file-contract"></i></div>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase">Gaji Sudah Dibayar</p>
                                <h3 class="text-2xl font-black text-slate-800 mt-1">${formatRupiah(d.payroll.total_paid_salary)}</h3>
                                <p class="text-[11px] text-amber-600 font-bold mt-1">Sisa Kasbon: ${formatRupiah(d.cash_advance.remaining_unpaid)}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center text-xl font-bold"><i class="fa-solid fa-receipt"></i></div>
                        </div>
                    `;

                    // Recent SPKs
                    let spkRows = '';
                    (d.recent_spks || []).forEach(s => {
                        spkRows += `
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-bold text-slate-800">${s.spk_number}</td>
                                <td class="px-4 py-3">${s.shop?.name || '-'}</td>
                                <td class="px-4 py-3 font-bold">${formatRupiah(s.spk_value)}</td>
                                <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-[10px] font-bold ${s.job_status === 'Selesai' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">${s.job_status}</span></td>
                            </tr>
                        `;
                    });

                    extraContainer.innerHTML = `
                        <div class="lg:col-span-2 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                            <h3 class="font-bold text-slate-900 text-sm">SPK Terbaru</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 text-slate-500 font-bold">
                                        <tr><th class="px-4 py-2">No. SPK</th><th class="px-4 py-2">Toko</th><th class="px-4 py-2">Nilai SPK</th><th class="px-4 py-2">Status</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">${spkRows || '<tr><td colspan="4" class="p-4 text-center">Belum ada SPK</td></tr>'}</tbody>
                                </table>
                            </div>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                            <h3 class="font-bold text-slate-900 text-sm">Quick Action</h3>
                            <div class="space-y-2 text-xs font-bold">
                                <button onclick="switchTab('spks'); openModalSpk();" class="w-full p-3 bg-indigo-50 text-indigo-700 rounded-xl hover:bg-indigo-100 flex items-center justify-between"><span>+ Buat SPK Baru</span><i class="fa-solid fa-arrow-right"></i></button>
                                <button onclick="switchTab('payrolls'); openModalCreatePayroll();" class="w-full p-3 bg-emerald-50 text-emerald-700 rounded-xl hover:bg-emerald-100 flex items-center justify-between"><span>+ Hitung Slip Gaji</span><i class="fa-solid fa-arrow-right"></i></button>
                                <button onclick="switchTab('cash-advances'); openModalCashAdvance();" class="w-full p-3 bg-amber-50 text-amber-700 rounded-xl hover:bg-amber-100 flex items-center justify-between"><span>+ Input Kasbon</span><i class="fa-solid fa-arrow-right"></i></button>
                            </div>
                        </div>
                    `;
                } else if (role === 'Owner') {
                    container.innerHTML = `
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                            <p class="text-xs font-bold text-slate-400 uppercase">Omset Kotor SPK</p>
                            <h3 class="text-2xl font-black text-slate-800 mt-1">${formatRupiah(d.financial.gross_revenue)}</h3>
                            <p class="text-[11px] text-slate-500 font-bold mt-1">PPN (3%): ${formatRupiah(d.financial.estimated_ppn)}</p>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                            <p class="text-xs font-bold text-slate-400 uppercase">Omset Bersih SPK</p>
                            <h3 class="text-2xl font-black text-indigo-600 mt-1">${formatRupiah(d.financial.net_revenue)}</h3>
                            <p class="text-[11px] text-slate-500 font-bold mt-1">Setelah PPN 3%</p>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                            <p class="text-xs font-bold text-slate-400 uppercase">Beban Gaji Karyawan</p>
                            <h3 class="text-2xl font-black text-rose-600 mt-1">${formatRupiah(d.financial.total_salary_expense)}</h3>
                            <p class="text-[11px] text-emerald-600 font-bold mt-1">Dibayar: ${formatRupiah(d.financial.paid_salary_expense)}</p>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                            <p class="text-xs font-bold text-slate-400 uppercase">Margin Operasional</p>
                            <h3 class="text-2xl font-black text-emerald-600 mt-1">${formatRupiah(d.financial.operating_margin)}</h3>
                            <p class="text-[11px] text-slate-500 font-bold mt-1">Laba Kasar Bengkel</p>
                        </div>
                    `;

                    let shopRows = '';
                    (d.top_shops || []).forEach(sh => {
                        shopRows += `
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-bold">${sh.name}</td>
                                <td class="px-4 py-3">${sh.total_spk_count} Order</td>
                                <td class="px-4 py-3 font-bold text-right text-emerald-600">${formatRupiah(sh.total_revenue)}</td>
                            </tr>
                        `;
                    });

                    extraContainer.innerHTML = `
                        <div class="lg:col-span-3 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                            <h3 class="font-bold text-slate-900 text-sm">Top Toko Pelanggan</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 text-slate-500 font-bold">
                                        <tr><th class="px-4 py-2">Toko</th><th class="px-4 py-2">Total SPK</th><th class="px-4 py-2 text-right">Kontribusi Omset</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">${shopRows || '<tr><td colspan="3" class="p-4 text-center">Belum ada data toko</td></tr>'}</tbody>
                                </table>
                            </div>
                        </div>
                    `;
                } else if (role === 'Karyawan') {
                    container.innerHTML = `
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                            <p class="text-xs font-bold text-slate-400 uppercase">Total SPK Ditugaskan</p>
                            <h3 class="text-2xl font-black text-slate-800 mt-1">${d.spk.total_assigned}</h3>
                            <p class="text-[11px] text-emerald-600 font-bold mt-1">${d.spk.completed} Selesai</p>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                            <p class="text-xs font-bold text-slate-400 uppercase">Gaji Diterima (Paid)</p>
                            <h3 class="text-2xl font-black text-emerald-600 mt-1">${formatRupiah(d.financial.total_salary_received)}</h3>
                            <p class="text-[11px] text-slate-500 font-bold mt-1">Pending: ${formatRupiah(d.financial.pending_salary)}</p>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                            <p class="text-xs font-bold text-slate-400 uppercase">Sisa Kasbon Saya</p>
                            <h3 class="text-2xl font-black text-amber-600 mt-1">${formatRupiah(d.financial.remaining_cash_advance)}</h3>
                            <p class="text-[11px] text-slate-500 font-bold mt-1">Akan dipotong di slip berikutnya</p>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                            <p class="text-xs font-bold text-slate-400 uppercase">Pekerjaan Aktif</p>
                            <h3 class="text-2xl font-black text-indigo-600 mt-1">${d.spk.in_progress}</h3>
                            <p class="text-[11px] text-indigo-500 font-bold mt-1">Sedang dikerjakan</p>
                        </div>
                    `;
                    let taskRows = '';
                    (d.active_tasks || []).forEach(t => {
                        taskRows += `
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono font-bold text-indigo-600">${t.spk_number}</td>
                                <td class="px-4 py-3 font-bold text-slate-800">${t.shop?.name || '-'}</td>
                                <td class="px-4 py-3">${t.job_name}</td>
                                <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700">${t.job_status}</span></td>
                            </tr>
                        `;
                    });

                    extraContainer.innerHTML = `
                        <div class="lg:col-span-3 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                            <h3 class="font-bold text-slate-900 text-sm">Daftar Pekerjaan SPK Aktif Saya</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 text-slate-500 font-bold">
                                        <tr><th class="px-4 py-2">No. SPK</th><th class="px-4 py-2">Toko</th><th class="px-4 py-2">Pekerjaan</th><th class="px-4 py-2">Status</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">${taskRows || '<tr><td colspan="4" class="p-4 text-center text-slate-400">Tidak ada pekerjaan SPK aktif saat ini</td></tr>'}</tbody>
                                </table>
                            </div>
                        </div>
                    `;
                }
            } catch (err) {
                container.innerHTML = `<div class="col-span-4 p-4 text-center text-rose-500">Gagal memuat dashboard.</div>`;
            }
        }

        // 2. SHOPS
        async function loadShops() {
            const tbody = document.getElementById('table-shops-body');
            tbody.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-slate-400"><i class="fa-solid fa-spinner animate-spin"></i> Memuat data toko...</td></tr>`;

            try {
                const res = await fetchApi(`${API_URL}/shops`);
                const json = await res.json();
                const shops = json.data || [];

                if (shops.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-slate-400">Belum ada toko yang terdaftar.</td></tr>`;
                    return;
                }

                tbody.innerHTML = shops.map(s => `
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 font-bold text-indigo-600">${s.code}</td>
                        <td class="px-6 py-4 font-bold text-slate-800">${s.name}</td>
                        <td class="px-6 py-4">${s.owner_name || '-'}</td>
                        <td class="px-6 py-4">${s.phone || '-'}</td>
                        <td class="px-6 py-4"><span class="px-2 py-0.5 rounded text-[10px] font-bold ${s.status === 'Aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}">${s.status}</span></td>
                        <td class="px-6 py-4 text-right space-x-1">
                            <button onclick="deleteShop('${s.id}')" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                `).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-rose-500">Gagal memuat data toko.</td></tr>`;
            }
        }

        function openModalShop() {
            document.getElementById('form-shop').reset();
            document.getElementById('shop-id').value = '';
            openModal('modal-shop');
        }

        async function handleSaveShop(e) {
            e.preventDefault();
            const payload = {
                code: document.getElementById('shop-code').value,
                name: document.getElementById('shop-name').value,
                owner_name: document.getElementById('shop-owner').value,
                phone: document.getElementById('shop-phone').value,
                address: document.getElementById('shop-address').value,
            };

            const res = await fetchApi(`${API_URL}/shops`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (res.ok) {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Toko baru berhasil ditambahkan.', timer: 1200, showConfirmButton: false });
                closeModal('modal-shop');
                loadShops();
            } else {
                const data = await res.json();
                Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Terjadi kesalahan' });
            }
        }

        async function deleteShop(id) {
            const conf = await Swal.fire({ title: 'Hapus Toko?', text: 'Data toko akan dihapus.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus' });
            if (conf.isConfirmed) {
                await fetchApi(`${API_URL}/shops/${id}`, { method: 'DELETE' });
                loadShops();
            }
        }

        // 3. EMPLOYEES
        async function loadEmployees() {
            const tbody = document.getElementById('table-employees-body');
            tbody.innerHTML = `<tr><td colspan="7" class="p-8 text-center text-slate-400"><i class="fa-solid fa-spinner animate-spin"></i> Memuat karyawan...</td></tr>`;

            try {
                const res = await fetchApi(`${API_URL}/employees`);
                const json = await res.json();
                const emps = json.data || [];

                tbody.innerHTML = emps.map(e => `
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 font-bold text-slate-700">${e.nik}</td>
                        <td class="px-6 py-4 font-bold text-slate-900">${e.name}</td>
                        <td class="px-6 py-4 text-indigo-600 font-semibold">${e.position}</td>
                        <td class="px-6 py-4 font-mono text-slate-500">${e.user?.username || '-'}</td>
                        <td class="px-6 py-4">${e.phone || '-'}</td>
                        <td class="px-6 py-4"><span class="px-2 py-0.5 rounded text-[10px] font-bold ${e.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}">${e.is_active ? 'Aktif' : 'Non-Aktif'}</span></td>
                        <td class="px-6 py-4 text-right space-x-1">
                            <button onclick="deleteEmployee('${e.id}')" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                `).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="7" class="p-8 text-center text-rose-500">Gagal memuat data karyawan.</td></tr>`;
            }
        }

        function openModalEmployee() {
            document.getElementById('form-employee').reset();
            openModal('modal-employee');
        }

        async function handleSaveEmployee(e) {
            e.preventDefault();
            const payload = {
                nik: document.getElementById('emp-nik').value,
                name: document.getElementById('emp-name').value,
                position: document.getElementById('emp-position').value,
                username: document.getElementById('emp-username').value,
                password: document.getElementById('emp-password').value,
                phone: document.getElementById('emp-phone').value,
            };

            const res = await fetchApi(`${API_URL}/employees`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (res.ok) {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Karyawan dan akun login berhasil dibuat.', timer: 1200, showConfirmButton: false });
                closeModal('modal-employee');
                loadEmployees();
            } else {
                const data = await res.json();
                Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Periksa NIK atau Username yang mungkin sudah terdaftar.' });
            }
        }

        async function deleteEmployee(id) {
            const conf = await Swal.fire({ title: 'Hapus Karyawan?', text: 'Akun user dan data karyawan akan dihapus.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus' });
            if (conf.isConfirmed) {
                await fetchApi(`${API_URL}/employees/${id}`, { method: 'DELETE' });
                loadEmployees();
            }
        }

        // 4. SPK
        async function loadSpks() {
            const tbody = document.getElementById('table-spks-body');
            tbody.innerHTML = `<tr><td colspan="9" class="p-8 text-center text-slate-400"><i class="fa-solid fa-spinner animate-spin"></i> Memuat SPK...</td></tr>`;

            const role = getUserRole();
            let url = `${API_URL}/spks`;
            if (role === 'Owner') url = `${API_URL}/owner/spks`;
            if (role === 'Karyawan') url = `${API_URL}/karyawan/spks`;

            const btnCreateSpk = document.querySelector("button[onclick='openModalSpk()']");
            if (btnCreateSpk) {
                if (role === 'Admin') btnCreateSpk.classList.remove('hidden');
                else btnCreateSpk.classList.add('hidden');
            }

            try {
                const res = await fetchApi(url);
                const json = await res.json();
                const spks = json.data || [];

                if (spks.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="9" class="p-8 text-center text-slate-400">Belum ada data SPK.</td></tr>`;
                    return;
                }

                tbody.innerHTML = spks.map(s => `
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 font-bold text-indigo-600 font-mono">${s.spk_number}</td>
                        <td class="px-6 py-4">${s.date}</td>
                        <td class="px-6 py-4 font-bold text-slate-800">${s.shop?.name || '-'}</td>
                        <td class="px-6 py-4">${s.job_name}</td>
                        <td class="px-6 py-4 font-semibold text-slate-700">${s.employee?.name || '<span class="text-amber-500">Belum Ada</span>'}</td>
                        <td class="px-6 py-4 font-bold text-slate-900">${formatRupiah(s.spk_value)}</td>
                        <td class="px-6 py-4"><span class="px-2 py-0.5 rounded text-[10px] font-bold ${s.job_status === 'Selesai' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">${s.job_status}</span></td>
                        <td class="px-6 py-4"><span class="px-2 py-0.5 rounded text-[10px] font-bold ${s.payment_status === 'Sudah Dibayar' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}">${s.payment_status}</span></td>
                        <td class="px-6 py-4 text-right space-x-1">
                            ${role === 'Admin' ? `<button onclick="deleteSpk('${s.id}')" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg"><i class="fa-solid fa-trash"></i></button>` : '-'}
                        </td>
                    </tr>
                `).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="9" class="p-8 text-center text-rose-500">Gagal memuat SPK.</td></tr>`;
            }
        }

        async function openModalSpk() {
            document.getElementById('form-spk').reset();
            document.getElementById('spk-date').value = new Date().toISOString().split('T')[0];

            // Load Shops & Employees dropdowns
            const [shopRes, empRes] = await Promise.all([
                fetchApi(`${API_URL}/shops`),
                fetchApi(`${API_URL}/employees`)
            ]);
            const shops = (await shopRes.json()).data || [];
            const emps = (await empRes.json()).data || [];

            document.getElementById('spk-shop-id').innerHTML = shops.map(s => `<option value="${s.id}">${s.code} - ${s.name}</option>`).join('');
            document.getElementById('spk-employee-id').innerHTML = '<option value="">-- Tanpa Karyawan --</option>' + emps.map(e => `<option value="${e.id}">${e.nik} - ${e.name} (${e.position})</option>`).join('');

            openModal('modal-spk');
        }

        async function handleSaveSpk(e) {
            e.preventDefault();
            const payload = {
                date: document.getElementById('spk-date').value,
                shop_id: document.getElementById('spk-shop-id').value,
                employee_id: document.getElementById('spk-employee-id').value || null,
                job_name: document.getElementById('spk-job-name').value,
                spk_value: document.getElementById('spk-value').value,
                job_status: document.getElementById('spk-status').value,
                notes: document.getElementById('spk-notes').value,
            };

            const res = await fetchApi(`${API_URL}/spks`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (res.ok) {
                const data = await res.json();
                Swal.fire({ icon: 'success', title: 'SPK Diterbitkan!', text: `Nomor: ${data.data.spk_number}`, timer: 1500, showConfirmButton: false });
                closeModal('modal-spk');
                loadSpks();
            } else {
                const data = await res.json();
                Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Gagal membuat SPK' });
            }
        }

        async function deleteSpk(id) {
            const conf = await Swal.fire({ title: 'Hapus SPK?', text: 'SPK akan dihapus aman.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus' });
            if (conf.isConfirmed) {
                await fetchApi(`${API_URL}/spks/${id}`, { method: 'DELETE' });
                loadSpks();
            }
        }

        // 5. CASH ADVANCES
        async function loadCashAdvances() {
            const tbody = document.getElementById('table-cash-advances-body');
            tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-slate-400"><i class="fa-solid fa-spinner animate-spin"></i> Memuat data kasbon...</td></tr>`;

            const role = getUserRole();
            let url = `${API_URL}/cash-advances`;
            if (role === 'Karyawan') url = `${API_URL}/karyawan/cash-advances`;

            try {
                const res = await fetchApi(url);
                const json = await res.json();
                const list = json.data || [];

                tbody.innerHTML = list.map(c => `
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">${c.date}</td>
                        <td class="px-6 py-4 font-bold text-slate-800">${c.employee?.name || '-'}</td>
                        <td class="px-6 py-4 font-bold text-amber-600">${formatRupiah(c.amount)}</td>
                        <td class="px-6 py-4">${c.description || '-'}</td>
                        <td class="px-6 py-4 text-right space-x-1">
                            ${role === 'Admin' ? `<button onclick="deleteCashAdvance('${c.id}')" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg"><i class="fa-solid fa-trash"></i></button>` : '-'}
                        </td>
                    </tr>
                `).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-rose-500">Gagal memuat kasbon.</td></tr>`;
            }
        }

        async function openModalCashAdvance() {
            document.getElementById('form-cash-advance').reset();
            document.getElementById('ca-date').value = new Date().toISOString().split('T')[0];

            const empRes = await fetchApi(`${API_URL}/employees`);
            const emps = (await empRes.json()).data || [];
            document.getElementById('ca-employee-id').innerHTML = emps.map(e => `<option value="${e.id}">${e.nik} - ${e.name}</option>`).join('');

            openModal('modal-cash-advance');
        }

        async function handleSaveCashAdvance(e) {
            e.preventDefault();
            const payload = {
                employee_id: document.getElementById('ca-employee-id').value,
                date: document.getElementById('ca-date').value,
                amount: document.getElementById('ca-amount').value,
                description: document.getElementById('ca-desc').value,
            };

            const res = await fetchApi(`${API_URL}/cash-advances`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (res.ok) {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Kasbon dicatat.', timer: 1200, showConfirmButton: false });
                closeModal('modal-cash-advance');
                loadCashAdvances();
            } else {
                const data = await res.json();
                Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Gagal menyimpan kasbon.' });
            }
        }

        async function deleteCashAdvance(id) {
            const conf = await Swal.fire({ title: 'Hapus Kasbon?', text: 'Catatan kasbon akan dihapus.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus' });
            if (conf.isConfirmed) {
                await fetchApi(`${API_URL}/cash-advances/${id}`, { method: 'DELETE' });
                loadCashAdvances();
            }
        }

        // 6. PAYROLL
        async function loadPayrolls() {
            const tbody = document.getElementById('table-payrolls-body');
            tbody.innerHTML = `<tr><td colspan="11" class="p-8 text-center text-slate-400"><i class="fa-solid fa-spinner animate-spin"></i> Memuat slip gaji...</td></tr>`;

            const role = getUserRole();
            let url = `${API_URL}/payrolls`;
            if (role === 'Owner') url = `${API_URL}/owner/payrolls`;
            if (role === 'Karyawan') url = `${API_URL}/karyawan/payrolls`;

            try {
                const res = await fetchApi(url);
                const json = await res.json();
                const list = json.data || [];

                tbody.innerHTML = list.map(p => `
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 font-mono font-bold text-indigo-600">${p.slip_number}</td>
                        <td class="px-6 py-4 font-bold">${p.period}</td>
                        <td class="px-6 py-4 font-bold text-slate-900">${p.employee?.name || '-'}</td>
                        <td class="px-6 py-4 font-mono text-slate-500">${p.spk?.spk_number || '-'}</td>
                        <td class="px-6 py-4">${formatRupiah(p.original_spk_value)}</td>
                        <td class="px-6 py-4 font-bold text-indigo-600">${formatRupiah(p.gross_salary)}</td>
                        <td class="px-6 py-4 text-rose-500 font-medium">-${formatRupiah(p.meal_allowance)}</td>
                        <td class="px-6 py-4 text-rose-500 font-medium">-${formatRupiah(p.cash_advance_deduction)}</td>
                        <td class="px-6 py-4 font-black text-emerald-600 text-sm">${formatRupiah(p.net_salary)}</td>
                        <td class="px-6 py-4"><span class="px-2 py-0.5 rounded text-[10px] font-bold ${p.payment_status === 'Sudah Dibayar' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">${p.payment_status}</span></td>
                        <td class="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                            <button onclick="viewSlip('${p.id}')" title="Cetak Slip Gaji" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold"><i class="fa-solid fa-print"></i></button>
                            ${role === 'Admin' && p.payment_status !== 'Sudah Dibayar' ? `<button onclick="confirmPayment('${p.id}')" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold">Bayar</button>` : ''}
                            ${role === 'Admin' ? `<button onclick="deletePayroll('${p.id}')" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg"><i class="fa-solid fa-trash"></i></button>` : ''}
                        </td>
                    </tr>
                `).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="11" class="p-8 text-center text-rose-500">Gagal memuat slip gaji.</td></tr>`;
            }
        }

        async function openModalCreatePayroll() {
            document.getElementById('form-payroll').reset();
            document.getElementById('pr-period').value = new Date().toISOString().slice(0, 7);

            // Load available SPKs that have an employee
            const res = await fetchApi(`${API_URL}/spks`);
            const spks = (await res.json()).data || [];

            const validSpks = spks.filter(s => s.employee);
            if (validSpks.length === 0) {
                Swal.fire({ icon: 'info', title: 'Belum Ada SPK', text: 'Silakan buat SPK dan tugaskan teknisi terlebih dahulu.' });
                return;
            }

            document.getElementById('pr-spk-id').innerHTML = validSpks.map(s => `
                <option value="${s.id}">${s.spk_number} - ${s.job_name} (${s.employee.name} | ${formatRupiah(s.spk_value)})</option>
            `).join('');

            openModal('modal-create-payroll');
            triggerPayrollPreview();
        }

        async function triggerPayrollPreview() {
            const spkId = document.getElementById('pr-spk-id').value;
            if (!spkId) return;

            const salaryPercentage = document.getElementById('pr-salary-percentage').value || 22;
            const mealAllowance = document.getElementById('pr-meal-allowance').value || 0;
            const cashAdvanceDeduction = document.getElementById('pr-cash-advance-deduction').value;

            const payload = {
                spk_id: spkId,
                salary_percentage: salaryPercentage,
                meal_allowance: mealAllowance,
            };
            if (cashAdvanceDeduction !== '') {
                payload.cash_advance_deduction = cashAdvanceDeduction;
            }

            try {
                const res = await fetchApi(`${API_URL}/payrolls/preview`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.success) {
                    const d = json.data;
                    document.getElementById('prev-spk-val').innerText = formatRupiah(d.original_spk_value);
                    document.getElementById('prev-ppn-val').innerText = '- ' + formatRupiah(d.ppn_amount);
                    document.getElementById('prev-net-spk').innerText = formatRupiah(d.net_spk_value);
                    document.getElementById('prev-gross-salary').innerText = formatRupiah(d.gross_salary);
                    document.getElementById('prev-meal').innerText = '- ' + formatRupiah(d.meal_allowance);
                    document.getElementById('prev-rem-cash').innerText = formatRupiah(d.remaining_cash_advance);
                    document.getElementById('prev-deduct-cash').innerText = '- ' + formatRupiah(d.cash_advance_deduction);
                    document.getElementById('prev-net-salary').innerText = formatRupiah(d.net_salary);

                    // Auto-fill deduction input if blank
                    if (document.getElementById('pr-cash-advance-deduction').value === '') {
                        document.getElementById('pr-cash-advance-deduction').value = d.cash_advance_deduction;
                    }
                }
            } catch (err) {}
        }

        async function handleSavePayroll(e) {
            e.preventDefault();
            const payload = {
                spk_id: document.getElementById('pr-spk-id').value,
                period: document.getElementById('pr-period').value,
                salary_percentage: document.getElementById('pr-salary-percentage').value,
                meal_allowance: document.getElementById('pr-meal-allowance').value,
                cash_advance_deduction: document.getElementById('pr-cash-advance-deduction').value,
            };

            const res = await fetchApi(`${API_URL}/payrolls`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (res.ok) {
                Swal.fire({ icon: 'success', title: 'Slip Gaji Diterbitkan!', text: `Nomor: ${data.data.slip_number}`, timer: 1500, showConfirmButton: false });
                closeModal('modal-create-payroll');
                loadPayrolls();
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Gagal menerbitkan slip gaji.' });
            }
        }

        async function confirmPayment(id) {
            const conf = await Swal.fire({
                title: 'Konfirmasi Pelunasan?',
                text: 'Status gaji akan diubah menjadi Sudah Dibayar dan SPK terkait akan dilunasi.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Tandai Sudah Dibayar'
            });

            if (conf.isConfirmed) {
                await fetchApi(`${API_URL}/payrolls/${id}/payment`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ payment_status: 'Sudah Dibayar' })
                });
                Swal.fire({ icon: 'success', title: 'Berhasil Dilunasi!', timer: 1200, showConfirmButton: false });
                loadPayrolls();
            }
        }

        async function deletePayroll(id) {
            const conf = await Swal.fire({ title: 'Hapus Slip Gaji?', text: 'Slip gaji akan dihapus.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus' });
            if (conf.isConfirmed) {
                await fetchApi(`${API_URL}/payrolls/${id}`, { method: 'DELETE' });
                loadPayrolls();
            }
        }

        // View Slip Gaji Resmi (Print Template)
        async function viewSlip(id) {
            const res = await fetchApi(`${API_URL}/payrolls/${id}`);
            const json = await res.json();
            const p = json.data;

            document.getElementById('printable-slip').innerHTML = `
                <div class="text-center border-b-2 border-dashed border-slate-300 pb-3">
                    <h2 class="text-base font-black tracking-wider uppercase">MANN PLUS ADVERTISING</h2>
                    <p class="text-[10px] text-slate-500">Jl. Operasional Bengkel Advertising, Indonesia</p>
                    <p class="text-[11px] font-bold mt-1 uppercase text-indigo-700">SLIP GAJI KARYAWAN</p>
                </div>

                <div class="grid grid-cols-2 gap-2 text-[11px]">
                    <div>No. Slip : <span class="font-bold">${p.slip_number}</span></div>
                    <div class="text-right">Periode : <span class="font-bold">${p.period}</span></div>
                    <div>Nama     : <span class="font-bold">${p.employee?.name}</span></div>
                    <div class="text-right">NIK : ${p.employee?.nik}</div>
                    <div>Jabatan  : ${p.employee?.position}</div>
                    <div class="text-right">Status: <span class="font-bold text-emerald-600">${p.payment_status}</span></div>
                </div>

                <div class="border-t border-b border-dashed border-slate-300 py-2 space-y-1 text-[11px]">
                    <div class="font-bold text-slate-800">SPK : ${p.spk?.spk_number} (${p.spk?.job_name})</div>
                    <div class="text-slate-500">Toko: ${p.spk?.shop?.name}</div>
                    <div class="flex justify-between"><span>Nilai SPK Asli:</span> <span>${formatRupiah(p.original_spk_value)}</span></div>
                    <div class="flex justify-between text-slate-500"><span>Potongan PPN (3%):</span> <span>- ${formatRupiah(p.ppn_amount)}</span></div>
                    <div class="flex justify-between font-bold"><span>Nilai Bersih SPK:</span> <span>${formatRupiah(p.net_spk_value)}</span></div>
                    <div class="flex justify-between text-indigo-700 font-bold"><span>Hak Komisi Gaji (${p.salary_percentage}%):</span> <span>${formatRupiah(p.gross_salary)}</span></div>
                    <div class="flex justify-between text-rose-600"><span>Potongan Uang Makan:</span> <span>- ${formatRupiah(p.meal_allowance)}</span></div>
                    <div class="flex justify-between text-rose-600"><span>Potongan Kasbon:</span> <span>- ${formatRupiah(p.cash_advance_deduction)}</span></div>
                </div>

                <div class="flex justify-between items-center text-sm font-black border-b-2 border-dashed border-slate-300 pb-2">
                    <span>TOTAL GAJI DITERIMA:</span>
                    <span class="text-emerald-700">${formatRupiah(p.net_salary)}</span>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4 text-center text-[10px]">
                    <div>
                        <p class="text-slate-400">Penerima,</p>
                        <div class="h-12"></div>
                        <p class="font-bold">(${p.employee?.name})</p>
                    </div>
                    <div>
                        <p class="text-slate-400">Kasir / Admin,</p>
                        <div class="h-12"></div>
                        <p class="font-bold">(Administrator)</p>
                    </div>
                </div>
            `;
            openModal('modal-view-slip');
        }

        // 7. REPORTS
        async function switchReportSubTab(type) {
            ['spk', 'payroll', 'cash-advance'].forEach(t => {
                const btn = document.getElementById(`subtab-btn-${t}`);
                if (btn) {
                    btn.classList.remove('bg-indigo-600', 'text-white');
                    btn.classList.add('text-slate-600');
                }
            });
            const activeBtn = document.getElementById(`subtab-btn-${type}`);
            if (activeBtn) {
                activeBtn.classList.add('bg-indigo-600', 'text-white');
                activeBtn.classList.remove('text-slate-600');
            }

            const container = document.getElementById('report-content-container');
            container.innerHTML = `<div class="p-8 text-center text-slate-400"><i class="fa-solid fa-spinner animate-spin"></i> Memuat laporan...</div>`;

            try {
                if (type === 'spk') {
                    const res = await fetchApi(`${API_URL}/reports/spk`);
                    const json = await res.json();
                    const r = json.report;

                    container.innerHTML = `
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-4">
                            <div class="p-4 rounded-xl bg-slate-50 border"><p class="text-slate-400 text-xs">Total SPK</p><h4 class="text-xl font-bold">${r.summary.total_spk}</h4></div>
                            <div class="p-4 rounded-xl bg-slate-50 border"><p class="text-slate-400 text-xs">Total Omset Kotor</p><h4 class="text-xl font-bold text-slate-900">${formatRupiah(r.summary.total_gross_spk_value)}</h4></div>
                            <div class="p-4 rounded-xl bg-slate-50 border"><p class="text-slate-400 text-xs">Total Omset Bersih (Net)</p><h4 class="text-xl font-bold text-emerald-600">${formatRupiah(r.summary.total_net_spk_value)}</h4></div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 font-bold"><tr><th class="px-4 py-2">No SPK</th><th class="px-4 py-2">Toko</th><th class="px-4 py-2">Teknisi</th><th class="px-4 py-2">Nilai SPK</th><th class="px-4 py-2">Status</th></tr></thead>
                                <tbody class="divide-y">${(r.data || []).map(i => `<tr class="hover:bg-slate-50"><td class="px-4 py-2 font-mono font-bold">${i.spk_number}</td><td class="px-4 py-2">${i.shop?.name || '-'}</td><td class="px-4 py-2">${i.employee?.name || '-'}</td><td class="px-4 py-2 font-bold">${formatRupiah(i.spk_value)}</td><td class="px-4 py-2">${i.job_status}</td></tr>`).join('')}</tbody>
                            </table>
                        </div>
                    `;
                } else if (type === 'payroll') {
                    const res = await fetchApi(`${API_URL}/reports/payroll`);
                    const json = await res.json();
                    const r = json.report;

                    container.innerHTML = `
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-4">
                            <div class="p-4 rounded-xl bg-slate-50 border"><p class="text-slate-400 text-xs">Total Slip</p><h4 class="text-xl font-bold">${r.summary.total_slips}</h4></div>
                            <div class="p-4 rounded-xl bg-slate-50 border"><p class="text-slate-400 text-xs">Kasbon Terpotong</p><h4 class="text-xl font-bold text-rose-500">${formatRupiah(r.summary.total_cash_advance_deducted)}</h4></div>
                            <div class="p-4 rounded-xl bg-slate-50 border"><p class="text-slate-400 text-xs">Total Gaji Bersih</p><h4 class="text-xl font-bold text-emerald-600">${formatRupiah(r.summary.total_net_salary)}</h4></div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 font-bold"><tr><th class="px-4 py-2">No Slip</th><th class="px-4 py-2">Periode</th><th class="px-4 py-2">Karyawan</th><th class="px-4 py-2">Gaji Bersih</th><th class="px-4 py-2">Status</th></tr></thead>
                                <tbody class="divide-y">${(r.data || []).map(i => `<tr class="hover:bg-slate-50"><td class="px-4 py-2 font-mono font-bold">${i.slip_number}</td><td class="px-4 py-2">${i.period}</td><td class="px-4 py-2 font-bold">${i.employee?.name || '-'}</td><td class="px-4 py-2 font-bold text-emerald-600">${formatRupiah(i.net_salary)}</td><td class="px-4 py-2">${i.payment_status}</td></tr>`).join('')}</tbody>
                            </table>
                        </div>
                    `;
                } else if (type === 'cash-advance') {
                    const res = await fetchApi(`${API_URL}/reports/cash-advances`);
                    const json = await res.json();
                    const r = json.report;

                    container.innerHTML = `
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 mb-4">
                            <div class="p-4 rounded-xl bg-slate-50 border"><p class="text-slate-400 text-xs">Total Catatan Kasbon</p><h4 class="text-xl font-bold">${r.summary.total_records}</h4></div>
                            <div class="p-4 rounded-xl bg-slate-50 border"><p class="text-slate-400 text-xs">Total Nominal Pinjaman</p><h4 class="text-xl font-bold text-amber-600">${formatRupiah(r.summary.total_amount)}</h4></div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 font-bold"><tr><th class="px-4 py-2">Tanggal</th><th class="px-4 py-2">Karyawan</th><th class="px-4 py-2">Nominal</th><th class="px-4 py-2">Keterangan</th></tr></thead>
                                <tbody class="divide-y">${(r.data || []).map(i => `<tr class="hover:bg-slate-50"><td class="px-4 py-2">${i.date}</td><td class="px-4 py-2 font-bold">${i.employee?.name || '-'}</td><td class="px-4 py-2 font-bold text-amber-600">${formatRupiah(i.amount)}</td><td class="px-4 py-2">${i.description || '-'}</td></tr>`).join('')}</tbody>
                            </table>
                        </div>
                    `;
                }
            } catch (err) {
                container.innerHTML = `<div class="p-8 text-center text-rose-500 font-bold">Gagal memuat laporan. Silakan refresh halaman.</div>`;
            }
        }
    </script>
</body>
</html>
