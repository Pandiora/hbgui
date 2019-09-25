<?php
header('Content-type: application/json'); // used for ajax posts

// Includes and base path to this dir
$base = __DIR__.DIRECTORY_SEPARATOR;
require_once($base.'db_query.php');
require_once($base.'getid3/getid3.php');
require_once($base.'php-ffmpeg/autoload.php');

// Let's fix some very basic shit - automatically adjusts
setTimezone('Europe/Berlin');

// Maybe database needs to be filled
initDatabase();

// check if we got an AJAX Request
if( isset($_POST['fn']) ){ 
	if ( function_exists($_POST['fn']) ){

		$args = (isset($_POST['args'])) ? $_POST['args'] : null;
		$args = (strpos($args, ',')) ? explode(',', $args) : array($args);

		echo json_encode(call_user_func_array($_POST['fn'], $args));
	}
}

// check if we got a request from shell
if( isset($argv) ){
	error_log($argv[2]);
	if ( function_exists($argv[2]) ){
		echo json_encode(call_user_func($argv[2], $argv[3]));

		// ToDo: make sure only one background trask runs at once
	}
}

/*****************************************************************************************************************
*
* M E N U S
*
*****************************************************************************************************************/



function fetchMenus(){

	$sql = "SELECT * FROM menus";
	$conn = new DBConnector() or die("cannot open the database");
	$result = $conn->run($sql)->fetchAll(); // array
	$conn = null;

	$menus_html = '<div class="btn-group btn-group-toggle" id="navbar" data-toggle="buttons">';

	foreach($result as $row){

		$menus_html .= '
		<label class="btn btn-secondary" id="nav-'.strtolower($row['name']).'">
			<input type="radio" name="options" autocomplete="off"> '.$row['name'].'
		</label>';

	}

	return $menus_html.'</div>';
}




/*****************************************************************************************************************
*
* S E T T I N G S
*
*****************************************************************************************************************/



function initDatabase(){

	$base = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
	$size = file_exists($base.'db/totallynota.db') ? filesize($base.'db/totallynota.db') : 0;

	if($size > 0) return;
	createDatabase();
}




function createDatabase(){

	// SO YO https://stackoverflow.com/questions/147821/loading-sql-files-from-within-php
	include_once 'SqlScriptParser.php';

	$base = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
	$pdo = new DBConnector() or die("cannot open the database");
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$parser = new SqlScriptParser();
	$sqlStatements = $parser->parse($base.'db/totallynota.db.sql');

	foreach ($sqlStatements as $statement) {
		try {
	        $stmt = $pdo->prepare($statement);
	        $affectedRows = $stmt->execute();
		} catch(Exception $e){
			error_log('
				Statement: '.$statement.'
				Error: '.$e.'
			');
			return;
		}
	}

	$pdo = null;

	// update the settings with actual paths
	updateSettings(Array(
		["id" => 4, "val" => realpath($base."config/hevc-10bit-default.json")],
		["id" => 5, "val" => realpath($base."processing/trash/")],
		["id" => 6, "val" => realpath($base."processing/temp/")],
		["id" => 16, "val" => realpath($base."processing/broken/")]
	));

}




function updateSettings($data){

	$sql = 'UPDATE `settings` SET `val1` = ? WHERE `id` = ?';
	$conn = new DBConnector() or die("cannot open the database");

	foreach($data as $row){ 
		$conn->run($sql, [$row['val'],$row['id']]); 
	}
	$conn = null;

	return 'database updated';
}



function resetSettings($data){

	// Delete Database file first
	$base = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
	unlink($base.'db/totallynota.db');
	createDatabase();

	return 'database updated';
}




function fetchSettingsData($name = false, $column = false){

	/*
		Possible parameters ~ 

		<names>:
		watch_folders, watch_interval, destination_folder, config_path
		src_file_extensions, gui_auto_refresh, exclude_by_size, cpu_threads, 
		auto_batch, exclude_samples, move_to_temp
		OR all if name isn't set	

		<column>:
		id,sid,name,val1,val2,type,label,placeholder
		OR all if column isn't set

	*/

	$sql = "SELECT * FROM settings ORDER BY sid ASC";
	$conn = new DBConnector() or die("cannot open the database");
	$result = $conn->run($sql)->fetchAll();

	if(!$name) return $result;

	foreach($result as $row){
		if($row['name'] === $name){
			if($column) return $row[$column];
			return $row;
		}
	}

}



function searchSettingsByName($set, $key, $ret = 'val1'){
	foreach($set as $st){
		if($st['name'] === $key) return $st[$ret];
	}
}


function fetchMachineInfo(){

	$data = array();
	$data[0] = round(intval(file_get_contents('/sys/class/thermal/thermal_zone0/temp'))/1000);
	$data[1] = round(intval(file_get_contents('/sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq'))/1000000,2);
	$data[2] = intval(shell_exec('sudo smartctl -a /dev/sda | grep Temp | cut -d" " -f 31,37'));
	$data[3] = intval(shell_exec('sudo smartctl -a /dev/sdb | grep Temp | cut -d" " -f 31,37'));

	return $data;
}


function fetchSettings(){

	$sql = "SELECT * FROM settings ORDER BY sid ASC";
	$conn = new DBConnector() or die("cannot open the database");
	$result = $conn->run($sql)->fetchAll(); // array
	$conn = null;

	$settings_html = ''; $int = 0;
	$save_button = '	
	<div class="row">
		<div class="col-md-auto"><button type="button" id="saveme" class="btn-lg btn-block btn-primary">Save settings</button></div>
		<div class="col-md-auto"><button type="button" id="killme" class="btn-lg btn-block btn-danger">Recreate database</button></div>
	</div>';


	foreach($result as $row){

		$int++;
		$html_str = '<div class="form-group row">';
		$html_id = 'settingsin'.$int;

		if($row['type'] === 'switch'){
			$html_str .= '						
			<div class="col-sm-2"></div>
			<div class="col-sm-10">
				<div class="custom-control custom-switch">
				  <input '.(($row['val1'])?'checked':'').' type="checkbox" class="custom-control-input" id="'.$html_id.'" data-type="'.$row['type'].'" data-dbid="'.$row['id'].'">
				  <label class="custom-control-label" for="'.$html_id.'">'.$row['label'].'</label>
				</div>
			</div>';	
		} else {
			$html_str .= '
			<label for="'.$html_id.'" class="col-sm-2 col-form-label">'.$row['label'].'</label>
			<div class="col-sm-10">
				<input type="'.(($row['type']==='tags')?'text':$row['type']).'" class="form-control" value="'.$row['val1'].'" placeholder="'.$row['placeholder'].'" id="'.$html_id.'" 
				data-type="'.$row['type'].'" data-dbid="'.$row['id'].'"'.(($row['type']==='tags')?' data-role="tagsinput"':'').'>
			</div>';
		}

		$settings_html .= $html_str.'</div>';
	}

	return '<div class="container" id="content"><form>'.$settings_html.$save_button.'</form></div>';

}



/*****************************************************************************************************************
*
* Q U E U E
*
*****************************************************************************************************************/


function fetchQueue(){


	return '';
}



/*****************************************************************************************************************
*
* F I L E S
*
*****************************************************************************************************************/


function fetchFiles($relaunch = false){
	// ToDo: Only return files which are not broken
	$start = microtime(true);
	$sql = "SELECT * FROM files";
	$conn = new DBConnector() or die("cannot open the database");
	$result = $conn->run($sql)->fetchAll();

	if($relaunch || empty($result)){
		scanFileSystem();
		return fetchFiles();
	}

	$output = '
		<div class="container-fluid h-100"  id="content">
		<div class="row mx-2 py-2" id="files-controls">

			<button type="button" class="btn btn-secondary btn-sm mx-1" id="rescanfiles">Rescan folders</button>
			<button type="button" class="btn btn-secondary btn-sm mx-1">Remove</button>
			<button type="button" class="btn btn-secondary btn-sm mx-1">Add to queue</button>

			<input class="btn-sm mx-1" type="search" placeholder="Search" id="search" />
		</div>

		<div class="row mx-2 scrollme">
			<table class="table table-dark table-sm">
				<thead>
					<tr>
					<th scope="col"><input id="checkall" type="checkbox" /></th>
					<th scope="col">Status</th>
					<th scope="col">Time</th>
					<th scope="col">File</th>
					<th scope="col">Size</th>
					</tr>
				</thead>

				<tbody>';

	foreach ($result as $row){

		$file_time = strftime('%a %b %d %Y %H:%M:%S', $row['added']);
		$file_name = $row["file_name"];
		$file_size = human_filesize($row["file_size"]);
		$file_stat = getStatusTag( $row["file_status"] );

		$output .= '
			<tr>
				<td><input class="checkme" type="checkbox" /></td>
				<td>'.$file_stat.'</td>
				<td>'.$file_time.'</td>
				<td>'.$file_name.'</td>
				<td>'.$file_size.'</td>
			</tr>
		';

	}

	return $output.'</tbody></table></div></div>';
}



function getStatusTag($status = 10){

	$tag = '<span class="badge badge-';

	switch($status){
		case 10: $tag .= 'info">Scanned'; 		break; // watch folder scan found this file
		case 11: $tag .= 'success">Scanned'; 	break; // file meta-data got scanned
		case 20: $tag .= 'warning">Queued'; 	break; // file is queued for transcoding
		case 21: $tag .= 'warning">Working';	break; // file is being worked on
		case 30: $tag .= 'success">Transcoded';	break; // file got transcoded
		case 31: $tag .= 'success">Rescanned';	break; // tanscoded output file got rescanned
		case 32: $tag .= 'success">Done!';		break; // transcoding succesful
		case 33: $tag .= 'success">No Job';		break; // does not needs to be transcoded
		case 40: $tag .= 'danger">Broken'; 		break; // for broken files
		case 41: $tag .= 'danger">Failed'; 		break; // if transcoding failed
		case 42: $tag .= 'danger">Deleted'; 	break; // if file got deleted
		case 43: $tag .= 'danger">Aborted'; 	break; // queued item got cancelled manually
		case 44: $tag .= 'danger">Error'; 		break; // just an error tag for history
	}

	return $tag.'</span>';
}



function scanFileSystem(){

	// fetch needed informations and vars
	$s = fetchSettingsData();
	$paths = explode(',', searchSettingsByName($s,'watch_folders'));

	// ToDo: make sure folders are accessable by our script or find a way to run shell spawn with admin rights
	// add error to history if none of the paths can be accessed
	$files = fetchFolders($paths, searchSettingsByName($s,'src_file_extensions'));
	$stats = fetchFileInfo($files, 500);
	$exsiz = searchSettingsByName($s,'exclude_by_size')*1048576;
	$exsam = searchSettingsByName($s,'exclude_samples');
	$time  = time();

	// set up db
	$conn = new DBConnector() or die("cannot open the database");
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$conn->beginTransaction();
	$stmt = $conn->prepare('
		INSERT INTO files(commit_id,file_path,file_name,file_ext,file_dev,file_inode,file_status,file_size,added) 
		VALUES(?,?,?,?,?,?,?,?,?) 
		ON CONFLICT(file_inode)
		DO UPDATE SET commit_id=?, file_path=?, file_name=?, file_ext=?, file_status=?, file_size=?
	');

	// format data and prepare inserts
	for($i=0;$i<count($files);$i++){

		// ToDo: maybe add duplicate check
		$stat = explode('-', $stats[$i]);
		$size = round($stat[0],0); // do not parse as int - use round
		$path = pathinfo($files[$i]);

		// exclude samples if setting is set and exclude files < x
		if($exsam && strpos($path['basename'], 'sample')) continue;
		if($exsam > $size) continue;

        $stmt->execute([
        	// for inserts
        	$time, $path['dirname'], $path['basename'], $path['extension'], $stat[2], $stat[1], 10, $size, $time,
        	// for updates
        	$time, $path['dirname'], $path['basename'], $path['extension'], 10, $size
        ]);		
	}

	// finally insert all data
	$conn->commit();

	// using time as unique commit id to detect removed files - trigger will do the rest
	$conn->run('UPDATE files SET file_status = ? WHERE commit_id <> ?', [42, $time]);
	// ToDo: Log if file got deleted

	//  update history table with msg and finally close db
	$conn->run('INSERT INTO files_history(status,msg,added) VALUES(?,?,?)', [10, 'Watch folders succesfully scanned', $time]);	
	$conn = null;

	// start meta-scan for all files that didn't got meta-data
	getMetaData(null);

}



function getMetaData($arg){

	// prevent script from being executed multiple times
	// restart script from shell for long running background task
	if(checkProcess('functions.php', 'getMetaData')) return;
	if(!$arg) return shell_exec('php '.__DIR__.'/functions.php -- getMetaData test > /dev/null 2>&1 &');

	// since only shell can run this part of the scrpt, we need to create our own logfile
	ini_set("log_errors", 1);
	ini_set("error_log", __DIR__."/../php-error.log");

	// measure start
	$start = microtime(true);

	// Initialize getID3 engine and DB
	$getID3 = new getID3;
	$getID3->setOption(array('encoding' => "UTF-8"));
	$conn = new DBConnector() or die("cannot open the database");

	// kinda group by - just find out if there are entries missing the meta data
	$result = $conn->run('
		SELECT * 
		FROM files F
		LEFT JOIN files_meta M
		ON F.file_inode = M.fid
		WHERE M.fid IS NULL;
	')->fetchAll();

	// no more data needs to be fetched
	if(!$result) return postMetaData($start,0);

	// prepare huge insert
	$stmt = $conn->prepare('
		INSERT INTO files_meta(fid,video_codec,video_res_x,video_res_y,video_framerate,video_bitrate,audio_codec,audio_samplerate,
		audio_bitrate,audio_channels,playtime,bitrate,muxing_app,writing_app,encoder,created,added) 
		VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);
	');

	foreach($result as $res){

		$file = $res['file_path'].'/'.$res['file_name'];

		// get metadata by getID3 and store info
		$fi = $getID3->analyze($file, $res['file_size']);
		$fi = ($fi) ? $fi : 0;
		$err= ($fi && isset($fi['error'])) ? $fi['error'][0] : 1;

		// move probably broken files
		if($fi === 0 || $err !== 1) markFileRemoved($res[0], $file, $err, $conn);

		$vid = (isset($fi['video'])) ? $fi['video'] : 0;
		$aud = (isset($fi['audio'])) ? $fi['audio'] : 0;
		$tag = (isset($fi['tags'] )) ? $fi['tags'][array_key_first($fi['tags'])] : 0;

		$a = Array();
		$a[0]  = $res['file_inode'];
		$a[1]  = (isset($vid['dataformat'])) 		? $vid['dataformat'] 			: 0;
		$a[2]  = (isset($vid['resolution_x'])) 		? $vid['resolution_x'] 			: 0;
		$a[3]  = (isset($vid['resolution_y'])) 		? $vid['resolution_y'] 			: 0;
		$a[4]  = (isset($vid['frame_rate'])) 		? round($vid['frame_rate'],2) 	: 0;
		$a[5]  = (isset($vid['bitrate'])) 			? $vid['bitrate'] 				: 0;
		$a[6]  = (isset($aud['dataformat'])) 		? $aud['dataformat'] 			: 0;
		$a[7]  = (isset($aud['sample_rate'])) 		? $aud['sample_rate'] 			: 0;
		$a[8]  = (isset($aud['bitrate'])) 			? $aud['bitrate'] 				: 0;
		$a[9]  = (isset($aud['channels'])) 			? $aud['channels'] 				: 0;
		$a[10] = (isset($fi['playtime_seconds']))	? (int)$fi['playtime_seconds']	: 0;
		$a[11] = (isset($fi['bitrate'])) 			? (int)$fi['bitrate'] 			: 0;
		$a[12] = (isset($tag['muxingapp'])) 		? $tag['muxingapp'][0] 			: 0;
		$a[13] = (isset($tag['writingapp'])) 		? $tag['writingapp'][0] 		: 0;
		$a[14] = (isset($tag['encoder'])) 			? $tag['encoder'][0] 			: 0;
		$a[15] = (isset($tag['creation_time'])) 	? $tag['creation_time'][0] 		: 0;
		$a[16] = time();

		//error_log('<pre>'.htmlentities(print_r($arr, true), ENT_SUBSTITUTE).'</pre>');

		// insert meta-data only if there is no entry yet
	   	$stmt->execute($a);

	   	// if file got metadata and doesn't seems to be broken - update status to scanned
	   	if($fi !== 0 && ($err === 1 || array_sum(array_slice($a,1,count($a)-2)) > 0)){
	   		$conn->run('UPDATE files SET file_status = ? WHERE id = ?', [11, $res[0]]);
	   	}

	}
	// jump jump DNW
	postMetaData($start, count($result), $conn);
}



function postMetaData($start, $cnt, $conn){

	// end measuring - update history-table
	$str = 'Getting metadata for '.$cnt.' files took '.round((microtime(true) - $start)/60).'s'; 
	$conn->run('INSERT INTO files_history(status,msg,added) VALUES(?,?,?)', [11, $str, time()]);	

	if($cnt < 1) return;

	// additional search for broken files
	//searchBrokenFiles();

	// add files to queue if needed
	moveFileToQueue();

}



function moveFileToQueue(){

	// update all files which got the status 11 and meet the requirements
	// need to study php ffmpeg to find out

	return;
}




function searchBrokenFiles($file){

	// php ffmpeg cannot find binaries
	$options = [
	    'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
	    'ffprobe.binaries' => '/usr/bin/ffprobe',
	];

	$ffmpeg = FFMpeg\FFMpeg::create($options);
	$thumb = __DIR__.'/../frame.jpg';
	$tok = $cok = 0;

	// First attempt is to create a thumbnail
	if(file_exists($thumb)) unlink($thumb);

	try{
		$video = $ffmpeg->open($file);
		$video
		    ->filters()
		    ->resize(new FFMpeg\Coordinate\Dimension(320, 240))
		    ->synchronize();
		$video
		    ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(10))
		    ->save($thumb);
		// make sure file got created
		if(file_exists($thumb)) $tok = 1;
	} catch (Throwable $e){
		// catch specific errors to find out if the file is okay
		$tok = !preg_match('/undefined|Unable/', $e->getMessage());
	}

	// Second attempt is to probe the file
	$ffprobe = FFMpeg\FFProbe::create($options);
	$cok = $ffprobe->isValid($file) ? 1 : 0;
	$sum = (($tok+$cok) > 1) ? 1 : 0; // return 1 if 2 -> okay
	
	return $sum;
}



function markFileRemoved($id,$file,$msg, $conn){

	// ToDo: maybe add more info if needed
	//$msg = ($msg === 1) ? 'could not access file' : $msg;

	/* 	Errors:
		unable to determine file format
		Could not open "<file>" (!is_readable)
	*/

	// ToDo: scan the broken files again - with another tool

	$conn 		= new DBConnector() or die("cannot open the database");
	$isokay 	= searchBrokenFiles($file);
	
	if($isokay){
		$conn->run('UPDATE files SET file_status=? WHERE id=?', [11,$id]);
		$conn = null;
		return;
	}

	$arr 		= pathinfo($file);
	$set 		= fetchSettingsData();
	$del_broken = searchSettingsByName($set,'delete_broken');
	$mov_broken = searchSettingsByName($set,'move_broken');
	$new_file 	= $mov_broken.'/'.$arr['basename'];
	$mov_label	= $arr['filename'].' moved to broken folder';
	$del_label	= $arr['filename'].' got deleted';
	$not_moved  = $arr['filename'].' could not be moved and is damaged';

	// either delete or move file
	if($del_broken){

		$rlydothis = 0;
		if($rlydothis){
			unlink($file);
			$new_file = '';
		}

	} else 
	if($mov_broken){

		// additional check for file
		if(file_exists($file)){

			// another check if folder exists
			if (!file_exists($mov_broken)) mkdir($mov_broken, 0775, true);
			// rename & move file to 'broken' folder
			rename($file, $new_file);
			// make sure file rly got moved to destination
			if(!file_exists($new_file)){
				$mov_label = $not_moved;
				$new_file = $arr['dirname'];
			} else { $new_file = $mov_broken; }

		} else { $del_broken = 1; }
	}

	// Use status -> either broken or broken and deleted
	$status = ($del_broken) ? 42 : 40;
	$status_label = ($status === 40) ? $mov_label : $del_label;

	// Finally update/insert - add to history which files got broken and why -> error code
	$str = $conn->run('UPDATE files SET file_status = ?,file_path = ? WHERE id = ?', [$status, $new_file, $id]);
	$conn->run('INSERT INTO files_history(status,msg,added) VALUES(?,?,?)', [$status, $status_label, time()]);
	$conn = null;


	return;
}



function fetchFolders($paths, $ext){

	$files_arr = Array();
	$ext = join('|', explode(',', $ext));

	// Iterate watch-folders
	foreach($paths as $path){

		// recursive lookup
		$dir = new RecursiveDirectoryIterator($path);
		$ite = new RecursiveIteratorIterator($dir);
		$files = new RegexIterator($ite, "/^.*\.($ext)$/", RegexIterator::GET_MATCH);

		// push files to array
		foreach($files as $file){ array_push($files_arr, $file[0]); }
	}

	return $files_arr;

}



function fetchFileInfo($files, $chunkcnt){

	$stat_arr = Array();
	$chunks = array_chunk($files, $chunkcnt);

	// get inode, deviceid and real file-size - create a corresponding array
	for($i=0;$i<count($chunks);$i++){

		// pipe through escapeshellarg -> single quoted is the key
		$stat = join(' ', array_map('escapeshellarg', $chunks[$i]));
		// format output as %size%-%inode%-%deviceid%\n
		$stat = shell_exec('stat --format=%s-%i-%d '.$stat);
		// turn stat output into array (and merge with previous results)
		$stat_arr += array_merge($stat_arr, explode(PHP_EOL, trim($stat)));

	}

	return $stat_arr;
}



/*****************************************************************************************************************
*
* H I S T O R Y
*
*****************************************************************************************************************/


function fetchHistory(){


	$sql = "SELECT * FROM files_history ORDER BY added DESC";
	$conn = new DBConnector() or die("cannot open the database");
	$result = $conn->run($sql)->fetchAll();
	$output = '
		<div class="container-fluid h-100"  id="content">
		<div class="row mx-2 py-2" id="files-controls">

			<button type="button" class="btn btn-secondary btn-sm mx-1">Remove</button>

		</div>

		<div class="row mx-2 scrollme">
			<table class="table table-dark table-sm">
				<thead>
					<tr>
					<th scope="col"><input id="checkall" type="checkbox" /></th>
					<th scope="col">Status</th>
					<th scope="col">Time</th>
					<th scope="col">Message</th>
					</tr>
				</thead>

				<tbody>';

	foreach ($result as $row){

		$message = $row["msg"];
		$status = getStatusTag( $row["status"] );
		$time = strftime('%a %b %d %Y %H:%M:%S', $row['added']);

		$output .= '
			<tr>
				<td><input class="checkme" type="checkbox" /></td>
				<td>'.$status.'</td>
				<td>'.$time.'</td>
				<td>'.$message.'</td>
			</tr>
		';

	}

	return $output.'</tbody></table></div></div>';
}





/*****************************************************************************************************************
*
* M I S C E L L A N E O U S
*
*****************************************************************************************************************/

function checkForRunningJob($update = false){

	// ToDo: optimize checking for different functions

	// add different jobs to check for here
	$job1 = checkProcess('functions.php', 'getMetaData', 0);
	$job2 = checkProcess('test-loop.php', 'loopme', 0);
	$job3 = ''; //checkProcess('test-loop.php', 'loopme');
	$msg  = null;

	switch(true){
		case $job1: $msg = 'Fetching Metadata ...'; break;
		case $job2: $msg = 'Just testing ...'; 		break;
		case $job3: $msg = 'Transcode running ...'; break;
	}

	$html = '
		<button class="btn btn-secondary" type="button" id="jobstatus" disabled>
			<span class="spinner-border spinner-border-sm text-warning"></span>
			'.$msg.'
		</button>
	';

	$msg = (!$update && $msg) ? $html : $msg;

	return $msg;

}


// thx to chris on phpnet for this function:
// https://www.php.net/manual/de/function.date-default-timezone-set.php
function setTimezone($default) {
    $timezone = "";
   
    // On many systems (Mac, for instance) "/etc/localtime" is a symlink
    // to the file with the timezone info
    if (is_link("/etc/localtime")) {
       
        // If it is, that file's name is actually the "Olsen" format timezone
        $filename = readlink("/etc/localtime");
       
        $pos = strpos($filename, "zoneinfo");
        if ($pos) {
            // When it is, it's in the "/usr/share/zoneinfo/" folder
            $timezone = substr($filename, $pos + strlen("zoneinfo/"));
        } else {
            // If not, bail
            $timezone = $default;
        }
    }
    else {
        // On other systems, like Ubuntu, there's file with the Olsen time
        // right inside it.
        $timezone = file_get_contents("/etc/timezone");
        if (!strlen($timezone)) {
            $timezone = $default;
        }
    }
    date_default_timezone_set($timezone);
}


// thx to rommel on php.net: https://www.php.net/manual/en/function.filesize.php#102135
function human_filesize($bytes, $decimals = 2) {
  $sz = 'BKMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' '.@$sz[$factor].'B';
}



function checkProcess($x, $y, $z = 1){

	$c = exec('ps aux | grep php.* | grep -v grep | grep -v php-fpm');
	$c = ($c && substr_count($c, $x) > $z && substr_count($c, $y) > $z) ? 1 : 0;

	return $c;
}





?>