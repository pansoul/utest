<?php

namespace UTest\Components;

use UTest\Kernel\DB;
use UTest\Kernel\User\User;
use UTest\Kernel\Test\Test;
use UTest\Kernel\Test\Assignment;
use UTest\Kernel\Site;
use UTest\Kernel\Utilities;
use UTest\Kernel\HttpRequest;

class PrepodTestsModel extends \UTest\Kernel\Component\Model
{
    private $test = null;
    private $assignment = null;

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->test = new Test(User::user()->getUID());
        $this->assignment = new Assignment(User::user()->getUID());
    }

    public function myAction()
    {
        $res = DB::table(TABLE_PREPOD_SUBJECT)
            ->select(
                TABLE_PREPOD_SUBJECT.'.*',
                DB::raw('count('.TABLE_TEST.'.id) as test_count')
            )
            ->leftJoin(TABLE_TEST, TABLE_TEST.'.subject_id', '=', TABLE_PREPOD_SUBJECT.'.id')
            ->where(TABLE_PREPOD_SUBJECT.'.user_id', '=', User::user()->getUID())
            ->groupBy(TABLE_PREPOD_SUBJECT.'.id')
            ->orderBy(TABLE_PREPOD_SUBJECT.'.title')
            ->get();

        $this->setData($res);
    }

    public function myTestsAction($subjectCode)
    {
        if ($this->isActionRequest('del_all') && $this->isNativeActionMethod()) {
            foreach ($this->_POST['i'] as $id) {
                if (!$id) {
                    continue;
                }
                $this->test->delete($id);
            }
            Site::redirect(Site::getUrl());
        }

        $parent = DB::table(TABLE_PREPOD_SUBJECT)
            ->where('alias', '=', $subjectCode)
            ->where('user_id', '=', User::user()->getUID())
            ->first();

        $res = DB::table(TABLE_TEST)
            ->where([
                'subject_id' => $parent['id'],
                'user_id' => User::user()->getUID()
            ])
            ->orderBy('title')
            ->get();

        if (!$parent) {
            $this->setErrors('Предмет не найден', ERROR_ELEMENT_NOT_FOUND);
        }

        $this->setData($res);
        return $parent;
    }

    public function myNewTestAction($v = array())
    {
        $subject = $this->myTestsAction($this->vars['subject_code']);
        if ($this->hasErrors(ERROR_ELEMENT_NOT_FOUND)) {
            $this->setData(null);
            return;
        }

        $subjectList = DB::table(TABLE_PREPOD_SUBJECT)
            ->where('user_id', '=', User::user()->getUID())
            ->orderBy('title')
            ->get()
            ->toArray();
        $subjectList = array_reduce($subjectList, function($acc, $item){
            $acc[$item['id']] = $item['title'];
            return $acc;
        }, []);

        if ($this->isActionRequest()) {
            $v = $this->_POST;
            $dataRow = [
                'title' => $v['title'],
                'subject_id' => isset($subjectList[$v['subject_id']]) ? $v['subject_id'] : $subject['id']
            ];

            $this->test->createOrEdit($dataRow, $v['id']);

            if ($this->test->hasErrors()) {
                $this->setErrors($this->test->getErrors());
            } else {
                Site::redirect(Site::getModurl() . '/my/' . $this->vars['subject_code']);
            }
        } else {
            $v['subject_id'] = $subject['id'];
        }

        $this->setData([
            'form' => $v,
            'subject_list' => $subjectList
        ]);
    }

    public function myEditTestAction($id)
    {
        if (!$this->test->loadTest($id)) {
            $this->setErrors($this->test->getErrors(), ERROR_ELEMENT_NOT_FOUND);
        }
        return $this->myNewTestAction($this->test->getTestData());
    }

    public function myTestQuestionsAction($subjectCode, $testId)
    {
        $this->myTestsAction($subjectCode);
        if ($this->hasErrors(ERROR_ELEMENT_NOT_FOUND)) {
            $this->setData(null);
            return;
        }

        if (!$this->test->loadTest($testId)) {
            $this->setErrors($this->test->getErrors(), ERROR_ELEMENT_NOT_FOUND);
        } else {
            if ($this->isActionRequest('del_all') && $this->isNativeActionMethod()) {
                foreach ($this->_POST['i'] as $id) {
                    if (!$id) {
                        continue;
                    }
                    $this->test->deleteQuestion($id);
                }
                Site::redirect(Site::getUrl());
            }

            $this->test->loadQuestionsList();
        }

        $this->setData($this->test->getQuestionsList());
    }

    public function myNewQuestionAction($v = array())
    {
        $this->myTestQuestionsAction($this->vars['subject_code'], $this->vars['tid']);
        if ($this->hasErrors(ERROR_ELEMENT_NOT_FOUND)) {
            $this->setData(null);
            return;
        }

        $arQuestionTypes = Test::getQuestionTypes();
        $arQuestionTypes = array_map(function($value){
            return $value['name'];
        }, $arQuestionTypes);
        
        if ($this->isActionRequest()) {
            $v = $this->_POST;

            if (isset($v['question']['text'])) {
                $v['question']['text'] = HttpRequest::convert2safe($v['~question']['text']);
            }

            $this->test->createOrEditQuestion($v['question'], $v['variant'], $v['right'], $v['question']['id']);

            if ($this->test->hasErrors()) {
                $this->setErrors($this->test->getErrors());
            } else {
                Site::redirect(Site::getModurl() . '/my/' . $this->vars['subject_code'] . '/test-' . $this->vars['tid']);
            }
        }

        $this->setData([
            'question_type_list' => $arQuestionTypes,
            'form_question' => $v['question'],
            'form_answer' => $v['variant'],
            'form_right' => $v['right']
        ]);
    }

    public function myEditQuestionAction($tid, $id)
    {
        $v = [];
        if (!$this->test->loadTest($tid) || !$this->test->loadQuestion($id)) {
            $this->setErrors($this->test->getErrors(), ERROR_ELEMENT_NOT_FOUND);
        } elseif ($this->test->loadAnswersList()) {
            $v['id'] = $this->test->getQuestionId();
            $v['question'] = $this->test->getQuestionData();
            $v['variant'] = $this->test->getAnswersList(Test::ANSWERS_MODE_VARIANTS);
            $v['right'] = $this->test->getAnswersList(Test::ANSWERS_MODE_RIGHTS);
        }
        $this->myNewQuestionAction($v);
    }

    public function forAction()
    {
        $res = DB::table(TABLE_UNIVER_GROUP)
            ->select(
                TABLE_UNIVER_GROUP.'.*',
                TABLE_UNIVER_SPECIALITY.'.title as speciality_title',
                TABLE_UNIVER_FACULTY.'.title as faculty_title'
            )
            ->leftJoin(TABLE_UNIVER_SPECIALITY, TABLE_UNIVER_SPECIALITY.'.id', '=', TABLE_UNIVER_GROUP.'.speciality_id')
            ->leftJoin(TABLE_UNIVER_FACULTY, TABLE_UNIVER_FACULTY.'.id', '=', TABLE_UNIVER_SPECIALITY.'.faculty_id')
            ->orderBy(TABLE_UNIVER_GROUP.'.title')
            ->get();

        $this->setData($res);
    }

    public function forSubjectAction($groupCode)
    {
        $group = DB::table(TABLE_UNIVER_GROUP)->where('alias', '=', $groupCode)->first();

        $res = DB::table(TABLE_PREPOD_SUBJECT)
            ->select(
                TABLE_PREPOD_SUBJECT.'.*',
                DB::raw('count('.TABLE_STUDENT_TEST.'.id) as test_count')
            )
            ->leftJoin(TABLE_STUDENT_TEST, function($join) use ($group) {
                $join->on(TABLE_STUDENT_TEST.'.subject_id', '=', TABLE_PREPOD_SUBJECT.'.id')
                    ->where(TABLE_STUDENT_TEST.'.group_id', '=', $group['id'])
                    ->where(TABLE_STUDENT_TEST.'.user_id', '=', User::user()->getUID());
            })
            ->where(TABLE_PREPOD_SUBJECT.'.user_id', '=', User::user()->getUID())
            ->groupBy(TABLE_PREPOD_SUBJECT.'.id')
            ->orderBy(TABLE_PREPOD_SUBJECT.'.title')
            ->get();

        if (!$group) {
            $this->setErrors('Группа не найдена', ERROR_ELEMENT_NOT_FOUND);
        }

        $this->setData($res);
        return $group;
    }

    public function forTestsAction($groupCode, $subjectCode)
    {
        $group = $this->forSubjectAction($groupCode);
        if ($this->hasErrors(ERROR_ELEMENT_NOT_FOUND)) {
            $this->setData(null);
            return;
        }

        if ($this->isActionRequest('del_all') && $this->isNativeActionMethod()) {
            foreach ($this->_POST['i'] as $id) {
                if (!$id) {
                    continue;
                }
                $this->assignment->delete($id);
            }
            Site::redirect(Site::getUrl());
        }

        $subject = DB::table(TABLE_PREPOD_SUBJECT)
            ->where('alias', '=', $subjectCode)
            ->where('user_id', '=', User::user()->getUID())
            ->first();

        $res = DB::table(TABLE_STUDENT_TEST)
            ->select(
                TABLE_STUDENT_TEST.'.*',
                TABLE_TEST.'.title as base_title',
                DB::raw('count('.TABLE_TEST_QUESTION.'.id) as base_count_q')
            )
            ->leftJoin(TABLE_TEST, TABLE_TEST.'.id', '=', TABLE_STUDENT_TEST.'.test_id')
            ->leftJoin(TABLE_TEST_QUESTION, TABLE_TEST_QUESTION.'.test_id', '=', TABLE_STUDENT_TEST.'.test_id')
            ->where([
                TABLE_STUDENT_TEST.'.group_id' => $group['id'],
                TABLE_STUDENT_TEST.'.subject_id' => $subject['id'],
                TABLE_STUDENT_TEST.'.user_id' => User::user()->getUID(),
            ])
            ->orderBy(TABLE_STUDENT_TEST.'.date')
            ->groupBy(TABLE_STUDENT_TEST.'.id')
            ->get();

        if (!$subject) {
            $this->setErrors('Предмет не найден', ERROR_ELEMENT_NOT_FOUND);
        }

        $this->setData($res);

        return [
            'group' => $group,
            'subject' => $subject
        ];
    }

    public function forNewTestAction($v = array())
    {
        $data = $this->forTestsAction($this->vars['group_code'], $this->vars['subject_code']);
        $group = $data['group'];
        $subject = $data['subject'];

        if ($this->hasErrors(ERROR_ELEMENT_NOT_FOUND)) {
            $this->setData(null);
            return;
        }

        $baseList = DB::table(TABLE_TEST)
            ->where(['subject_id' => $subject['id'], 'user_id' => User::user()->getUID()])
            ->orderBy('title')
            ->get()
            ->toArray();
        $baseList = array_reduce($baseList, function($acc, $item){
            $acc[$item['id']] = $item['title'];
            return $acc;
        }, []);

        if ($this->isActionRequest()) {
            $v = $this->_POST;
            $dataRow = [
                'title' => $v['title'],
                'group_id' => $group['id'],
                'subject_id' => $subject['id'],
                'test_id' => isset($baseList[$v['test_id']]) ? $v['test_id'] : 0,
                'count_q' => $v['count_q'],
                'is_mixing' => $v['is_mixing'],
                'is_show_true' => $v['is_show_true'],
                'date' => Utilities::getDateTime()
            ];

            $this->assignment->createOrEdit($dataRow, $v['id']);

            if ($this->assignment->hasErrors()) {
                $this->setErrors($this->assignment->getErrors());
            } else {
                Site::redirect(Site::getModurl() . '/for/' . $this->vars['group_code'] . '/' . $this->vars['subject_code']);
            }
        }

        $this->setData([
            'form' => $v,
            'base_list' => $baseList
        ]);
    }

    public function forEditTestAction($id)
    {
        if (!$this->assignment->loadAssign($id)) {
            $this->setErrors($this->assignment->getErrors(), ERROR_ELEMENT_NOT_FOUND);
        }
        return $this->forNewTestAction($this->assignment->getAssignData());
    }

    public function deleteAction($type, $id)
    {
        if (!($type && $id)) {
            return;
        }

        $back = [];
        $back[] = $type;

        if ($type == 'my') {
            $back[] = $this->vars['subject_code'];

            if ($this->vars['tid']) {
                $this->test->loadTest($this->vars['tid']);
                $this->test->deleteQuestion($id);
                $back[] = 'test-' . $this->vars['tid'];
            } else {
                $this->test->delete($id);
            }
        }
        elseif ($type == 'for') {
            $back[] = $this->vars['group_code'];
            $back[] = $this->vars['subject_code'];
            $this->assignment->delete($id);
        }

        Site::redirect(Site::getModurl() . '/' . join('/', $back));
    }

    public function answerDisplayAction($q, $a, $r)
    {
        $this->setData([
            'form_question' => $q,
            'form_answer' => $a,
            'form_right' => $r
        ]);
    }
}