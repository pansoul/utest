<?php

/**
 * Класс для работы пользователей с системой.
 * 
 * Была идея - создать класс(ы), реализующий не просто работу с пользователями,
 * а класс, представляющий из себя набор основных функций, позволяющий каждому определенному
 * пользователю совершать доступные только для него манипуляции с системой (осуществляется через роли). 
 * Таким образом, каждый из определюящего какую-либо роль классов пользователей
 * описывает уникальный набор методов и свойств некого "персонажа".
 * 
 * P.S. Идея была интересна, но, к сожалениею, не оправдала всех возложенных на 
 * неё ожиданий и в итоге Я посчитал её неудачной. 
 * Возможно кто-нибудь сможет довести идею до идеальных соотношений
 * эффективность/трудозатраты, или вовсе переписать данный функционал.
 */

class UUser {
    /**
     * Данную роль нельзя переопределять.
     * Список пользовательских ролей содержится в файле roles.php в папке конфигурации
     */
    const VIP_ROLE = 'admin';    
    
    protected $arAvailableGet = array (
        
    );
    
    /**
     * Системный список доступных ролей
     * @var array 
     */
    protected static $arRoles = array(       
        'prepod' => array(
            'name'  => 'Преподаватель'
        ),
        'student' => array(
            'name'  => 'Студент'
        ),
    );    
    
    /**
     * Id текущего пользователя
     * @var integer 
     */
    protected static $uid = 0;
    
    /**
     * Id запрошенного пользователя     
     * @var integer
     */
    protected static $query_uid;
    
    /**
     * Имя пользователя
     * @var string 
     */
    protected $name;
    
    /**
     * Какая роль у пользователя
     * @var string 
     */
    protected $role;
    
    /**
     * К какой группе принадлежит текущая роль пользователя.
     * Название группы будет иметься только у унаследованных групп пользователей, у
     * групп-родителей этот параметр будет выводить "0"
     * @var string 
     */
    protected $group;
    
    /**
     * Содержит название самой корневой группы, от которой произошло 
     * любое наследование для текущей группы.
     * @var string 
     */
    protected $rootgroup;
    
    protected static $table = 'u_user';
    protected static $table_roles = 'u_user_roles';
    
    /**
     * Собственно, это и есть текущий пользователь с доступными только для 
     * него набором определенных свойств и методов
     * @var object
     */
    private static $user;
    
    /**
     * Объект запрашиваемого пользователя.
     * Функционал у данного пользователя ограничен. А именно, он не имеет доступ к метода
     * класса группы, к которой он относится.
     * @var object
     */
    private static $query_user;
    
    /**
     * Флаг, определяющий, введётся ли работа с текущим авторизованным пользователем
     * или же с запрошенным.
     * @var bool
     */
    protected static $isCurrentAuth;
    
    /**
     * Переменная содержит список текстовых ошибок
     * @var array
     */
    public static $last_errors = array();

    private function __construct()
    {        
       //
    }
    
    public function __call($name, $arguments)
    {
        print USiteErrors::warning("Метод '$name' не существует у данного пользователя");
    }
    
    public static function __callStatic($name, $arguments) 
    {
        print USiteErrors::warning("Статический метод '$name' не существует у данного пользователя");
    }
    
    /**
     * Только через эту функцию можно обращаться к методам и свойствам пользователя.
     * 
     * Если $identification не передан, то будет браться текущий авторизованный
     * пользователь, иначе передастся оъбъект запрашиваемого пользователя.
     * @return object
     */
    public static function user($identification)
    {        
        if (self::$user && empty($identification)) {                 
            self::$isCurrentAuth = true;
            return self::$user;
        } else            
            return self::getUser($identification);                     
    }
    
    /**
     * Функция находит пользователя по текущему Id. 
     * Возвращает нам объект пользователя, в зависимости от его принадлежности к группе
     * @return object
     * @throws Exception
     */
    private static function getUser($identification)
    {   
        // Если пользователь авторизован (при первом его обращении)
        if (isset($_SESSION['u_uid']))
            self::$uid = $_SESSION['u_uid'];
        
        // Если пользователь не авторизован и нет запроса на получение иного пользователя        
        if (!self::$uid && !$identification)            
            return new UEmptyUser($identification);
        
        // Если есть запрос на получение произвольного пользователя и при этом
        // текущий пользователь не такой же, как запрашиваемый
        if ($identification && self::$uid != $identification && is_integer($identification)) {            
            
            self::$isCurrentAuth = false;
            
            // Если запрашиваемый пользователь уже есть в "кеше", то выдаём его сразу
            if (self::$query_uid == $identification) 
                return self::$query_user;            
            
            $query_user = R::load(self::$table, $identification);            
            if (!$query_user->id)
                return new UEmptyUser($identification);           
        
            $newUserClass = ucfirst($query_user->role);
            $newUserClass_path = KERNEL_PATH . '/user/roles/' . $newUserClass . '.php';

            if (!file_exists($newUserClass_path))
                throw new UAppException(sprintf("Файла '%s' для типа пользователя '%s' не нашлось", $newUserClass_path, $newUserClass));

            self::$query_uid = $identification;  
            self::$query_user = new $newUserClass;
            self::$query_user->name = $query_user->name;            
            self::$query_user->role = $query_user->role;   
            self::$query_user->setGroups($query_user->role);
            return self::$query_user;            
            
        } elseif (self::$uid == $identification)
            return self::$user;
        elseif ($identification === 0)
            return new UEmptyUser($identification);        
        
        self::$isCurrentAuth = true;        
        $user = R::load(self::$table, self::$uid);
        
        $newUserClass = ucfirst($user->role);
        $newUserClass_path = KERNEL_PATH . '/user/roles/' . $newUserClass . '.php';
        
        if (!file_exists($newUserClass_path))
            throw new UAppException (sprintf ("Файла '%s' для типа пользователя '%s' не нашлось", $newUserClass_path, $newUserClass));   
        
        self::$user = new $newUserClass;
        self::$user->name = $user->name;
        self::$user->role = $user->role;  
        self::$user->setGroups($user->role);        
        
        return self::$user;
    }
    
    /**
     * Устанавливает для пользователя такие значения как "название" и "название корневой группы"
     * @param string $role
     */
    private function setGroups($role) 
    {       
        $rootGroup = R::findOne(self::$table_roles, 'type = ?', array($role));
        $this->group = $rootGroup->group;        
        
        while ($rootGroup->group != '0') {                                    
            $rootGroup = R::findOne(self::$table_roles, 'type = ?', array($rootGroup->group));            
        }                
        $this->rootgroup = $rootGroup->type;
    }
    
    /**
     * Находит пользователя по переданному Id
     * @param integer $uid
     * @return boolean|object
     */
    public static function getById($uid)
    {
        $_e = array();
        $user = R::load(self::$table, $uid);
        if (!$user->id) {
            $_e[] = "Пользователя с Id = '$uid' не существует";
            
            self::$last_errors = $_e;
            return false;
        }        
        return $user;
    }

    /**
     * Находит пользователя по переданному логину
     * @param string $login
     * @return boolean|object
     */
    public static function getByLogin($login)
    {
        $_e = array();
        $user = R::findOne(self::$table, 'login = ?', array((string)$login));
        if (!$user->id) {
            $_e[] = "Пользователя с login = '$login' не существует";
            
            self::$last_errors = $_e;
            return false;
        }        
        return $user;
    }
    
    /**
     * Возвращает Id пользователя
     * @return integer
     */
    public function getUID()
    {
        if (self::$isCurrentAuth)
            return self::$uid;
        else            
            return self::$query_uid;
    }
    
    /**
     * Возвращает имя пользователя
     * @return string
     */
    public function getName()
    {   
        if (self::$isCurrentAuth)
            return self::$user->name;
        else            
            return self::$query_user->name;
    }

    /**
     * Возвращает название роли пользователя
     * @return string
     */
    public function getRole()
    {
        if (self::$isCurrentAuth)
            return self::$user->role;
        else            
            return self::$query_user->role;
    }

    /**
     * Возвращает название группы пользователя
     * @return string
     */
    public function getGroup()
    {
        if (self::$isCurrentAuth)
            return self::$user->group;
        else            
            return self::$query_user->group;
    }
    
    /**
     * Возвращает название самой корневой группы пользователя
     * @return string
     */
    public function getRGroup()
    {
        if (self::$isCurrentAuth)
            return self::$user->rootgroup;
        else            
            return self::$query_user->rootgroup;
    }

    public function getFields($arFields, $uid)
    {
        $_e = array();
        $arOut = array();
        $uid = intval($uid);
        
        if (!$uid) {
            if (self::$isCurrentAuth)
                $uid = self::$uid;
            else
                $uid = self::$query_uid;
        }        
        
        $user = R::load(self::$table, $uid);
        
        if ($arFields === '*') {
            foreach ($user as $k => $v)
                $arOut[$k] = $v;
        } elseif (!is_array($arFields) || empty($arFields)) {
            $_e[] = "Запрашиваемые поля пользователя должны быть массивом";
            self::$last_errors = $_e;
            return false;
        } else {
            foreach ($arFields as $v)
            {
                //if (!isset($user->$v)) {
                if (property_exists($user, $v)) {
                    $_e[] = "Поля '$v' не существует в списке свойств пользователей";
                    self::$last_errors = $_e;
                    return false;
                }   
                $arOut[$v] = $user->$v;
            }
        }
        
        return $arOut;
    }

    public function doAction($fromRole, $action, array $args = array())
    {
        $_e = array();
        $arAvailableRoles = glob(KERNEL_PATH . '/user/roles/*.php');
        foreach ($arAvailableRoles as &$oneDir)
            $oneDir = basename($oneDir, '.php');
        
        if (!self::$isCurrentAuth) {
            $_e[] = 'Выполнение методов от других ролей доступно только для текущего авторизованного пользователя';
            self::$last_errors = $_e;
            return false;
        }
        
        $classRole = ucfirst(strtolower($fromRole));
        
        if (!in_array($classRole, $arAvailableRoles)) {
            $_e[] = "Класс запрашиваемой роли '$classRole' не найден";
            self::$last_errors = $_e;
            return false;
        } elseif (!method_exists($classRole, $action)) {
            $_e[] = "Метода '$action' у класса запрашиваемой роли '$classRole' не существует";
            self::$last_errors = $_e;
            return false;
        }
        
        $o = new $classRole();
        return call_user_func_array(array($o, $action), $args);
    }
    
    /**
     * Генерирует новый пароль
     * @param integer $length
     * @return string
     */    
    public static function newPassword($length = 8)
    { 
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $count = mb_strlen($chars);

        for ($i = 0, $password = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $password .= mb_substr($chars, $index, 1);
        }

        return $password;
    }
    
    /**
     * Генерирует соль для пароля
     * @return string
     */
    public function generateSalt()
    {
        $salt = '';        
        $length = rand(5, 10); // длина соли (от 5 до 10 сомволов)
        for ($i = 0; $i < $length; $i++) {
            $salt .= chr(rand(33, 126)); // символ из ASCII-table
        }
        return $salt;
    }
    
    /**
     * Находит корневую группу для переданной роли
     * @param string $role
     * @return boolean|string
     */
    public static function getRootGroup($role)
    {        
        $_e = array();        
        $rootGroup = R::findOne(self::$table_roles, '`type` = ?', array((string)$role));        
        
        if (empty($rootGroup)) {
            $_e[] = "Роль '$role' в системе не существует";
            self::$last_errors = $_e;
            return false;
        }    
        while ($rootGroup->group != '0') {                                    
            $rootGroup = R::findOne(self::$table_roles, 'type = ?', array($rootGroup->group));            
        }                
        return $rootGroup->type;
    }
    
    /**
     * Возвращает массив групп, которые относятся к переданной роли
     * @param string $role
     * @return array
     */
    public static function getTreeGroup($role)
    {        
        return self::addTreeItems($role);
    }
    
    /**
     * Создаёт массив групп, которые относятся к передаваемой роли, рекурсивно проходя
     * по всем зависимым группам.
     * @param string $role
     * @param array $output
     * @return array
     */
    private static function addTreeItems($role, &$output = array())
    {
        $arRelatedRoles = R::findAndExport(self::$table_roles, '`group` = ?', array($role));
        $arCurRole = R::findOne(self::$table_roles, '`type` = ?', array($role));
        
        if (empty($arCurRole) || $role === 0 || $role == '0')
            return;
        
        $output[] = $role;
        foreach ($arRelatedRoles as $arRole)
        {
           self::addTreeItems($arRole['type'], $output);
        }                
        return $output;
    }    
    
    /**
     * Возвращает массив всех системных (=корневых) ролей приложения
     * @return array
     */
    public static function getSysRoles()
    {
        return R::findAndExport(self::$table_roles, "`group` = '0'"); 
    }

    /**
     * Проверяет пользователя на авторизацию
     * @return boolean
     */
    public static function isAuth()
    {
        if (self::$uid || $_SESSION['u_uid'])
            return true;
        else
            return false;
    }

    public static function login($login, $pass)
    {       
        $_e = array();
        $user = R::findOne(self::$table, '`login` = ?', array(strtolower((string)$login)));        
        
        if (empty($user)) {
            $_e[] = "Неверно введён логин или пароль";
            self::$last_errors = $_e;
            return false;
        }        
        if ($user->password == md5(sha1($pass).$user->salt)) {
            $_SESSION['u_uid'] = $user->id;                        
            return true;
        } else {
            $_e[] = "Неверно введён логин или пароль";
            self::$last_errors = $_e;
            return false;
        }
    }

    public static function logout($redirect_url = '/') {
        if (self::$uid != 0) {
            unset($_SESSION['u_uid']);  
            USite::redirect($redirect_url);            
        }
    }
    
    public static function refreshDataRoles()
    {
        $arExtRoles = include APP_CONFIG_PATH . '/roles.php';
        $arFullRoles = array_merge($arExtRoles, self::$arRoles);
        $arFullRoles = array_reverse($arFullRoles);    
        
        foreach ($arFullRoles as $k => $v)
        {         
            if ($k == self::VIP_ROLE)
                throw new UAppException ("Нельзя переопределять роль 'admin'");
            
            $result = R::findOne(self::$table_roles, 'type=?', array( strtolower($k) )); 
            
            if ($result && $result->name == $v['name']) {
                continue;
            } elseif ($result) {
                $result->name = $v['name'];
                R::store($result);
            } else {
                $role = R::dispense(self::$table_roles);
                $role->name = $v['name'];
                $role->group = $v['group'] ? $v['group'] : 0;
                $role->type = $k;                
                R::store($role);
            }
        }
    }
}