<?php

namespace UTest\Components;

use UTest\Kernel\Site;

class PrepodMaterialsController extends \UTest\Kernel\Component\Controller
{
    protected $arTabs;

    protected $routeMap = array(
        'title' => 'Материал',
        'add_breadcrumb' => true,
        'action_main' => 'my',
        'actions_params' => array(
            '/my/<subject_code>' => [
                'action' => 'myMaterial',
            ],

            'my' => '/<subject_code>',
            'for' => '/<group_code>/<subject_code>',
            'newmy' => '/<in>',
            'newfor' => '/<group_code>/<subject_code>',
            'newcomment' => '/<group_code>/<subject_code>',
            'editmy' => '/<id>',
            'editcomment' => '/<id>',
            'delete' => '/<type>/<id>'
        ),
        'vars_rules' => array(
            'subject_code' => '[-_a-zA-Z0-9]',
            'group_code' => '[-_a-zA-Z0-9]',
            'in' => '[-_a-zA-Z0-9]',
            'id' => '[0-9]',
            'type' => '[a-zA-Z]'
        )
    );

    public function run()
    {
        $html = '';
        $this->arTabs = array(
            'my' => array(
                'name' => 'Мой материал',
                'href' => Site::getModurl() . '/my'
            ),
            'for' => array(
                'name' => 'Материал для групп',
                'href' => Site::getModurl() . '/for'
            )
        );
        $tabSelected = 'my';

        dump($this->action);

        switch ($this->action) {
            case 'my':
                $this->doAction($this->action);
                $html = $this->getVars('subject_code')
                    ? $this->loadView('mymaterial')
                    : $this->loadView($this->action);
                break;

            case 'for':
                UAppBuilder::editBreadcrumpItem(array(
                    'name' => 'Материал для групп',
                    'url' => Site::getModurl() . '/for'
                ));
                if ($this->model->vars['subject_code']) {
                    $html = $this->loadView('formaterial');
                } elseif ($this->model->vars['group_code']) {
                    $html = $this->loadView('forsubject');
                } else {
                    $html = $this->loadView($this->action);
                }
                break;

            case 'newfor':
            case 'newcomment':
                UAppBuilder::editBreadcrumpItem(array(
                    'name' => 'Материал для групп',
                    'url' => Site::getModurl() . '/for'
                ));
                $html = $this->loadView($this->action);
                break;

            case 'editmy':
                $this->doAction($this->action, (array)$this->model->vars['id']);
                $html = $this->loadView('newmy');
                break;

            case 'editcomment':
                UAppBuilder::editBreadcrumpItem(array(
                    'name' => 'Материал для групп',
                    'url' => Site::getModurl() . '/for'
                ));
                $this->doAction($this->action, (array)$this->model->vars['id']);
                $html = $this->loadView('newcomment');
                break;

            case 'delete':
                $this->doAction($this->action,
                    array($this->model->vars['type'], $this->model->vars['id']));
                break;

            default:
                $html = $this->loadView($this->action);
                break;
        }

        $tabs = $this->loadView('tabs', [
            'tabs' => $this->arTabs,
            'selected' => $tabSelected
        ]);

        $this->putContent($tabs);
        $this->putContent($html);
    }

}
