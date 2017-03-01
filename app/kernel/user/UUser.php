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
     * Id текущего авторизованного пользователя
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
    protected $roleGroup;
    
    /**
     * Содержит название самой корневой группы, от которой произошло 
     * любое наследование для текущей роли.
     * @var string 
     */
    protected $roleRootGroup;
    
    /**
     * Логин пользователя
     * @var string
     */
    protected $login;
    
    /**
     * Идентификатор группы, к которой относится пользователь.
     * @var integer
     */
    protected $groupId;
    
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
        print UForm::warning("Метод '$name' не существует у данного пользователя");
    }
    
    public static function __callStatic($name, $arguments) 
    {
        print UForm::warning("Статический метод '$name' не существует у данного пользователя");
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
        // Осуществляем проверку, был ли уже создан объект авторизованного пользователь
        if (self::isAuth() 
            && self::$user 
            && !$identification
        ) {                 
            self::$isCurrentAuth = true;
            return self::$user;
        } 
        // Если объект не создан, или пользователь был запрошен через идентификатор,
        // то посылаем запрос на получение данного объекта пользователя.
        else {     
            return self::getUser($identification);                     
        }
    }
    
    /**
     * Функция находит пользователя по переданному Id. 
     * Возвращает объект пользователя, в зависимости от его принадлежности к группе.
     * @return object
     * @throws Exception
     */
    private static function getUser($identification)
    {   
        $identification = intval($identification);
        
        // Установим Id текущего авторизованного пользователя
        if (self::isAuth() && !self::$uid) {            
            self::$uid = $_SESSION['u_uid'];
        }                 
        
        // Если есть запрос на получение произвольного пользователя и при этом
        // текущий пользователь не такой же, как запрашиваемый, то формируем запрос ниже.
        // Примечание! При таком расскладе авторизованность пользователя не учитывается.
        if ($identification && self::$uid != $identification) {                        
            self::$isCurrentAuth = false;
            
            // Если запрашиваемый пользователь уже есть в кэше, то выдаём его сразу
            if (self::$query_uid == $identification) {
                return self::$query_user;            
            }
            
            $user = R::load(self::$table, $identification);
            if (!$user->id) {
                return new UEmptyUser($identification);           
            }            
            self::$query_uid = $identification;
            $userCache =& self::$query_user;
        }
        // Если кэша текущего авторизованного пользователя нет
        elseif (self::isAuth() && !self::$user) {  
            self::$isCurrentAuth = true;
            $user = R::load(self::$table, self::$uid);
            $userCache =& self::$user;
        }
        // Отдаём кэш текущего авторизованного пользователя
        elseif ((!$identification || self::$uid == $identification) && self::$user) {            
            return self::$user;
        }
        else {            
            return new UEmptyUser($identification);        
        }         
        
        $newUserClass = ucfirst($user->role);
        $newUserClass_path = KERNEL_PATH . '/user/roles/' . $newUserClass . '.php';
        
        if (!file_exists($newUserClass_path)) {
            throw new UAppException (sprintf ("Файла '%s' для типа пользователя '%s' не найден", $newUserClass_path, $newUserClass));   
        }
        
        $userCache = new $newUserClass;
        $userCache->name = $user->name;
        $userCache->role = $user->role;  
        $userCache->login = $user->login;
        $userCache->groupId = $user->group_id;
        $userCache->setRoleGroups($user->role);        
        
        return $userCache;        
    }
    
    /**
     * Устанавливает для пользователя такие значения как "название" и "название 
     * корневой группы" по его роли.
     * @param string $role
     */
    private function setRoleGroups($role) 
    {       
        $rootGroup = R::findOne(self::$table_roles, 'type = ?', array($role));
        $this->roleGroup = $rootGroup->group;        
        
        while ($rootGroup->group != '0') {
            $rootGroup = R::findOne(self::$table_roles, 'type = ?', array($rootGroup->group));
        }
        $this->roleRootGroup = $rootGroup->type;
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
        return self::$isCurrentAuth
            ? self::$uid
            : self::$query_uid;        
    }
    
    /**
     * Возвращает имя пользователя
     * @return string
     */
    public function getName()
    {   
        return self::$isCurrentAuth
            ? self::$user->name
            : self::$query_user->name;        
    }

    /**
     * Возвращает название роли пользователя
     * @return string
     */
    public function getRole()
    {
        return self::$isCurrentAuth
            ? self::$user->role
            : self::$query_user->role;        
    }

    /**
     * Возвращает имя группы пользователя, к которой относится его роль
     * @return string
     */
    public function getRoleGroup()
    {
        return self::$isCurrentAuth
            ? self::$user->roleGroup
            : self::$query_user->roleGroup;    
    }
    
    /**
     * Возвращает название самой корневой группы пользователя, к коорой относится его роль
     * @return string
     */
    public function getRoleRootGroup()
    {
        return self::$isCurrentAuth
            ? self::$user->roleRootGroup
            : self::$query_user->roleRootGroup;    
    }
    
    /**
     * Возвращает логин
     * @return string
     */
    public function getLogin()
    {
        return self::$isCurrentAuth
            ? self::$user->login
            : self::$query_user->login;    
    }
    
    /**
     * Возвращает идентификатор группы пользователя.
     * Внимание! Значение заполняется только у пользовтелей "студенты".
     * @return integer
     */
    public function getGroupId()
    {
        return self::$isCurrentAuth
            ? self::$user->groupId
            : self::$query_user->groupId;    
    }

    public function getFields($arFields, $uid)
    {
        $_e = array();
        $arOut = array();
        $uid = intval($uid);
        
        if (!$uid) {
            $uid = self::$isCurrentAuth
                ? self::$uid
                : self::$query_uid;            
        }        
        
        $user = R::load(self::$table, $uid);
        
        if ($arFields === '*') {
            foreach ($user as $k => $v)
            {
                $arOut[$k] = $v;
            }
        } 
        elseif (!is_array($arFields) || empty($arFields)) {
            $_e[] = "Запрашиваемые поля пользователя должны быть массивом";
            self::$last_errors = $_e;
            return false;
        } 
        else {
            foreach ($arFields as $v)
            {
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
        {
            $oneDir = basename($oneDir, '.php');
        }
        
        if (!self::$isCurrentAuth) {
            $_e[] = 'Выполнение методов от других ролей доступно только для будучи авторизованного пользователя';
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
        $role = intval($role);
        $arRelatedRoles = R::findAndExport(self::$table_roles, '`group` = ?', array($role));
        $arCurRole = R::findOne(self::$table_roles, '`type` = ?', array($role));
        
        if (empty($arCurRole) || !$role) {
            return false;
        }
        
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
        return $_SESSION['u_uid'] ? true : false;
    }
    
    // Флаг, определяющий, введётся ли работа с текущим авторизованным пользователем
    // или же с запрошенным.
    public static function curUserIsAuth()
    {
        
    }

    public static function login($login, $pass)
    {       
        $_e = array();
        $user = R::findOne(self::$table, '`login` = ?', array(strtolower((string)$login)));        
              
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
        self::$uid = 0;
        self::$user = null;
        unset($_SESSION['u_uid']);  
        USite::redirect($redirect_url);                    
    }
    
    public static function refreshDataRoles()
    {
        $arExtRoles = include APP_CONFIG_PATH . '/roles.php';
        $arFullRoles = array_merge($arExtRoles, self::$arRoles);
        $arFullRoles = array_reverse($arFullRoles);    
        
        foreach ($arFullRoles as $k => $v)
        {         
            if ($k == self::VIP_ROLE) {
                throw new UAppException ("Нельзя переопределять роль 'admin'");
            }
            
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