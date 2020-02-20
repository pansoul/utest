<?php
error_reporting (E_ALL & ~E_WARNING & ~E_NOTICE);
@session_start();

define('CONFIG_FILE', '/app/config/main.php');
define('SQL_FILE', '/app/config/extra/utest.sql');

$arStep = array(
    'welcome' => 'Установка системы онлайн тестирования UTest',
    'connect' => 'Создание подключения к БД',
    'import' => 'Импорт БД'
);
$step = $_GET['step'] ? $_GET['step'] : 'welcome';
$title = $arStep[$step];
$html = '';

if (!array_key_exists($step, $arStep)) {
    die('Недопустимый параметр');
}

function showError($message)
{
    return "<div class='alert alert-danger'>{$message}</div>";
}

function showSuccess($message)
{
    return "<div class='alert alert-success'>{$message}</div>";
}

function showWarning($message)
{
    return "<div class='alert alert-warning'>{$message}</div>";
}

class Connection {

	private $dbh;
	private $usedDB = false;
	private $error = false;
	
	public function __construct($host, $port, $user, $pass)
	{
		try {
			$this->dbh = new PDO("mysql:host={$host};port={$port};charset=utf8", $user, $pass); 
			//$this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			//$this->dbh->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);			
			//$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);			
		} catch (PDOException $e) {                     
			$this->error = "Не удалось установить соединение <br/>" . $e->getMessage();                
		}		
	}
	
	public function close()
	{
		$this->dbh = null;
		$this->usedDB = false;
	}
	
	public function checkDB($dbname, $create = false)
	{
		if (!$this->dbh) {
			return false;
		}
		
		if (!$dbname) {   
			$this->error = "Укажите имя базы данных."; 
		} elseif (!$create && !$this->checkDBExists($dbname)) {                    
			$this->error = "База данных '{$dbname}' не найдена.";                    
		} else {
			return true;
		}
	}
	
	public function import($sql_file, $dbname, $create)
	{
		if (!$this->dbh) {
			return false;
		}
		
		if ($this->checkDB($dbname, $create)) 
		{
			if (!$this->checkDBExists($dbname)) {
				$res = $this->dbh->query("CREATE DATABASE `{$dbname}`");
				if (!$res) {
					$e = $this->dbh->errorInfo();
					$this->error = "Ошибка при создании базы <br/>{$e[2]}";
					return false;
				}
			}			
			
			$this->setDB($dbname);
			
			if (!$this->dropAllTables()) {
				$this->error = "Не удалось очистить базу";
				return false;
			}
			
			// @todo А что, если "точка с запятой", "комментарии" или "переводы строк" будут в записываемом значении?!
			
			$sql = file_get_contents($sql_file);
			// Удалим комментарии
			$sql = trim( preg_replace('!/\*.*?\*/!s', '', $sql) );	
			// Удалим переводы строк
			$sql = preg_replace('![\r\n]*!', '', $sql);
			// Разбиваем файл на отдельные запросы
			$sqlQueries = array_filter( explode(';', $sql) );	
			
			$countQueries = count($sqlQueries);			
			$lastCountQueriesErrors = 0;
			$arQueriesSucces = array();
			$importSuccess = false;
			$doQuery = true;			
			
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			while ($doQuery)
			{
				$countQueriesErrors = 0;		
				$errorsLog = array();				
				
				foreach ($sqlQueries as $key => $query)
				{
					// Проверяем, был ли этот запрос уже обработан ранее
					if (in_array($key, $arQueriesSucces)) {
						continue;
					}
					
					// Пытаемся выполнить запрос
					try {
						$this->dbh->query($query);
					} 
					// При неудачном выполнении запроса переходим к следующему запросу, 
					// прежде выполнив действия ниже:
					catch (PDOException $e) { 
						// Увеличиваем текущий счётчик ошибок
						$countQueriesErrors++;
						// Записываем ошибку в лог
						$errorsLog[] = $e->getMessage();	
						// Если общее количество ошибок равняется количеству общих запросов
						// или их количество не изменилось с предыдущего цикла, то завершаем цикл.
						if ($countQueriesErrors == $countQueries || $countQueriesErrors == $lastCountQueriesErrors)
						{
							$doQuery = false;
							break;
						}
						continue;
					}
					
					// Записываем ключ успешно выполненного запроса.
					$arQueriesSucces[] = $key;
					// Если количество успешных запросов равно общему количеству запросов,
					// то выходим из цикла! Импорт успешно завершён! B-)
					if (count($arQueriesSucces) == $countQueries) {
						$doQuery = false;
						$importSuccess = true;
						break;
					}					
				}
				
				// Запоминаем текущий счетчик количества ошибок
				$lastCountQueriesErrors = $countQueriesErrors;
			}
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);			
			
			if (!$importSuccess) {				
				$this->error = "Лог ошибок при импорте файла <br/><ol><li>" . implode('</li><li>', $errorsLog) . "</li></pre>";
				return false;
			}
			
			return true;
		}		
	}
	
	public function setAdminPassword($password)
	{
		if (!$this->dbh || !$this->usedDB) {
			return false;
		}
        $salt = $this->generateSalt();
        $passwordHash = md5(sha1($password).$salt);
        $res = $this->dbh->exec("UPDATE `u_user` SET `password` = '{$passwordHash}', `salt` = '{$salt}' WHERE `id` = 1;");
        if (!$res) {
			$e = $this->dbh->errorInfo();
            $this->errors[] = "Ошибка обновления данных Администратора <br/>{$e[2]}";
            return false;
        }			
        return true;
	}
	
	public function checkDBExists($dbname)
	{
		if (!$this->dbh) {
			return false;
		}		
		$res = $this->dbh->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$dbname}'");
		return $res->fetchColumn();				
	}
	
	private function setDB($dbname)
	{
		if (!$this->dbh) {
			return false;
		}				
		$this->dbh->query("USE `{$dbname}`");
		$this->usedDB = $dbname;
	}
	
	private function dropAllTables()
	{
		if (!$this->dbh || !$this->usedDB) {
			return false;
		}
		$res = $this->dbh->query("
			SELECT 
				TABLE_NAME
			FROM information_schema.TABLES 
			WHERE
				TABLE_TYPE='BASE TABLE'
				AND TABLE_SCHEMA = '{$this->usedDB}';
		");
		$tables = $res->fetchAll();		
		$countQueries = count($tables);			
		if (!$countQueries) {
			return true;
		}		
		$lastCountQueriesErrors = 0;
		$arTablesDeleted = array();
		$deleteSuccess = false;
		$doQuery = true;
		$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		while ($doQuery)
		{
			$countQueriesErrors = 0;		
			$errorsLog = array();				
			
			foreach ($tables as $value)
			{
				$table = $value['TABLE_NAME'];
				if (in_array($table, $arTablesDeleted)) {
					continue;
				}				
				try {
					$this->dbh->query("DROP TABLE `{$table}`;");
				} catch (PDOException $e) { 					
					$countQueriesErrors++;					
					$errorsLog[] = $e->getMessage();	
					if ($countQueriesErrors == $countQueries || $countQueriesErrors == $lastCountQueriesErrors)
					{
						$doQuery = false;
						break;
					}
					continue;
				}
				$arTablesDeleted[] = $table;
				if (count($arTablesDeleted) == $countQueries) {
					$doQuery = false;
					$deleteSuccess = true;
					break;
				}					
			}
			$lastCountQueriesErrors = $countQueriesErrors;
		}
		$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);		
		return $deleteSuccess;
	}
	
	private function generateSalt()
    {
        $salt = '';        
        $length = rand(5, 10); 
        for ($i = 0; $i < $length; $i++) {
            $salt .= chr(rand(33, 126));
        }
        return $salt;
    }
	
	public function getError()
	{
		return $this->error;
	}
}

// Step 1
//------------------------------------------------------------------------------
if ($step == 'welcome') 
{
    $_SESSION['connection'] = array();
    $f = file_get_contents($_SERVER['DOCUMENT_ROOT'].CONFIG_FILE);
    $isExist = file_exists($_SERVER['DOCUMENT_ROOT'].CONFIG_FILE) && is_writable($_SERVER['DOCUMENT_ROOT'].CONFIG_FILE);
    $issetRFields = preg_match("/#host#/i", $f) && preg_match("/#port#/i", $f) && preg_match("/#user#/i", $f) && preg_match("/#pass#/i", $f) && preg_match("/#name#/i", $f);
    $isSqlDumpExists = file_exists($_SERVER['DOCUMENT_ROOT'].SQL_FILE) && is_writable($_SERVER['DOCUMENT_ROOT'].SQL_FILE);

    $primaryLog = [];
    $primaryLog[] = "наличие конфигурационного файла - " . ($isExist ? "<span style='color:green'>ok</span>" : "<span style='color:red'>error</span> (Файл '".CONFIG_FILE."' не найден или недоступен для записи)");
    $primaryLog[] = "наличие изменяемых полей в конфигурационном файле - " . ($issetRFields ? "<span style='color:green'>ok</span>" : "<span style='color:red'>error</span> (Не найдены один или несколько масок изменяемых полей: #host#, #port#, #user#, #pass#, #name#)");
    $primaryLog[] = "наличие sql дампа - " . ($isSqlDumpExists ? "<span style='color:green'>ok</span>" : "<span style='color:red'>error</span> (Файл '".SQL_FILE."' не найден или недоступен для записи)");

    $html .= "<p>";
    $html .= "Лог первичных настроек:";
    $html .= "<ul>";
    $html .= "<li>" . join('</li><li>', $primaryLog) . "</li>";
    $html .= "</ul>";
    $html .= "</p>";
    $html .= "<a class='btn' href='?step=connect'>далее</a>";
} 
// Step 2
//------------------------------------------------------------------------------
elseif ($step == 'connect') 
{
    $check 	= '';
    $arData = $_SERVER['REQUEST_METHOD'] == 'POST' ? $_POST : $_SESSION['connection'];    
    $host 	= isset($arData['host']) ? trim($arData['host']) : 'localhost';
    $port 	= isset($arData['port']) ? trim($arData['port']) : '';
    $user 	= isset($arData['user']) ? trim($arData['user']) : 'root';
    $pass 	= isset($arData['pass']) ? trim($arData['pass']) : '';
    $name 	= isset($arData['name']) ? trim($arData['name']) : 'utest';
	$create = isset($arData['create']) ? intval($arData['create']) : 1;
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {        
        if ($_POST['btn'] == 'check') {     
			$con = new Connection($host, $port, $user, $pass);
			$con->checkDB($name, $create);
			$check = $con->getError()
				? showError($con->getError())
				: showSuccess("Соединение успешно установлено.");			
			$con->close();			
        } 
        elseif ($_POST['btn'] == 'next') {			
            $_SESSION['connection'] = array(
                'host' 		=> $host,
                'port' 		=> $port,
                'user' 		=> $user,
                'pass' 		=> $pass,
                'name' 		=> $name,
				'create' 	=> $create
            );
            header("Location: /setup.php?step=import");
            exit;
        }  
    }
    
    $html .= "<p>";
    $html .= "Для установки соединения с БД, необходимо заранее иметь данные для её подключения.<br/>";
    $html .= "Если вы не владеете нужной информацией, обратитесь к вашему системному администратору, или хостеру.";
    $html .= showWarning("Внимание! Импорт в существующую базу удалит всю информацию из неё.");
    $html .= $check;    
    $html .= "<form method='post' action=''>";
    $html .= "<table class='table-connect' border='0'>";
    
    $html .= "<tr>";
    $html .= "<td><label for='host'>Адрес MySQL хоста:</label></td>";
    $html .= "<td><input id='host' name='host' value='{$host}' /></td>";
    $html .= "</tr>";
    
    $html .= "<tr>";    
    $html .= "<td><label for='port'>Номер порта:</label></td>";
    $html .= "<td><input id='port' size='5' name='port' value='{$port}' /></td>";
    $html .= "</tr>";
    
    $html .= "<tr>";    
    $html .= "<td><label for='user'>Имя пользователя:</label></td>";
    $html .= "<td><input id='user' name='user' value='{$user}' /></td>";
    $html .= "</tr>";
    
    $html .= "<tr>";
    $html .= "<td><label for='pass'>Пароль:</label></td>";
    $html .= "<td><input id='pass' name='pass' value='{$pass}' /></td>";
    $html .= "</tr>";
    
    $html .= "<tr>";
    $html .= "<td><label for='name'>Название БД:</label></td>";
    $html .= "<td><input id='name' name='name' value='{$name}' /></td>";
    $html .= "</tr>";
	
	$html .= "<tr>";
    $html .= "<td><label for='create'>Создать БД, если не существует:</label></td>";
    $html .= "<td>";
	$html .= "<input type='hidden' name='create' value='0' />";
	$html .= "<input " . ($create ? 'checked' : '') . " id='create' type='checkbox' name='create' value='1' />";
	$html .= "</td>";
    $html .= "</tr>";
    
    $html .= "</table>";    
    $html .= "<button class='btn' name='btn' value='check' type='submit'>проверить соединение</button>";
    $html .= "<button class='btn' name='btn' value='next' type='submit'>далее</button>";    
    $html .= "</form>";    
    $html .= "</p>";
}
// Step 3
//------------------------------------------------------------------------------
elseif ($step == 'import')
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$error 			= null;
		$password		= $_POST['password'];
        $cnfg 			= $_SESSION['connection'];
        $config_file 	= $_SERVER['DOCUMENT_ROOT'] . CONFIG_FILE;
        $sql_file 		= $_SERVER['DOCUMENT_ROOT'] . SQL_FILE;
		$f 				= file_get_contents($config_file);
			
        if (!file_exists($sql_file)) {
			$error = showError("Не найден дамп базы данных для импорта");            
        } elseif (!file_exists($config_file) || !is_writable($config_file)) {
			$error = showError("Конфигурационный файл '".CONFIG_FILE."' не найден или недоступен для записи");            
        } elseif (!(preg_match("/#host#/i", $f) && preg_match("/#port#/i", $f) && preg_match("/#user#/i", $f) && preg_match("/#pass#/i", $f) && preg_match("/#name#/i", $f))) {
			$error = showError("Не найдены один или несколько масок изменяемых полей: #host#, #port#, #user#, #pass#, #name#"); 
		} else {
			$con = new Connection($cnfg['host'], $cnfg['port'], $cnfg['user'], $cnfg['pass']);
			$con->import($sql_file, $cnfg['name'], $cnfg['create']);
			$con->setAdminPassword($password);
			$con->close();
			if ($con->getError()) {
				$error = showError($con->getError()); 
			} 
		}		
		
		if (!$error) {				
			$f = str_replace(
				array(
					'#host#',
					'#port#',
					'#user#',
					'#pass#',
					'#name#',
				), 
				array(
					$cnfg['host'],
					$cnfg['port'],
					$cnfg['user'],
					$cnfg['pass'],
					$cnfg['name'],
				),
				$f
			);
			file_put_contents($config_file, $f);
			rename('_index.php', 'index.php');
			@unlink(__FILE__);
			//@unlink($_SERVER['DOCUMENT_ROOT'] . SQL_FILE); @todo Нужно ли удалять дамп?
			header('Location: /');
			exit;
		}
    }    
    
    $html .= "<p>";	
    $html .= "На завершающем шаге система скорректирует главный кофигурационный файл с учетом введённых ранее данных и подготовит БД.<br/>";
    $html .= "После чего в систему можно зайти под администратором, используя логин и пароль, указанный ниже.<br/>";
	$html .= $error;
	
	$html .= "<form method='post' action=''>";
	$html .= "<table class='table-import' border='0'>";    
    $html .= "<tr>";
    $html .= "<td>Логин:</td>";
    $html .= "<td><b>admin</b></td>";
    $html .= "</tr>";
	$html .= "<tr>";
    $html .= "<td><label for='password'>Пароль:</label></td>";
    $html .= "<td><input id='password' autocomplete='off' name='password' value='{$password}' /></td>";
    $html .= "</tr>";
	$html .= "</table>";	    
	$html .= "<a title='изменить настройки подключения БД' class='btn' href='?step=connect'>назад</a>";
    $html .= "<button class='btn' type='submit'>начать импорт БД</button>";
	$html .= "</form>";   
}
?>

<!doctype html>
<html>
    <head>
        <title>Установка системы онлайн тестирования UTest</title>
        <meta charset="utf-8" />
        <style>            
            html, body{
                margin: 0;
                padding: 0;
            }
            
            body{
                font: 14px/1.7em Verdana;
                color: #333;
                background: #E6E6E6;
            }
            
            h1{
                background: #3E464E;
                color: #F1F2F0;
                font-weight: normal;
                margin: 0;
                padding: 15px;
            }
            
            p{
                margin: 0 0 20px;
            }
            
            #content{
                padding: 15px;
            }
            
            .btn{
                display: inline-block;
                text-decoration: none;
                line-height: 1;
                padding: 3px 15px;
                background: #fff;
                color: inherit;  
                margin: 20px 5px 0 0;
                border: none;
                cursor: pointer;
                font: inherit;                
            }
            .btn:hover{
                background: #f9f9f9;
            }
            
            .alert {
                padding: 15px;
                margin-bottom: 20px;
                border: 1px solid transparent;
                border-radius: 4px;
            }
            .alert-danger {
                background-color: #f2dede;
                border-color: #eed3d7;
                color: #b94a48;
            }
            .alert-success {
                background-color: #dff0d8;
                border-color: #d6e9c6;
                color: #468847;
            }
			.alert-warning {
                background-color: #fcf8e3;
				border-color: #fbeed5;
				color: #c09853;
            }
            .alert-info {
                background-color: #d9edf7;
                border-color: #bce8f1;
                color: #3a87ad;
            }
        </style>
    </head>
    <body>
        <h1><?=$title?></h1>
        <div id="content">
            <?=$html?>
        </div>
    </body>
</html>