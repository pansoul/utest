<?php

namespace UTest\Kernel\Test;

use UTest\Kernel\DB;
use UTest\Kernel\User\User;
use UTest\Kernel\Traits\FieldsValidateTraitHelper;
use UTest\Kernel\Utilities;

class Passage
{
    use \UTest\Kernel\Traits\ErrorsManageTrait;
    use \UTest\Kernel\Traits\FieldsValidateTrait;

    const STATUS_WAITED_FOR_START = 0;
    const STATUS_IN_PROCESS = 1;
    const STATUS_FINISHED = 2;

    const CHECK_UID = 'uid';
    const CHECK_ATID = 'atid';
    const CHECK_AID = 'aid';

    private $uid = 0; // {userId} - Id учащегося
    private $atid = 0; // {assignedTestId} - Id назначенного теста
    private $aid = 0; // Id сформированного вопроса
    private $retake = 0; // Номер пересдачи

    private $customOptions = [];
    private $options = [];
    private $passageData = [];
    private $timeData = [];
    private $lastAnswerData = [];

    /**
     * @var \UTest\Kernel\Test\Assignment
     */
    private $assignedTest = null;

    /**
     * @var \UTest\Kernel\Test\Test
     */
    private $baseTest = null;

    private static $arTestStatuses = array(
        self::STATUS_WAITED_FOR_START => 'ожидает старта',
        self::STATUS_IN_PROCESS => 'в процессе прохождения',
        self::STATUS_FINISHED => 'пройден'
    );

    public function __construct($uid = 0, $atid = 0, $retake = null, $options = [])
    {
        $this->customOptions = (array) $options;

        if (!User::getById($uid)) {
            $this->setErrors('Id учащегося для прохождения теста указан неверно или не существует');
        } else {
            $this->uid = $uid;
        }

        if ($atid) {
            $this->loadAssign($atid);
        }

        $this->retake = is_null($retake) ? $this->getRetake() : intval($retake);
        $this->loadPassageData();
        $this->loadTimeData();
        $this->loadLastAnswerData();
    }

    private function passageFieldsMap()
    {
        return [
            'user_id' => [
                FieldsValidateTraitHelper::_NAME => 'Привязка к пользователю',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'test_id' => [
                FieldsValidateTraitHelper::_NAME => 'Назначенный тест',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'status' => [
                FieldsValidateTraitHelper::_NAME => 'Статус',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'retake' => [
                FieldsValidateTraitHelper::_NAME => '№ пересдачи',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ]
            ],
            'last_q_number ' => [
                FieldsValidateTraitHelper::_NAME => '№ последнего вопроса',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ]
            ],
        ];
    }

    private function timeFieldsMap()
    {
        return [
            'user_id' => [
                FieldsValidateTraitHelper::_NAME => 'Привязка к пользователю',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'test_id' => [
                FieldsValidateTraitHelper::_NAME => 'Назначенный тест',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'date_start' => [
                FieldsValidateTraitHelper::_NAME => 'Статус',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ]
            ],
            'date_finish ' => [
                FieldsValidateTraitHelper::_NAME => 'Статус',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_EDIT
                ]
            ],
            'retake_value' => [
                FieldsValidateTraitHelper::_NAME => '№ пересдачи',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ]
            ],
        ];
    }

    private function answerFieldsMap()
    {
        return [
            'user_id' => [
                FieldsValidateTraitHelper::_NAME => 'Привязка к пользователю',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'test_id' => [
                FieldsValidateTraitHelper::_NAME => 'Назначенный тест',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'question_id' => [
                FieldsValidateTraitHelper::_NAME => 'Привязка к вопросу',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'user_answer' => [
                FieldsValidateTraitHelper::_NAME => 'Ответ',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ]
            ],
            'number' => [
                FieldsValidateTraitHelper::_NAME => '№ вопроса',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'retake_value' => [
                FieldsValidateTraitHelper::_NAME => '№ пересдачи',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ]
            ],
            'q' => [
                FieldsValidateTraitHelper::_NAME => 'Полная информация о вопросе',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ]
            ],
        ];
    }

    public function getRetake()
    {
        return (int) DB::table(TABLE_STUDENT_TEST_PASSAGE)
            ->select('retake')
            ->where([
                'user_id' => $this->uid,
                'test_id' => $this->atid
            ])
            ->first()['retake'];
    }

    public function loadAssign($id)
    {
        if ($id > 0 && $id == $this->atid) {
            return $this->assignedTest;
        }

        // @todo
        $this->assignedTest = null;
        $this->baseTest = null;
        $this->options = $this->customOptions;

        if (!$this->checkPermissions(self::CHECK_UID)) {
            return false;
        }

        $authorId = DB::table(TABLE_STUDENT_TEST)->find($id, ['user_id']);
        if (!$authorId) {
            $this->setErrors('Тест не найден');
        } else {
            $this->assignedTest = new Assignment($authorId, $id);

            if ($this->assignedTest->hasErrors()) {
                $this->setErrors($this->assignedTest->getErrors());;
                $this->assignedTest = null;
            } else {
                $this->baseTest = new Test($authorId, $this->assignedTest->getBaseTestId());
                $assignData = $this->getAssignData();
                $this->assignedTest->loadBaseList();
                $this->atid = $this->assignedTest->getAssignedTestId();
                $this->options = array_merge(
                    array_intersect_key($assignData, array_flip(['is_mixing', 'is_show_true', 'count_q', 'time'])),
                    $this->customOptions
                );
                if ($this->options['count_q'] == 0) {
                    $this->options['count_q'] = DB::table(TABLE_TEST_QUESTION)->where('test_id', '=', $this->assignedTest->getBaseTestId())->count();
                }
            }
        }

        return $this->assignedTest;
    }

    private function loadPassageData()
    {
        if (!$this->checkPermissions([self::CHECK_UID, self::CHECK_ATID])) {
            return false;
        }

        $this->passageData = DB::table(TABLE_STUDENT_TEST_PASSAGE)
            ->where([
                'user_id' => $this->uid,
                'test_id' => $this->atid,
                'retake' => $this->retake
            ])
            ->first();

        return true;
    }

    private function loadTimeData()
    {
        if (!$this->checkPermissions([self::CHECK_UID, self::CHECK_ATID])) {
            return false;
        }

        $this->timeData = DB::table(TABLE_STUDENT_TEST_TIME)
            ->where([
                'user_id' => $this->uid,
                'test_id' => $this->atid,
                'retake_value' => $this->retake
            ])
            ->first();

        return true;
    }

    private function loadLastAnswerData()
    {
        if (!$this->checkPermissions([self::CHECK_UID, self::CHECK_ATID])) {
            return false;
        }

        $res = DB::table(TABLE_STUDENT_TEST_ANSWER)
            ->where([
                'user_id' => $this->uid,
                'test_id' => $this->atid,
                'retake_value' => $this->retake,
                'number' => $this->getLastNumberQuestion()
            ])
            ->first();

        if ($res) {
            $res['q'] = unserialize($res['q']);
            $this->lastAnswerData = $res;
        }

        return $this->lastAnswerData;
    }

    public function getAssignData()
    {
        return $this->assignedTest->getAssignData();
    }

    public function getPassageData()
    {
        return $this->passageData;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function isMixing()
    {
        return (bool) $this->options['is_mixing'];
    }

    public function isShowTrue()
    {
        return (bool) $this->options['is_show_true'];
    }

    public function canSkip()
    {
        return (bool) $this->options['can_skip'];
    }

    public function canChange()
    {
        return (bool) $this->options['can_change'];
    }

    public function getNumberQuestions()
    {
        return (int) $this->options['count_q'];
    }

    public function hasTimeLimit()
    {
        return $this->getTimeLimit() > 0;
    }

    public function isTimeLeft()
    {
        if ($this->hasTimeLimit()) {
            return $this->getTimeLeft() <= 0;
        }
        return false;
    }

    public function getTimeLeft()
    {
        $timeStart = strtotime($this->getTimeStart());
        $curTime = time();
        $passageTimeLive = $curTime - $timeStart;
        return $this->getTimeLimit() - $passageTimeLive;
    }

    public function getTimeStart()
    {
        return $this->passageData['date_start'];
    }

    public function getTimeLimit()
    {
        return (int) $this->options['time'];
    }

    public function getLastNumberQuestion()
    {
        return (int) $this->passageData['last_q_number'];
    }

    public function getStatus($getStatusText = false)
    {
        return $getStatusText ? self::getTestStatuses($this->getStatus()) : intval($this->passageData['status']);
    }

    public function start()
    {
        if (!$this->checkPermissions([self::CHECK_UID, self::CHECK_ATID]) || ($this->getStatus() != self::STATUS_WAITED_FOR_START && $this->hasEqualComplexRetake())) {
            return false;
        }

        $passageFields = [
            'user_id' => $this->uid,
            'test_id' => $this->atid,
            'status' => self::STATUS_IN_PROCESS,
            'retake' => $this->retake,
            'last_q_number' => 0,
        ];
        $timeFields = [
            'user_id' => $this->uid,
            'test_id' => $this->atid,
            'date_start' => Utilities::getDateTime(),
            'retake_value' => $this->retake,
        ];

        $passageFields = $this->checkFields($this->passageFieldsMap(), $passageFields, FieldsValidateTraitHelper::_ADD, $this->errors);
        $timeFields = $this->checkFields($this->timeFieldsMap(), $timeFields, FieldsValidateTraitHelper::_ADD, $this->errors);

        if ($this->hasErrors()) {
            return false;
        }

        DB::table(TABLE_STUDENT_TEST_PASSAGE)->updateOrInsert(['user_id' => $this->uid, 'test_id' => $this->atid] ,$passageFields);
        DB::table(TABLE_STUDENT_TEST_TIME)->insert($timeFields);
    }

    public function finish()
    {
        if (!$this->checkPermissions([self::CHECK_UID, self::CHECK_ATID]) || $this->getStatus() == self::STATUS_WAITED_FOR_START) {
            return false;
        }

        // @todo
    }

    public function resume()
    {
        if (!$this->checkPermissions([self::CHECK_UID, self::CHECK_ATID]) || $this->getStatus() != self::STATUS_IN_PROCESS) {
            return false;
        }

        // @todo
    }

    public function getNextQuestionNumber()
    {
        $nextNumber = $this->getLastNumberQuestion() + 1;
        return $nextNumber > $this->getNumberQuestions() ? $this->getNumberQuestions() : $nextNumber;
    }

    public function getPrevQuestionNumber()
    {
        $prevNumber = $this->getLastNumberQuestion() - 1;
        return $prevNumber <= 0 ? 1 : $prevNumber;
    }

    public function loadQuestion($number = 0)
    {
        if (!$this->checkPermissions([self::CHECK_UID, self::CHECK_ATID])) {
            return false;
        }

        $number = (int) $number;

        if (empty($this->passageData) || empty($this->timeData) || !$this->hasEqualComplexRetake()) {
            $this->setErrors('Начало теста не проинициализировано');
        } elseif ($number < 1 || $number > $this->getNumberQuestions()) {
            $this->setErrors('Несуществующий номер вопроса');
        } elseif (!$this->canSkip() && $number > $this->getNextQuestionNumber()) {
            $this->setErrors('Нельзя перескакивать вопросы');
        } elseif (!$this->canChange() && $number < $this->getPrevQuestionNumber()) {
            $this->setErrors('Нельзя изменять ответы на отвеченные вопросы');
        }

        if ($this->hasErrors()) {
            return false;
        }

        $usedIds = $this->getUsedQuestionsIds();
        $availableIds = $this->getAvailableQuestionsIds(count($usedIds) == $this->getNumberQuestions());
        $questionId = $this->isMixing() ? array_rand(array_flip($availableIds)) : $availableIds[0];

        if (isset($usedIds[$number])) {
            return $this->getQuestion($number);
        }

        $this->baseTest->loadQuestion($questionId);
        $this->baseTest->loadAnswersList($questionId);

        $q = [
            'question' => $this->baseTest->getQuestionData(),
            'answer_list' => $this->baseTest->getAnswersList(Test::ANSWERS_MODE_VARIANTS),
            'cur_num' => $number
        ];
        $answerFields = [
            'user_id' => $this->uid,
            'test_id' => $this->atid,
            'question_id' => $questionId,
            'retake_value' => $this->retake,
            'number' => $number,
            'q' => serialize($q)
        ];

        $answerFields = $this->checkFields($this->answerFieldsMap(), $answerFields, FieldsValidateTraitHelper::_ADD, $this->errors);

        if ($this->hasErrors()) {
            return false;
        }

        DB::table(TABLE_STUDENT_TEST_ANSWER)->insert($answerFields);
        DB::table(TABLE_STUDENT_TEST_PASSAGE)->where('id', '=', $this->passageData['id'])->update(['last_q_number' => $number]);

        $this->setLastNumberQuestion($number);

        return $q;
    }

    public function saveAnswer($v = [])
    {
        if (!$this->checkPermissions([self::CHECK_UID, self::CHECK_ATID, self::CHECK_AID])) {
            return false;
        }


    }

    private function getQuestion($number = 0)
    {
        $this->setLastNumberQuestion($number);

        $q = $this->lastAnswerData['q'];
        $q['user_answer'] = $this->lastAnswerData['user_answer'];

        return $q;
    }

    private function getAvailableQuestionsIds($full = false)
    {
        if (!$this->checkPermissions([self::CHECK_UID, self::CHECK_ATID])) {
            return false;
        }

        $res = DB::table(TABLE_TEST_QUESTION)->where('test_id', '=', $this->assignedTest->getBaseTestId())->orderBy('ord')->get()->toArray();
        $ids = array_map(function($item){
            return $item['id'];
        }, $res);

        if (!$full) {
            $usedIds = $this->getUsedQuestionsIds();
            $ids = array_values(array_diff($ids, $usedIds));
        }

        return $ids;
    }

    private function getUsedQuestionsIds()
    {
        if (!$this->checkPermissions([self::CHECK_UID, self::CHECK_ATID])) {
            return false;
        }

        $res = DB::table(TABLE_STUDENT_TEST_ANSWER)
            ->select('question_id', 'number')
            ->where([
                'test_id' => $this->atid,
                'user_id' => $this->uid,
                'retake_value' => $this->retake
            ])
            ->get()
            ->toArray();

        $ids = array_reduce($res, function($acc, $item){
            $acc[$item['number']] = $item['question_id'];
            return $acc;
        }, []);

        return $ids;
    }

    private function setLastNumberQuestion($number)
    {
        $this->options['last_q_number'] = (int) $number;
        $this->loadLastAnswerData();
    }

    private function hasEqualComplexRetake()
    {
        return $this->retake == intval($this->passageData['retake']) && $this->retake == intval($this->timeData['retake_value']);
    }

    /**
     * Проверяет на наличие необходимых загруженных данных
     * @param null $types
     * @return bool
     */
    private function checkPermissions($types = null)
    {
        $types = (array) $types;
        if (empty($types)) {
            return false;
        }

        $errorMessages = [
            self::CHECK_UID => 'Учащийся не загружен',
            self::CHECK_ATID => 'Тест не загружен',
            self::CHECK_AID => 'Вопрос не загружен'
        ];

        $result = 0;
        foreach ($types as $type) {
            $check = false;
            switch ($type) {
                case self::CHECK_UID:
                    $check = !empty($this->uid);
                    break;

                case self::CHECK_ATID:
                    $check = !empty($this->atid);
                    break;

                case self::CHECK_AID:
                    $check = !empty($this->aid);
                    break;

                default:
                    continue;
            }
            if (!$check) {
                $this->setErrors($errorMessages[$type]);
            }
            $result += intval($check);
        }

        return $result == count($types);
    }

    public static function getTestStatuses($statusCode = null)
    {
        return is_null($statusCode) ? self::$arTestStatuses : self::$arTestStatuses[intval($statusCode)];
    }
}