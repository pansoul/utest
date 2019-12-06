<?php

namespace UTest\Kernel\User;

use UTest\Kernel\Site;
use UTest\Kernel\Errors\AppException;
use UTest\Kernel\DB;

class User
{
    /**
     * Данную роль нельзя переопределять.
     * Список пользовательских ролей содержится в файле roles.php в папке конфигурации
     */
    const ADMIN_ROLE = Roles\Admin::ROLE;

    /**
     * Предопределённый Id админа
     */
    const ADMIN_ID = 1;

    /**
     * Id пользователя
     * @var int
     */
    protected $uid = 0;

    /**
     * Имя пользователя
     * @var string
     */
    protected $name = '';

    /**
     * Какая роль у пользователя
     * @var string
     */
    protected $role = '';

    /**
     * К какой группе принадлежит текущая роль пользователя.
     * Название группы будет иметься только у унаследованных групп пользователей, у групп-родителей этот параметр будет выводить "0"
     * @var string
     */
    protected $roleGroup = '';

    /**
     * Содержит название самой корневой группы, от которой произошло любое наследование для текущей роли.
     * @var string
     */
    protected $roleRootGroup = '';

    /**
     * Логин пользователя
     * @var string
     */
    protected $login = '';

    /**
     * Идентификатор группы, к которой относится пользователь.
     * @var integer
     */
    protected $groupId = 0;

    /**
     * Флаг, показывающий что текущий пользователь был запрошен
     * @var bool
     */
    protected $isRequestedUser = false;

    /**
     * Объект данных пользователя
     * @var null
     */
    protected $userData = null;

    /**
     * Список доступных ролей
     * @var array
     */
    protected static $arRoles = [
        self::ADMIN_ROLE => 'Администратор'
    ];

    /**
     * Объект текущего авторизованного пользователя
     * @var object
     */
    private static $user = null;

    /**
     * Объект запрашиваемого пользователя.
     * @var object
     */
    private static $requestedUser = null;

    /**
     * Переменная содержит список текстовых ошибок
     * @var array
     */
    public static $last_errors = array();

    protected function __construct($userData, $isRequestedUser = false)
    {
        $this->userData = $userData;
        $this->isRequestedUser = $isRequestedUser;

        $this->uid = $userData['id'];
        $this->name = $userData['name'];
        $this->role = $userData['role'];
        $this->login = $userData['login'];
        $this->groupId = $userData['group_id'];
        $this->setRoleGroups($userData['role']);
    }

    public function __call($name, $arguments)
    {
        throw new AppException("Метод '{$name}' не существует у данного пользователя");
    }

    public static function __callStatic($name, $arguments)
    {
        throw new AppException("Статический метод '{$name}' не существует у данного пользователя");
    }

    /**
     * Только через эту функцию можно обращаться к методам и свойствам пользователя.
     *
     * Если $uid не передан, то будет браться текущий авторизованный пользователь,
     * иначе передастся оъбъект запрашиваемого пользователя.
     *
     * @param int $uid
     *
     * @return mixed|object
     * @throws AppException
     */
    public static function user($uid = 0)
    {
        // Осуществляем проверку, был ли уже создан объект авторизованного пользователь
        if (!$uid && self::isAuth() && self::$user) {
            return self::$user;
        }
        // Если объект не создан, или пользователь был запрошен через идентификатор,
        // то посылаем запрос на получение данного объекта пользователя.
        else {
            return self::getUser($uid);
        }
    }

    /**
     * Функция находит пользователя по переданному Id.
     * Возвращает объект пользователя, в зависимости от его принадлежности к группе.
     *
     * @param $uid
     *
     * @return mixed
     * @throws AppException
     */
    private static function getUser($uid)
    {
        $uid = intval($uid);
        $isRequestedUser = $uid && $uid != @$_SESSION['u_uid'];

        // Если есть запрос на получение произвольного пользователя и текущий пользователь не такой же, как запрашиваемый.
        // Примечание! При таком расскладе авторизованность пользователя не учитывается.
        if ($isRequestedUser) {
            // Если запрашиваемый пользователь уже есть в кэше, то выдаём его сразу
            if (self::$requestedUser && self::$requestedUser->getUID() == $uid) {
                return self::$requestedUser;
            }

            $userData = self::getById($uid);
            if (!$userData) {
                return new EmptyUser($uid);
            }
        }
        // Если кэша текущего авторизованного пользователя нет
        elseif (self::isAuth() && !self::$user) {
            $userData = self::getById($_SESSION['u_uid']);
        }
        else {
            return new EmptyUser($uid);
        }

        $person = self::loadPerson($userData['role'], $userData, $isRequestedUser);

        if ($isRequestedUser) {
            self::$requestedUser = $person;
        } else {
            self::$user = $person;
        }

        return $person;
    }

    /**
     * Возвращает объект персонажа (пользователя конкретной роли)
     *
     * @param $role
     * @param array $userData
     * @param bool $isRequestedUser
     *
     * @return mixed
     * @throws AppException
     */
    private static function loadPerson($role, $userData = [], $isRequestedUser = false)
    {
        $userRole = ucfirst($role);
        $userClass = '\\UTest\\Kernel\\User\\Roles\\' . $userRole;
        $userClassPath = KERNEL_PATH . '/user/roles/' . $userRole . '.php';

        if (!file_exists($userClassPath)) {
            throw new AppException("Файл '{$userClassPath}' для типа пользователя '{$userRole}' не найден");
        }
        if (!class_exists($userClass)) {
            throw new AppException("Класс '{$userClass}' для типа пользователя '{$userRole}' не найден");
        }

        return new $userClass($userData, $isRequestedUser);
    }

    /**
     * Устанавливает для пользователя такие значения как "название" и "название корневой группы" по его роли.
     * @param string $role
     */
    private function setRoleGroups($role)
    {
        $rootGroup = DB::table(TABLE_USER_ROLES)->where('type', '=', $role)->first();
        $this->roleGroup = $rootGroup['group'];

        while ($rootGroup['group'] != 0) {
            $rootGroup = DB::table(TABLE_USER_ROLES)->where('type', '=', $rootGroup['group'])->first();
        }
        $this->roleRootGroup = $rootGroup['type'];
    }

    /**
     * Находит пользователя по переданному Id
     * @param integer $uid
     * @return boolean|object
     */
    public static function getById($uid)
    {
        $e = array();
        $user = DB::table(TABLE_USER)->find($uid);
        if (!$user) {
            $e[] = "Пользователя с Id = '{$uid}' не существует";
            self::$last_errors = $e;
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
        $e = array();
        $user = DB::table(TABLE_USER)->where('login', '=', $login)->first();
        if (!$user) {
            $e[] = "Пользователя с login = '{$login}' не существует";
            self::$last_errors = $e;
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
        return $this->uid;
    }

    /**
     * Возвращает имя пользователя
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Возвращает название роли пользователя
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Возвращает имя группы пользователя, к которой относится его роль
     * @return string
     */
    public function getRoleGroup()
    {
        return $this->roleGroup;
    }

    /**
     * Возвращает название самой корневой группы пользователя, к коорой относится его роль
     * @return string
     */
    public function getRoleRootGroup()
    {
        return $this->roleRootGroup;
    }

    /**
     * Возвращает логин
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Возвращает идентификатор группы пользователя.
     * Внимание! Значение заполняется только у пользовтелей "студенты".
     * @return integer
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Возвращает список полей пользователя
     * @param array $arFields - вывести только список определённых ключей пользователя
     * @return array|bool
     */
    public function getFields($arFields = [])
    {
        $e = [];
        $arOut = [];
        $arFields = (array) $arFields;

        if (empty($arFields)) {
            return $this->userData;
        } else {
            foreach ($arFields as $v) {
                if (!isset($this->userData[$v])) {
                    $e[] = "Поле '{$v}' не существует в списке свойств пользователя";
                    self::$last_errors = $e;
                    return false;
                }
                $arOut[$v] = $this->userData[$v];
            }
        }

        return $arOut;
    }

    /**
     * Находит корневую группу для переданной роли
     * @param string $role
     * @return boolean|string
     */
    public static function getRootGroup($role)
    {
        $e = array();
        $rootGroup = DB::table(TABLE_USER_ROLES)->where('type', '=', $role)->first();

        if (empty($rootGroup)) {
            $e[] = "Роль '{$role}' в системе не существует";
            self::$last_errors = $e;
            return false;
        }

        while ($rootGroup['group'] != 0) {
            $rootGroup = DB::table(TABLE_USER_ROLES)->where('type', '=', $rootGroup['group'])->first();
        }
        return $rootGroup['type'];
    }

    /**
     * Возвращает массив всех системных (=корневых) ролей приложения
     * @return array
     */
    public static function getSysRoles()
    {
        return DB::table(TABLE_USER_ROLES)->where('group', '=', 0)->get();
    }

    /**
     * Проверяет пользователя на авторизацию
     * @return boolean
     */
    public static function isAuth()
    {
        return intval($_SESSION['u_uid']) > 0 ? true : false;
    }

    /**
     * Авторизует пользователя
     *
     * @param $login
     * @param $pass
     * @param bool $redirectUrl
     *
     * @return bool
     */
    public static function login($login, $pass, $redirectUrl = false)
    {
        $e = array();
        $user = self::getByLogin($login);

        if ($user['password'] != md5(sha1($pass) . $user['salt'])) {
            $e[] = "Неверно введён логин или пароль";
            self::$last_errors = $e;
            return false;
        }

        $_SESSION['u_uid'] = $user['id'];
        if (strlen($redirectUrl) > 0) {
            Site::redirect($redirectUrl);
        }
        return true;
    }

    /**
     * Разлогинивает пользователя
     * @param string $redirectUrl
     */
    public static function logout($redirectUrl = '/')
    {
        self::$user = null;
        unset($_SESSION['u_uid']);
        if (strlen($redirectUrl) > 0) {
            Site::redirect($redirectUrl);
        }
    }

    /**
     * Выполняет произвольный метод от лица указанного персонажа (пользователя конкретной роли)
     *
     * @param $role
     * @param $action
     * @param mixed ...$args
     *
     * @return bool|mixed
     * @throws AppException
     */
    public function doAction($role, $action, ...$args)
    {
        $e = array();
        $person = self::loadPerson($role, [], true);

        if (!method_exists($person, $action)) {
            $e[] = "Метод '{$action}' отсутствует у класса запрашиваемой роли '{$role}'";
            self::$last_errors = $e;
            return false;
        }

        return call_user_func_array([$person, $action], $args);
    }

    /**
     * Возвращает массив групп, которые относятся к переданной роли
     * @param string $role
     * @return array
     *
     * @todo пересмотреть
     */
    /*public static function getTreeGroup($role)
    {
        return self::addTreeItems($role);
    }*/

    /**
     * Создаёт массив групп, которые относятся к передаваемой роли, рекурсивно проходя по всем зависимым группам.
     *
     * @param string $role
     * @param array $output
     *
     * @return array
     *
     * @todo пересмотреть
     */
    /*private static function addTreeItems($role, &$output = array())
    {
        $role = intval($role);
        $arRelatedRoles = R::findAndExport(TABLE_USER_ROLES, '`group` = ?', array($role));
        $arCurRole = R::findOne(TABLE_USER_ROLES, '`type` = ?', array($role));

        if (empty($arCurRole) || !$role) {
            return false;
        }

        $output[] = $role;
        foreach ($arRelatedRoles as $arRole) {
            self::addTreeItems($arRole['type'], $output);
        }
        return $output;
    }*/

    // @todo пересмотреть
    /*public static function refreshDataRoles()
    {
        $arExtRoles = include APP_CONFIG_PATH . '/roles.php';
        $arFullRoles = array_merge($arExtRoles, self::$arRoles);
        $arFullRoles = array_reverse($arFullRoles);

        foreach ($arFullRoles as $k => $v) {
            if ($k == self::ADMIN_ROLE) {
                throw new AppException ("Нельзя переопределять роль 'admin'");
            }

            $result = R::findOne(TABLE_USER_ROLES, 'type=?', array(strtolower($k)));

            if ($result && $result->name == $v['name']) {
                continue;
            } elseif ($result) {
                $result->name = $v['name'];
                R::store($result);
            } else {
                $role = R::dispense(TABLE_USER_ROLES);
                $role->name = $v['name'];
                $role->group = $v['group'] ? $v['group'] : 0;
                $role->type = $k;
                R::store($role);
            }
        }
    }*/



}