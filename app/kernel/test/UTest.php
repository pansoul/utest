<?php

class UTest {
    
    /**
     * Id теста
     * @var int
     */
    private $tid = 0;
    
    /**
     * Id автора теста
     * @var int
     */
    private $uid = 0;
    
    /**
     * Id вопроса
     * @var int
     */
    private $qid = 0;
    
    /**
     * Тип вопроса
     * @var string
     */
    private $qtype;
    
    /**
     * Answer Object Listener
     * @var object
     */
    private $AOListener; 
    
    /**
     * Объект RedBeanPHP\OODBBean теста
     * @var object
     */
    private $testBean;
    
    /**
     * Объект RedBeanPHP\OODBBean вопроса
     * @var object 
     */
    private $qBean;
    
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
    
    /**
     * Список последних ошибок
     * @var array
     */
    public static $last_errors = array();    
    
    public function __construct($tid, $uid)
    {
        $tid = intval($tid);
        $uid = intval($uid);
          
        $cond = $uid ? 'id = :tid AND user_id = :uid' : 'id = :tid';        
        $values = $uid ? array(':tid' => $tid, ':uid' => $uid) : array(':tid' => $tid);
        $res = R::findOne(TABLE_TEST, $cond, $values);

        if (!$res) {
            $this->last_errors = array('Тест не найден');
            return;
        }

        $this->tid = $tid;               
        $this->testBean = $res;        
        if ($uid) {
            $this->uid = $uid;     
        }
    }
    
    /**
     * Создаёт новый тест, автором которого является $uid
     * @param array $arFields - массмв входных параметров теста
     * @param int $uid - Id автора теста. Необзательный параметр, 
     * по умолчанию берётся значения $uid, переданное при созании объекта.
     * @return boolean
     */
    public function create($arFields = array(), $uid)
    {
        $uid = intval($uid) ? intval($uid) : $this->uid;  
        
        if (!$uid || !UUser::getById($uid)) {
            $this->last_errors = array('Id автора теста указан неверно или не существует');
            return false;
        }
        
        $dataRow = R::dispense(TABLE_TEST);
        $dataRow->subject_id = $arFields['subject_id'];
        $dataRow->user_id = $uid;
        
        return $this->_pushRequest($dataRow, $arFields);
    }
    
    /**
     * Редактирование параметров теста
     * @param array $arFields
     * @return boolean
     */
    public function edit($arFields = array())
    {
        if (!$this->checkPermissions(true)) {
            return false;
        }        
        
        return $this->_pushRequest($this->testBean, $arFields);
    }  
    
    /**
     * Отсылает запрос при создании/редактировании теста
     * @param RedBeanPHP\OODBBean $dataRow - объект записи
     * @param array $arFields - параметры
     * @return boolean
     */
    private function _pushRequest(RedBeanPHP\OODBBean $dataRow, $arFields)
    {
        $_e = array();
        $arRequired = array(
            'title' => 'Укажите название теста',
            'subject_id' => 'Укажите предмет, по которому будет создаваться тест'
        );
        
        foreach ($arRequired as $k => $j)
        {
            if (empty($arFields[$k])) {
                $_e[] = $j;
            }
        }
        
        if (!empty($_e)) {
            $this->last_errors = $_e;
            return false;
        }

        $dataRow->title = $arFields['title'];
        if (R::store($dataRow)) {
            return true;
        }
    }
    
    /**
     * Удаление теста
     * @return boolean
     */
    public function delete()
    {
        if (!$this->checkPermissions(true)) {
            return false;
        }      
        
        R::trash($this->testBean);
        return $this->testBean;        
    }
    
    /**
     * Возвращает параметры текущего теста
     * @param bool $asBeanObject
     * @return object|array
     */
    public function getProperties($asBeanObject = false)
    {        
        if ($asBeanObject) {
            return $this->testBean;
        } else {
            $arProps = array();
            foreach ($this->testBean as $k => $v)
            {
                $arProps[$k] = $v;
            }
            return $arProps;
        }
    } 
    
    public function getQProperties($asBeanObject = false)
    {        
        if ($asBeanObject) {
            return $this->qBean;
        } else {
            $arProps = array();
            foreach ($this->qBean as $k => $v)
            {
                $arProps[$k] = $v;
            }
            return $arProps;
        }
    } 
    
    public function loadQuestion($qid)
    {
        if (!$this->checkPermissions()) {
            return false;
        }
        
        $question = R::findOne(TABLE_TEST_QUESTION, 'test_id = :tid AND id = :qid', array(
            ':tid' => $this->tid,
            ':qid' => $qid
        ));

        if (!$question) {
            $this->last_errors = array('Вопрос не найден');
            return false;
        }
        
        $classAType = ucfirst($question['type']);
        $this->AOListener = new $classAType($qid);
        $this->type = $question['type'];
        $this->qid = $question['id'];     
        $this->qBean = $question;
        
        return $this;
    }
    
    public function loadFullQuestion($qid)
    {
        if (!$this->checkPermissions()) {
            return false;
        }
        
        if (!$this->loadQuestion($qid)) {
            return false;
        }
        
        $v = array();        
        
        $v['id'] = $this->qid;
        $v['question'] = $this->getQProperties();
        $v['answer'] = $this->getAnswerList();
        $v['right_answer'] = array();
        foreach ($v['answer'] as $item)
        {
            if ($item['right_answer']) {
                $v['right_answer'] = $item['right_answer'];
            }
        }
        
        return $v;
    }
    
    /**
     * Создаёт полноценный вопрос с вариантами ответов
     * @param array $arQuestion - массив параметров вопроса
     * @param array $arAnswers - массив вариантов ответов
     * @param array $arRightAnswers - массив  верных ответов
     * @return boolean
     */
    public function createFullQuestion($arQuestion, $arAnswers, $arRightAnswers)
    {
        $e = array();
        if (!$this->checkPermissions(true)) {
            return false;
        }
        
        $questionValidationErrors = $this->_questionValidate($arQuestion);
        if ($questionValidationErrors) {
            $e = array_merge($e, $questionValidationErrors);  
        } 
        
        if ($arQuestion['type'] && isset(self::$arTypeQuestion[$arQuestion['type']])) {
            $classAType = ucfirst(strtolower($arQuestion['type']));
            $this->AOListener = new $classAType();                    
            
            $answerValidationErrors = $this->_answerValidate($arAnswers, $arRightAnswers);            
            if ($answerValidationErrors) {
                $e = array_merge($e, $answerValidationErrors);                
            }
        }
        
        if ($e) {
            $this->last_errors = $e;
            return false;
        }
        
        $qResult = $this->createQuestion($arQuestion);
        $aResult = $this->addAnswerList($arAnswers, $arRightAnswers);            
        
        return ($qResult && $aResult);
    }
    
    public function editFullQuestion($arQuestion, $arAnswers, $arRightAnswers)
    {
        $e = array();
        if (!$this->checkPermissions(true, true)) {
            return false;
        }
        
        $questionValidationErrors = $this->_questionValidate($arQuestion);
        if ($questionValidationErrors) {
            $e = array_merge($e, $questionValidationErrors);  
        }     

        $answerValidationErrors = $this->_answerValidate($arAnswers, $arRightAnswers);            
        if ($answerValidationErrors) {
            $e = array_merge($e, $answerValidationErrors);                
        }
        
        if ($e) {
            $this->last_errors = $e;
            return false;
        }
        
        $qResult = $this->editQuestion($arQuestion);
        $aResult = $this->addAnswerList($arAnswers, $arRightAnswers);            
        
        return ($qResult && $aResult);
    }
    
    
    public function createQuestion($arFields)
    {
        if (!$this->checkPermissions(true)) {
            return false;
        }
        
        $validationErrors = $this->_questionValidate($arFields);
        if ($validationErrors) {
            $this->last_errors = $validationErrors;
            return false;
        }        
        
        $dataRow = R::dispense(TABLE_TEST_QUESTION);
        $dataRow->text = $arFields['text'];
        $dataRow->type = strtolower($arFields['type']);
        $dataRow->ord = time();
        $dataRow->test_id = $this->tid;
        
        $insertId = R::store($dataRow);        
        $classAType = ucfirst($dataRow->type);
        $this->AOListener = new $classAType($insertId);
        $this->qid = $insertId;
        $this->type = $dataRow->type;
        $this->qBean = $dataRow;
        
        return $this;
    }
    
    private function _questionValidate($arFields)
    {
        $e = array();
        $arRequiredQuestion = array(
            'text' => 'Заполните текст вопроса',
            'type' => 'Укажите тип вопроса'
        );
        
        foreach ($arRequiredQuestion as $k => $v)
        {
            if (!isset($arFields[$k]) || empty($arFields[$k])) {
                $e[] = $v;
            } elseif ($k == 'type' && !isset(self::$arTypeQuestion[$arFields[$k]])) {                
                $e[] = 'Несуществующий тип вопроса';
            }
        }
        
        return $e;
    }
    
    public function deleteQuestion($qid)
    {
        if (!$this->checkPermissions(true)) {
            return false;
        }
        
        if (!$this->loadQuestion($qid)) {
            return false;
        }
        
        R::trash($this->qBean);
        return $this->qBean;        
    }
    
    public function editQuestion($arFields)
    {
        if (!$this->checkPermissions(true, true)) {
            return false;
        }
        
        $questionValidationErrors = $this->_questionValidate($arFields);
        if ($questionValidationErrors) {
            $this->last_errors = $questionValidationErrors;  
            return false;
        } 
        
        $this->qBean->text = $arFields['text'];
        R::store($this->qBean);
        
        return true;
    }
    
    /**
     * Возвращает список вопросов загруженного теста
     * @return array
     */
    public function getQuestionList()
    {
        if (!$this->checkPermissions(true)) {
            return false;
        }
        
        $sql = "
            SELECT q.*
            FROM " . TABLE_TEST_QUESTION . " AS q 
            LEFT JOIN " . TABLE_TEST . " AS t 
                ON (q.test_id = t.id)
            WHERE
                t.user_id = {$this->uid}
                AND q.test_id = {$this->tid}
            ORDER BY q.ord
        ";
        $records = R::getAll($sql);
        return R::convertToBeans(TABLE_TEST_QUESTION, $records);
    }
    
    /**
     * Добавляет варианты ответов к загруженному/созданному вопросу
     * @param array $arAnswers
     * @param array $arRightAnswers
     * @return boolean|\UTest
     */
    public function addAnswerList($arAnswers, $arRightAnswers)
    {
        if (!$this->checkPermissions(true, true)) {
            return false;
        }
        
        $validationErrors = $this->_answerValidate($arAnswers, $arRightAnswers);
        if ($validationErrors) {
            $this->last_errors = $validationErrors;
            return false;
        }    
        
        $res = $this->AOListener->save();
        if (!$res) {
            $this->last_errors = $this->AOListener->last_error;
            return false;
        }
        
        return $this;
    }  
    
    private function _answerValidate($arAnswers, $arRightAnswers)
    {
        if (!is_object($this->AOListener)) {
            return array('AOListener для работы с ответами не создан');            
        }        
        $this->AOListener->validate($arAnswers, $arRightAnswers);        
        return $this->AOListener->last_error;        
    }

    public function deleteAnswer($aid)
    {
        if (!$this->checkPermissions(true, true)) {
            return false;
        }
        
        $this->AOListener->delete($aid);
        if ($this->AOListener->last_error) {
            $this->last_errors = $this->AOListener->last_error;
            return false;
        }
        
        return true;
    }
    
    public function getAnswerList($fromAOListener = false)
    {
        if (!$this->checkPermissions(true, true)) {
            return false;
        }
        return $fromAOListener 
            ? $this->AOListener->getValidVariant()
            : R::find(TABLE_TEST_ANSWER, 'question_id = ?', array($this->qid));        
    }
    
    public function getTID()
    {
        return $this->tid;
    }
    
    public function getQID()
    {
        return $this->qid;
    }
    
    public function geQType()
    {
        return $this->qtype;
    }
    
    public function getQText()
    {
        return $this->qBean['text'];
    }
    
    /**
     * Проверяет наличие необходимых "прав-данных". 
     * По умолчанию, функция проверяет на наличие Id теста.
     * @param bool $checkUID - делать ли проверку на наличие Id автора теста
     * @param bool $checkAOListener - делать ли проверку на наличие объекта AOListener'а. 
     * Также данный паарметр будет актуален для проверки на загруженность вопроса в целом.
     * @return boolean
     */
    private function checkPermissions($checkUID = false, $checkAOListener = false) 
    {
        if (!$this->tid) {
            return false;
        } 
        if ($checkUID && !$this->uid) {
            $this->last_errors = array('Id автора теста не указан');
            return false;
        }   
        if ($checkAOListener && !is_object($this->AOListener)) {
            $this->last_errors = array('AOListener для работы с ответами не создан');
            return false;
        }
        return true;
    }

    /**
     * Возвращает список всех созданных тестов по переданному Id пользователя
     * @param integer $uid
     * @param integer $sid
     * @return array
     */
    public static function getList($uid, $sid = null)
    {
        $uid = intval($uid);
        
        if (!$uid) {
            self::$last_errors = array('Id пользователя не указан');
            return false;
        }
        
        $cond = is_null($sid) ? 'user_id = ? ' : 'user_id = ? AND subject_id = ? ';
        $values = is_null($sid) ? array($uid) : array($uid, $sid);
        $cond .= 'ORDER BY title';
        
        return R::find(TABLE_TEST, $cond, $values);
    }
    
    /**
     * Возвращает все доступные статусы тестов
     * @return array
     */
    public static function getStatusTest()
    {
        return self::$arStatusTest;
    }
    
    /**
     * Возвращает список всех доступных типов вопросов
     * @return array
     */
    public static function getTypeQuestion()
    {
        return self::$arTypeQuestion;
    }
    
}

