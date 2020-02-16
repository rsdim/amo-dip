<html>
<head>
	<meta charset="UTF-8">
</head>
<body>
<?php
//=====================================================================================================
require("config.php"); //функции дл¤ работы с CRM
require("AMOinclude.php"); //функции дл¤ работы с CRM

@file_put_contents($logfile,"Start\r\n");
$arrAmocrmAuth = fncAmocrmAuth($userlogin, $amodomain, $apikey, $strCookieFile);
if (
    ! $arrAmocrmAuth['boolOk']
) {
    if ($logfile!="") {
        @file_put_contents($logfile,"\r\nAMO register NOT OK\r\n",FILE_APPEND);
    }
	
} else {
    if ($logfile!="") {
        @file_put_contents($logfile,"\r\nAMO register OK (3)\r\n",FILE_APPEND);
    }
	$arrusers = fncAmocrmGetAllUsers($amodomain, $strCookieFile);
	foreach($arrusers["arrResponse"] as $nodeuser) {
		if ($nodeuser["is_admin"] == "Y") {
			$adminmail = $nodeuser["login"];
		}					
	}	
	//====проходим по директории в которой храняться файлы, обрабатываем их и переносим в обработанную директорию
	$dir    = 'in';
	$files1 = scandir($dir);	
	foreach($files1 as $nodefile) {
		if (strlen($nodefile)>4) {
			$searchflag = "";
			//обрабатываем только файлы удовлетворящие нужному критерию
			$strpost = @file_get_contents("in/".$nodefile);
			$arrpost = json_decode($strpost, TRUE);
			
			$contactname = $arrpost["uname"];
			//оставим только номер
			$tel = $arrpost["tel"];
			$tel = str_replace("+","",$tel);
			$tel = str_replace("-","",$tel);
			$tel = str_replace("(","",$tel);
			$tel = str_replace(")","",$tel);			
			$email = $arrpost["email"];
			echo "Ищем по email:".$email;
			$arrcontacts = fncAmocrmContactsList($amodomain, $strCookieFile, $email);
			echo "<br>ContactsList:".json_encode($arrcontacts);
			if (
				$arrcontacts['boolOk'] == true
			) {
				//контакты по email найдены, создаем сделку и крепим к отвественному контакта
				//проверим точное совпадение
				$emailflag = "";
				$items = $arrcontacts["arrResponse"]["_embedded"]["items"];
				foreach($items as $contactitem) {
					$customfields = $contactitem["custom_fields"];
					foreach($customfields as $customfield) {
						if ($customfield["code"] == "EMAIL") {
							foreach($customfield["values"] as $emailvalue) {
								if ($emailvalue["value"] == $email) {
									$emailflag = "1";
									$responsible_user_id = $contactitem["responsible_user_id"];
									$contact_id = $contactitem["id"];
								}
							}
						}
					}
				}
				if ($emailflag == "1") {
				//для простоты берем ответственного 1го контакта 
					//$responsible_user_id = $arrcontacts["arrResponse"]["_embedded"]["items"][0]["responsible_user_id"];
					//$contact_id = $arrcontacts["arrResponse"]["_embedded"]["items"][0]["id"];
					//создаем контакт и сделку
					$arrCreateLead = array(
						array(
							"name" => "Заявка с сайта.Обработать лид с сайта - ".$nodefile,
							"status_id" => "3178606",
							"responsible_user_id" => $responsible_user_id,
							"contacts_id" => array($contact_id)
						)
					);
					$arrResponceLead = fncAmocrmLeadsCreate( $amodomain, $strCookieFile,  $arrCreateLead);
					echo "<br>Created lead:".json_encode($arrResponceLead);
					$leadId = $arrResponceLead["arrResponse"]["_embedded"]["items"][0]["id"];
					$linktolead = 'https://dprastegaev.amocrm.ru/leads/detail/'.$leadId;
					//создадим задачу 
					$arrTasksCreate = array(
						array(
							"element_id" => $leadId,
							"element_type" => 2,
							"task_type" => 1,
							"complete_till" => time()+86400,
							"responsible_user_id" => $responsible_user_id,
							"text" => "Обработать лид с сайта. Телефон:".$tel." email:".$email
						)
					);
					$arrResponceTask = fncAmocrmTasksCreate( $amodomain, $strCookieFile, $arrTasksCreate);
					$searchflag = "1";
					//===отправим письмо администратору аккаунта
					SendMailToAdmin($adminmail, "По заявке с сайта создана сделка!", $linktolead);
				} else {
					//ищем по телефону
					echo "<br>Ищем по tel1=".$tel;
					$arrcontacts = fncAmocrmContactsList($amodomain, $strCookieFile, $tel);
					if (is_null($arrcontacts["arrResponse"])) {
						
					} else {
						echo "<br>ContactsList:".json_encode($arrcontacts);
						if (
							$arrcontacts['boolOk'] == true
						) {
							//контакт по email найден, создаем сделку и крепим к отвественному контакта
							$responsible_user_id = $arrcontacts["arrResponse"]["_embedded"]["items"][0]["responsible_user_id"];		
							$contact_id = $arrcontacts["arrResponse"]["_embedded"]["items"][0]["id"];					
							$arrCreateLead = array(
								array(
									"name" => "Заявка с сайта. Обработать лид с сайта - ".$nodefile,
									"status_id" => "3178606",
									"responsible_user_id" => $responsible_user_id,
									"contacts_id" => array($contact_id)
								)
							);
							$arrResponceLead = fncAmocrmLeadsCreate( $amodomain, $strCookieFile,  $arrCreateLead);
							echo "<br>Created lead:".json_encode($arrResponceLead);
					
							$leadId = $arrResponceLead["arrResponse"]["_embedded"]["items"][0]["id"];
							$linktolead = 'https://dprastegaev.amocrm.ru/leads/detail/'.$leadId;
							//создадим задачу 
							$arrTasksCreate = array(
								array(
									"element_id" => $leadId,
									"element_type" => 2,
									"task_type" => 1,
									"complete_till" => time()+86400,
									"responsible_user_id" => $responsible_user_id,
									"text" => "Обработать лид с сайта. Телефон:".$tel." email:".$email
								)
							);
							$arrResponceTask = fncAmocrmTasksCreate( $amodomain, $strCookieFile, $arrTasksCreate);
							//===отправим письмо администратору аккаунта
							SendMailToAdmin($adminmail, "По заявке с сайта создана сделка!", $linktolead);
							$searchflag = "1";
							//---the end-------------
						}
					}
				}	
				//---the end-------------
			} else {
				//ищем по телефону
				echo "<br>Ищем по tel2=".$tel;
				$arrcontacts = fncAmocrmContactsList($amodomain, $strCookieFile, $tel);
				if (
					$arrcontacts['boolOk'] == true
				) {
					if (is_null($arrcontacts["arrResponse"])) {
						
					} else {	
						//контакт по email найден, создаем сделку и крепим к отвественному контакта
						$responsible_user_id = $arrcontacts["arrResponse"]["_embedded"]["items"][0]["responsible_user_id"];		
						$contact_id = $arrcontacts["arrResponse"]["_embedded"]["items"][0]["id"];					
						$arrCreateLead = array(
							array(
								"name" => "Заявка с сайта. Обработать лид с сайта - ".$nodefile,
								"status_id" => "3178606",
								"responsible_user_id" => $responsible_user_id,
								"contacts_id" => array($contact_id)
							)
						);
						$arrResponceLead = fncAmocrmLeadsCreate( $amodomain, $strCookieFile,  $arrCreateLead);
						echo "<br>Created lead:".json_encode($arrResponceLead);
					
						$leadId = $arrResponceLead["arrResponse"]["_embedded"]["items"][0]["id"];
						$linktolead = 'https://dprastegaev.amocrm.ru/leads/detail/'.$leadId;
						//создадим задачу 
						$arrTasksCreate = array(
							array(
								"element_id" => $leadId,
								"element_type" => 2,
								"task_type" => 1,
								"complete_till" => time()+86400,
								"responsible_user_id" => $responsible_user_id,
								"text" => "Обработать лид с сайта. Телефон:".$tel." email:".$email
							)
						);
						$arrResponceTask = fncAmocrmTasksCreate( $amodomain, $strCookieFile, $arrTasksCreate);
						//===отправим письмо администратору аккаунта
						SendMailToAdmin($adminmail, "По заявке с сайта создана сделка!", $linktolead);
						$searchflag = "1";
						//---the end-------------
					}
				} 
			}
			//если не получилось найти по email или телефону
				if ($searchflag == "")	{
					//определяем ответственного 
					
					$maxleads = 1000000;
					foreach($arrusers["arrResponse"] as $nodeuser) {
						if ($nodeuser["is_admin"] == "Y") {
							//администратор не участвует в распределении новых
						} else {
							if ($nodeuser["active"] == true) {
								$users[] = $nodeuser["id"];
								//получим все сделки нашего пользователя за сегодня
								$day = (int)date("d");
								$month = (int)date("m");
								$year = (int)date("Y");
								$modified = mktime(0, 0, 0, $month, $day, $year);
								echo "<br>modified:".$modified." - ";
								$arrleads = fncAmocrmLeadsList($amodomain, $strCookieFile, '', 500, 0,	$nodeuser["id"], $modified);
								if ($arrleads["boolOk"] == true) {
									$userleads = $arrleads["arrResponse"]["_embedded"]["items"];
									//Количество сделок за текущие сутки у которых один и тот же контакт считать как одна сделка.
									$i = 0;
									foreach($userleads as $nodelead) {
										if (count($nodelead["main_contact"])>0) {
											$i++;
											$uniquearr["main_contact"] = $i;							
										} else {
											$contactid = $nodelead["main_contact"]["id"];
											$uniquearr[$contactid] = 1;
										}
									} //end foreach					
									$i = 0;
									foreach($uniquearr as $trueleads) {
										$i = $i + $trueleads;
									}	//end foreach					
									echo "<br>".json_encode($userleads);
									if ($i<$maxleads) {
										$maxleads = $i;
										$managerid = $nodeuser["id"];
										$manageremail = $nodeuser["login"];
									}
								} else {
									// если сделок за сегодня нет, то это лидер
									$maxleads = 0;
									$managerid = $nodeuser["id"];
									$manageremail = $nodeuser["login"];
								}
							}
						}		
					} //end foreach по пользователям
					//ответственный выбран
					//создаем сделку и контакт
					$arrCreateLead = array(
						array(
							"name" => "Заявка с сайта. Обработать лид с сайта - ".$nodefile,
							"status_id" => "3178606",
							"responsible_user_id" => $managerid							
						)
					);
					$arrResponceLead = fncAmocrmLeadsCreate( $amodomain, $strCookieFile,  $arrCreateLead);
					
					
					echo "<br>Created lead:".json_encode($arrResponceLead);
					if ($arrResponceLead["boolOk"] == true) {						
						//если создали сделку то создаем и контакт
						$leadid = $arrResponceLead["arrResponse"]["_embedded"]["items"][0]["id"];
						$linktolead = 'https://dprastegaev.amocrm.ru/leads/detail/'.$leadid;
						$arrCreateContact = array(
							array(
								"name" => $contactname,
								"custom_fields" => array(
									array(
										"id" => "203689",
										"values" => array(
											array(
												"value"=>$tel,
												"enum" => "WORK"
											)
										)
									),
									array(
										"id" => "203691",
										"values" => array(
											array(
												"value"=>$email,
												"enum" => "WORK"
											)
										)
									)
								),
								"leads_id" => array($leadid),
								"responsible_user_id" => $managerid			
							)
						);
						$arrResponceContact = fncAmocrmContactsSet( $amodomain, $strCookieFile, $arrCreateContact);
						
						//создадим задачу 
						$arrTasksCreate = array(
							array(
								"element_id" => $leadid,
								"element_type" => 2,
								"task_type" => 1,
								"complete_till" => time()+86400,
								"text" => "Обработать лид с сайта, проверить данные нового контакта. елефон:".$tel." email:".$email,
								"responsible_user_id" => $managerid,
								"created_by" => $managerid
							)
						);
						$arrResponceTask = fncAmocrmTasksCreate( $amodomain, $strCookieFile, $arrTasksCreate);
						echo "<br>====================";
						echo "<br> Task: ".json_encode($arrResponceTask);
						//===отправим письмо администратору аккаунта
						SendMailToAdmin($adminmail, "По заявке с сайта создана сделка!", $linktolead);
					}					
				}
			
			rename("in/".$nodefile,"out/".$nodefile);
		}
		echo "<br>";
		sleep(1); //задержка для обработки  api
	} //end foreach post
    $z = 0;
    $offset = 0;
    $limit = 500;
    
    echo "<br>=============<br>";
	$stime = time()+1000;
	echo $stime;
	echo "<br>=============<br>";
	$arrLeadsCreate = array(
		array(
			"id" => "27196337",
			"status_id" => 142,			
			"price" => "7000",
			"date_close" => time()-86400,
			"updated_at" => $stime			
		)
	);
	//$arrLeadUpdate = fncAmocrmLeadsUpdate($amodomain, $strCookieFile, $arrLeadsCreate);
	//echo json_encode($arrLeadUpdate);
	echo "<br>=============<br>";

}	//конец авторизации в амо
?>
</body>
</html>