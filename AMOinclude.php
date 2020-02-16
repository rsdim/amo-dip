<?php
// https://domen.amocrm.ru/?USER_LOGIN=mail@mail.ru&USER_HASH=af1038cead9876c96acbab25910596f8
// функции в данном файле
// fncAmocrmAuth авторизация в АМО
// fncAmocrmGetAllUsers получает массив
// fncAmocrmGetAllStatuses - получаем массив статусов вида 
// fncAmocrmGetAllTaskTypes - Список типов задач аккаунта
// fncAmocrmGetAllGroups получает массив
// fncAmocrmGetAllField - получает значения всех кастомных полей из аккаунта
// fncAmocrmSetCustomField - обновляет изначение в кастомном поле
// fncAmocrmUpdateCustomField - Добавляет значение в кастомное поле через удаление и создание поля
// fncAmocrmCompanSet - создание компании
// fncAmocrmCompanyList - получает список компаний по запросу
// fncAmocrmContactsSet - создание и обновление контакат
// fncAmocrmContactsGet - получает связь между контактами и сделками
// fncAmocrmContactsList - список контактов по запросу

// fncAmocrmContactsListByResponsibleID - список контактов ответственного пользователя
// fncAmocrmTasksCreate - создание задачи
// fncAmocrmTaskList - листинг задач
// fncAmocrmLeadsCreate - создание сделки
// fncAmocrmLeadsUpdate - update
// fncAmocrmLeadsListById- получает список сделок
// fncAmocrmLeadsList - получает список сделок по запросу
// fncAmocrmLeadsListAll - список всех сделок со смещением
// fncAmocrmNotesCreate - создание примечания
// fncAmocrmNotesList - список примечаний
// fncAmocrmNotesList2 - список примечаний, новая версия
// fncAmocrmContactsListById - получает контакт по id
// fncAmocrmGetPipeline - получает воронку
// fncAmocrmGetFieldValuesById - получает массив значений поля из аккаунта АМО
// fncAmocrmCatalogElementList - получает элементы каталога
// fncAmocrmGetCatalogFromElement - - получает элементы каталога и их количество из сущности (контакта, сделки и т.п.)
// fncAmocrmGetCatalogList - получает список каталогов 

// авторизация в АМО
function fncAmocrmAuth($strLogin, $strSubdomain, $strApiKey, $strCookieFileName) {

    # почти copy-paste из документации (((

    #Массив с параметрами, которые нужно передать методом POST к API системы
    $user=array(
      'USER_LOGIN'=>$strLogin, #Ваш логин (электронная почта)
      'USER_HASH'=>$strApiKey #Хэш для доступа к API (смотрите в профиле пользователя)
    );

    $subdomain=$strSubdomain; #Наш аккаунт - поддомен

    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/auth.php?type=json';

    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
    curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($user));
    curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
     
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE); #Получим HTTP-код ответа сервера
    curl_close($curl); #Завершаем сеанс cURL

    $code=(int)$code;
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */
    $Response=json_decode($out,true);

    $Response=$Response['response'];
    if(isset($Response['auth'])) #Флаг авторизации доступен в свойстве "auth"
        return array (
          'boolOk' => TRUE,
        );
    return array (
      'boolOk' => FALSE,
      'strErrDevelopUtf8' => 'AmoCRM error: ' . 'Авторизация не удалась',
    );

    # ))) почти copy-paste из документации

} # function регистрации

function escape_win ($path) {
	$path = strtoupper ($path);
	return strtr($path, array("\U0430"=>"а", "\U0431"=>"б", "\U0432"=>"в",
"\U0433"=>"г", "\U0434"=>"д", "\U0435"=>"е", "\U0451"=>"ё", "\U0436"=>"ж", "\U0437"=>"з", "\U0438"=>"и",
"\U0439"=>"й", "\U043A"=>"к", "\U043B"=>"л", "\U043C"=>"м", "\U043D"=>"н", "\U043E"=>"о", "\U043F"=>"п",
"\U0440"=>"р", "\U0441"=>"с", "\U0442"=>"т", "\U0443"=>"у", "\U0444"=>"ф", "\U0445"=>"х", "\U0446"=>"ц",
"\U0447"=>"ч", "\U0448"=>"ш", "\U0449"=>"щ", "\U044A"=>"ъ", "\U044B"=>"ы", "\U044C"=>"ь", "\U044D"=>"э",
"\U044E"=>"ю", "\U044F"=>"я", "\U0410"=>"А", "\U0411"=>"Б", "\U0412"=>"В", "\U0413"=>"Г", "\U0414"=>"Д",
"\U0415"=>"Е", "\U0401"=>"Ё", "\U0416"=>"Ж", "\U0417"=>"З", "\U0418"=>"И", "\U0419"=>"Й", "\U041A"=>"К",
"\U041B"=>"Л", "\U041C"=>"М", "\U041D"=>"Н", "\U041E"=>"О", "\U041F"=>"П", "\U0420"=>"Р", "\U0421"=>"С",
"\U0422"=>"Т", "\U0423"=>"У", "\U0424"=>"Ф", "\U0425"=>"Х", "\U0426"=>"Ц", "\U0427"=>"Ч", "\U0428"=>"Ш",
"\U0429"=>"Щ", "\U042A"=>"Ъ", "\U042B"=>"Ы", "\U042C"=>"Ь", "\U042D"=>"Э", "\U042E"=>"Ю", "\U042F"=>"Я"));
}



//GetAllUsers - получает массив пользователей из аккаунта АМО
function fncAmocrmGetAccount(
		$strSubdomain,
		$strCookieFileName,
		$request
	) {
		$subdomain=$strSubdomain; #Наш аккаунт - поддомен
		#Формируем ссылку для запроса
		$link='https://'.$subdomain.'.amocrm.ru/api/v2/account?with='.$request;
		
		$curl=curl_init(); #Сохраняем дескриптор сеанса cURL
		#Устанавливаем необходимые опции для сеанса cURL
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
		curl_setopt($curl,CURLOPT_URL,$link);
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
 
		$out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
		$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
		curl_close($curl);
		$code=(int)$code;
		$errors=array(
			301=>'Moved permanently',
			400=>'Bad request',
			401=>'Unauthorized',
			403=>'Forbidden',
			404=>'Not found',
			500=>'Internal server error',
			502=>'Bad gateway',
			503=>'Service unavailable'
		);
		try
		{
			#Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
		if($code!=200 && $code!=204)
		throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error',$code);
		}
		catch(Exception $E)
		{
			//die('Ошибка: '.$E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode());
			return array (
				'boolOk' => False,
				'arrResponse' => "Error",
			);
		}
 
		/**
		* Данные получаем в формате JSON, поэтому, для получения читаемых данных,
		* нам придётся перевести ответ в формат, понятный PHP
		*/
		
		$Response=json_decode($out,true);
		
		//echo json_encode($Response['_links']);
		return array (
			'boolOk' => TRUE,
			'arrResponse' => $Response['_embedded'],
		);
	}
//

//GetAllUsers - получает массив пользователей из аккаунта АМО
function fncAmocrmGetAllUsers(
		$strSubdomain,
		$strCookieFileName		
	) {
		$subdomain=$strSubdomain; #Наш аккаунт - поддомен
		#Формируем ссылку для запроса
		$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/accounts/current';
		$curl=curl_init(); #Сохраняем дескриптор сеанса cURL
		#Устанавливаем необходимые опции для сеанса cURL
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
		curl_setopt($curl,CURLOPT_URL,$link);
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
 
		$out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
		$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
		curl_close($curl);
		$code=(int)$code;
		$errors=array(
			301=>'Moved permanently',
			400=>'Bad request',
			401=>'Unauthorized',
			403=>'Forbidden',
			404=>'Not found',
			500=>'Internal server error',
			502=>'Bad gateway',
			503=>'Service unavailable'
		);
		try
		{
			#Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
		if($code!=200 && $code!=204)
		throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error',$code);
		}
		catch(Exception $E)
		{
			//die('Ошибка: '.$E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode());
			return array (
				'boolOk' => False,
				'arrResponse' => "Error",
			);
		}
 
		/**
		* Данные получаем в формате JSON, поэтому, для получения читаемых данных,
		* нам придётся перевести ответ в формат, понятный PHP
		*/
		$Response=json_decode($out,true);
		$Response=$Response['response']; #Response - объект класса StdClass			
		return array (
			'boolOk' => TRUE,
			'arrResponse' => $Response['account']['users'],
		);
	}
//
//GetAllUsers - получает массив пользователей из аккаунта АМО
function fncAmocrmGetAllGroups(
		$strSubdomain,
		$strCookieFileName		
	) {
		$subdomain=$strSubdomain; #Наш аккаунт - поддомен
		#Формируем ссылку для запроса
		$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/accounts/current';
		$curl=curl_init(); #Сохраняем дескриптор сеанса cURL
		#Устанавливаем необходимые опции для сеанса cURL
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
		curl_setopt($curl,CURLOPT_URL,$link);
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
 
		$out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
		$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
		curl_close($curl);
		$code=(int)$code;
		$errors=array(
			301=>'Moved permanently',
			400=>'Bad request',
			401=>'Unauthorized',
			403=>'Forbidden',
			404=>'Not found',
			500=>'Internal server error',
			502=>'Bad gateway',
			503=>'Service unavailable'
		);
		try
		{
			#Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
		if($code!=200 && $code!=204)
		throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error',$code);
		}
		catch(Exception $E)
		{
			//die('Ошибка: '.$E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode());
			return array (
				'boolOk' => False,
				'arrResponse' => "Error",
			);
		}
 
		/**
		* Данные получаем в формате JSON, поэтому, для получения читаемых данных,
		* нам придётся перевести ответ в формат, понятный PHP
		*/
		$Response=json_decode($out,true);
		$Response=$Response['response']; #Response - объект класса StdClass			
		return array (
			'boolOk' => TRUE,
			'arrResponse' => $Response['account']['users'],
		);
	}
//
function fncAmocrmGetAllStatuses2(
		$strSubdomain,
		$strCookieFileName		
	) {
		$subdomain=$strSubdomain; #Наш аккаунт - поддомен
		#Формируем ссылку для запроса
		$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/accounts/current';
		$curl=curl_init(); #Сохраняем дескриптор сеанса cURL
		#Устанавливаем необходимые опции для сеанса cURL
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
		curl_setopt($curl,CURLOPT_URL,$link);
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
 
		$out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
		$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
		curl_close($curl);
		$code=(int)$code;
		$errors=array(
			301=>'Moved permanently',
			400=>'Bad request',
			401=>'Unauthorized',
			403=>'Forbidden',
			404=>'Not found',
			500=>'Internal server error',
			502=>'Bad gateway',
			503=>'Service unavailable'
		);
		try
		{
			#Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
		if($code!=200 && $code!=204)
		throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error',$code);
		}
		catch(Exception $E)
		{
			//die('Ошибка: '.$E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode());
			return array (
				'boolOk' => False,
				'arrResponse' => "Error",
			);
		}
 
		/**
		* Данные получаем в формате JSON, поэтому, для получения читаемых данных,
		* нам придётся перевести ответ в формат, понятный PHP
		*/
		$Response=json_decode($out,true);
		$Response=$Response['response']; #Response - объект класса StdClass			
		return array (
			'boolOk' => TRUE,
			'arrResponse' => $Response['account']['pipelines'],
		);
}

function fncAmocrmGetAllTaskTypes(
		$strSubdomain,
		$strCookieFileName		
	) {
		$subdomain=$strSubdomain; #Наш аккаунт - поддомен
		#Формируем ссылку для запроса
		$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/accounts/current';
		$curl=curl_init(); #Сохраняем дескриптор сеанса cURL
		#Устанавливаем необходимые опции для сеанса cURL
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
		curl_setopt($curl,CURLOPT_URL,$link);
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
 
		$out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
		$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
		curl_close($curl);
		$code=(int)$code;
		$errors=array(
			301=>'Moved permanently',
			400=>'Bad request',
			401=>'Unauthorized',
			403=>'Forbidden',
			404=>'Not found',
			500=>'Internal server error',
			502=>'Bad gateway',
			503=>'Service unavailable'
		);
		try
		{
			#Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
		if($code!=200 && $code!=204)
		throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error',$code);
		}
		catch(Exception $E)
		{
			//die('Ошибка: '.$E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode());
			return array (
				'boolOk' => False,
				'arrResponse' => "Error",
			);
		}
 
		/**
		* Данные получаем в формате JSON, поэтому, для получения читаемых данных,
		* нам придётся перевести ответ в формат, понятный PHP
		*/
		$Response=json_decode($out,true);
		$Response=$Response['response']; #Response - объект класса StdClass			
		return array (
			'boolOk' => TRUE,
			'arrResponse' => $Response['account']['task_types'],
		);
}
//fncAmocrmGetAllTaskTypes - конец	

function fncAmocrmGetAllField(
		$strSubdomain,
		$strCookieFileName		
	) {
		$subdomain=$strSubdomain; #Наш аккаунт - поддомен
		#Формируем ссылку для запроса
		$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/accounts/current';
		$curl=curl_init(); #Сохраняем дескриптор сеанса cURL
		#Устанавливаем необходимые опции для сеанса cURL
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
		curl_setopt($curl,CURLOPT_URL,$link);
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
 
		$out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
		$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
		curl_close($curl);
		$code=(int)$code;
		$errors=array(
			301=>'Moved permanently',
			400=>'Bad request',
			401=>'Unauthorized',
			403=>'Forbidden',
			404=>'Not found',
			500=>'Internal server error',
			502=>'Bad gateway',
			503=>'Service unavailable'
		);
		try
		{
			#Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
		if($code!=200 && $code!=204)
		throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error',$code);
		}
		catch(Exception $E)
		{
			//die('Ошибка: '.$E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode());
			return array (
				'boolOk' => False,
				'arrResponse' => "Error",
			);
		}
 
		/**
		* Данные получаем в формате JSON, поэтому, для получения читаемых данных,
		* нам придётся перевести ответ в формат, понятный PHP
		*/
		$Response=json_decode($out,true);
		$Response=$Response['response']; #Response - объект класса StdClass			
		return array (
			'boolOk' => TRUE,
			'arrResponse' => $Response['account']['custom_fields'],
		);
	}
	//GetAllFields - конец
	
	
//GetAllUsers - получает массив пользователей из аккаунта АМО
function fncAmocrmGetAllStatuses(
		$strSubdomain,
		$strCookieFileName		
	) {
		$subdomain=$strSubdomain; #Наш аккаунт - поддомен
		#Формируем ссылку для запроса
		$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/accounts/current';
		$curl=curl_init(); #Сохраняем дескриптор сеанса cURL
		#Устанавливаем необходимые опции для сеанса cURL
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
		curl_setopt($curl,CURLOPT_URL,$link);
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
 
		$out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
		$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
		curl_close($curl);
		$code=(int)$code;
		$errors=array(
			301=>'Moved permanently',
			400=>'Bad request',
			401=>'Unauthorized',
			403=>'Forbidden',
			404=>'Not found',
			500=>'Internal server error',
			502=>'Bad gateway',
			503=>'Service unavailable'
		);
		try
		{
			#Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
		if($code!=200 && $code!=204)
		throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error',$code);
		}
		catch(Exception $E)
		{
			//die('Ошибка: '.$E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode());
			return array (
				'boolOk' => False,
				'arrResponse' => "Error",
			);
		}
 
		/**
		* Данные получаем в формате JSON, поэтому, для получения читаемых данных,
		* нам придётся перевести ответ в формат, понятный PHP
		*/
		$Response=json_decode($out,true);
		$Response=$Response['response']; #Response - объект класса StdClass			
		return array (
			'boolOk' => TRUE,
			'arrResponse' => $Response['account']['leads_statuses'],
		);
}
//GetAllUsers - конец

function fncAmocrmSetCustomField(
		$strSubdomain,
		$strCookieFileName,
		$arrFieldSet		
	) {
		# почти copy-paste из документации (((

    $cfields['request']['fields']['add'] = $arrFieldSet;	
    $subdomain=$strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/fields/set';

    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
    curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($cfields));
    curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
     
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    
    $code=(int)$code;
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */
    $Response=json_decode($out,true);

    return array (
      'boolOk' => TRUE,
      'arrResponse' => $Response['response'],
    );

    # ))) почти copy-paste из документации
	}
	
function fncAmocrmDeleteCustomField(
		$strSubdomain,
		$strCookieFileName,
		$id,
		$originid
	) {
		# почти copy-paste из документации (((

    $cfields['request']['fields']['delete'] = array(
		array(
			"id" => $id,
			"origin" => $originid
		)
	);

    $subdomain=$strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/fields/set';

    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
    curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($cfields));
    curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
     
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    
    $code=(int)$code;
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */
    $Response=json_decode($out,true);

    return array (
      'boolOk' => TRUE,
      'arrResponse' => $Response['response'],
    );

    # ))) почти copy-paste из документации
	}
	
function fncAmocrmUpdateCustomField(
		$strSubdomain,
		$strCookieFileName,
		$fieldid,
		$filedval,
		$elementtype,
		$originid = ""
	) 
	{
		//	Тип привязываемого елемента (1 - контакт, 2- сделка, 3 - компания)
		# выполняет апдейт поля с указанным именем 
		$arrfields = fncAmocrmGetAllField($strSubdomain, $strCookieFileName);
		if ($elementtype=="1") {
			$resarr = $arrfields['arrResponse']['contacts'];
		}
		if ($elementtype=="2") {
			$resarr = $arrfields['arrResponse']['leads'];
		}
		if ($elementtype=="3") {
			$resarr = $arrfields['arrResponse']['companies'];
		}
		
		$flag = "";
		$elementtype = "".$elementtype;
		
		foreach($resarr as $field) {
			if ($field['id'] == $fieldid) {				
				//сохраняем данные поля
				$name = $field['name'];
				$type = $field['type_id'];
				$enums = $field['enums'];
				$newenums = array();
				foreach($enums as $key=>$value) {
					array_push($newenums,$value);				
				}
				array_push($newenums,$filedval);				
				//удаляем поле
				$arrdelete = fncAmocrmDeleteCustomField($strSubdomain,$strCookieFileName,$fieldid,$originid);				
			}
		}
		sleep(1);
		$arrfld = array(
			array(
				"name" => $name,
				"disabled" => 0,
				"type" => (int)$type,						
				"element_type" => (int)$elementtype,
				"origin" => $originid,
				"enums"=> $newenums
			)
		);
		$arrres = fncAmocrmSetCustomField($strSubdomain,$strCookieFileName,$arrfld);
		return $arrres;
		
	}
	

function fncAmocrmGetPipeline(
		$strSubdomain,
		$strCookieFileName,
		$pipeid = ""
	) {
		$subdomain=$strSubdomain; #Наш аккаунт - поддомен
		#Формируем ссылку для запроса
		if ($pipeid == "") {
			$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/pipelines/list';
		} else {
			$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/pipelines/list?id='.$pipeid;
		}
		$curl=curl_init(); #Сохраняем дескриптор сеанса cURL
		#Устанавливаем необходимые опции для сеанса cURL
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
		curl_setopt($curl,CURLOPT_URL,$link);
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
 
		$out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
		$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
		curl_close($curl);
		$code=(int)$code;
		$errors=array(
			301=>'Moved permanently',
			400=>'Bad request',
			401=>'Unauthorized',
			403=>'Forbidden',
			404=>'Not found',
			500=>'Internal server error',
			502=>'Bad gateway',
			503=>'Service unavailable'
		);
		try
		{
			#Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
		if($code!=200 && $code!=204)
		throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error',$code);
		}
		catch(Exception $E)
		{
			//die('Ошибка: '.$E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode());
			return array (
				'boolOk' => False,
				'arrResponse' => "Error",
			);
		}
 
		/**
		* Данные получаем в формате JSON, поэтому, для получения читаемых данных,
		* нам придётся перевести ответ в формат, понятный PHP
		*/
		$Response=json_decode($out,true);
		$Response=$Response['response']; #Response - объект класса StdClass			
		return array (
			'boolOk' => TRUE,
			'arrResponse' => $Response,
		);
}
//GetAllUsers - конец
	
function fncAmocrmGetNextUserFromGroup($strSubdomain,$strCookieFileName, $datafile, $groupid,$logfile = "") {
	if ($logfile != "") {
		@file_put_contents($logfile,"start fncAmocrmGetNextUserFromGroup\r\n",FILE_APPEND);
	}
	$amousers = fncAmocrmGetAllUsers($strSubdomain,	$strCookieFileName);	
		
	$prevuser = @file_get_contents($datafile);
		$i=0;
		$flag="";
		$previosid = "";				
		$ruserid = "";					
		$arrusers = $amousers['arrResponse'];
		$arrgroupusers = array();
		foreach($arrusers as $nodeuser) {
			$groups = "".$nodeuser['group_id'];
			if ($groups!="") {
				if ($groupid == $groups)  {										
					array_push($arrgroupusers,$nodeuser["id"]);
				}				
			}						
			$i++;
		}
		if (count($arrgroupusers)==0) {			
			$ruserid = "";
		}
		if (count($arrgroupusers)==1) {			
			$ruserid = $arrgroupusers[0];
		}
		if (file_exists($datafile)) {
			if (count($arrgroupusers)>1) {
				$flag1406 = "";
				for($x=0; $x<count($arrgroupusers); $x++) {					
					if($prevuser==$arrgroupusers[$x] || empty($prevuser)) {
						$flag1406 = "1";
						if ($x==(count($arrgroupusers)-1)) {												
							if ($flag=="") {
								$prevuser=$arrgroupusers[0];
							}
						} else {						
							if ($flag=="") {
								$prevuser=$arrgroupusers[$x+1];
							}
							$flag="1";
						}
					}
				}		
				if($flag1406 == "1") {
					$ruserid = $prevuser;
				} else {
					$x = rand(0,(count($arrgroupusers)-1));
					$ruserid = $arrgroupusers[$x];
				}
			
				@file_put_contents($datafile,$ruserid);
			}		
		
		} else {
			$x = rand(0,(count($arrgroupusers)-1));
			$ruserid = $arrgroupusers[$x];
			@file_put_contents($datafile,$ruserid);
		}
	return $ruserid;
}	
//fncAmocrmGetNextUserFromGroup - конец	
function fncAmocrmLeadsListById(
    $strSubdomain,
    $strCookieFileName,
    $query1512 = ''
) {

    # почти copy-paste из документации (((
	
    $subdomain=$strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/leads/list';
    if (
        $query1512 != ''
    ) {
        $link .= '?id=' . urlencode($query1512);
    } # if
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
	$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
	curl_close($curl); 
    //$resout = gettype($out);
	// --- 628 - 600,500,525 -> 530
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */

    $Response=json_decode($out,true);
	if ($out=="") {
		return array (
      		'boolOk' => FALSE,
      		'arrResponse' => "",
    	);
	} else {
    	return array (
      		'boolOk' => TRUE,
      		'arrResponse' => $Response['response'],
    	);
	}
	
    # ))) почти copy-paste из документации

} # 
#-----------
#-----------	
//Создание компании	
function fncAmocrmCompanSet(
    $strSubdomain,
    $strCookieFileName,
    $arrContactsSet,
    $addORupdate # 'add' или 'update'
) {

    # почти copy-paste из документации (((

    $contacts['request']['contacts'][$addORupdate] = $arrContactsSet;

    $subdomain=$strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/company/set';

    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
    curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($contacts));
    curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
     
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    
    $code=(int)$code;
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */
    $Response=json_decode($out,true);

    return array (
      'boolOk' => TRUE,
      'arrResponse' => $Response['response'],
    );

    # ))) почти copy-paste из документации

} # function создания компании
#-----------
function fncAmocrmContactsSet(
    $strSubdomain,
    $strCookieFileName,
    $arrContactsSet,
    $addORupdate = 'add' # 'add' или 'update'
) {

    # почти copy-paste из документации (((

    //$contacts['request']['contacts'][$addORupdate] = $arrContactsSet;
	$contacts[$addORupdate] = $arrContactsSet;
    $subdomain=$strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    //$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/contacts/set';
    $link = 'https://' . $subdomain . '.amocrm.ru/api/v2/contacts';
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
    curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($contacts));
    curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
     
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    
    $code=(int)$code;
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */
    $Response=json_decode($out,true);

    return array (
      'boolOk' => TRUE,
      'arrResponse' => $Response,
    );

    # ))) почти copy-paste из документации

} # function
//конец функции добавления контакта
//добавление задачи
function fncAmocrmTasksCreate(
    $strSubdomain,
    $strCookieFileName,
    $arrTasksCreate
) {

    # почти copy-paste из документации (((

    //$tasks['request']['tasks']['add'] = $arrTasksCreate;
	$tasks['add'] = $arrTasksCreate;
    $subdomain=$strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    //$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/tasks/set';
	$link='https://'.$subdomain.'.amocrm.ru/api/v2/tasks';
    //api/v2/tasks
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
	echo "<br>POST:".json_encode($tasks);
    curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($tasks));
    curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
     
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    
    $code=(int)$code;
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */
    $Response=json_decode($out,true);

    return array (
      'boolOk' => TRUE,
      'arrResponse' => $Response,
    );

    # ))) почти copy-paste из документации

} # function
//конец функции добавления задачи
#-----------
function fncAmocrmTaskList(
    $strSubdomain,
    $strCookieFileName,
    $query1512 = '',
	$limit = 500,
	$offset = 0
) {
	

    # почти copy-paste из документации (((
	
    $subdomain = $strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/tasks/list';
	$flagg = "";
    if (
        $query1512 != ''
    ) {
        $link .= '?id='. urlencode($query1512);
		$flagg = "1";
    } # if
	
	if ($flagg == "") {
		$link .= "?limit_rows=". urlencode($limit);
		$link .= "&limit_offset=". urlencode($offset);
	} else {
		$link .= "&limit_rows=". urlencode($limit);
		$link .= "&limit_offset=". urlencode($offset);
	}
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
	$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
	curl_close($curl); 
    //$resout = gettype($out);
	// --- 628 - 600,500,525 -> 530
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */

    $Response=json_decode($out,true);
	if ($out=="") {
		return array (
      		'boolOk' => FALSE,
      		'arrResponse' => "",
    	);
	} else {
    	return array (
      		'boolOk' => TRUE,
      		'arrResponse' => $Response['response'],
    	);
	}
	
    # ))) почти copy-paste из документации
} # 

//добавление сделки
#-----------
function fncAmocrmLeadsCreate(
    $strSubdomain,
    $strCookieFileName,
    $arrLeadsCreate
) {

    # почти copy-paste из документации (((	
    //$leads['request']['leads']['add'] = $arrLeadsCreate;
    $leads['add'] = $arrLeadsCreate;
    $subdomain=$strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/leads/set';
	$link = 'https://' . $subdomain . '.amocrm.ru/api/v2/leads';
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
    curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($leads));
    curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
     
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    
    $code=(int)$code;
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */
    $Response=json_decode($out,true);

    return array (
      'boolOk' => TRUE,
      'arrResponse' => $Response,
    );

    # ))) почти copy-paste из документации

} # function добавление сделки

function fncAmocrmLeadsUpdate(
    $strSubdomain,
    $strCookieFileName,
    $arrLeadsCreate
) {	
    # почти copy-paste из документации (((	
    $leads['request']['leads']['update'] = $arrLeadsCreate;
	$leads['update'] = $arrLeadsCreate;
    $subdomain=$strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/leads/set';
	$link = 'https://' . $subdomain . '.amocrm.ru/api/v2/leads';
   
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
    curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($leads));
    curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
     
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    
    $code=(int)$code;
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */
    $Response=json_decode($out,true);
	echo "Update:".$out;
    return array (
      'boolOk' => TRUE,
      'arrResponse' => $Response,
    );

    # ))) почти copy-paste из документации

} # function обновление сделки

function fncAmocrmNotesCreate(
    $strSubdomain,
    $strCookieFileName,
    $arrNotesCreate
) {

    # почти copy-paste из документации (((

    $notes['request']['notes']['add']= $arrNotesCreate;

    $subdomain=$strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/notes/set';
    
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
    curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($notes));
    curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
     
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    
    $code=(int)$code;
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */
    $Response=json_decode($out,true);

    return array (
      'boolOk' => TRUE,
      'arrResponse' => $Response['response'],
    );

    # ))) почти copy-paste из документации

} # function создать примечание

function fncAmocrmNotesList(
    $strSubdomain,
    $strCookieFileName,
    $type,
	$elemetid = "",
	$limit = 500,
	$offset = 0,
	$modified = null
) {
	

    # почти copy-paste из документации (((
	
    $subdomain = $strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/notes/list';	
	$link.="?type=".$type."&element_id=".$elemetid;
	$link .= "&limit_rows=". urlencode($limit);
	$link .= "&limit_offset=". urlencode($offset);
	
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
	if ($modified) {
		curl_setopt($curl,CURLOPT_HTTPHEADER,array('IF-MODIFIED-SINCE: '.date("D, d M Y H:i:s",$modified)));
	}
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
	$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
	curl_close($curl); 
    //$resout = gettype($out);
	// --- 628 - 600,500,525 -> 530
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */

    $Response=json_decode($out,true);
	if ($out=="") {
		return array (
      		'boolOk' => FALSE,
      		'arrResponse' => "",
    	);
	} else {
    	return array (
      		'boolOk' => TRUE,
      		'arrResponse' => $Response['response'],
    	);
	}
	
    # ))) почти copy-paste из документации
} # 

function fncAmocrmNotesList2(
    $strSubdomain,
    $strCookieFileName,
    $type,
	$elemetid = "",
	$limit = 500,
	$offset = 0,
	$modified = null
) {
	

    # почти copy-paste из документации (((
	
    $subdomain = $strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/notes';	
	$link.="?type=".$type;
	if($elemetid == "") {
		
	} else {
		$link.= "&element_id=".$elemetid;
	}
	$link .= "&limit_rows=". urlencode($limit);
	$link .= "&limit_offset=". urlencode($offset);
	
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
	if ($modified) {
		curl_setopt($curl,CURLOPT_HTTPHEADER,array('IF-MODIFIED-SINCE: '.date("D, d M Y H:i:s",$modified)));
	}
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
	$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
	curl_close($curl); 
    //$resout = gettype($out);
	// --- 628 - 600,500,525 -> 530
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */

    $Response=json_decode($out,true);
	if ($out=="") {
		return array (
      		'boolOk' => FALSE,
      		'arrResponse' => "",
    	);
	} else {
    	return array (
      		'boolOk' => TRUE,
      		'arrResponse' => $Response['response'],
    	);
	}
	
    # ))) почти copy-paste из документации
} # 
//Получает контакт по id контакта
function fncAmocrmContactsListById(
    $strSubdomain,
    $strCookieFileName,
    $query1512 = ''
) {

    # почти copy-paste из документации (((
	
    $subdomain=$strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/contacts/list';
    if (
        $query1512 != ''
    ) {
        $link .= '?id=' . urlencode($query1512);
    } # if
    
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

    $strout=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);

    $code=(int)$code;
	//$resout = gettype($out);
	//@file_put_contents("curl.txt",$strout);
	//$out2 = quotemeta($out);
	// --- 628 - 600,500,525 -> 530
	//$outpos = strpos($strout,'"custom_fields":[{');
	//$outpos = $outpos - 1;
	//$out2 = substr($strout, 0, $outpos);	
	//$out2 .= '}]}}';
	$out2 = $strout;
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */
	
	
    $Response=json_decode($out2,true);
	
    return array (
      'boolOk' => TRUE,
      'arrResponse' => $Response['response'],
    );
	
    # ))) почти copy-paste из документации

} # function
//Получает контакт по id контакта

function fncAmocrmCompanyList(
    $strSubdomain,
    $strCookieFileName,
    $query1512 = '',	
	$limit = 500,
	$offset = 0
) {

    # почти copy-paste из документации (((
	
    $subdomain=$strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/company/list';
   $flagg = "";
    if (
        $query1512 != ''
    ) {
        $link .= '?id='. urlencode($query1512);
		$flagg = "1";
    } # if
	
	if ($flagg == "") {
		$link .= "?limit_rows=". urlencode($limit);
		$link .= "&limit_offset=". urlencode($offset);
	} else {
		$link .= "&limit_rows=". urlencode($limit);
		$link .= "&limit_offset=". urlencode($offset);
	}
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
	$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
	curl_close($curl); 
    //$resout = gettype($out);
	// --- 628 - 600,500,525 -> 530
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */

    $Response=json_decode($out,true);
	if ($out=="") {
		return array (
      		'boolOk' => FALSE,
      		'arrResponse' => "",
    	);
	} else {
    	return array (
      		'boolOk' => TRUE,
      		'arrResponse' => $Response['response'],
    	);
	}
	
    # ))) почти copy-paste из документации

} # 
#-----------

#-----------
function fncAmocrmContactsListByResponsibleID(
    $strSubdomain,
    $strCookieFileName,
    $strresponsibleid = ''
) {	
	//example - domitex.amocrm.ru/private/api/v2/json/contacts/list?responsible_user_id=628743
    # почти copy-paste из документации (((

    $subdomain = $strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/contacts/list';
    if (
        $strresponsibleid != ''
    ) {
        $link .= '?responsible_user_id=' . urlencode($strresponsibleid);
    } # if
    
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
     
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    
    $code=(int)$code;
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */
    $Response=json_decode($out,true);

    return array (
      'boolOk' => TRUE,
      'arrResponse' => $Response['response'],
    );

    # ))) почти copy-paste из документации

} # function
#-----------
#-----------
function fncAmocrmContactsList(
    $strSubdomain,
    $strCookieFileName,
    $query = '',	
	$limit = 500,
	$offset = 0
) {

    # почти copy-paste из документации (((

    $subdomain=$strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    //$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/contacts/list';
	$link='https://'.$subdomain.'.amocrm.ru/api/v2/contacts';
	
	$flagg = "";
    if (
        $query != ''
    ) {
        $link .= '?query='. urlencode($query);
		$flagg = "1";
    } # if
	
	if ($flagg == "") {
		$link .= "?limit_rows=". urlencode($limit);
		$link .= "&limit_offset=". urlencode($offset);
	} else {
		$link .= "&limit_rows=". urlencode($limit);
		$link .= "&limit_offset=". urlencode($offset);
	}    
    
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
     
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    
    $code=(int)$code;
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */
    $Response=json_decode($out,true);
	if (is_null($Response)) {
		return array (
			'boolOk' => FALSE,
			'arrResponse' => $Response,
		);
	} else {
		return array (
			'boolOk' => TRUE,
			'arrResponse' => $Response,
		);
	}
    # ))) почти copy-paste из документации

} # function
#-----------
#-----------Получает связь между контактами и сделками
function fncAmocrmContactsGet(
    $strSubdomain,
    $strCookieFileName,
    $clink = '',
	$dlink = ''
) {
	# почти copy-paste из документации (((

    $subdomain = $strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/contacts/links';
    if (
        $clink != ''
    ) {
        $link .= '?contacts_link=' . urlencode($clink);
    } # if
	if (
        $dlink != ''
    ) {
        $link .= '?deals_link=' . urlencode($dlink);
    } # if
    
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
     
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    
    $code=(int)$code;
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */
    $Response = json_decode($out,true);

    return array (
      'boolOk' => TRUE,
      'arrResponse' => $Response['response'],
    );

    # ))) почти copy-paste из документации
    

} # function
#-----------
function fncAmocrmLeadsListAll(
    $strSubdomain,
    $strCookieFileName,
    $query1512 = '',
	$limit = 500,
	$offset = 0,
	$modified = null
) {
	

    # почти copy-paste из документации (((
	
    $subdomain = $strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/leads/list';
	$flagg = "";
    if (
        $query1512 != ''
    ) {
        $link .= '?id='. urlencode($query1512);
		$flagg = "1";
    } # if
	
	if ($flagg == "") {
		$link .= "?limit_rows=". urlencode($limit);
		$link .= "&limit_offset=". urlencode($offset);
	} else {
		$link .= "&limit_rows=". urlencode($limit);
		$link .= "&limit_offset=". urlencode($offset);
	}
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_HEADER,false);
	if ($modified) {
		curl_setopt($curl,CURLOPT_HTTPHEADER,array('IF-MODIFIED-SINCE: '.date("D, d M Y H:i:s",$modified)));
	}
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
	$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
	curl_close($curl); 
    //$resout = gettype($out);
	// --- 628 - 600,500,525 -> 530
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */

    $Response=json_decode($out,true);
	if ($out=="") {
		return array (
      		'boolOk' => FALSE,
      		'arrResponse' => "",
    	);
	} else {
    	return array (
      		'boolOk' => TRUE,
      		'arrResponse' => $Response['response'],
    	);
	}
	
    # ))) почти copy-paste из документации
} # 

function fncAmocrmLeadsList(
    $strSubdomain,
    $strCookieFileName,
    $query1512 = '',
	$limit = 500,
	$offset = 0,
	$responsible_user_id = '',
	$modified = null
) {
	

    # почти copy-paste из документации (((
	
    $subdomain = $strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/api/v2/leads';
	$flagg = "";
    if (
        $query1512 != ''
    ) {
        $link .= '?id='. urlencode($query1512);
		$flagg = "1";
    } # if
	if (
		$responsible_user_id != ''
	) {
		if ($flagg == "") {
			$link .= '?responsible_user_id='. urlencode($responsible_user_id);
		} else {
			$link .= '&responsible_user_id='. urlencode($responsible_user_id);
			$flagg = "1";
		}	
	}
	if ($flagg == "") {
		$link .= "?limit_rows=". urlencode($limit);
		$link .= "&limit_offset=". urlencode($offset);
	} else {
		$link .= "&limit_rows=". urlencode($limit);
		$link .= "&limit_offset=". urlencode($offset);
	}
	//echo $link;
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_HEADER,false);
	if ($modified) {
		curl_setopt($curl,CURLOPT_HTTPHEADER,array('IF-MODIFIED-SINCE: '.date("D, d M Y H:i:s",$modified)));
		//echo 'IF-MODIFIED-SINCE: '.date("D, d M Y H:i:s",$modified);
	}
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
	$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
	curl_close($curl); 
    //$resout = gettype($out);
	// --- 628 - 600,500,525 -> 530
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */

    $Response=json_decode($out,true);
	
	if ($out=="") {
		return array (
      		'boolOk' => FALSE,
      		'arrResponse' => "",
    	);
	} else {
    	return array (
      		'boolOk' => TRUE,
      		'arrResponse' => $Response,
    	);
	}
	
    # ))) почти copy-paste из документации
} # 

//fncAmocrmGetFieldValuesById - получает массив значений поля из аккаунта АМО
function fncAmocrmGetFieldValuesById(
		$strSubdomain,
		$strCookieFileName,
		$strFieldId,
		$strObjType
	) {		
		//'$strObjType = 'contacts','leads','companies'
		$subdomain=$strSubdomain; #Наш аккаунт - поддомен
		#Формируем ссылку для запроса
		$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/accounts/current';
		$curl=curl_init(); #Сохраняем дескриптор сеанса cURL
		#Устанавливаем необходимые опции для сеанса cURL
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
		curl_setopt($curl,CURLOPT_URL,$link);
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
 
		$out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
		$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
		curl_close($curl);
		$code=(int)$code;
		$errors=array(
			301=>'Moved permanently',
			400=>'Bad request',
			401=>'Unauthorized',
			403=>'Forbidden',
			404=>'Not found',
			500=>'Internal server error',
			502=>'Bad gateway',
			503=>'Service unavailable'
		);
		try
		{
			#Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
		if($code!=200 && $code!=204)
		throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error',$code);
		}
		catch(Exception $E)
		{
			//die('Ошибка: '.$E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode());
			return array (
				'boolOk' => False,
				'arrResponse' => "Error",
			);
		}
 
		/**
		* Данные получаем в формате JSON, поэтому, для получения читаемых данных,
		* нам придётся перевести ответ в формат, понятный PHP
		*/
		$Response=json_decode($out,true);
		$Response=$Response['response']; #Response - объект класса StdClass			
		if ($strObjType=='contacts') {
			foreach ($Response['account']['custom_fields'][$strObjType] as $customfield) {
				if ($strFieldId == $customfield['id']) {
					return array (
						'boolOk' => TRUE,
						'arrResponse' => $customfield['enums'],
					);
				}
			}
		}
		if ($strObjType=='leads') {
			foreach ($Response['account']['custom_fields'][$strObjType] as $customfield) {
				if ($strFieldId == $customfield['id']) {
					return array (
						'boolOk' => TRUE,
						'arrResponse' => $customfield['enums'],
					);
				}
			}
		}
		if ($strObjType=='companies') {
			foreach ($Response['account']['custom_fields'][$strObjType] as $customfield) {
				if ($strFieldId == $customfield['id']) {
					return array (
						'boolOk' => TRUE,
						'arrResponse' => $customfield['enums'],
					);
				}
			}
		}
		
	}
	// - конец
function fncAmocrmCatalogElementList(
    $strSubdomain,
    $strCookieFileName,
	$catalogid,
	$elementid = "",
    $query = '',
	$page = ""
) {

    # почти copy-paste из документации (((

    $subdomain=$strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/catalog_elements/list';	
	$flagg = "";    
    $link .= '?catalog_id='. urlencode($catalogid);
	if ($elementid != "") {
		$link .= '&id='.urlencode($elementid);
	}
	if ($query != "") {
		$link .= '&term='.urlencode($query);    
	}  
    //echo $link."<br>";
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
     
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    
    $code=(int)$code;
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */
    $Response=json_decode($out,true);

    return array (
      'boolOk' => TRUE,
      'arrResponse' => $Response['response'],
    );

    # ))) почти copy-paste из документации

} # function
#-----------
function fncAmocrmGetCatalogFromElement(
    $strSubdomain,
    $strCookieFileName,
	$from,
	$from_id,    
	$to_catalog_id,
	$from_type
) {
	//from_type = Данный параметр указывает на тип сущности: 1 - контакт, 2 - сделка, 3 - компания, 12 - покупатель.
    # почти copy-paste из документации (((

    $subdomain=$strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/links/list';
	$flagg = "";    
	//?links[0][from]=leads&links[0][from_id]=36163&links[0][to]=catalog_elements&links[0][from_catalog_id]=2&links[0][to_catalog_id]=1119
    $link .= '?links[0][from]='. urlencode($from)."&links[0][from_id]=".urlencode($from_id)."links[0][to]=catalog_elements&links[0][to_catalog_id]=".urlencode($to_catalog_id)."&links[0][from_catalog_id]=".$from_type;	
	//echo $link;
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
     
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    
    $code=(int)$code;
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */
    $Response=json_decode($out,true);

    return array (
      'boolOk' => TRUE,
      'arrResponse' => $Response['response'],
    );

    # ))) почти copy-paste из документации

} # function

function fncAmocrmGetCatalogList(
    $strSubdomain,
    $strCookieFileName,
	$id = ""
) {
    # почти copy-paste из документации (((
    $subdomain=$strSubdomain; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/catalogs/list';	
	if ($id != "") {
		$link .= '?id='.$id;	
	}	
	//echo $link."<br>";
    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
    curl_setopt($curl,CURLOPT_URL,$link);
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR, $strCookieFileName); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
     
    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    
    $code=(int)$code;
    $errors=array(
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    try
    {
      #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
      if($code!=200 && $code!=204)
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code),
        );
    }
    catch(Exception $E)
    {
        return array (
          'boolOk' => FALSE,
          'strErrDevelopUtf8' => 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode(),
        );
    }
     
    /**
     * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     * нам придётся перевести ответ в формат, понятный PHP
     */
    $Response=json_decode($out,true);

    return array (
      'boolOk' => TRUE,
      'arrResponse' => $Response['response'],
    );

    # ))) почти copy-paste из документации

} # function
function SendMailToAdmin($sendto, $subject1, $link) {
	$to= $sendto  ; //обратите внимание на запятую

	/* тема/subject */
	$subject = $subject1;

	/* сообщение */
	$message = '
		<html>
			<head>
				<title>Были созданы новые заявки с сайта</title>
			</head>
			<body>
				<p>Были созданы новые заявки с сайта</p><br>
				<a href="'.$link.'">'.$link.'</a>
			</body>
		</html>
	';

	/* Для отправки HTML-почты вы можете установить шапку Content-type. */
	$headers= "MIME-Version: 1.0\r\n";
	$headers .= "Content-type: text/html; charset=utf-8\r\n";
}
	// - конец
?>