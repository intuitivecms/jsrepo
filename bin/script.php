Derp
<?php
	$arch_dir 		= "archive/";
	$pkg_dir 			= "packages/";
	$pkg_listfile = "packlist.json";
	$pkg_infofile = "package.json";

	$packlist = json_decode(
							file_get_contents($pkg_listfile), true);

	function scandir_ext($dir) {
		return array_diff(
						scandir($dir),
						array("..","."));
	}

	$stability = scandir_ext($pkg_dir);

	foreach ($stability as $stb) {

		$packs = scandir_ext($pkg_dir.$stb);

		foreach ($packs as $pkg) {
			$subdir = $stb."/".$pkg."/";
			if (!isset($packlist[$stb][$pkg])) {

				// Get package information
				$pkginfo = json_decode(
										file_get_contents(
										$pkg_dir.$subdir.$pkg_infofile),
										true);

				$archive = $arch_dir.$stb."/";

				// Create archive and compress it using GZip
				$phar = new PharData($archive.$pkg.".tar");
				$phar->buildFromDirectory($pkg_dir.$subdir."lib/");
				$phar->compress(Phar::GZ);

				// Add version later bacause "compress" is buggy
				rename($archive.$pkg.".tar.gz",$archive.$pkg.
								"-".$pkginfo["version"].".tar.gz");

				unlink($archive.$pkg.".tar");

				$packlist[$stb][$pkg] = $pkginfo;
			}
		}
		//print_r($packs);
	}
