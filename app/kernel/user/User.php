<?php

namespace UTest\Kernel\User;

use UTest\Kernel\Site;
use UTest\Kernel\Errors\AppException;
use UTest\Kernel\DB;
use UTest\Kernel\Base;

class User
{
    use \UTest\Kernel\Traits\ErrorsManageTrait;

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
     * Список предустановленных ролей
     */
    const PRESET_ROLES = [
        self::ADMIN_ROLE => 'Администратор'
    ];

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
     * Объект текущего авторизованного пользователя
     * @var object
     */
    private static $user = null;

    /**
     * Объект запрашиваемого пользователя.
     * @var object
     */
    private static $requestedUser = null;

    protected function __construct($userData, $isRequestedUser = false)
    {
        $this->userData = $userData;
        $this->isRequestedUser = $isRequestedUser;

        $this->uid = $userData['id'];
        $this->name = $userData['name'];
        $this->role = $userData['role'];
        $this->login = $userData['login'];
        $this->groupId = $userData['group_id'];
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
     * @return self
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
        $userData = false;

        // Если есть запрос на получение произвольного пользователя и текущий пользователь не такой же, как запрашиваемый.
        // Примечание! При таком расскладе авторизованность пользователя не учитывается.
        if ($isRequestedUser) {
            // Если запрашиваемый пользователь уже есть в кэше, то выдаём его сразу
            if (self::$requestedUser && self::$requestedUser->getUID() == $uid) {
                return self::$requestedUser;
            }

            $userData = self::getById($uid);
        }
        // Если кэша текущего авторизованного пользователя нет
        elseif (self::isAuth() && !self::$user) {
            $userData = self::getById($_SESSION['u_uid']);
        }

        if (!$userData) {
            throw new AppException("User[{$uid}] не существует для работы с ним");
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

        if (!self::isSysRoleExists($userRole)) {
            throw new AppException("Роль '{$userRole}' не описана в системе");
        }
        if (!file_exists($userClassPath)) {
            throw new AppException("Файл '{$userClassPath}' для типа пользователя '{$userRole}' не найден");
        }
        if (!class_exists($userClass)) {
            throw new AppException("Класс '{$userClass}' для типа пользователя '{$userRole}' не найден");
        }

        return new $userClass($userData, $isRequestedUser);
    }

    /**
     * Находит пользователя по переданному Id
     * @param integer $uid
     * @return boolean|object
     */
    public static function getById($uid)
    {
        self::clearErrors();
        $user = DB::table(TABLE_USER)->find($uid);
        if (!$user) {
            self::setErrors("Пользователя с Id = '{$uid}' не существует");
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
        self::clearErrors();
        $user = DB::table(TABLE_USER)->where('login', '=', $login)->first();
        if (!$user) {
            self::setErrors("Пользователя с login = '{$login}' не существует");
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
        self::clearErrors();
        $arOut = [];
        $arFields = (array) $arFields;

        if (empty($arFields)) {
            return $this->userData;
        } else {
            foreach ($arFields as $v) {
                if (!array_key_exists($v, $this->userData)) {
                    self::setErrors("Поле '{$v}' не существует в списке свойств пользователя");
                    return false;
                }
                $arOut[$v] = $this->userData[$v];
            }
        }

        return $arOut;
    }

    /**
     * Возвращает массив всех системных ролей приложения
     * @return array
     */
    public static function getSysRoles()
    {
        $roles = array_change_key_case(Base::getConfig('roles'), CASE_LOWER);
        $roles = array_merge($roles, self::PRESET_ROLES);
        return $roles;
    }

    /**
     * Проверяет роль на существование
     * @param string $role
     * @return bool
     */
    public static function isSysRoleExists($role = '')
    {
        return isset(self::getSysRoles()[strtolower($role)]);
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
        self::clearErrors();
        $user = self::getByLogin($login);

        if ($user['password'] != md5(sha1($pass) . $user['salt'])) {
            self::setErrors("Неверно введён логин или пароль");
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
        self::clearErrors();
        $person = self::loadPerson($role, [], true);

        if (!method_exists($person, $action)) {
            self::setErrors("Метод '{$action}' отсутствует у класса запрашиваемой роли '{$role}'");
            return false;
        }

        return call_user_func_array([$person, $action], $args);
    }
}