<?php require_once('includes/init.php'); ?>
<?php cek_login($role = array(1)); ?>

<?php
$ada_error = false;
$result = '';

$id_penilaian = (isset($_GET['id'])) ? trim($_GET['id']) : ''; // akan menghapus spasi ekstra dari nilai yang diteruskan. Ini memastikan bahwa tidak ada spasi ekstra yang mengganggu ketika menggunakan nilai tersebut.

if(!$id_penilaian) {
	$ada_error = 'Maaf, data tidak dapat diproses.';
} else {
	$query = mysqli_query($koneksi,"SELECT * FROM penilaian WHERE id_alternatif = '$id_penilaian'");
	$cek = mysqli_num_rows($query);//menghitung jumlah baris yang terpengaruh oleh kueri 
	
	if($cek <= 0) { //Jika tidak ada baris yang terpengaruh (jumlah baris kurang dari atau sama dengan 0
		$ada_error = 'Maaf, data tidak dapat diproses.';
	} else {
		mysqli_query($koneksi,"DELETE FROM penilaian WHERE id_alternatif = '$id_penilaian';");
		redirect_to('list-penilaian.php?status=sukses-hapus');
	}
}
?>

<?php
$page = "Sub Kriteria";
require_once('template/header.php');
?>
	<?php if($ada_error): ?>
		<?php echo '<div class="alert alert-danger">'.$ada_error.'</div>'; ?>	
	<?php endif; ?>
<?php
require_once('template/footer.php');
?>
