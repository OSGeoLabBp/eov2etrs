<?php
/*
On-line service to transform EOV <-> ETRS89 coordinates
single point or several points from file

Usage
single point (GET & POST are accepted):
etrs2eov.php?e=<fi/EOVY>&n=<lambda/EOVX>&h=<ellipsoidal/orthogonal height>&sfradio=single&format=TXT/KML/GPX

file (POST only)
obligatory parameters
sfradio = file
format = TXT/KML/GPX

KML & GPX formats are valid in case of EOV -> ETRS89 transformation

Accepted file format (invalid lines are skipped)
point_id<separator>fi<separator>lambda<separator>heigth above ellipsoid or empty<separator>any other content in line
or 
point_id<separator>lambda<separator>fi<separator>heigth above ellipsoid or empty<separator>any other content in line
or
point_id<separator>EOVY<separator>EOVX<separator>heigth above mean sea level or empty<separator>any other content in line
or
point_id<separator>EOVX<separator>EOVY<separator>heigth above mean sea level or empty<separator>any other content in line

valid separators are: <space> <tab> <semicolon>;
*/

header('Content-type: text/plain');
$MAXROW = 20000; # max number of lines in input

/*
On-line service to transform EOV/Balti <-> ETRS89 coordinates
*/
function single($e, $n, $h) {
	# transform a single point
	# replace decimal coma with decimal point
	$e = preg_replace("/,/", ".", $e);
	$n = preg_replace("/,/", ".", $n);
	$h = preg_replace("/,/", ".", $h);
	if (! is_numeric($e) || ! is_numeric($n) || ! is_numeric($h)) {
		return "ERROR";
	}
	# check/exchange coordinates
	if ($e > 400000 && $e < 1100000 && $n > 0 && $n < 400000) {
		# nop OK
	} else if ($n > 400000 && $n < 1100000 && $e > 0 && $e < 400000) {
		# exchange EOV
		$w = $e;
		$e = $n;
		$n = $w;
	} else if ($n > 45.7 && $n < 48.7 && $e > 16 && $e < 23) {
		# nop ETRS OK
	} else if ($e > 45.7 && $e < 48.7 && $n > 16 && $n < 23) {
		# exchange ETRS
		$w = $e;
		$e = $n;
		$n = $w;
	} else {
		# error in coordinates
		return "ERROR";
	}
	# create temperary file for a single point
	$fn = tempnam("/tmp", "t");
	$f = fopen($fn, "w");
	fprintf($f, "%s %s %s\n", $e, $n, $h);
	fclose($f);
	if ($e < 100) {
		# lambda, fi -> EOVY, EOVX
		@$res = shell_exec("/usr/bin/cs2cs --3d +init=epsg:4258 +to +init=epsg:23700 +nadgrids=hu_bme_hd72corr.tif +geoidgrids=hu_bme_geoid2014.tif < $fn");
	} else {
		#EOVY, EOVX -> lambda, fi
		@$res = shell_exec("/usr/bin/cs2cs --3d -f \"%.7f\" +init=epsg:23700 +nadgrids=hu_bme_hd72corr.tif +geoidgrids=hu_bme_geoid2014.tif +to +init=epsg:4258 < $fn");
	}
	# remove temperary file
	@unlink($fn);
	# process result
	if ($res) {
		$w = preg_split('/\s+/', $res);
		$res1 = $w[0] . " " . $w[1];
		# add elevation if given
		if ($h != -500.0) {
			$res1 = $res1 . " " . $w[2];
		}
	} else {
		return "ERROR";
	}
	return $res1;
}

# check GET/POST parameters
if (isset($_REQUEST["debug"])) {
	print_r($_REQUEST);
}
# test case
if (isset($_REQUEST["test"])) {
	$test_file = "test.txt";
	if (isset($_REQUEST["testfile"])) {
		$test_file = $_REQUEST["testfile"];
	}
	if (! file_exists($test_file)) {
		echo "Test file not found: ". $test_file . "\n";
		return;
	}
	# read test file line by line and transform coordinates
	$fp = fopen($test_file, "r");
	$sum1 = array(0, 0, 0, 0, 0, 0);
	$max1 = array(0, 0, 0, 0, 0, 0);
	$sum2 = array(0, 0, 0, 0, 0, 0);
	$max2 = array(0, 0, 0, 0, 0, 0);
	$i = 0;
	while (! feof($fp)) {
		$buf = trim(fgets($fp));
		$i++;
		if (strlen(trim($buf)) == 0) { continue; }
		$fields = preg_split("/[ \t]+/", $buf);
		if (count($fields) != 7) {
			echo "Error in input line: $i\n";
			continue;
		}
		# EOV -> ETRS89
		$w = single($fields[1], $fields[2], $fields[3]);
		if ($w == "ERROR") {
			echo "Error in transformation EOV -> ETRS89 line: $i\n";
			continue;
		}
		$ww = preg_split("/ /", $w);
		$de = $fields[4] - $ww[1];
		$dn = $fields[5] - $ww[0];
		$dz = $fields[6] - $ww[2];
		$sum1[0] += $de; $sum1[1] += $dn; $sum1[2] = $dz;
		if (abs($de) > $max1[0]) { $max1[0] = abs($de); }
		if (abs($dn) > $max1[1]) { $max1[1] = abs($dn); }
		if (abs($dz) > $max1[2]) { $max1[2] = abs($dz); }
		# ETR89 -> EOV
		$w = single($fields[4], $fields[5], $fields[6]);
		if ($w == "ERROR") {
			echo "Error in transformation ETRS89 -> EOV line: $i\n";
			continue;
		}
		$ww = preg_split("/ /", $w);
		$de = $fields[1] - $ww[0];
		$dn = $fields[2] - $ww[1];
		$dz = $fields[3] - $ww[2];
		$sum2[0] += $de; $sum2[1] += $dn; $sum2[2] = $dz;
		if (abs($de) > $max2[0]) { $max2[0] = abs($de); }
		if (abs($dn) > $max2[1]) { $max2[1] = abs($dn); }
		if (abs($dz) > $max2[2]) { $max2[2] = abs($dz); }
	}
	fclose($fp);
	echo "Test of $i points\n";
	echo "=================\n";
	echo "EOV -> ETRS89\n";
	echo "Metric average differences\n";
	echo sprintf("%.3f", $sum1[0] / $i / 180.0 * pi() * 6380000) . " " . 
		sprintf("%.3f", $sum1[1] / $i / 180.0 * pi() * 6380000) . " " . 
		sprintf("%.3f", $sum1[2] / $i) . "\n";
	echo "Metric max differences\n";
	echo sprintf("%.3f", $max1[0] / 180.0 * pi() * 6380000) . " " . 
		sprintf("%.3f", $max1[1] / 180.0 * pi() * 6380000) . " " . 
		sprintf("%.3f", $max1[2]) . "\n";
	echo "\nETRS89 -> EOV\n";
	echo "Metric average differences\n";
	echo sprintf("%.3f", $sum2[0] / $i) . " " . 
		sprintf("%.3f", $sum2[1] / $i) . " " . 
		sprintf("%.3f", $sum2[2] / $i) . "\n";
	echo "Metric max differences\n";
	echo sprintf("%.3f", $max2[0]) . " " . 
		sprintf("%.3f", $max2[1]) . " " . 
		sprintf("%.3f", $max2[2]);
	return;
}
# end of test case

if (isset($_REQUEST["e"])) { $e = $_REQUEST["e"]; } # easting
if (isset($_REQUEST["n"])) { $n = $_REQUEST["n"]; } # northing
if (isset($_REQUEST["h"])) { $h = $_REQUEST["h"]; } # height
$sfradio = "single";
if (isset($_REQUEST["sfradio"])) { $sfradio = $_REQUEST["sfradio"]; } # file or single point
$format = "TXT";
if (isset($_REQUEST["format"])) { $format = $_REQUEST["format"]; } # TXT/KML/GPX
if (isset($_FILES["fname"]) && strlen($_FILES["fname"]["tmp_name"])) {
	$fname = $_FILES["fname"]["tmp_name"];
}
# file headers
$d = date("Y-m-d") . "T" . date("H:i:s");
switch ($format) {
	case "GPX":
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n" .
		"<gpx xmlns=\"http://www.topografix.com/GPX/1/1\" creator=\"etrs2eov\" version=\"1.1\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd\">\n";
		echo "<metadata><link href=\"www.agt.bme.hu\"><text>BME AFGT</text></link><time>$d</time></metadata>\n";
		break;
	case "KML":
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
			"<kml xmlns=\"http://www.opengis.net/kml/2.2\" xmlns:gx=\"http://www.google.com/kml/ext/2.2\" xmlns:kml=\"http://www.opengis.net/kml/2.2\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n" .
			"<Document><name>etrs2eov</name>\n" . 
			"<Style id=\"waypoint_n\"><IconStyle><Icon><href>http://maps.google.com/mapfiles/kml/pal4/icon61.png</href></Icon></IconStyle></Style>\n" . 
			"<StyleMap id=\"waypoint\"><Pair><key>normal</key><styleUrl>#waypoint_n</styleUrl></Pair><Pair><key>highlight</key><styleUrl>#waypoint_h</styleUrl></Pair></StyleMap>\n" . 
			"<Style id=\"waypoint_h\"><IconStyle><scale>1.2</scale><Icon><href>http://maps.google.com/mapfiles/kml/pal4/icon61.png</href></Icon></IconStyle></Style>\n" . 
			"<Folder><name>Waypoints</name>\n";
		break;
	default:
}
if ($sfradio == "single" && isset($e) && isset($n)) {
	if (isset($h) && strlen(trim($h))) {
		$w = single($e, $n, $h);
	} else {
		$w = single($e, $n, -500.0);
	}
	if ($w == "ERROR") {
		echo $w;
		return;
	}
	$ww = preg_split("/ /", $w);
	switch ($format) {
		case "GPX":
				echo "<wpt lat=\"" . $ww[1] . "\" lon=\"" . $ww[0];
				if (count($ww) == 3) {echo "\" ele=\"" . $ww[2];}
				echo "\"><time>$d</time><name>1</name><cmt>etrs2eov</cmt>" .
					"<desc>etrs2eov</desc><sym>Waypoint</sym></wpt>\n";
				break;
			case "KML":
				$www=$ww[0] . "," . $ww[1];
				if (count($ww) == 3) {$www = $www . "," . $ww[2];}
				echo "<Placemark><name>1</name>" .
					"<description>etrs2eov</description>" .
					"<TimeStamp><when>$d</when></TimeStamp>" .
					"<styleUrl>#waypoint</styleUrl>i". 
					"<Point><coordinates>" . $www .
					"</coordinates></Point>" .
					"</Placemark>\n";
				break;
			default:
				echo "1 " . $w . "\n";
	}
} elseif (isset($fname)) {
	# get coordinate column positions
	if (isset($_REQUEST["c1"])) {
		$c1 = $_REQUEST["c1"];
	} else {
		$c1 = 1;
	}
	if (isset($_REQUEST["c2"])) {
		$c2 = $_REQUEST["c2"];
	} else {
		$c2 = 2;
	}
	if (isset($_REQUEST["c3"])) {
		$c3 = $_REQUEST["c3"];
	} else {
		$c3 = 3;
	}
	# transform uploaded coords
	$ff = fopen($fname, "r");
	$n = 0;
	while (! feof($ff)) {
		@$buf = fgets($ff);
		$n++;
		if ($n > $MAXROW) { break; }
		if ($buf) {
			if (preg_match("/^#/", $buf)) {
				# skip comment
				continue;
			}
			$buf = trim($buf);
			$parts = preg_split("/[\s;]/", $buf);
			if (count($parts) < 3) {
				# few fields in line, skip
				echo "ERROR: " . $buf . " # few fields\n";
				continue;
			}
			if (strlen($parts[$c1]) == 0 || strlen($parts[$c2]) == 0) {
				echo "ERROR: " . $buf . " # empty coordinate or double separator\n";
				continue;
			}
			if (isset($parts[$c3]) && strlen($parts[$c3])) {
				$w =  single($parts[$c1], $parts[$c2], $parts[$c3]);
			} else {
				# no elevation given
				$w =  single($parts[$c1], $parts[$c2], -500.0);
			}

			if ($w == "ERROR") {
				echo "ERROR: " . $buf . " # can convert, out of range?\n";
				continue;	# skip point
			}
			$ww = preg_split("/ /", $w);
			switch ($format) {
				case "GPX":
					echo "<wpt lat=\"" . $ww[1] . "\" lon=\"" . $ww[0];
					if (count($ww) == 3) {echo "\" ele=\"" . $ww[2];}
					echo "\"><time>$d</time><name>" . $parts[0] . "</name><cmt>etrs2eov</cmt><desc>etrs2eov</desc><sym>Waypoint</sym></wpt>\n";
					break;
				case "KML":
					echo "<Placemark><name>" . $parts[0] . "</name><description>etrs2eov</description><TimeStamp><when>$d</when></TimeStamp><styleUrl>#waypoint</styleUrl><Point><coordinates>";
					echo $ww[0] . "," . $ww[1];
					if (count($ww) == 3) {echo "," . $ww[2];}
					echo "</coordinates></Point></Placemark>\n";
					break;
				default:
					for($i = 0; $i < count($parts); $i++) {
						if ($i == $c1) { echo $ww[0] . " ";}
						elseif ($i == $c2) { echo $ww[1] . " ";}
						elseif ($i == $c3) { echo $ww[2] . " ";}
						else { echo $parts[$i] . " "; }
					}
					echo "\n";
			}
		}
	}
	fclose($ff);
}
switch ($format) {
	case "GPX":
		echo "</gpx>";
		break;
	case "KML":
		echo "</Folder></Document></kml>";
		break;
	default:
		break;
}
?>
