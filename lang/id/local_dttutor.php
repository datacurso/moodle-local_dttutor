<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Indonesian language strings for Tutor-IA plugin.
 *
 * @package    local_dttutor
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['avatar'] = 'Avatar Tutor AI';
$string['avatar_desc'] = 'Pilih avatar untuk ditampilkan pada tombol obrolan mengambang Tutor AI. Jika tidak ada yang dipilih atau file tidak ada, Avatar 1 akan digunakan secara default.';
$string['avatar_position'] = 'Posisi avatar';
$string['avatar_position_desc'] = 'Konfigurasikan di mana tombol avatar mengambang Tutor AI akan ditampilkan. Pilih posisi sudut prasetel atau sesuaikan koordinat X,Y yang tepat. Pratinjau langsung menunjukkan bagaimana itu akan terlihat.';
$string['cachedef_course_knowledge'] = 'Cache pengetahuan kursus yang dimuat sebelumnya yang digunakan oleh obrolan';
$string['cachedef_sessions'] = 'Cache untuk pengenal sesi obrolan Tutor AI';
$string['char'] = 'karakter';
$string['chars'] = 'karakter';
$string['clear_selection'] = 'Hapus pilihan';
$string['close'] = 'Tutup Tutor AI';
$string['ctx_loc_activity'] = 'Lokasi: Aktivitas';
$string['ctx_loc_admin'] = 'Lokasi: Administrasi';
$string['ctx_loc_calendar'] = 'Lokasi: Kalender';
$string['ctx_loc_course'] = 'Lokasi: Kursus';
$string['ctx_loc_dashboard'] = 'Lokasi: Dasbor';
$string['ctx_loc_files'] = 'Lokasi: Berkas';
$string['ctx_loc_gradebook'] = 'Lokasi: Buku Nilai';
$string['ctx_loc_messages'] = 'Lokasi: Pesan';
$string['ctx_loc_profile'] = 'Lokasi: Profil';
$string['custom_prompt'] = 'Prompt kustom';
$string['custom_prompt_desc'] = 'Instruksi kustom untuk mengontrol perilaku tutor AI. Gunakan bidang ini untuk memberikan panduan khusus, nada, atau batasan pengetahuan untuk tutor.';
$string['customavatar'] = 'Avatar kustom';
$string['customavatar_desc'] = 'Unggah gambar avatar kustom Anda sendiri. Ini akan menimpa avatar prasetel yang dipilih.';
$string['customavatar_dimensions'] = 'Dimensi yang disarankan: 200x200 piksel. Format yang didukung: PNG, JPG, JPEG, SVG. Ukuran file maksimal: 512KB.';
$string['drawer_side'] = 'Sisi pembukaan laci';
$string['drawer_side_help'] = 'Pilih dari sisi mana laci obrolan akan terbuka. Ini tidak tergantung pada posisi tombol avatar.';
$string['drawer_side_left'] = 'Buka dari kiri';
$string['drawer_side_right'] = 'Buka dari kanan';
$string['dttutor:use'] = 'Gunakan Tutor AI';
$string['edit_cancel'] = 'Batal';
$string['edit_message'] = 'Edit pesan';
$string['edit_save'] = 'Simpan';
$string['enable_tutor_for_course'] = 'Aktifkan Tutor AI untuk kursus ini';
$string['enable_tutor_for_course_help'] = 'Saat diaktifkan, Tutor AI akan tersedia untuk siswa dan guru di kursus ini. Pengaturan plugin global juga harus diaktifkan.';
$string['enabled'] = 'Aktifkan Obrolan';
$string['enabled_desc'] = 'Aktifkan atau nonaktifkan obrolan Tutor AI secara global';
$string['error_api_not_configured'] = 'Konfigurasi API hilang. Silakan periksa pengaturan Anda.';
$string['error_attempt_later'] = 'Terjadi kesalahan. Silakan coba lagi nanti.';
$string['error_history_unavailable'] = 'Percakapan sebelumnya tidak dapat dimuat. Anda dapat terus mengobrol.';
$string['error_insufficient_tokens'] = 'Tidak ada cukup kredit AI yang tersedia untuk memproses permintaan Anda. Silakan hubungi administrator Anda untuk menambahkan lebih banyak kredit untuk terus menggunakan Tutor AI.';
$string['error_insufficient_tokens_short'] = 'Kredit Tidak Cukup';
$string['error_internal'] = 'Kesalahan internal: {$a}';
$string['error_invalid_coordinates'] = 'Koordinat tidak valid. Silakan gunakan nilai CSS yang valid (misalnya, 10px, 2rem, 50%)';
$string['error_invalid_message'] = 'Silakan masukkan pesan yang valid';
$string['error_invalid_position'] = 'Data posisi tidak valid';
$string['error_license_fallback'] = 'Kesalahan lisensi: {$a}';
$string['error_license_fallback_short'] = 'Kesalahan Lisensi';
$string['error_license_not_allowed'] = 'Lisensi Anda tidak mengizinkan akses ke layanan Tutor AI. Silakan hubungi administrator Anda untuk memverifikasi status lisensi Anda atau tingkatkan paket Anda.';
$string['error_license_not_allowed_short'] = 'Kesalahan Lisensi';
$string['error_message_too_long'] = '[Kesalahan] Pesan terlalu panjang. Maksimal 4000 karakter.';
$string['error_no_credits_fallback'] = 'Kredit tidak cukup: {$a}';
$string['error_ratelimit_exceeded'] = 'Batas penggunaan yang diizinkan telah terlampaui. Silakan coba lagi pada {$a}.';
$string['error_tutor_not_available'] = 'Tutor AI tidak tersedia untuk kursus ini.';
$string['error_unexpected'] = 'Terjadi kesalahan yang tidak terduga. Silakan coba lagi.';
$string['error_unknown'] = 'Terjadi kesalahan yang tidak diketahui. Silakan coba lagi.';
$string['include_grades'] = 'Kirim nilai siswa ke tutor AI';
$string['include_grades_desc'] = 'Jika diaktifkan, nilai siswa yang bersangkutan dalam kursus ditambahkan ke konteks yang dikirim ke layanan Datacurso AI agar tutor dapat menjawab pertanyaan tentangnya. Dinonaktifkan secara bawaan untuk meminimalkan data pribadi yang ditransfer.';
$string['line'] = 'baris';
$string['lines'] = 'baris';
$string['loading'] = 'Memuat...';
$string['manage_tutor'] = 'Manajemen Tutor AI';
$string['open'] = 'Buka Tutor AI';
$string['pluginname'] = 'Tutor AI';
$string['position_custom'] = 'Posisi kustom';
$string['position_left'] = 'Sudut kiri bawah';
$string['position_preset'] = 'Posisi prasetel';
$string['position_right'] = 'Sudut kanan bawah';
$string['position_x'] = 'Posisi horizontal (X)';
$string['position_x_help'] = 'Jarak dari tepi kiri. Contoh: 2rem, 20px, 5%. Gunakan nilai negatif untuk memposisikan dari tepi kanan.';
$string['position_y'] = 'Posisi vertikal (Y)';
$string['position_y_help'] = 'Jarak dari tepi bawah. Contoh: 6rem, 80px, 10%. Gunakan nilai negatif untuk memposisikan dari tepi atas.';
$string['positiondisplay_corner'] = 'Posisi: sudut {$a->preset} | Laci: {$a->drawer}';
$string['positiondisplay_custom'] = 'Posisi: X: {$a->x}, Y: {$a->y} | Laci: {$a->drawer}';
$string['preview'] = 'Pratinjau Langsung';
$string['privacy:export:course_config'] = 'Konfigurasi tutor kursus';
$string['privacy:export:sessions'] = 'Sesi obrolan';
$string['privacy:metadata:datacurso_ai'] = 'Pesan obrolan dan konteks kursus dikirim ke layanan Datacurso AI untuk menghasilkan jawaban tutor.';
$string['privacy:metadata:datacurso_ai:cmid'] = 'ID modul kursus yang dilihat pengguna saat menulis pesan.';
$string['privacy:metadata:datacurso_ai:course_structure'] = 'Struktur kursus yang terlihat oleh pengguna (aktivitas, bagian, tanggal, dan nilai maksimum).';
$string['privacy:metadata:datacurso_ai:custom_prompt'] = 'Instruksi khusus institusi yang dikonfigurasi oleh administrator.';
$string['privacy:metadata:datacurso_ai:grades'] = 'Nilai pengguna dalam kursus, hanya jika pengaturan "Kirim nilai siswa" diaktifkan.';
$string['privacy:metadata:datacurso_ai:lang'] = 'Bahasa pengguna saat ini.';
$string['privacy:metadata:datacurso_ai:messages'] = 'Pesan obrolan yang ditulis pengguna dan jawaban tutor sebelumnya.';
$string['privacy:metadata:datacurso_ai:page_url'] = 'URL halaman Moodle tempat pesan dikirim.';
$string['privacy:metadata:datacurso_ai:selected_text'] = 'Teks yang dipilih pengguna di halaman untuk ditanyakan.';
$string['privacy:metadata:datacurso_ai:site_id'] = 'Pengenal anonim situs Moodle ini.';
$string['privacy:metadata:datacurso_ai:site_url'] = 'URL situs Moodle ini.';
$string['privacy:metadata:datacurso_ai:timezone'] = 'Zona waktu pengguna.';
$string['privacy:metadata:datacurso_ai:userid'] = 'ID pengguna yang mengirim pesan.';
$string['privacy:metadata:local_dttutor_course_config'] = 'Konfigurasi tutor AI per kursus.';
$string['privacy:metadata:local_dttutor_course_config:timemodified'] = 'Waktu terakhir konfigurasi diubah.';
$string['privacy:metadata:local_dttutor_course_config:usermodified'] = 'ID pengguna yang terakhir mengubah konfigurasi.';
$string['privacy:metadata:local_dttutor_session'] = 'Pengenal sesi obrolan yang dibuka pengguna dengan tutor Datacurso AI, disimpan agar dapat dihapus dari jarak jauh.';
$string['privacy:metadata:local_dttutor_session:cmid'] = 'ID modul kursus tempat sesi berada (0 untuk seluruh kursus).';
$string['privacy:metadata:local_dttutor_session:courseid'] = 'ID kursus tempat sesi berada.';
$string['privacy:metadata:local_dttutor_session:remotesessionid'] = 'Pengenal sesi di layanan Datacurso AI.';
$string['privacy:metadata:local_dttutor_session:timecreated'] = 'Waktu sesi dibuat.';
$string['privacy:metadata:local_dttutor_session:timemodified'] = 'Waktu terakhir sesi diperbarui.';
$string['privacy:metadata:local_dttutor_session:userid'] = 'ID pengguna pemilik sesi.';
$string['ref_bottom'] = 'Bawah';
$string['ref_left'] = 'Kiri';
$string['ref_right'] = 'Kanan';
$string['ref_top'] = 'Atas';
$string['reference_edge_x'] = 'Tepi referensi horizontal';
$string['reference_edge_y'] = 'Tepi referensi vertikal';
$string['selected'] = 'dipilih';
$string['sendmessage'] = 'Kirim pesan';
$string['student'] = 'Siswa';
$string['teacher'] = 'Guru';
$string['tutor_disabled_notice'] = 'Tutor AI saat ini dinonaktifkan untuk kursus ini. Siswa tidak akan melihat antarmuka obrolan.';
$string['tutor_status'] = 'Status Tutor AI';
$string['tutorcustomization'] = 'Kustomisasi Tutor';
$string['tutorname_default'] = 'Tutor AI';
$string['tutorname_setting'] = 'Nama tutor';
$string['tutorname_setting_desc'] = 'Konfigurasikan nama yang akan ditampilkan di header obrolan. Anda dapat menggunakan {teachername} untuk menampilkan nama guru yang sebenarnya dari kursus, atau memasukkan nama kustom. Contoh: "{teachername}" akan menampilkan "John Doe", "Asisten AI" akan menampilkan "Asisten AI".';
$string['typemessage'] = 'Ketik pesan Anda...';
$string['welcomemessage_default'] = 'Halo! Saya {teachername}, asisten AI Anda. Bagaimana saya bisa membantu Anda hari ini?';
$string['welcomemessage_setting'] = 'Pesan sambutan';
$string['welcomemessage_setting_desc'] = 'Sesuaikan pesan sambutan yang ditampilkan saat obrolan dibuka. Anda dapat menggunakan penampung: {teachername}, {coursename}, {username}, {firstname}';
$string['yesterday'] = 'Kemarin';
