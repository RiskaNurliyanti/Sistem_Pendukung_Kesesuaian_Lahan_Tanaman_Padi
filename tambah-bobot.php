<?php 
require_once('includes/init.php'); ?>
<?php cek_login($role = array(1)); ?>

<?php
$page = "Kriteria";
require_once('template/header.php');
?>

<?php

//query kriteria
$kriteria = array();
$query = mysqli_query($koneksi,"SELECT * FROM kriteria ORDER BY kode_kriteria ASC");
while($krit = mysqli_fetch_array($query)){
	$kriteria[$krit['id_kriteria']]['id_kriteria'] = $krit['id_kriteria'];
	$kriteria[$krit['id_kriteria']]['kode_kriteria'] = $krit['kode_kriteria'];
	$kriteria[$krit['id_kriteria']]['nama'] = $krit['nama'];
}

//query simpan
if (isset($_POST['save'])) {	
	mysqli_query($koneksi,"TRUNCATE TABLE kriteria_ahp");
	
	$i = 0;
	foreach ($kriteria as $row1) {
		$ii = 0;
		foreach ($kriteria as $row2) {
			//perbandingan sekali antar kriteria
			if ($i < $ii) {
				$nilai_input = $_POST["nilai_" . $row1['id_kriteria'] . "_" . $row2['id_kriteria']];//mengambil nilai perbandingan antar kriteria yang telah dipilih oleh pengguna dari formulir
				error_log($nilai_input, 3, 'log_file.txt');
				$nilai_1 = 0;
				$nilai_2 = 0;
				if ($nilai_input < 1) {
					$nilai_1 = abs($nilai_input);
					$nilai_2= 1/abs($nilai_input);
					//$nilai_2 = number_format(1 / abs($nilai_input), 7));
					// $nilai_2 = bcdiv('1', abs($nilai_input), 7);
				} elseif ($nilai_input > 1) {
					$nilai_1= 1/abs($nilai_input);
					//$nilai_1 = number_format(1 / abs($nilai_input),7);
					// $nilai_1 = bcdiv('1', abs($nilai_input), 7);
					$nilai_2 = abs($nilai_input);
				} elseif ($nilai_input == 1) {
					$nilai_1 = 1;
					$nilai_2 = 1;
				}
				
				mysqli_query($koneksi,"INSERT INTO kriteria_ahp (id_kriteria_1, id_kriteria_2, nilai_1, nilai_2) VALUES ('$row1[id_kriteria]', '$row2[id_kriteria]', '$nilai_1', '$nilai_2')");
			}
			$ii++;
		}
		$i++;
	}
	echo "<meta content='0; url=tambah-bobot.php?status=sukses-baru' http-equiv='refresh'>";
}

//cek konsistensi
if (isset($_POST['check'])) {
	if (mysqli_num_rows($query) < 3) {					
		echo "<meta content='0; url=tambah-bobot.php?status=gagal-min' http-equiv='refresh'>";
	} else {
		$id_kriterias = array();
		foreach ($kriteria as $row) {
			$id_kriterias[] = $row['id_kriteria'];
		}
	}

	// perhitungan metode AHP
	$matrik_kriteria = ahp_get_matrik_kriteria($id_kriterias);
	$jumlah_kolom = ahp_get_jumlah_kolom($matrik_kriteria);	
	$matrik_normalisasi = ahp_get_normalisasi($matrik_kriteria, $jumlah_kolom);
	$prioritas = ahp_get_prioritas($matrik_normalisasi);
	$matrik_baris = ahp_get_matrik_baris($prioritas, $matrik_kriteria);
	$jumlah_matrik_baris = ahp_get_jumlah_matrik_baris($matrik_baris);
	$hasil_tabel_konsistensi = ahp_get_tabel_konsistensi($jumlah_matrik_baris, $prioritas);

	//update nilai bobot
	if (ahp_uji_konsistensi($hasil_tabel_konsistensi)) {
		//echo "<meta content='0; url=tambah-bobot.php?status=sukses-konsisten' http-equiv='refresh'>";
		$i = 0;
		foreach ($kriteria as $row) {
			$bobot = $prioritas[$i++];
			$id_kriteria = $row['id_kriteria'];
			mysqli_query($koneksi,"UPDATE kriteria SET bobot = '$bobot' WHERE id_kriteria = '$id_kriteria'");
		}
		
		$list_data = tampil_data_1($matrik_kriteria, $jumlah_kolom);
		$list_data2 = tampil_data_2($matrik_normalisasi, $prioritas);
		$list_data3 = tampil_data_3($matrik_baris, $jumlah_matrik_baris);
		$list_data4 = tampil_data_4($jumlah_matrik_baris, $prioritas, $hasil_tabel_konsistensi);
		$list_data5 = tampil_data_5($jumlah_matrik_baris, $prioritas, $hasil_tabel_konsistensi);
	} else {
		echo "<meta content='0; url=tambah-bobot.php?status=gagal-konsisten' http-equiv='refresh'>";
	}
}


//query nilai 
$result = array();
$i = 0;
foreach ($kriteria as $row1) {
	$ii = 0;
	foreach ($kriteria as $row2) {
		//perbandingan sekali antar kriteria
		if ($i < $ii) {			
			$query1 = mysqli_query($koneksi,"SELECT * FROM kriteria_ahp WHERE id_kriteria_1 = '$row1[id_kriteria]' AND id_kriteria_2 = '$row2[id_kriteria]';");
			$kriteria_ahp = mysqli_fetch_array($query1);
			
			
			if (empty($kriteria_ahp)) {
				mysqli_query($koneksi,"INSERT INTO kriteria_ahp (id_kriteria_1, id_kriteria_2, nilai_1, nilai_2) VALUES ('$row1[id_kriteria]', '$row2[id_kriteria]', '1', '1')");
				
				$nilai_1 = 1;
				$nilai_2 = 1;
			} else {
				$nilai_1 = $kriteria_ahp['nilai_1'];
				$nilai_2 = $kriteria_ahp['nilai_2'];
			}
			$nilai = 0;
			if ($nilai_1 < 1) {
				$nilai = $nilai_2; //nilai 1 = 0,3333 maka nilai_2 = 3
			} elseif ($nilai_1 > 1) {
				$nilai = -$nilai_1;
			} elseif ($nilai_1 == 1) {
				$nilai = 1;
			}
			$result[$row1['id_kriteria']][$row2['id_kriteria']] = $nilai;
		}
		$ii++;
	}
	$i++;
}
$kriteria_ahp = $result;


function ahp_get_matrik_kriteria($id_kriterias)
{		
	$matrik = array();
	$i = 0;
	foreach ($id_kriterias as $row1) {
		$ii = 0;
		foreach ($id_kriterias as $row2) {
			if ($i == $ii) {
				$matrik[$i][$ii] = 1;
			} else {
				if ($i < $ii) {
					include ('includes/konek-db.php');
					$sqledit = mysqli_query($koneksi,"SELECT * FROM kriteria_ahp WHERE id_kriteria_1='$row1' AND id_kriteria_2='$row2';");
					$kriteria_ahp=mysqli_fetch_array($sqledit);
					
					if (empty($kriteria_ahp)) {
						$matrik[$i][$ii] = 1;
						$matrik[$ii][$i] = 1;
					} else {
						$matrik[$i][$ii] = $kriteria_ahp['nilai_1'];//contoh nilai baris 1 kolom 2.
						$matrik[$ii][$i] = $kriteria_ahp['nilai_2']; //baris 2 kolom 1.
					}
				}
			}
			$ii++;
		}
		$i++;
	}
	return $matrik;
}

function ahp_get_jumlah_kolom($matrik)
{
	$jumlah_kolom = array();
	for ($i = 0; $i < count($matrik); $i++) { //iterasi setiap kolom
		$jumlah_kolom[$i] = 0;
		for ($ii = 0; $ii < count($matrik); $ii++) { //iterasi baris
			$jumlah_kolom[$i] = $jumlah_kolom[$i]+$matrik[$ii][$i]; //Di setiap iterasi, nilai dari baris matriks ditambahkan ke nilai yang sudah ada untuk kolom yang bersangkutan. jumlah kolom baris + baris 1 dan seterusnya kolom 1
			// $jumlah_kolom[$i] = number_format($jumlah_kolom[$i] + $matrik[$ii][$i], 7);
			//$jumlah_kolom[$i]= bcadd($jumlah_kolom[$i], $matrik[$ii][$i],7);
		}
	}
	return $jumlah_kolom;
}

function ahp_get_normalisasi($matrik, $jumlah_kolom)
{
	$matrik_normalisasi = array();
	for ($i = 0; $i < count($matrik); $i++) { //iterasi baris matriks
		for ($ii = 0; $ii < count($matrik); $ii++) { //iterasi kolom matriks
			$matrik_normalisasi[$i][$ii] = $matrik[$i][$ii] / $jumlah_kolom[$ii];
			//matriks baris 1 kolom 1 dibagi jumlah_kolom kolom 1
			//$matrik_normalisasi[$i][$ii] = bcdiv($matrik[$i][$ii], $jumlah_kolom[$ii],7);
		}
	}
	return $matrik_normalisasi;
}

function ahp_get_prioritas($matrik_normalisasi)
{
	$prioritas = array();
	for ($i = 0; $i < count($matrik_normalisasi); $i++) { //iterasi baris matriks_normalisasi
		$prioritas[$i] = 0;
		for ($ii = 0; $ii < count($matrik_normalisasi); $ii++) { //iterasi kolom matriks_normalisasi
			$prioritas[$i] = $prioritas[$i] + $matrik_normalisasi[$i][$ii];
			//prioritas baris ditambah matrik baris 1 kolom 1 dan seterusnya, ditambah matriks baris 1 kolom 2.

			//$prioritas = bcadd($prioritas[$i], $matrik_normalisasi[$i][$ii],5);
			// $prioritas[$i] = number_format($prioritas[$i] + $matrik_normalisasi[$i][$ii],5);
		}
		$prioritas[$i]=$prioritas[$i]/count($matrik_normalisasi);
		//nilai  prioritas dibagi dengan jumlah elemen dalam baris matriks normalisasi

		// $prioritas[$i] = number_format($prioritas[$i] / count($matrik_normalisasi), 7);
		//$prioritas = bcdiv($prioritas[$i],count($matrik_normalisasi), 7);
	}
	return $prioritas;
}

function ahp_get_matrik_baris($prioritas, $matrik_kriteria)
{
	$matrik_baris = array();
	for ($i = 0; $i < count($matrik_kriteria); $i++) {
		for ($ii = 0; $ii < count($matrik_kriteria); $ii++) {
			// $temp = '0';
			$matrik_baris[$i][$ii] = $prioritas[$ii] * $matrik_kriteria[$i][$ii];
			//prioritas hanya 1 baris
			//Hasil Perkalian Bobot Prioritas Relatif dengan Elemen pada Matriks Perbandingan Berpasangan
			//bobot baris 1 kolom 1 x elemen baris 1 kolom 1, bobot baris 1 kolom 1 x elemen baris 2 kolom 1
			//$matrik_baris[$i][$ii] = bcadd($temp, bcmul($prioritas[$ii], $matrik_kriteria[$i][$ii],7));
		}
	}
	return $matrik_baris;
}

function ahp_get_jumlah_matrik_baris($matrik_baris)
{
	$jumlah_baris = array();
	for ($i = 0; $i < count($matrik_baris); $i++) {
		$jumlah_baris[$i] = 0;
		for ($ii = 0; $ii < count($matrik_baris); $ii++) {
			$jumlah_baris[$i] = $jumlah_baris[$i] + $matrik_baris[$i][$ii];
			//hasil perkalian bobot prioritas dengan elemen pada matriks perbandingan berpasangan ditambahkan
			//(bobot baris 1 kolom 1 x elemen baris 1 kolom 1) ditambah (bobot baris 1 kolom 2 x elemen baris 1 kolom 2)
			//jumlah_baris kolomnya hanya 1
			//matriks baris matriks 2 dimensi 
			//jumlah_baris array 1 dimensi
			//$jumlah_baris[$i]= bcadd($jumlah_baris[$i],$matrik_baris[$i][$ii],7);
		}
	}
	return $jumlah_baris;
}

function ahp_get_tabel_konsistensi($jumlah_matrik_baris, $prioritas)
{
	$jumlahlambda = array();
	for ($i = 0; $i < count($jumlah_matrik_baris); $i++) {
		$jumlahlambda[$i] = $jumlah_matrik_baris[$i] / $prioritas[$i];
		//$jumlah[$i]= bcdiv($jumlah_matrik_baris[$i], $prioritas[$i] ,5);
		//$jumlah[$i] = $jumlah_matrik_baris[$i] + $prioritas[$i];
	}
	return $jumlahlambda;
}

function ahp_uji_konsistensi($tabel_konsistensi)
{
	$jumlahlambda = array_sum($tabel_konsistensi); 
	$n = count($tabel_konsistensi);
	$lambda_maks = $jumlahlambda / $n;
	$ci = ($lambda_maks - $n) / ($n - 1);
	$ir = array(0, 0, 0.58, 0.9, 1.12, 1.24, 1.32, 1.41, 1.45, 1.49, 1.51, 1.48, 1.56, 1.57, 1.59); //nilai index rasio AHP
	if ($n <= 15) {
		$ir = $ir[$n - 1];
	} else {
		$ir = $ir[14];
	}
		$cr = $ci / $ir;
		//$cr= bcdiv($ci, $ir,5);

	if ($cr <= 0.1) {
		return true;
	} else {
		return false;
	}
}

function tampil_data_1($matrik_kriteria, $jumlah_kolom)
{
	include ('includes/konek-db.php');
	$kriteria = array();
	$query = mysqli_query($koneksi,"SELECT * FROM kriteria ORDER BY kode_kriteria ASC");
	while($krit = mysqli_fetch_array($query)){
		$kriteria[$krit['id_kriteria']]['id_kriteria'] = $krit['id_kriteria'];
		$kriteria[$krit['id_kriteria']]['kode_kriteria'] = $krit['kode_kriteria'];
		$kriteria[$krit['id_kriteria']]['nama'] = $krit['nama'];
	}
	// --- tabel matriks perbandingan berpasangan
	$list_data = '';
	$list_data .= '<tr><td></td>';
	foreach ($kriteria as $row) {
		$list_data .= '<td class="text-center">' . $row["kode_kriteria"] . '</td>';
	}
	$list_data .= '</tr>';
	$i = 0;
	foreach ($kriteria as $row) {
		$list_data .= '<tr>';
		$list_data .= '<td>' . $row["kode_kriteria"] . '</td>';
		$ii = 0;
		foreach ($kriteria as $row2) {
			$list_data .= '<td class="text-center">' . number_format($matrik_kriteria[$i][$ii],5) . '</td>';
			$ii++;
		}
		$list_data .= '</tr>';
		$i++;
	}
	$list_data .= '<tr><td class="font-weight-bold">Jumlah</td>';
	for ($i = 0; $i < count($jumlah_kolom); $i++) {
		$list_data .= '<td class="text-center font-weight-bold">' . number_format($jumlah_kolom[$i],5) . '</td>';
	}
	$list_data .= '</tr>';
	// ---
	return $list_data;
}

function tampil_data_2($matrik_normalisasi, $prioritas)
{
	include ('includes/konek-db.php');
	$kriteria = array();
	$query = mysqli_query($koneksi,"SELECT * FROM kriteria ORDER BY kode_kriteria ASC");
	while($krit = mysqli_fetch_array($query)){
		$kriteria[$krit['id_kriteria']]['id_kriteria'] = $krit['id_kriteria'];
		$kriteria[$krit['id_kriteria']]['kode_kriteria'] = $krit['kode_kriteria'];
		$kriteria[$krit['id_kriteria']]['nama'] = $krit['nama'];
	}
	// --- matriks nilai kriteria
	$list_data2 = '';
	$list_data2 .= '<tr><td></td>';
	foreach ($kriteria as $row) {
		$list_data2 .= '<td class="text-center">' . $row["kode_kriteria"] . '</td>';
	}
	$list_data2 .= '<td class="text-center font-weight-bold">Jumlah</td>';
	$list_data2 .= '<td class="text-center font-weight-bold">Prioritas</td>';
	$list_data2 .= '</tr>';
	$i = 0;
	foreach ($kriteria as $row) {
		$list_data2 .= '<tr>';
		$list_data2 .= '<td>' . $row["kode_kriteria"] . '</td>';
		$jumlah = 0;
		$ii = 0;
		foreach ($kriteria as $row2) {
			$list_data2 .= '<td class="text-center">' . number_format($matrik_normalisasi[$i][$ii],5) . '</td>';
			$jumlah_hitung += $matrik_normalisasi[$i][$ii]; //jumlahkan perbaris matriks normalisasi
			$ii++;
		}
		$list_data2 .= '<td class="text-center font-weight-bold">' . number_format($jumlah_hitung,5) . '</td>';
		$list_data2 .= '<td class="text-center font-weight-bold">' . number_format($prioritas[$i],5) . '</td>';
		$list_data2 .= '</tr>';
		$i++;
	}
	// ---
	return $list_data2;
}

function tampil_data_3($matrik_baris, $jumlah_matrik_baris)
{
	include ('includes/konek-db.php');
	$kriteria = array();
	$query = mysqli_query($koneksi,"SELECT * FROM kriteria ORDER BY kode_kriteria ASC");
	while($krit = mysqli_fetch_array($query)){
		$kriteria[$krit['id_kriteria']]['id_kriteria'] = $krit['id_kriteria'];
		$kriteria[$krit['id_kriteria']]['kode_kriteria'] = $krit['kode_kriteria'];
		$kriteria[$krit['id_kriteria']]['nama'] = $krit['nama'];
	}
	// --- matriks penjumlahan setiap baris
	$list_data3 = '';
	$list_data3 .= '<tr><td></td>';
	foreach ($kriteria as $row) {
		$list_data3 .= '<td class="text-center">' . $row["kode_kriteria"] . '</td>';
	}
	$list_data3 .= '<td class="text-center font-weight-bold">Jumlah</td>';
	$list_data3 .= '</tr>';
	$i = 0;
	foreach ($kriteria as $row) {
		$list_data3 .= '<tr>';
		$list_data3 .= '<td>' . $row["kode_kriteria"] . '</td>';
		$ii = 0;
		foreach ($kriteria as $row2) {
			$list_data3 .= '<td class="text-center">' . number_format($matrik_baris[$i][$ii],5) . '</td>';
			$ii++;
		}
		$list_data3 .= '<td class="text-center font-weight-bold">' . number_format($jumlah_matrik_baris[$i],5) . '</td>';
		$list_data3 .= '</tr>';
		$i++;
	}
	// ---
	return $list_data3;
}

function tampil_data_4($jumlah_matrik_baris, $prioritas, $hasil_tabel_konsistensi)
{
	include ('includes/konek-db.php');
	$kriteria = array();
	$query = mysqli_query($koneksi,"SELECT * FROM kriteria ORDER BY kode_kriteria ASC");
	while($krit = mysqli_fetch_array($query)){
		$kriteria[$krit['id_kriteria']]['id_kriteria'] = $krit['id_kriteria'];
		$kriteria[$krit['id_kriteria']]['kode_kriteria'] = $krit['kode_kriteria'];
		$kriteria[$krit['id_kriteria']]['nama'] = $krit['nama'];
	}
	// --- perhitungan rasio konsistensi
	$list_data4 = '';
	$list_data4 .= '<tr><td></td>';
	$list_data4 .= '<td class="text-center">Jumlah per Baris</td>';
	$list_data4 .= '<td class="text-center">Prioritas</td>';
	$list_data4 .= '<td class="text-center font-weight-bold">Hasil</td>';
	$list_data4 .= '</tr>';
	$i = 0;
	foreach ($kriteria as $row) {
		$list_data4 .= '<tr>';
		$list_data4 .= '<td>' . $row["kode_kriteria"] . '</td>';
		$list_data4 .= '<td class="text-center">' . number_format($jumlah_matrik_baris[$i],5) . '</td>';
		$list_data4 .= '<td class="text-center">' . number_format($prioritas[$i],5) . '</td>';
		$list_data4 .= '<td class="text-center font-weight-bold">' . number_format($hasil_tabel_konsistensi[$i],5) . '</td>';
		$list_data4 .= '</tr>';
		$i++;
	}
	return $list_data4;
}


function tampil_data_5($jumlah_matrik_baris, $prioritas, $hasil_tabel_konsistensi)
{
	$jumlahlambda = array_sum($hasil_tabel_konsistensi);
	$n = count($hasil_tabel_konsistensi);
	$lambda_maks = $jumlahlambda / $n;
	$ci = ($lambda_maks - $n) / ($n - 1);
	$ir = array(0, 0, 0.58, 0.9, 1.12, 1.24, 1.32, 1.41, 1.45, 1.49, 1.51, 1.48, 1.56, 1.57, 1.59);
	if ($n <= 15) {
		$ir = $ir[$n - 1];
	} else {
		$ir = $ir[14];
	}
	$cr = $ci / $ir;

	$list_data5 = '';
	$list_data5 .= '<table class="table">
	<tr>
	<td width="100">Jumlah</td>
	<td>= ' . number_format($jumlahlambda,5) . '</td>
	</tr>
	<tr>
	<td width="100">n </td>
	<td>= ' . $n . '</td>
	</tr>
	<tr>
	<td width="100">λ maks</td>
	<td>= ' . number_format($lambda_maks, 5) . '</td>
	</tr>
	<tr>
	<td width="100">CI</td>
	<td>= ' . number_format($ci, 5) . '</td>
	</tr>
	<tr>
	<td width="100">CR</td>
	<td>= ' . number_format($cr,5) . '</td>
	</tr>
	<tr>
	<td width="100">CR <= 0.1</td>';
	if ($cr <= 0.1) {
		$list_data5 .= '
	<td>Konsisten</td>';
	} else {
		$list_data5 .= '
	<td>Tidak Konsisten</td>';
	}
	$list_data5 .= '
	</tr>
	</table>';
	// ---
	return $list_data5;
}

?>


<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 green-icon"><i class="fas fa-fw fa-cubes"></i> Data Kriteria</h1>

	<a href="list-kriteria.php" class="btn btn-secondary btn-icon-split"><span class="icon text-white-50"><i class="fas fa-arrow-left"></i></span>
		<span class="text">Kembali</span>
	</a>
</div>

<?php if(!empty($errors)): ?>
	<div class="alert alert-info">
		<?php foreach($errors as $error): ?>
			<?php echo $error; ?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>	

<?php
$status = isset($_GET['status']) ? $_GET['status'] : '';
$msg = '';
switch($status):
	case 'sukses-baru':
		$msg = 'Data berhasil diupdate';
		break;
	case 'sukses-konsisten':
		$msg = 'Nilai perbandingan : KONSISTEN';
		break;
	case 'gagal-konsisten':
		$msg = 'Nilai perbandingan : TIDAK KONSISTEN';
		break;
	case 'gagal-min':
		$msg = 'Jumlah kriteria kurang, minimal 3!';
		break;
endswitch;

if($msg):
	echo '<div class="alert alert-info">'.$msg.'</div>';
endif;

?>

<div class="alert alert-info">
	Silahkan isi terlebih dahulu nilai kriteria menggunakan perbandingan berpasangan berdasarkan skala perbandingan 1-9 (sesuai teori) kemudian klik <b>SIMPAN</b>. Setelah itu klik <b>CEK KONSISTENSI</b> untuk melakukan pembobotan preferensi dengan menggunakan metode AHP.
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold green-icon">Perbandingan Data Antar Kriteria</h6>
    </div>
	
	<form action="" method="post">
		<div class="card-body">
			<table class="table table-bordered">
				<thead>
					<tr>
						<th class="text-right" width="25%">Nama Kriteria</th>
						<th class="text-center" width="50%">Skala Perbandingan</th>
						<th class="text-left" width="25%">Nama Kriteria</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$no = 1;
					$i = 0;
					foreach ($kriteria as $row1) :
						$ii = 0;
						foreach ($kriteria as $row2) :
							if ($i < $ii) :
								$nilai = $kriteria_ahp[$row1['id_kriteria']][$row2['id_kriteria']];
					?>
								<tr>
									<td class="text-right">(<?= $row1['kode_kriteria'] ?>) <?= $row1['nama'] ?></td>
									<td class="text-center">
										<div class="btn-group btn-group-toggle" data-toggle="buttons">
											<label class="btn btn-success <?= $nilai == -9 ? "active" : "" ?>"><input type="radio" id="radio_a_<?= $no ?>" name="nilai_<?= $row1['id_kriteria'] . '_' . $row2['id_kriteria'] ?>" value="-9" <?= $nilai == -9 ? "checked" : "" ?>>9</label>
											<label class="btn btn-success <?= $nilai == -8 ? "active" : "" ?>"><input type="radio" id="radio_b_<?= $no ?>" name="nilai_<?= $row1['id_kriteria'] . '_' . $row2['id_kriteria'] ?>" value="-8" <?= $nilai == -8 ? "checked" : "" ?>>8</label>
											<label class="btn btn-success <?= $nilai == -7 ? "active" : "" ?>"><input type="radio" id="radio_c_<?= $no ?>" name="nilai_<?= $row1['id_kriteria'] . '_' . $row2['id_kriteria'] ?>" value="-7" <?= $nilai == -7 ? "checked" : "" ?>>7</label>
											<label class="btn btn-success <?= $nilai == -6 ? "active" : "" ?>"><input type="radio" id="radio_d_<?= $no ?>" name="nilai_<?= $row1['id_kriteria'] . '_' . $row2['id_kriteria'] ?>" value="-6" <?= $nilai == -6 ? "checked" : "" ?>>6</label>
											<label class="btn btn-success <?= $nilai == -5 ? "active" : "" ?>"><input type="radio" id="radio_e_<?= $no ?>" name="nilai_<?= $row1['id_kriteria'] . '_' . $row2['id_kriteria'] ?>" value="-5" <?= $nilai == -5 ? "checked" : "" ?>>5</label>
											<label class="btn btn-success <?= $nilai == -4 ? "active" : "" ?>"><input type="radio" id="radio_f_<?= $no ?>" name="nilai_<?= $row1['id_kriteria'] . '_' . $row2['id_kriteria'] ?>" value="-4" <?= $nilai == -4 ? "checked" : "" ?>>4</label>
											<label class="btn btn-success <?= $nilai == -3 ? "active" : "" ?>"><input type="radio" id="radio_g_<?= $no ?>" name="nilai_<?= $row1['id_kriteria'] . '_' . $row2['id_kriteria'] ?>" value="-3" <?= $nilai == -3 ? "checked" : "" ?>>3</label>
											<label class="btn btn-success <?= $nilai == -2 ? "active" : "" ?>"><input type="radio" id="radio_h_<?= $no ?>" name="nilai_<?= $row1['id_kriteria'] . '_' . $row2['id_kriteria'] ?>" value="-2" <?= $nilai == -2 ? "checked" : "" ?>>2</label>
											<label class="btn btn-success <?= $nilai == 1 ? "active" : "" ?>"><input type="radio" id="radio_i_<?= $no ?>" name="nilai_<?= $row1['id_kriteria'] . '_' . $row2['id_kriteria'] ?>" value="1" <?= $nilai == 1 ? "checked" : "" ?>>1</label>
											<label class="btn btn-success <?= $nilai == 2 ? "active" : "" ?>"><input type="radio" id="radio_j_<?= $no ?>" name="nilai_<?= $row1['id_kriteria'] . '_' . $row2['id_kriteria'] ?>" value="2" <?= $nilai == 2 ? "checked" : "" ?>>2</label>
											<label class="btn btn-success <?= $nilai == 3 ? "active" : "" ?>"><input type="radio" id="radio_k_<?= $no ?>" name="nilai_<?= $row1['id_kriteria'] . '_' . $row2['id_kriteria'] ?>" value="3" <?= $nilai == 3 ? "checked" : "" ?>>3</label>
											<label class="btn btn-success <?= $nilai == 4 ? "active" : "" ?>"><input type="radio" id="radio_l_<?= $no ?>" name="nilai_<?= $row1['id_kriteria'] . '_' . $row2['id_kriteria'] ?>" value="4" <?= $nilai == 4 ? "checked" : "" ?>>4</label>
											<label class="btn btn-success <?= $nilai == 5 ? "active" : "" ?>"><input type="radio" id="radio_m_<?= $no ?>" name="nilai_<?= $row1['id_kriteria'] . '_' . $row2['id_kriteria'] ?>" value="5" <?= $nilai == 5 ? "checked" : "" ?>>5</label>
											<label class="btn btn-success <?= $nilai == 6 ? "active" : "" ?>"><input type="radio" id="radio_n_<?= $no ?>" name="nilai_<?= $row1['id_kriteria'] . '_' . $row2['id_kriteria'] ?>" value="6" <?= $nilai == 6 ? "checked" : "" ?>>6</label>
											<label class="btn btn-success <?= $nilai == 7 ? "active" : "" ?>"><input type="radio" id="radio_o_<?= $no ?>" name="nilai_<?= $row1['id_kriteria'] . '_' . $row2['id_kriteria'] ?>" value="7" <?= $nilai == 7 ? "checked" : "" ?>>7</label>
											<label class="btn btn-success <?= $nilai == 8 ? "active" : "" ?>"><input type="radio" id="radio_p_<?= $no ?>" name="nilai_<?= $row1['id_kriteria'] . '_' . $row2['id_kriteria'] ?>" value="8" <?= $nilai == 8 ? "checked" : "" ?>>8</label>
											<label class="btn btn-success <?= $nilai == 9 ? "active" : "" ?>"><input type="radio" id="radio_q_<?= $no ?>" name="nilai_<?= $row1['id_kriteria'] . '_' . $row2['id_kriteria'] ?>" value="9" <?= $nilai == 9 ? "checked" : "" ?>>9</label>
										</div>
									</td>
									<td class="text-left">(<?= $row2['kode_kriteria'] ?>) <?= $row2['nama'] ?></td>
								</tr>
					<?php
								$no++;
							endif;
							$ii++;
						endforeach;
						$i++;
					endforeach;
					?>
					<tr>
						<td class="text-center" colspan="3">
							<button type="submit" name="save" class="btn btn-primary"><i class="fas fa-fw fa-save mr-1"></i> Simpan</button>
							<button type="submit" name="check" class="btn btn-success"><i class="fas fa-fw fa-check mr-1"></i> Cek Konsistensi</button>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</form>
</div>

<?php if (isset($_POST['check'])) : ?>
	
	<div class="card shadow mb-4">
		<div class="card-header py-3">
			<h6 class="m-0 font-weight-bold green-icon">Matriks Perbandingan Berpasangan</h6>
		</div>
		
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-bordered">
					<?= $list_data ?>
				</table>
			</div>
		</div>
	</div>
	
	
	<div class="card shadow mb-4">
		<div class="card-header py-3">
			<h6 class="m-0 font-weight-bold green-icon">Matriks Normalisasi Perbandingan Berpasangan</h6>
		</div>
		
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-bordered">
					<?= $list_data2 ?>
				</table>
			</div>
		</div>
	</div>
	
	<div class="card shadow mb-4">
		<div class="card-header py-3">
			<h6 class="m-0 font-weight-bold green-icon">Hasil Perkalian Bobot Prioritas Relatif dengan Elemen pada Matriks Perbandingan Berpasangan</h6>
		</div>
		
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-bordered">
					<?= $list_data3 ?>
				</table>
			</div>
		</div>
	</div>
	
	<div class="card shadow mb-4">
		<div class="card-header py-3">
			<h6 class="m-0 font-weight-bold green-icon"> Lambda Maksimum Dan Rasio Konsistensi</h6>
		</div>
		
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-bordered">
					<?= $list_data4 ?>
				</table>
				<?= $list_data5 ?>
			</div>
		</div>
	</div>
<?php endif; ?>


<?php
require_once('template/footer.php');
?>