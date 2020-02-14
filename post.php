<?php
	file_put_contents("log.txt", json_encode($_POST), FILE_APPEND);
	echo "post ok";
 ?>