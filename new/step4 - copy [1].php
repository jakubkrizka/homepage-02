<html>
	<head>
		<title>Upload - Step 3</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<link rel="stylesheet" href="lib/jquery/jquery-ui.css">
		<script src="lib/jquery/jquery-1.11.1.min.js"></script>
		<script src="lib/jquery/jquery-ui.js"></script>
		<script src='lib/jquery/autosizeinput.js'></script>
		<script src='lib/jquery/jquery.autosize.js'></script>
		<style type="text/css">
			body,td,th {
				font-family: sans-serif;
				font-size: 9pt;
			}
			.ui-autocomplete {
				width: 30px;
				overflow-y: hidden;
				overflow-x: hidden;
			}
			textarea {
				-webkit-transition: height 0.6s;
				-moz-transition: height 0.6s;
				transition: height 0.6s;
			}
			input {
				text-align: center;
				-webkit-transition: width 0.6s;
				-moz-transition: width 0.6s;
				transition: width 0.6s;
			}
			#nazev {
				width: 90px;
				min-width: 90px;
				text-align: left;
			}
			input#encoding {
				width: 65px;
			}
			input#format {
				width: 80px;
			}
			input#bitrate {
				width: 80px;
			}
			input#rozliseni {
				width: 80px;
			}
			input#fps {
				width: 55px;
			}
			input#jazyk {
				width: 35px;
			}
			input#kanaly {
				min-width: 10px;
			}
		</style>
		<script>

		</script>
	</head>
	<body>
<!-------------------------------------------------------------------------------------
getID3() by James Heinrich <info@getid3.org>
available at http://getid3.sourceforge.net
or http://www.getid3.org
also https://github.com/JamesHeinrich/getID3
getID3() is released under multiple licenses. You may choose
from the following licenses, and use getID3 according to the
terms of the license most suitable to your project.
GNU GPL: https://gnu.org/licenses/gpl.html (v3)
https://gnu.org/licenses/old-licenses/gpl-2.0.html (v2)
https://gnu.org/licenses/old-licenses/gpl-1.0.html (v1)
GNU LGPL: https://gnu.org/licenses/lgpl.html (v3)
Mozilla MPL: http://www.mozilla.org/MPL/2.0/ (v2)
getID3 Commercial License: http://getid3.org/#gCL (payment required)
Copies of each of the above licenses are included in the 'licenses'
directory of the getID3 distribution.
---------------------------------------------------------------------------------------
jQuery.Autosize.Input
https://github.com/MartinF/jQuery.Autosize.Input
The MIT License (MIT)
Copyright (c) 2013 Dynamo
Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:
The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
-------------------------------------------------------------------------------------->

<?php
require_once('lib/getid3/getid3.php');
$getID3 = new getID3;

$FullFileName = $_POST["videoname"];
$ThisFileInfo = $getID3->analyze($FullFileName);
getid3_lib::CopyTagsToComments($ThisFileInfo);
$ThisFileInfo->$encoding="UTF-8";



include 'lib/csfd/global.php';


// FILM

$id = isset($_POST['csfdid']) ? intval($_POST['csfdid']) : 0;
$sessionid = isset($_POST['sessionid']) ? $_POST['sessionid'] : "";
$user = isset($_POST['user']) ? $_POST['user'] : "";
$password = isset($_POST['password']) ? $_POST['password'] : "";

if(!$id){ exit; }

logAction('FILM: '.$id);
$film_html = getUrl('http://www.csfd.cz/film/'.$id, $sessionid, $user);
$html = str_get_html($film_html);

// INFO

$info = $html->find('.info', 0);
$nazev_cz = strip_tags( trim( $info->find('h1', 0)->innertext ) );
$nazev_orig = trim( @$info->find('h3', 0)->innertext );
$zanr = $info->find('.genre', 0)->innertext;
$zeme = $info->find('.origin', 0)->innertext;
$zeme = str_replace("&#039;", "'", $zeme);

foreach($info->find('div') as $tvurci_html){
  $tvurci_array = csfdFilmTvurci($tvurci_html);
  $tvurci_typ = $tvurci_array['typ'];
  $tvurci[$tvurci_typ] = $tvurci_array['tvurci'];
}

$rating = csfdFilmRating( $html->find('#rating .average', 0)->innertext );

$obsah = trim( strip_tags( @$html->find('#plots li', 0)->plaintext ) );
$obsah = str_replace('&nbsp;', ' ', $obsah);
$obsah = str_replace('&', '&amp;', $obsah);

$obrazek = trim( $html->find('#poster img', 0)->src );

$trailer_class = $html->find('.videos', 0)->class;
$trailer = strstr($trailer_class, "disabled") ? 0 : 1;

$galerie_class = $html->find('.photos', 0)->class;
$galerie = strstr($galerie_class, "disabled") ? 0 : 1;

// KOMENTARE

$komentare = null;
$i=0;
$komentare_html = $html->find('.ui-posts-list', 0);
if($komentare_html){
  foreach($komentare_html->find('li') as $komentar_html){  $i++;
    $komentar_dom = str_get_html($komentar_html);
    $komentare[$i]['jmeno'] = $komentar_dom->find('a', 0)->plaintext;
    $komentare[$i]['id'] = csfdId($komentar_dom->find('a', 0)->href);
    $text = $komentar_dom->find('p.post', 0)->plaintext;
    //$text = htmlspecialchars($text);
    $text = str_replace("&", "&amp;", $text);
    $komentare[$i]['text'] = $text;

    $rating_e = $komentar_dom->find('.rating', 0);
    if($rating_e){
      $rating_star = intval( strlen( @$komentar_dom->find('img.rating', 0)->alt ) );
    }else{
      $rating_star = null;
    }
    $komentare[$i]['rating'] = $rating_star;
  }
}

// TOKEN
$token = @$html->find("#my-rating input[name=_token_]", 0)->value;

// DELETE TOKEN
$delete_link = @$html->find("#my-rating .private", 0)->href;
preg_match("@token=(.+)&@", $delete_link, $delete_parts);
$delete_token = isset($delete_parts[1]) ? $delete_parts[1] : null;

// MY RATING
$mystars = $html->find("#my-rating .my-rating img");
$myrating = count($mystars);
if($myrating==0){
  $isodpad = @$html->find("#my-rating .rating", 0)->plaintext;
  if($isodpad=="odpad!"){$myrating=0;}else{$myrating='';}
}

// LOGIN
$login = @csfdId( @$html->find("#user-menu a", 0).href );

// relogin
if(!$login && $sessionid && $password){
  $logintext = file_get_contents($dirpath."login.php?user=$user&password=$password");
  $loginxml = new SimpleXMLElement($logintext);
  $sessionid = (string) $loginxml->sessionid;
  header("location:$dirpath"."film.php?id=$id&user=$user&password=$password&sessionid=$sessionid");
  exit;
}




echo '<form method="post" action="mysql.php">';
echo '<table border="1" cellspacing="0" cellpadding="3">';
echo '<tr><th colspan="12">'.htmlentities($ThisFileInfo['filenamepath']).'</th></tr>';
echo '<tr><th>Název (rok)</th><th>Velikost</th><th>Čas</th><th>Encoding</th><th>Format</th><th>Bitrate</th><th colspan="2">Video</th><th colspan="2"><a href="language.html">Audio</a></th><th>Kvalita</th><th>Kategorie</th></tr>';
		echo '<tr>';
		echo '<td><input value="'.htmlentities(!empty($ThisFileInfo['comments']['title'])?implode('<br>', $ThisFileInfo['comments']['title']) : chr(160)).'" name="name" id="nazev" placeholder="Metadata" data-autosize-input="{ \'space\': 40 }"/></td>';
		echo '<td><input value="'.htmlentities(!empty($ThisFileInfo['filesize'])?round($ThisFileInfo['filesize'] / 1000000).' MB' : chr(160)).'" name="size" id="velikost" disabled/></td>';
		echo '<td><input value="'.htmlentities(!empty($ThisFileInfo['playtime_string'])?$ThisFileInfo['playtime_string'] : chr(160)).'" name="time" id="cas" disabled/></td>';
		echo '<td><input value="'.htmlentities($ThisFileInfo['encoding']).'" name="encoding" id="encoding" disabled/></td>';
		echo '<td><input value="'.htmlentities($ThisFileInfo['fileformat']).'" name="format" id="format" disabled/></td>';
		echo '<td><input value="'.htmlentities(!empty($ThisFileInfo['bitrate'])?round($ThisFileInfo['bitrate'] / 1000).' kbps' : chr(160)).'" name="bitrate" id="bitrate" disabled/></td>';
		echo '<td><input value="'.$ThisFileInfo['video']['resolution_x'].'x'.$ThisFileInfo['video']['resolution_y'].'" name="rozliseni" id="rozliseni" disabled/></td>';
		echo '<td><input value="'.htmlentities(!empty($ThisFileInfo['video']['frame_rate'])?round($ThisFileInfo['video']['frame_rate']). 'fps': chr(160)).'" name="fps" id="fps" disabled/></td>';
		echo '<td><input value="'.$ThisFileInfo['audio']['language'].'" name="language" id="jazyk" placeholder="Code"/></td>';
		echo '<td><input value="'.$ThisFileInfo['audio']['channelmode'].'@'.$ThisFileInfo['audio']['channels'].'" name="kanaly" id="kanaly" disabled/></td>';
		echo '<td><select id="kvalita" name="quality"><option value="LQ">LQ</option><option value="HQ">HQ</option><option value="UQ">UQ</option></select></td>';
		echo '<td><select id="kategorie" name="category"><option value="Filmy">Filmy</option><option value="Seriály">Seriály</option></select></td>';
echo '</tr>';
echo '</table>';
echo '<table border="1" cellspacing="0" cellpadding="3">';
echo '<tr>';
echo '<th>CSFD - info</th>';
echo '<th>Rok</th>';
echo '<th>Hodnocení</th>';
echo '</tr>';
echo '<tr>';
echo '<td><input value="'.$nazev_cz.'" name="nazev_cz" id="nazev_cz"/></td>';
echo '<td><input value="'.$zeme.'" name="nazev_cz" id="nazev_cz"/></td>';
echo '<td><input value="'.$rating.'%" name="nazev_cz" id="nazev_cz"/></td>';
echo '</tr>';
echo '</table>';
echo '<br>';
echo '<textarea placeholder="Popis" name"obsah" id="obsah" rows="15" cols="55">'.$obsah.'</textarea>';
echo '<br>';
echo '<br>';
echo '<button type="submit">Zapsat</button>';
echo '</form>';
echo '<br>';




echo '<br>';
			echo $nazev_orig;
echo '<br>';
echo '<br>';
echo '<br>';
			echo $zanr;
echo '<br>';


?>

</body>
<script>
		$(function() {
			var availableTags = [
				"ab",
				"aa",
				"af",
				"sq",
				"am",
				"ar",
				"an",
				"hy",
				"as",
				"ay",
				"az",
				"ba",
				"eu",
				"bn",
				"dz",
				"bh",
				"bi",
				"br",
				"bg",
				"my",
				"be",
				"km",
				"ca",
				"zh",
				"co",
				"hr",
				"cs",
				"da",
				"nl",
				"en",
				"eo",
				"et",
				"fo",
				"fa",
				"fj",
				"fi",
				"fr",
				"fy",
				"gl",
				"gd",
				"gv",
				"ka",
				"de",
				"el",
				"kl",
				"gn",
				"gu",
				"ht",
				"ha",
				"he",
				"iw",
				"hi",
				"hu",
				"is",
				"io",
				"id",
				"in",
				"ia",
				"ie",
				"iu",
				"ik",
				"ga",
				"it",
				"ja",
				"jv",
				"kn",
				"ks",
				"kk",
				"rw",
				"ky",
				"rn",
				"ko",
				"ku",
				"lo",
				"la",
				"lv",
				"li",
				"ln",
				"lt",
				"mk",
				"mg",
				"ms",
				"ml",
				"mt",
				"mi",
				"mr",
				"mo",
				"mn",
				"na",
				"ne",
				"no",
				"oc",
				"or",
				"om",
				"ps",
				"pl",
				"pt",
				"pa",
				"qu",
				"rm",
				"ro",
				"ru",
				"sm",
				"sg",
				"sa",
				"sr",
				"sh",
				"st",
				"tn",
				"sn",
				"ii",
				"sd",
				"si",
				"ss",
				"sk",
				"sl",
				"so",
				"es",
				"su",
				"sw",
				"sv",
				"tl",
				"tg",
				"ta",
				"tt",
				"te",
				"th",
				"bo",
				"ti",
				"to",
				"ts",
				"tr",
				"tk",
				"tw",
				"ug",
				"uk",
				"ur",
				"uz",
				"vi",
				"vo",
				"wa",
				"cy",
				"wo",
				"xh",
				"yi",
				"ji",
				"yo",
				"zu"
			];
			$( "#jazyk" ).autocomplete({
				source: availableTags
			});
			$(nazev).autosizeInput();
$(cas).autosizeInput();
$(velikost).autosizeInput();
			$(kanaly).autosizeInput();
			$(document).ready(function(){
				$('textarea').autosize();
			});
		});
		</script>
</html>



