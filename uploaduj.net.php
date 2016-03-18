<?php
#########################################################################################################
#                                         System NO®©A by st-A-ch                                       #
################################## uploaduj.net skan by MySql database #################### v.1.0 beta ##
/*
usage:
	CRON by mysql table item	--> system('wget -O /dev/null /uploadnet.php?test=true');
	CRON by custom queriess		--> system('wget -O /dev/null /uploadnet.php?test=custom queries');
	WEB user API show		--> /uploadnet.php?show=true
	SEARCH manual page		--> /uploadnet.php
*/
parse_str($_SERVER['QUERY_STRING']);
if ($ShowSource == 'code'){show_source(__FILE__); die();}

###################################### MYSQL DATABASE CONFIG #######################################
$hostname = '***** mysql host name *****';
$username = '***** mysql user name *****';
$password = '***** mysql password *****';
$database = '***** mysql database name *****';
$MediaList = '***** media list table name *****';	//struktura postawowa: id (auto_increment), name (text), word (text), data (data time)...
$UploadujLog = '***** data log table name *****';	//struktura postawowa: id (text), url (text), title (text), ready (int), data (data time)...

$head = "<html>\n\t<head>\n\t<meta charset='utf-8'>\n<link href='favicon.png' rel='icon' type='image/x-icon'/>\n\t</head>\n<body>";
################################# unetAPI CLASS ##############################################
class unetAPI {		// for login unetAPI data e-mail uploaduj.net admin

    private $gdataURL = 'http://uploaduj.net/api/';
    private $useragent = '***** API agentname *****';
    private $username = '***** API username *****';
    private $password = '***** API userpass *****';

public function getFile($title) {
	global $multi;
        $json = $this->getURL( $this->gdataURL.'getFile.php?'."multi=true&title=".urlencode($title) );
        return json_decode($json);
}

private function getURL($url) {

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username.':'.$this->password);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
################################# TEST BY CRON ##############################################
if ($test) {
	if ($test != 'true') {$q = $test;} else {			//loading queries from CRON or database
		$db_handle = mysql_connect($hostname, $username, $password);
		$db_found = mysql_select_db($database, $db_handle);
			if ($db_found) {
				$SQL  = "SELECT * FROM $MediaList ORDER BY data ASC LIMIT 1";     // read last queries from database! 
				$result = mysql_query($SQL);
				while ($row = mysql_fetch_array($result)) {
					$tm = date("Y-m-d H:i");
					$id = $row["id"];
   					$SQL2 = "UPDATE $MediaList SET data='$tm' WHERE id='$id'";
					$result2 = mysql_query($SQL2);
 					if ($row["word"]) {
  						 $q = $row["word"];
  					} else {
  						$q = $row["name"];
  					}
  				}
			}
		mysql_close($db_handle);
	}
}
################################# POST COOPYRIGHT ###########################################
if ($del AND $url) {
	$autor ='***** autor info *****';	// Autor muzyki - wlasciciel praw autorskich
	$adres ='**** adress info *****';	// adres korespondencyjny zglaszającego
	$mail ='***** email info *****';	// adres email zglaszającego
	$tel ='***** phone info *****';		// telefon kontaktowy zglaszajacego
	$msg ='***** msg info *****';		// wiadomość do wlasciciela usuwanego pliku - np.: Naruszenie prawa autorskiego
	
	$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://uploaduj.net/report-abuse/".$del."/");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,
        "url=".$url."&ip=".$_SERVER['REMOTE_ADDR']."&time=".date("Y-m-d H:i")."&temat=1&prawa=1&regulamin=1&ver=1&ver_code=976431&wiadomosc=".$msg.
        "&dane=".$autor."&mail=".$mail."&dane2=".$adres."&tel=".$tel);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec($ch);
		curl_close($ch);
		
	if ($server_output) {
		$db_handle = mysql_connect($hostname, $username, $password);
		$db_found = mysql_select_db($database, $db_handle);
			if ($db_found) {
				$SQL = "UPDATE $UploadujLog SET ready='2' WHERE url='$url'";
				$result = mysql_query($SQL);
			}
		mysql_close($db_handle);
		die( $set.' removed');
	}
}
############################ EXCLUDE NON COOPYRIGHT ITEM #######################################
if ($set) {
$db_handle = mysql_connect($hostname, $username, $password);
$db_found = mysql_select_db($database, $db_handle);
	if ($db_found) {
		$SQL = "UPDATE $UploadujLog SET ready='3' WHERE id='$set'";
		$result = mysql_query($SQL);
	}
	mysql_close($db_handle);
	die( $set.' updated');
}
################################ SHOW NEW COLLECTED ITEM #######################################
if ($show) {
	echo $head."<iframe name='pop'></iframe>
		<style>
			body {text-align:center;}
			iframe {height:30px; width:160px; border:none; float:right;}
			form {line-height:.7em; display:inline;}
			.results {margin:0px auto; max-width:800px; border:1px solid gray; border-bottom:none; min-width:600px;}
			.results td {text-align:right; border-bottom:1px solid gray; cursor:pointer; cursor:hands;}
			table a {text-decoration:none;}
		</style>
		<script>
			function reload(x,y) {
				setTimeout(function () { document.getElementById(x).style.display = 'none'; }, 100);
				window.open('?del='+ x + 'url=' +y, 'pop');
			}
			function remove_line(x){
				setTimeout(function () { document.getElementById(x).style.display = 'none'; }, 100);
				window.open('?set='+ x, 'pop');
			}
		</script><table class='results' cellpadding='5' cellspacing='0'>";
	$db_handle = mysql_connect($hostname, $username, $password);
	$db_found = mysql_select_db($database, $db_handle);
	$query  = "SELECT * FROM $UploadujLog WHERE ready=0 ORDER BY data DESC";
	$result = mysql_query($query)
  or die("Query failed");
  	while ($row = mysql_fetch_array($result)) {$r++;		//print new database records
  		if ($row["url"] != 'SEARCH_FRAZE_OVERLOAD') {$copy = "<a href='?del=".$row["id"]."&url=".$row["url"]."' target='pop'><button type='submit' onClick='reload(\"".$row["id"]."\")'> © </button></a>";} else {$copy = "";}
 			echo "<tr id='".$row["id"]."'><td><a href='".$row["url"]."'>".$row["title"]."</a></td><td>".$copy."</td><td><span onClick=\"javascript:remove_line('".$row["id"]."');\"><button>USUŃ</button></span></td></tr>";
  	}
  	if (!$r) {echo "<tr><td style='text-align:center;'>Brak nowych rekordów</td><td><form action='' method='get'><input type='text' name='q' value='impres'><button type='submit' > TEST </button></form></td></tr>";}
	mysql_close($db_handle);
	die( $set.'</table><br /></body></html>');
}
################################ SEARCH ITEM FROM SERWER #######################################
if ($q) {
	echo $head; 
	$unetAPI = new unetAPI();
	$results = $unetAPI->getFile($q);
	$val = count($results->enity);
	if($val > 0){
		$db_handle = mysql_connect($hostname, $username, $password);
		$db_found = mysql_select_db($database, $db_handle);
			if ($db_found) {
				for($x = 0; $x < $val; $x++) {		//request as value
					$result = mysql_query("SELECT 1 FROM $UploadujLog WHERE url='".$results->enity[$x]->link."' LIMIT 1");
					if (!mysql_fetch_row($result)) {		//add new record
						$SQL = "INSERT INTO $UploadujLog (url, title, id, ready, data) VALUES ('".$results->enity[$x]->link."', '".$results->enity[$x]->nazwa."', '".$results->enity[$x]->idn."', '0', '".date("Y-m-d H:i:s")."')";
						$result = mysql_query($SQL); $y++;
					}
				}
				if ($x-$y > 95) {		//overload test MSG --> przy setce oznaczonych moze zaistniec sytuacja iż bedą tylko nie nasze wyniki - info by lepiej dobrać słowa kluczowe - aktualnie brak stronicowania.
					$SQL = "UPDATE $UploadujLog SET ready='0', title='<b style=\"color:red\">WARNING!!! SEARCH QUERIES -[".strtoupper($q)."]- OVERLOAD</b>' WHERE url='SEARCH_FRAZE_OVERLOAD'";
					$result = mysql_query($SQL);		//w tablicy musi być odpowiedni rekord z url = SEARCH_FRAZE_OVERLOAD i ready = 3 by to zadziłało :)
				}
		}
	mysql_close($db_handle);
}
	if (!$test) {echo "<script>window.location.assign('?show=1')</script></body></html>";} else {echo $q;}		//redirect to show=true for display record
} else {
	echo $head."<form action='' method='get'>Upload.Net Search: <input type='text' name='q' value='impres'><button type='submit' > TEST </button></form></body></html>";
}

?>
