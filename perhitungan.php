<?php
require_once('includes/init.php');

$user_role = get_role();
if($user_role == 'admin') {

$page = "Perhitungan";
require_once('template/header.php');

mysqli_query($koneksi,"TRUNCATE TABLE hasil;");

$kriterias = array();
$q1 = mysqli_query($koneksi,"SELECT * FROM kriteria ORDER BY kode_kriteria ASC");	
//array kriterias dengan data dari tabel kriteria yang diakses melalui id_kriteria		
while($krit = mysqli_fetch_array($q1)){
	$kriterias[$krit['id_kriteria']]['id_kriteria'] = $krit['id_kriteria'];
	$kriterias[$krit['id_kriteria']]['kode_kriteria'] = $krit['kode_kriteria'];
	$kriterias[$krit['id_kriteria']]['nama'] = $krit['nama'];
	$kriterias[$krit['id_kriteria']]['type'] = $krit['type'];
	$kriterias[$krit['id_kriteria']]['bobot'] = $krit['bobot'];
	$kriterias[$krit['id_kriteria']]['ada_pilihan'] = $krit['ada_pilihan'];
}

$alternatifs = array();
$q2 = mysqli_query($koneksi,"SELECT * FROM alternatif");			
while($alt = mysqli_fetch_array($q2)){
	$alternatifs[$alt['id_alternatif']]['id_alternatif'] = $alt['id_alternatif'];
	$alternatifs[$alt['id_alternatif']]['nama'] = $alt['nama'];
} 

//Matrix Keputusan (X)
$matriks_x = array();
foreach($kriterias as $kriteria): //mengiterasi melalui semua kriteria.
	foreach($alternatifs as $alternatif): //mengiterasi melalui semua alternatif
		
		$id_alternatif = $alternatif['id_alternatif'];
		$id_kriteria = $kriteria['id_kriteria'];
		
		if($kriteria['ada_pilihan']==1){
			$q4 = mysqli_query($koneksi,"SELECT sub_kriteria.nilai FROM penilaian JOIN sub_kriteria WHERE penilaian.nilai=sub_kriteria.id_sub_kriteria AND penilaian.id_alternatif='$alternatif[id_alternatif]' AND penilaian.id_kriteria='$kriteria[id_kriteria]'");
			$data = mysqli_fetch_array($q4);
			if ($data['nilai'] == null) {
				echo '<script>';
				echo 'alert("Terdapat data penilaiannya yang kosong. Isi penilaiannya terlebih dahulu.");';
				echo 'window.location.href = "list-penilaian.php"';
				echo '</script>';
			}

			$nilai = $data['nilai'];

		}else{
			$q4 = mysqli_query($koneksi,"SELECT sub_kriteria.nilai FROM penilaian JOIN sub_kriteria WHERE penilaian.nilai=sub_kriteria.id_sub_kriteria AND penilaian.id_alternatif='$alternatif[id_alternatif]' AND penilaian.id_kriteria='$kriteria[id_kriteria]'");
			$nilai = $data['nilai'];
		}
		
		$matriks_x[$id_kriteria][$id_alternatif] = $nilai;
	endforeach;
endforeach;

//Matriks Ternormalisasi (R)
$matriks_r = array();
foreach($matriks_x as $id_kriteria => $penilaians): //mengiterasi melalui setiap kriteria
	
	$jumlah_kuadrat = 0;
	foreach($penilaians as $penilaian): //Mengiterasi lagi melalui setiap penilaian dalam kriteria
		$jumlah_kuadrat += pow($penilaian, 2);//pangkat 2
	endforeach;
	$akar_kuadrat = sqrt($jumlah_kuadrat);
	
	foreach($penilaians as $id_alternatif => $penilaian):
		$matriks_r[$id_kriteria][$id_alternatif] = $penilaian / $akar_kuadrat; 
	endforeach;
	
	//
endforeach;

 //Matriks Y
$matriks_y = array();
foreach($kriterias as $kriteria):
	foreach($alternatifs as $alternatif):
		
		$bobot = $kriteria['bobot'];
		$id_alternatif = $alternatif['id_alternatif'];
		$id_kriteria = $kriteria['id_kriteria'];
		
		$nilai_r = $matriks_r[$id_kriteria][$id_alternatif];
		$matriks_y[$id_kriteria][$id_alternatif] = $bobot * $nilai_r;

	endforeach;
endforeach;

//Solusi Ideal Positif & Negarif
$solusi_ideal_positif = array();
$solusi_ideal_negatif = array();
foreach($kriterias as $kriteria):

	$id_kriteria = $kriteria['id_kriteria'];
	$type_kriteria = $kriteria['type'];
	
	$nilai_max = @(max($matriks_y[$id_kriteria])); //@untuk error control
	$nilai_min = @(min($matriks_y[$id_kriteria]));
	
	if($type_kriteria == 'Benefit'):
		$s_i_p = $nilai_max;
		$s_i_n = $nilai_min;
	elseif($type_kriteria == 'Cost'):
		$s_i_p = $nilai_min;
		$s_i_n = $nilai_max;
	endif;
	
	$solusi_ideal_positif[$id_kriteria] = $s_i_p;
	$solusi_ideal_negatif[$id_kriteria] = $s_i_n;

endforeach;

//Jarak Ideal Positif & Negatif
$jarak_ideal_positif = array();
$jarak_ideal_negatif = array();
foreach($alternatifs as $alternatif):

	$id_alternatif = $alternatif['id_alternatif'];		
	$jumlah_kuadrat_jip = 0;
	$jumlah_kuadrat_jin = 0;
	
	// Mencari penjumlahan kuadrat
	foreach($matriks_y as $id_kriteria => $penilaians):
		
		$hsl_pengurangan_jip = $penilaians[$id_alternatif] - $solusi_ideal_positif[$id_kriteria]; // nilai penilaian alternatif saat ini untuk kriteria dengan $id_kriteria, nilai solusi ideal positif untuk kriteria yang sama. 
		$hsl_pengurangan_jin = $penilaians[$id_alternatif] - $solusi_ideal_negatif[$id_kriteria];
		
		$jumlah_kuadrat_jip += pow($hsl_pengurangan_jip, 2); //pangkat 2
		$jumlah_kuadrat_jin += pow($hsl_pengurangan_jin, 2);
	
	endforeach;
	
	// Mengakarkan hasil penjumlahan kuadrat
	$akar_kuadrat_jip = sqrt($jumlah_kuadrat_jip);
	$akar_kuadrat_jin = sqrt($jumlah_kuadrat_jin);
	
	// Memasukkan ke array matriks jip & jin
	$jarak_ideal_positif[$id_alternatif] = $akar_kuadrat_jip;
	$jarak_ideal_negatif[$id_alternatif] = $akar_kuadrat_jin;
	
endforeach;

//Kedekatan Relatif Terhadap Solusi Ideal (V)
$kedekatan_relatif = array();
foreach($alternatifs as $alternatif):

	$s_negatif = $jarak_ideal_negatif[$alternatif['id_alternatif']];
	$s_positif = $jarak_ideal_positif[$alternatif['id_alternatif']];	
	
	$nilai_v = @($s_negatif / ($s_positif + $s_negatif));
	
	$kedekatan_relatif[$alternatif['id_alternatif']]['id_alternatif'] = $alternatif['id_alternatif'];
	$kedekatan_relatif[$alternatif['id_alternatif']]['nama'] = $alternatif['nama'];
	$kedekatan_relatif[$alternatif['id_alternatif']]['nilai'] = $nilai_v;
	
endforeach;
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 green-icon"><i class="fas fa-fw fa-calculator"></i> Data Hasil Perhitungan</h1>
	<a href="cetak.php" target="_blank" class="btn btn-primary"> <i class="fa fa-print"></i> Cetak Data </a>
</div>

<div class="card shadow mb-4">
    <!-- /.card-header -->
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold green-icon">Matrix Keputusan (X)</h6>
    </div>

    <div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th width="5%" rowspan="2">No</th>
						<th>Nama Alternatif</th>
						<?php foreach ($kriterias as $kriteria): ?>
							<th><?= $kriteria['kode_kriteria'] ?></th>
						<?php endforeach ?>
					</tr>
				</thead>
				<tbody>
					<?php 
						$no=1;
						foreach ($alternatifs as $alternatif): ?>
					<tr align="center">
						<td><?= $no; ?></td>
						<td align="left"><?= $alternatif['nama'] ?></td>
						<?php
						foreach ($kriterias as $kriteria):
							$id_alternatif = $alternatif['id_alternatif'];
							$id_kriteria = $kriteria['id_kriteria'];
							echo '<td>';
							echo $matriks_x[$id_kriteria][$id_alternatif];
							echo '</td>';
						endforeach
						?>
					</tr>
					<?php
						$no++;
						endforeach
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="card shadow mb-4">
    <!-- /.card-header -->
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold green-icon">Bobot Preferensi (W)</h6>
    </div>

    <div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<?php foreach ($kriterias as $kriteria): ?>
						<th><?= $kriteria['kode_kriteria'] ?> (<?= $kriteria['type'] ?>)</th>
						<?php endforeach ?>
					</tr>
				</thead>
				<tbody>
					<tr align="center">
						<?php foreach ($kriterias as $kriteria): ?>
						<td>
						<?php 
						echo number_format($kriteria['bobot'],5);
						?>
						</td>
						<?php endforeach ?>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="card shadow mb-4">
    <!-- /.card-header -->
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold green-icon">Matriks Ternormalisasi (R)</h6>
    </div>

    <div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th width="5%" rowspan="2">No</th>
						<th>Nama Alternatif</th>
						<?php foreach ($kriterias as $kriteria): ?>
							<th><?= $kriteria['kode_kriteria'] ?></th>
						<?php endforeach ?>
					</tr>
				</thead>
				<tbody>
					<?php 
						$no=1;
						foreach ($alternatifs as $alternatif): ?>
					<tr align="center">
						<td><?= $no; ?></td>
						<td align="left"><?= $alternatif['nama'] ?></td>
						<?php						
						foreach($kriterias as $kriteria):
							$id_alternatif = $alternatif['id_alternatif'];
							$id_kriteria = $kriteria['id_kriteria'];
							echo '<td>';
							echo number_format($matriks_r[$id_kriteria][$id_alternatif],5);
							echo '</td>';
						endforeach;
						?>
					</tr>
					<?php
						$no++;
						endforeach
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>


<div class="card shadow mb-4">
    <!-- /.card-header -->
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold green-icon">Matriks Y</h6>
    </div>

    <div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th width="5%" rowspan="2">No</th>
						<th>Nama Alternatif</th>
						<?php foreach ($kriterias as $kriteria): ?>
							<th><?= $kriteria['kode_kriteria'] ?></th>
						<?php endforeach ?>
					</tr>
				</thead>
				<tbody>
					<?php 
						$no=1;
						foreach ($alternatifs as $alternatif): ?>
					<tr align="center">
						<td><?= $no; ?></td>
						<td align="left"><?= $alternatif['nama'] ?></td>
						<?php						
						foreach($kriterias as $kriteria):
							$id_alternatif = $alternatif['id_alternatif'];
							$id_kriteria = $kriteria['id_kriteria'];
							echo '<td>';
							echo number_format($matriks_y[$id_kriteria][$id_alternatif],5);
							echo '</td>';
						endforeach;
						?>
					</tr>
					<?php
						$no++;
						endforeach
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="card shadow mb-4">
    <!-- /.card-header -->
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold green-icon">Solusi Ideal Positif (A+)</h6>
    </div>

    <div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<?php foreach($kriterias as $kriteria ): ?>
							<th><?php echo $kriteria['nama']; ?> (<?php echo $kriteria['kode_kriteria']; ?>)</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<tr align="center">
					<?php foreach($kriterias as $kriteria ): ?>
						<td>
							<?php
							$id_kriteria = $kriteria['id_kriteria'];							
							echo number_format($solusi_ideal_positif[$id_kriteria],5);
							?>
						</td>
					<?php endforeach; ?>
					</tr>					
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="card shadow mb-4">
    <!-- /.card-header -->
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold green-icon">Solusi Ideal Negatif (A-)</h6>
    </div>

    <div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<?php foreach($kriterias as $kriteria ): ?>
							<th><?php echo $kriteria['nama']; ?> (<?php echo $kriteria['kode_kriteria']; ?>)</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<tr align="center">
					<?php foreach($kriterias as $kriteria ): ?>
						<td>
							<?php
							$id_kriteria = $kriteria['id_kriteria'];							
							echo number_format($solusi_ideal_negatif[$id_kriteria],5);
							?>
						</td>
					<?php endforeach; ?>
					</tr>					
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="card shadow mb-4">
    <!-- /.card-header -->
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold green-icon">Jarak Ideal Positif (D<sub>i</sub>+)</h6>
    </div>

    <div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th width="5%">No</th>
						<th>Nama Alternatif</th>
						<th width="30%">Jarak Ideal Positif</th>
					</tr>
				</thead>
				<tbody>
				<?php 
				$no=1;
				foreach($alternatifs as $alternatif ): ?>
					<tr align="center">
						<td><?php echo $no; ?></td>
						<td align="left"><?php echo $alternatif['nama']; ?></td>
						<td>
							<?php								
							$id_alternatif = $alternatif['id_alternatif'];
							echo number_format($jarak_ideal_positif[$id_alternatif],5);
							?>
						</td>						
					</tr>
				<?php 
				$no++;
				endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="card shadow mb-4">
    <!-- /.card-header -->
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold green-icon">Jarak Ideal Negatif (D<sub>i</sub>-)</h6>
    </div>

    <div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th width="5%">No</th>
						<th>Nama Alternatif</th>
						<th width="30%">Jarak Ideal Negatif</th>
					</tr>
				</thead>
				<tbody>
				<?php 
				$no=1;
				foreach($alternatifs as $alternatif ): ?>
					<tr align="center">
						<td><?php echo $no; ?></td>
						<td align="left"><?php echo $alternatif['nama']; ?></td>
						<td>
							<?php								
							$id_alternatif = $alternatif['id_alternatif'];
							echo number_format($jarak_ideal_negatif[$id_alternatif],5);
							?>
						</td>						
					</tr>
				<?php 
				$no++;
				endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="card shadow mb-4">
    <!-- /.card-header -->
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold green-icon">Kedekatan Relatif Terhadap Solusi Ideal (V)</h6>
    </div>

    <div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th width="5%">No</th>
						<th>Nama Alternatif</th>
						<th width="30%">Nilai</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$no=1;
					foreach($kedekatan_relatif as $alternatif ): ?>
						<tr align="center">
							<td><?php echo $no; ?></td>
							<td align="left"><?php echo $alternatif['nama']; ?></td>
							<td><?php echo number_format($alternatif['nilai'],5); ?></td>											
						</tr>
					<?php 
					$no++;
					mysqli_query($koneksi,"INSERT INTO hasil (id_hasil, id_alternatif, nilai) VALUES ('', '$alternatif[id_alternatif]', '$alternatif[nilai]')");
					endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="card shadow mb-4">
    <!-- /.card-header -->
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold green-icon">Hasil Akhir Pemeringkatan</h6>
    </div>

    <div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th>Nama Alternatif</th>
						<th>Nilai</th>
						<th width="15%">Rank</th>
				</thead>
				<tbody>
					<?php 
						$no=0;
						$query = mysqli_query($koneksi,"SELECT * FROM hasil JOIN alternatif ON hasil.id_alternatif=alternatif.id_alternatif ORDER BY hasil.nilai DESC");
						while($data = mysqli_fetch_array($query)){
						$no++;
					?>
					<tr align="center">
						<td align="left"><?= $data['nama'] ?></td>
						<td><?= number_format($data['nilai'],5) ?></td>
						<td><?= $no; ?></td>
					</tr>
					<?php
						}
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>


<?php
require_once('template/footer.php');
}
else {
	header('Location: login.php');
}
?>