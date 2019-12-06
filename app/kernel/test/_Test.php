<?php

defined('LAST_Q') or define('LAST_Q', 'last');

class Test {

    private $tid; // Id теста(~родителя)
    private $uid; // Id пользователя
    private $qid; // Id вопроса
    private $stid; // Id проходимого теста
    private $QAdv; // вся информация о вопросое
    private $QText; // текст вопроса
    private $AList = array(); // список вариантов ответов
    private $AOListener; // Answer Object Listener
    private $arStackExecuting = array();
    private $arTProp = array(); // свойства проходимого теста
    
    private static $table_test = 'u_test';
    private static $table_question = 'u_test_question';
    private static $table_answer = 'u_test_answer';
    private static $table_st = 'u_student_test';
    private static $table_st_passage = 'u_student_test_passage';
    private static $table_st_answer = 'u_student_test_answer';
    private static $table_st_time = 'u_student_test_time';

    /**
     * Массив доступных для создания вариантов типов вопросов.
     * Ключами являются название классов, которые реализуют обработку выбранного
     * типа вопроса.
     * @var array 
     */
    private static $arTypeQuestion = array(
        'one' => array(
            'name' => 'единственный ответ',
            'desc' => 'Из числа возможных вариантов верным является только один'
        ),
        'multiple' => array(
            'name' => 'несколько ответов',
            'desc' => 'Из числа возможных вариантов верными могут быть несколько'
        ),
        'order' => array(
            'name' => 'порядок значимости',
            'desc' => 'Выставление вариантов ответов в верной последовательности'
        ),
        'match' => array(
            'name' => 'точность написания',
            'desc' => 'Необходимо будет написать в текстовом виде верный ответ'
        )
    );
    private static $arRequiredText = array(
        'title' => 'Укажите название теста',
        'subject_id' => 'Укажите предмет, по которому будет создаваться тест'
    );
    private static $arRequiredQuestion = array(
        'text' => 'Заполните текст вопроса',
        'type' => 'Укажите тип вопроса'
    );
    private static $arStatusTest = array(
        0 => 'прохождение не начиналось',
        1 => 'в процессе прохождения',
        2 => 'пройден'
    );
    public static $last_errors = array();

    public function __construct($tid, $uid, $isPrepod = false)
    {
        if ($isPrepod) {
            $res = R::findOne(self::$table_test, 'id = :id AND user_id = :uid ', array(
                        ':id' => $tid,
                        ':uid' => $uid
            ));

            if (!$res) {
                self::$last_errors = array('Тест не найден');
                return;
            }

            $this->tid = intval($tid);
            $this->uid = intval($uid);
        } else {
            $res = R::findOne(self::$table_st_passage, 'test_id = :tid AND user_id = :uid', array(
                        ':tid' => $tid,
                        ':uid' => $uid
            ));

            $this->uid = intval($uid);

            if ($res && $res['status'] == 2) {
                self::$last_errors = array('Данный тест уже пройден');
                return;
            } elseif (!$res) {
                $sql = "
                    SELECT COUNT(q.id)
                    FROM " . self::$table_question . " AS q 
                    LEFT JOIN " . self::$table_st . " AS s 
                        ON (q.test_id = s.test_id)
                    WHERE
                        s.id = {$tid}
                ";
                $resCountQ = R::getRow($sql);

                if ((int) reset($resCountQ) === 0) {
                    self::$last_errors = array('Упс, у теста отсутствуют вопросы!');
                    return;
                }

                $r = $this->runTest($tid);
                if (!$r) {
                    return;
                }
            }

            $this->stid = $r ? intval($tid) : $res['test_id'];
            $this->arTProp = $this->getTestProperties();
            $this->tid = $this->arTProp['test_id'];
            $_SESSION['stid'] = $this->stid;
        }
    }

    public static function createOrEdit(array $v = array(), $uid)
    {
        $_e = array();

        // edit
        if ($v['id']) {
            $dataRow = R::findOne(self::$table_test, 'id = :id AND user_id = :uid ', array(
                        ':id' => $v['id'],
                        ':uid' => $uid
            ));
            if (!$dataRow) {
                $_e[] = 'Тест для редактирования не найден';
            }
        }
        // create
        else {
            $dataRow = R::dispense(self::$table_test);
            $dataRow->subject_id = $v['subject_id'];
            $dataRow->user_id = $uid;
        }

        foreach (self::$arRequiredText as $k => $j)
        {
            if (!isset($v[$k]) || empty($v[$k])) {
                $_e[] = $j;
            }
        }
        if (!empty($_e)) {
            self::$last_errors = $_e;
            return false;
        }

        $dataRow->title = $v['title'];

        if (R::store($dataRow)) {
            return true;
        }
    }

    /**
     * Удалает целый тест
     * @param integer $tid - id теста
     * @param integer $uid - id пользователя-создателя теста
     * @return boolean
     */
    public static function delete($tid, $uid)
    {
        $bean = R::findOne(self::$table_test, 'id = :id AND user_id = :uid ', array(
                    ':id' => $tid,
                    ':uid' => $uid
        ));

        if ($bean) {
            R::trash($bean);
            return $bean;
        } else {
            return false;
        }
    }

    /**
     * Возвращает список созданных тестов по переданному Id предмета 
     * @param integer $sid
     * @return array
     */
    public static function getTestsBySubject($sid)
    {
        return R::find(self::$table_test, 'subject_id = ? ORDER BY title', array($sid));
    }

    /**
     * Возвращает список всех созданных тестов по переданному Id пользователя
     * @param type $uid
     * @return array
     */
    public static function getTestsByUID($uid)
    {
        return R::find(self::$table_test, 'user_id = ? ORDER BY title', array($uid));
    }

    /**
     * Возвращает список всех доступных типов вопросов
     * @return array
     */
    public static function getTypeQuestion()
    {
        return self::$arTypeQuestion;
    }

    /**
     * Возвращает список вопросов загруженного теста
     * @return array
     */
    public function getQuestionList()
    {
        $sql = "
            SELECT q.*
            FROM " . self::$table_question . " AS q 
            LEFT JOIN " . self::$table_test . " AS t 
                ON (q.test_id = t.id)
            WHERE
                t.user_id = {$this->uid}
                AND q.test_id = {$this->tid}
            ORDER BY q.ord
        ";
        $records = R::getAll($sql);
        return R::convertToBeans(self::$table_question, $records);
    }

    public function getQID()
    {
        return $this->qid;
    }

    public function getTID()
    {
        return $this->tid;
    }

    public function getSTID()
    {
        return $this->stid;
    }

    /**
     * Загружает экземпляр вопроса (полноценного вопроса с текстом вопроса и 
     * вариантами ответов).
     * 
     * @param integer $qid - Id вопроса, что необходимо загрузить
     * @return boolean
     */
    public function loadFullQuestion($qid)
    {
        if ($this->$qid) {
            return false;
        }

        $question = R::findOne(self::$table_question, 'test_id = :tid AND id = :qid', array(
                    ':tid' => $this->tid,
                    ':qid' => $qid
        ));

        if (!$question) {
            self::$last_errors = array('Вопрос не найден');
            return false;
        }

        $classAType = ucfirst($question['type']);
        $this->AOListener = new $classAType();

        $this->QAdv = $question;
        $this->qid = $question['id'];
        $this->QText = $question['text'];
        $this->loadAnswerList();
    }

    /**
     * Загружает список вариантов ответов
     */
    private function loadAnswerList()
    {
        $this->AList = R::find(self::$table_answer, 'question_id = ?', array($this->qid));
    }

    /**
     * Создаёт вопрос, а именно - заполянет текст вопроса и указывает тип вопроса
     * @param array $v - массив входных параметров для создания 
     * @param boolean $addToStack - выполнить создание вопроса сразу или же добавить данное действие в стек выполнения
     * @return boolean
     */
    public function createQuestion(array $v = array(), $addToStack = true)
    {
        $_e = array();

        foreach (self::$arRequiredQuestion as $k => $j)
        {
            if (!isset($v[$k]) || empty($v[$k])) {
                $_e[] = $j;
            }
        }

        if (array_key_exists($v[$k], self::$arTypeQuestion)) {
            $classAType = ucfirst(strtolower($v['type']));
            $this->AOListener = new $classAType();
        } elseif (intval($v['type'])) {
            $_e[] = 'Несуществующий тип вопроса';
        }

        if (!empty($_e)) {
            self::$last_errors = $_e;
            return false;
        }

        $dataRow = R::dispense(self::$table_question);
        $dataRow->text = $v['text'];
        $dataRow->type = strtolower($v['type']);
        $dataRow->ord = time();
        $dataRow->test_id = $this->tid;

        if ($addToStack) {
            $this->arStackExecuting[] = array(
                'str' => '$insertId = R::store($dataRow);$this->qid = $insertId;',
                'vars' => array(
                    'dataRow' => serialize($dataRow)
                )
            );
        } else {
            $insertId = R::store($dataRow);
            $this->qid = $insertId;
        }

        return true;
    }

    public function editQuestion(array $v = array(), $addToStack = true)
    {
        $_e = array();

        if (!$this->qid) {
            $_e[] = 'Не указан Id вопроса для редактирования';
            return false;
        }

        foreach (self::$arRequiredQuestion as $k => $j)
        {
            if (!isset($v[$k]) || empty($v[$k])) {
                $_e[] = $j;
            }
        }

        if (!empty($_e)) {
            self::$last_errors = $_e;
            return false;
        }

        $dataRow = R::load(self::$table_question, $this->qid);
        $dataRow->text = $v['text'];

        if ($addToStack) {
            $this->arStackExecuting[] = array(
                'str' => 'R::store($dataRow);',
                'vars' => array(
                    'dataRow' => serialize($dataRow)
                )
            );
        } else {
            R::store($dataRow);
        }

        return true;
    }

    public function deleteQuestion()
    {
        if (!$this->qid) {
            self::$last_errors = 'Не указан Id вопроса для удаления';
            return false;
        }

        $bean = R::load(self::$table_question, $this->qid);

        if ($bean) {
            R::trash($bean);
            return true;
        } else {
            return false;
        }
    }

    public function saveAnswerList($v = array(), $r = null, $addToStack = true)
    {
        self::$last_errors = array();

        if (!is_object($this->AOListener)) {
            //self::$last_errors = array('AOListener для работы с ответами не создан');
            return false;
        }

        $this->AOListener->validate($v, $r);
        $this->AList = $this->AOListener->getValidVariant();

        if (!empty($this->AOListener->last_error)) {
            self::$last_errors = $this->AOListener->last_error;
            return false;
        }

        if ($addToStack) {
            $this->arStackExecuting[] = array(
                'str' => '$AOListener->save($this->qid);',
                'vars' => array(
                    'AOListener' => serialize($this->AOListener)
                )
            );
        } else {
            $this->AOListener->save($this->qid);
        }

        return true;
    }

    /**
     * Удаляет один из вариантов ответов из загруженного вопроса, передавая
     * управление AOListener'у
     * 
     * @param type $aid - Id варианта ответа
     * @return boolean
     */
    public function deleteAnswer($aid)
    {
        self::$last_errors = array();

        if (!is_object($this->AOListener)) {
            self::$last_errors = array('AOListener для работы с ответами не создан');
            return false;
        }

        $this->AOListener->delete($aid);

        if (!empty($this->AOListener->last_error)) {
            self::$last_errors = $this->AOListener->last_error;
            return false;
        }

        return true;
    }

    /**
     * Возвращает текст текущего вопроса
     * @return string
     */
    public function getTextQuestion()
    {
        return $this->QText;
    }

    /**
     * Возвращает расширенную информацию о вопросе
     * @return array
     */
    public function getAdvTextQuestion()
    {
        return $this->QAdv;
    }

    /**
     * Возвращает список вариантов ответов для текущего вопроса
     * @return array
     */
    public function getAnswerList()
    {
        return $this->AList;
    }

    /**
     * Устанавливает новый тип объекта-наблюдателя для работы с вариантами ответов
     * @param string $type - новый тип AOListener'а
     * @return boolean
     */
    public function setAOListener($type)
    {
        $type = strtolower((string) $type);

        if (!array_key_exists($type, self::$arTypeQuestion)) {
            self::$last_errors = array("Тип вопроса '$type' не существует");
            return false;
        }

        $classAType = ucfirst($type);
        $this->AOListener = new $classAType();
        return true;
    }

    /**
     * Очищает действующий стек выполнения
     */
    private function clearStackExecuting()
    {
        $this->arStackExecuting = array();
    }

    /**
     * Запускает на отрабатывание действующий стек выполнения
     */
    public function runStackExecuting()
    {
        foreach ($this->arStackExecuting as $str)
        {
            foreach ($str['vars'] as $k => $v)
            {
                ${$k} = unserialize($v);
            }
            eval($str['str']);
        }
    }

    ##
    ## ДАЛЕЕ ПОЙДУТ ФУНКЦИИ, НЕОБХОДИМЫЕ ДЛЯ ПРОХОЖДЕНИЯ ТЕСТОВ СТУДЕНТАМИ
    ## 

    /**
     * Устанавливает тест в статус "в процессе прохождения" и создаёт 
     * соотвествующую запись об этом в БД
     * 
     * @param integer $stid
     * @return boolean
     */
    private function runTest($stid)
    {
        // Сначала нужно проверить, а имеет ли текущий пользователь право
        // проходить выбранный тест
        $u = UUser::user()->getFields('*');
        $count = R::count(self::$table_st, 'id = :id AND group_id = :gid ', array(
                    ':id' => $stid,
                    ':gid' => $u['group_id']
        ));
        if (!$count) {
            self::$last_errors = array('Тест для прохождения не найден');
            return false;
        }

        $dataRow = R::dispense(self::$table_st_passage);
        $dataRow->user_id = $this->uid;
        $dataRow->test_id = $stid;
        $dataRow->status = 1;

        $dataTime = R::dispense(self::$table_st_time);
        $dataTime->user_id = $this->uid;
        $dataTime->test_id = $stid;
        $dataTime->date_start = UAppBuilder::getDateTime();
        R::store($dataTime);

        $insertId = R::store($dataRow);
        return $insertId;
    }

    /**
     * Возвращает список Id всех возможных вопросов теста
     * @param integer $tid - Id теста (необязательный параметр)
     * @return array
     */
    public function getAllQuestionsIds($tid)
    {
        $arIds = array();
        $tid = $tid ? $tid : $this->arTProp['test_id'];

        $res = R::find(self::$table_question, 'test_id = ? ', array($tid));
        if ($res) {
            foreach ($res as $v)
            {
                $arIds[] = (int) $v['id'];
            }
        }
        return $arIds;
    }

    /**
     * Возвращает список уже используемых вопросов
     * @return array
     */
    public function getUsedQuestionIds()
    {
        $arIds = array();
        $res = R::find(self::$table_st_answer, 'test_id = :stid AND user_id = :uid AND retake_value = :retake', array(
                    ':stid' => $this->stid,
                    ':uid' => $this->uid,
                    ':retake' => $this->arTProp['retake']
        ));
        if ($res) {
            foreach ($res as $v)
            {
                $arIds[$v['number']] = (int) $v['question_id'];
            }
        }
        return $arIds;
    }

    public function gotoQuestion($num)
    {
        $num = $num === LAST_Q ? $this->arTProp['last_q_number'] : (int) $num;

        if ($num === null) {
            return $this->addQuestion();
        }

        // Сохранение пользовательского ответа
        $request = new UHttpRequest();
        if (!empty($request->_GET)) {
            $this->toAnswerQuestion($request->_GET, $_SESSION['last_num']);
        }

        // Проверим, что нужно сделать в итоге - загрузить существующий вопрос, 
        // или же добавить новый вопрос и его порядковый номер в БД
        $count = R::count(self::$table_st_answer, 'test_id = :stid AND user_id = :uid AND number = :num AND retake_value = :retake', array(
                    ':stid' => $this->stid,
                    ':uid' => $this->uid,
                    ':num' => $num,
                    ':retake' => $this->arTProp['retake']
        ));

        return (!$count) ? $this->addQuestion($num) : $this->loadQuestion($num);
    }

    public function endTest()
    {
        $request = new UHttpRequest();
        $this->toAnswerQuestion($request->_GET, $_SESSION['last_num']);

        $dataRow = R::findOne(self::$table_st_passage, 'test_id = :stid AND user_id = :uid', array(
                    ':stid' => $this->stid,
                    ':uid' => $this->uid
        ));
        $dataRow->status = 2;

        $dataTime = R::findOne(self::$table_st_time, 'test_id = :stid AND user_id = :uid AND retake_value = :retake', array(
                    ':stid' => $this->stid,
                    ':uid' => $this->uid,
                    ':retake' => $dataRow['retake']
        ));
        $dataTime->date_finish = UAppBuilder::getDateTime();

        if (R::store($dataRow) && R::store($dataTime)) {
            return true;
        } else {
            self::$last_errors = array('Не удалось корректно завершить тест');
            return false;
        }
    }

    private function loadQuestion($num)
    {
        $q = R::findOne(self::$table_st_answer, 'test_id = :stid AND user_id = :uid AND number = :num AND retake_value = :retake', array(
                    ':stid' => $this->stid,
                    ':uid' => $this->uid,
                    ':num' => $num,
                    ':retake' => $this->arTProp['retake']
        ));

        $res = unserialize($q['q']);
        unset($res['right']);

        if ($q) {
            $res['status'] = 'OK';
            $_SESSION['last_num'] = $num;
        } else {
            $res['status'] = 'ERROR';
            $res['status_message'] = 'Вопрос не найден. Попробуйте обновить страницу';
        }

        $this->setLastQNumber($num);

        $res['answer'] = USiteController::loadComponent('utility', 'testanswer', array($res['type'], $res['answer'], unserialize($q['user_answer'])));
        $res['cur_num'] = $num;
        $res['is_last'] = $num == $this->arTProp['count_q'];
        $res['text'] = nl2br($res['text']);

        return $res;
    }

    private function addQuestion($num = 1)
    {
        $res = array();

        $curIds = $this->getUsedQuestionIds();
        $allIds = $this->getAllQuestionsIds();

        if ($num > $this->arTProp['count_q'] || $num === 0) {
            $res['status'] = 'ERROR';
            $res['status_message'] = 'Вопрос не найден. Попробуйте обновить страницу';
            return $res;
        }

        if ($this->arTProp['is_mixing']) {
            if (empty($curIds)) {
                $keyQ = array_rand($allIds);
            } else {
                $arDiff = array_diff($allIds, $curIds);
                $keyQ = empty($arDiff) ? array_rand($allIds) : array_rand($arDiff);
            }
        } else {
            $arDiff = array_diff($allIds, $curIds);
            $keyQ = empty($arDiff) ? array_rand($allIds) : $num - 1;
        }

        $qid = $allIds[$keyQ];
        $this->loadFullQuestion($qid);

        $dataRow = R::dispense(self::$table_st_answer);
        $dataRow->user_id = $this->uid;
        $dataRow->test_id = $this->stid;
        $dataRow->question_id = $this->QAdv['id'];
        $dataRow->q = serialize($this->getQuestionAsArray());
        $dataRow->retake_value = $this->arTProp['retake'];
        $dataRow->number = $num;
        $dataRow->user_answer = null;

        $res = $this->getQuestionAsArray();
        unset($res['right']);

        if (R::store($dataRow)) {
            $res['status'] = 'OK';
            $_SESSION['last_num'] = $num;
        } else {
            $res['status'] = 'ERROR';
            $res['status_message'] = 'Не удалось добавить вопрос в список проходимых';
        }

        $this->setLastQNumber($num);

        $res['answer'] = USiteController::loadComponent('utility', 'testanswer', array($res['type'], $res['answer']));
        $res['cur_num'] = $num;
        $res['is_last'] = $num == $this->arTProp['count_q'];

        return $res;
    }

    /**
     * Устанавливает в свойстве проходимого теста значение последнего загруженного вопроса
     * @param integer $num
     */
    private function setLastQNumber($num)
    {
        $dataRow = R::findOne(self::$table_st_passage, 'test_id = :stid AND user_id = :uid', array(
                    ':stid' => $this->stid,
                    ':uid' => $this->uid
        ));
        $dataRow->last_q_number = $num;
        R::store($dataRow);
    }

    /**
     * Возвращает список свойств проходимого теста
     * @return array|bool
     */
    private function getTestProperties()
    {
        $sql = "
            SELECT t.*, p.status, i.date_start, i.date_finish, p.retake, p.last_q_number
            FROM " . self::$table_st . " AS t
            LEFT JOIN " . self::$table_st_passage . " AS p 
                ON (p.test_id = t.id)
            LEFT JOIN " . self::$table_st_time . " AS i 
                ON (i.test_id = t.id AND i.retake_value = p.retake AND i.user_id = {$this->uid})
            WHERE
                t.id = {$this->stid}
                AND p.user_id = {$this->uid}                
        ";
        $res = R::getRow($sql);

        if (!$res) {
            self::$last_errors = array('Назначенный тест не найден');
            return false;
        }

        if ($res['date_start'] === null) {
            $dataTest = R::findOne(self::$table_st_passage, 'test_id = :tid AND user_id = :uid', array(
                        ':tid' => $this->stid,
                        ':uid' => $this->uid
            ));
            $dataTest->status = $res['status'] = 1;
            R::store($dataTest);

            $dataTime = R::dispense(self::$table_st_time);
            $dataTime->user_id = $this->uid;
            $dataTime->test_id = $this->stid;
            $dataTime->date_start = $res['date_start'] = UAppBuilder::getDateTime();
            $dataTime->retake_value = $res['retake'];
            R::store($dataTime);
        }

        if ($res['count_q'] == 0) {
            $res['count_q'] = R::count(self::$table_question, 'test_id = ?', array($res['test_id']));
        }

        return $res;
    }

    private function toAnswerQuestion($arFields, $num)
    {
        $dataRow = R::findOne(self::$table_st_answer, 'test_id = :stid AND user_id = :uid AND number = :num AND retake_value = :retake', array(
                    ':stid' => $this->stid,
                    ':uid' => $this->uid,
                    ':num' => $num,
                    ':retake' => $this->arTProp['retake']
        ));

        if ($dataRow['type'] == 'multiple') {
            $userAnswer = array();
            foreach ($arFields as $k => $v)
            {
                if ((bool) $v) {
                    $userAnswer[] = $k;
                }
            }
        } else {
            $userAnswer = $arFields['right_answer'];
        }

        $dataRow->user_answer = serialize($userAnswer);
        R::store($dataRow);
    }

    /**
     * Возвращает информацию о вопросе в виде ассоциативного массива.
     * Массив включает в себя следующие элементы:
     * <ul>
     *      <li>[text] - текст вопроса</li>
     *      <li>[type] - тип вопроса</li>
     *      <li>[answer] - варианты ответов (если имеются) в виде массива</li>
     *      <li>[right] - верные ответы</li>
     * </ul>
     * @return array
     */
    private function getQuestionAsArray()
    {
        $q = $arAnswer = array();

        foreach ($this->AList as $v)
        {
            $arAnswer[$v['id']] = $v['title'];

            if ($this->QAdv['type'] === 'match') {
                $rightAnswer = $v['right_answer'];
            } else {
                $rightAnswer[$v['id']] = $v['right_answer'];
            }
        }
        shuffle_assoc($arAnswer);

        $q['text'] = $this->QText;
        $q['type'] = $this->QAdv['type'];
        $q['answer'] = $arAnswer;
        $q['right'] = $rightAnswer;

        return $q;
    }

    /**
     * Проверяет имеется ли запись о прохождении теста пользователем
     * @param integer $stid
     * @param integer $uid
     * @return bool
     */
    public static function checkRunningUserTest($stid, $uid)
    {
        $sql = "
            SELECT t.*
            FROM " . self::$table_st_passage . " AS t
            LEFT JOIN " . self::$table_st_time . " AS i 
                ON (i.test_id = t.test_id AND i.user_id = t.user_id)
            WHERE
                t.test_id = {$stid}
                AND t.user_id = {$uid}
                AND i.retake_value = t.retake
        ";
        $res = R::getRow($sql);

        return (bool) count($res);
    }

    /**
     * Возвращает все доступные статусы тестов
     * @return array
     */
    public static function getStatusTest()
    {
        return self::$arStatusTest;
    }

}

function shuffle_assoc(&$array)
{
    $keys = array_keys($array);
    shuffle($keys);

    foreach ($keys as $key)
    {
        $new[$key] = $array[$key];
    }

    $array = $new;
    return true;
}
