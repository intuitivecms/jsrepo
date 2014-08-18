<?php
$arch_dir 		= "archive/";
$pkg_dir 			= "packages/";
$pkg_listfile = "packlist.json";
$pkg_infofile = "package.json";

$option_list = array(
	"force:",
	"remove:",
	"rebuild"
);

$opt = getopt("", $option_list);

$packlist = json_decode(
						file_get_contents($pkg_listfile), true);

function scandir_ext($dir) {
	return array_diff(
					scandir($dir),
					array("..","."));
}

$stability = scandir_ext($pkg_dir);

foreach ($stability as $stb) {
	$archive = $arch_dir.$stb."/";
	$method="";

	if (isset($opt["force"]))
		$method = "force";

	if (isset($opt["remove"])) {
		$method = "remove";
		$delete = true;
	}

	if (isset($opt[$method])) {
			$name = (is_array($opt[$method])?
				$opt[$method][0]:$opt[$method]);

			if (isset($packlist[$stb][$name])) {
				$tgz = glob($archive.$name."-".$packlist[$stb][$name]["version"]."*");
				unset($packlist[$stb][$name]);
				unlink($tgz[0]);
				echo "$method: \"$name\".\n";
			}
	}

	$packs = scandir_ext($pkg_dir.$stb);

	foreach ($packs as $pkg) {
		$subdir = $stb."/".$pkg."/";
		if (!isset($packlist[$stb][$pkg]) && (!isset($delete))) {

			// Get package information
			$pkginfo = json_decode(
									file_get_contents(
									$pkg_dir.$subdir.$pkg_infofile),
									true);

			// Create archive and compress it using GZip
			$phar = new PharData($archive.$pkg.".tar");
			$phar->buildFromDirectory($pkg_dir.$subdir."root/");
			$phar->compress(Phar::GZ);

			$newname = $archive.$pkg.
							"-".$pkginfo["version"].".tar.gz";

			// Add version later bacause "compress" is buggy
			rename($archive.$pkg.".tar.gz",$newname);

			echo "Packed ".$newname."\n";

			unlink($archive.$pkg.".tar");

			$packlist[$stb][$pkg] = $pkginfo;
		}
	}

}

file_put_contents($pkg_listfile,
	json_encode($packlist, JSON_PRETTY_PRINT));
