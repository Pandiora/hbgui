<?php
include __DIR__.DIRECTORY_SEPARATOR.'php/functions.php';
header("Content-Type: text/html; charset=utf-8");
?>

<!DOCTYPE html>

<html>
	<head>
		<!-- M E T A -->
		<title>HBGui</title>

		<!-- F A V I C O N -->
		<link rel="apple-touch-icon" sizes="180x180" href="img/apple-touch-icon.png">
		<link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="img/favicon-16x16.png">
		<link rel="manifest" href="img/site.webmanifest">

		<!-- C A S C A D I N G  S T Y L E  S H E E T -->
		<link rel="stylesheet" type="text/css" href="css/style.css?t=<?php echo time(); ?>" />
		<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css?t=<?php echo time(); ?>" />
		<link rel="stylesheet" type="text/css" href="css/bootstrap-tagsinput.css?t=<?php echo time(); ?>" />

		<!-- J A V A S C R I P T -->
		<script type="text/javascript" src="js/jquery.min.js?t=<?php echo time(); ?>"></script>
		<script type="text/javascript" src="js/popper.min.js?t=<?php echo time(); ?>"></script>
		<script type="text/javascript" src="js/bootstrap.min.js?t=<?php echo time(); ?>"></script>
		<script type="text/javascript" src="js/bootstrap-tagsinput.min.js?t=<?php echo time(); ?>"></script>
		<script type="text/javascript" src="js/main.js?t=<?php echo time(); ?>"></script>
	</head>
	<body>
		<div id="wrapper">
			<div class="container-fluid py-1" id="menu">
				<div class="row">
					<div class="col-3"></div>
					<div class="col-md-6 text-center"><?php echo fetchMenus(); ?></div>
					<div class="col-3">
						<button type="button" class="btn btn-secondary btn-sm disabled mt-1">
						  CPU <span class="badge badge-light"><span>0</span>°C</span>
						</button>
						<button type="button" class="btn btn-secondary btn-sm disabled mt-1">
						  CPU <span class="badge badge-light"><span>0.0</span> GHz</span>
						</button>
						<button type="button" class="btn btn-info btn-sm disabled mt-1">
						  HDD1 <span class="badge badge-light"><span>0</span>°C</span>
						</button>
						<button type="button" class="btn btn-primary btn-sm disabled mt-1">
						  HDD2 <span class="badge badge-light"><span>0</span>°C</span>
						</button>
					</div>
				</div>
			</div>

		</div>
	</body>
</html>
