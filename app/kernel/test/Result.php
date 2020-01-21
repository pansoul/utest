<?php
namespace UTest\Kernel\Test;

use UTest\Kernel\DB;
use UTest\Kernel\User\User;

/**
 * Класс по результатам тестирования
 * @package UTest\Kernel\Test
 */
class Result
{
    use \UTest\Kernel\Traits\ErrorsManageTrait;

    const CHECK_UID = 'uid';
    const CHECK_ATID = 'atid';
    const CHECK_PASSAGE = 'passage';

    private $uid = 0; // {userId} - Id учащегося
    private $atid = 0; // {assignedTestId} - Id назначенного теста
    private $retake = 0; // Номер пересдачи
    private $amountRightUserAnswers = 0;

    private $options = [];
    private $passageData = [];
    private $timeData = [];
    private $assignData = [];
    private $userAnswersList = [];

    /**
     * @var \UTest\Kernel\Test\Passage
     */
    private $passage = null;

    public function __construct($uid, $atid, $retake = null)
    {
        $this->passage = new Passage($uid, $atid, $retake);

        if ($this->passage->hasErrors()) {
            $this->setErrors($this->passage->getErrors());
            $this->passage = null;
        } else {
            $this->options = $this->passage->getOptions();
            $this->passageData = $this->passage->getPassageData();
            $this->assignData = $this->passage->getAssignData();
            $this->timeData = $this->passage->getTimeData();

            $this->atid = $this->assignData['id'];
            $this->uid = $uid;
            $this->retake = $this->passage->getRetake();

            $this->loadUserAnswersList();
        }
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getPassageData()
    {
        return $this->passageData;
    }

    public function getAssignData()
    {
        return $this->assignData;
    }

    public function getTimeData()
    {
        return $this->timeData;
    }

    public function getRetake()
    {
        return $this->retake;
    }

    public function getStatus($getStatusText = false)
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_PASSAGE)) {
            return false;
        }

        return $this->passage->getStatus($getStatusText);
    }

    public function isFinished()
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_PASSAGE)) {
            return false;
        }

        return $this->passage->isFinished();
    }

    public function isMixing()
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_PASSAGE)) {
            return false;
        }

        return $this->passage->isMixing();
    }

    public function isShowTrue()
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_PASSAGE)) {
            return false;
        }

        return $this->passage->isShowTrue();
    }

    public function canSkip()
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_PASSAGE)) {
            return false;
        }

        return $this->passage->canSkip();
    }

    public function canChange()
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_PASSAGE)) {
            return false;
        }

        return $this->passage->canChange();
    }

    public function getNumberQuestions()
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_PASSAGE)) {
            return false;
        }

        return $this->passage->getNumberQuestions();
    }

    public function hasTimeLimit()
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_PASSAGE)) {
            return false;
        }

        return $this->passage->hasTimeLimit();
    }

    public function getTimeStart()
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_PASSAGE)) {
            return false;
        }

        return $this->passage->getTimeStart();
    }

    public function getTimeFinish()
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_PASSAGE)) {
            return false;
        }

        return $this->passage->getTimeFinish();
    }

    public function getResult($getActual = false)
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_PASSAGE)) {
            return false;
        }

        if ($getActual) {
            $this->loadUserAnswersList();
        }

        return [
            'atid' => $this->atid,
            'retake' => $this->retake,
            'all_count' => $this->getNumberQuestions(),
            'right_count' => $this->amountRightUserAnswers,
            'wrong_count' => $this->getNumberQuestions() - $this->amountRightUserAnswers,
            'skipped_count' => count(array_filter($this->userAnswersList, function($item){ return is_null($item); })),
            'answered_count' => count(array_filter($this->userAnswersList)),
            'passage_percent' => round(100 / $this->getNumberQuestions() * $this->amountRightUserAnswers, 1),
            'answers_list' => $this->userAnswersList
        ];
    }

    public function getMeta()
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_PASSAGE)) {
            return false;
        }

        static $meta = null;
        if (is_null($meta)) {
            $subject = DB::table(TABLE_PREPOD_SUBJECT)->find($this->assignData['subject_id'], ['title']);
            $meta = [
                'subject' => $subject['title'],
                'author' => User::user($this->assignData['user_id'])->getFullName()
            ];
        }

        return $meta;
    }

    private function loadUserAnswersList()
    {
        $amountRightUserAnswers = 0;
        $userAnswersList = array_fill(1, $this->getNumberQuestions(), null);
        $answers = DB::table(TABLE_STUDENT_TEST_ANSWER)
            ->where([
                'user_id' => $this->uid,
                'test_id' => $this->atid,
                'retake_value' => $this->retake
            ])
            ->get();

        foreach ($answers as $item) {
            $question = unserialize($item['q'])['question'];
            $userAnswer = unserialize($item['user_answer']);
            $qTypeClass = Test::getQuestionTypeClass($question['type']);
            $qTypeEntity = new $qTypeClass($item['question_id']);
            $qTypeEntity->loadAnswersList();
            $amountRightUserAnswers += $qTypeEntity->check($userAnswer);

            $userAnswersList[$item['number']] = [
                'id' => $item['id'],
                'user_answer' => $userAnswer,
                'question' => $question,
                'variants' => $qTypeEntity->getValidVariants(),
                'right' =>  $qTypeEntity->getValidRights(),
                'isUserAnswerRight' => $qTypeEntity->check($userAnswer)
            ];
        }

        $this->userAnswersList = $userAnswersList;
        $this->amountRightUserAnswers = $amountRightUserAnswers;
    }

    private function checkPermissions($types = null)
    {
        $types = (array) $types;
        if (empty($types)) {
            return false;
        }

        $errorMessages = [
            self::CHECK_UID => 'Учащийся не загружен',
            self::CHECK_ATID => 'Тест не загружен',
            self::CHECK_PASSAGE => 'Не удалось загрузить данные проходимого теста'
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

                case self::CHECK_PASSAGE:
                    $check = !empty($this->passage);
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
}