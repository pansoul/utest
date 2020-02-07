<?php

return array(
    'admin' => array(
        array(
            'url' => '/admin/university',
            'title' => 'ВУЗ',
            'name' => 'university',
            'tooltip' => 'Управление структурой Вуза: создание факультетов и специальностей'
        ),
        array(
            'url' => '/admin/prepods',
            'title' => 'Преподаватели',
            'name' => 'prepod',
            'tooltip' => 'Создание базы преподавателей'
        ),
        array(
            'url' => '/admin/students',
            'title' => 'Студенты',
            'name' => 'student',
            'tooltip' => 'Управление базой учащихся: создание групп и студентов'
        ),
        array(
            'url' => '/admin/setting',
            'title' => 'Данные о ВУЗе',
            'name' => 'setting'
        ),
    ),
    
    'prepod' => array(
        array(
            'url' => '/prepod/subjects',
            'title' => 'Мои дисциплины',
            'name' => 'subject'
        ),
        array(
            'url' => '/prepod/tests',
            'title' => 'Тесты',
            'name' => 'test'
        ),
        array(
            'url' => '/prepod/results',
            'title' => 'Результаты',
            'name' => 'result',
            'tooltip' => 'Результаты тестирования студентов'
        ),        
        array(
            'url' => '/prepod/materials',
            'title' => 'Материал',
            'name' => 'material',
            'tooltip' => 'Материал для помощи к подготовке студентам'
        ),
        array(
            'url' => '/prepod/students',
            'title' => 'Студенты',
            'name' => 'student',
            'tooltip' => 'Возможность смены пароля студентам'
        ),
        
    ),
    
    'student' => array(
        array(
            'url' => '/student/tests',
            'title' => 'Тесты',
            'name' => 'test'
        ),
        array(
            'url' => '/student/results',
            'title' => 'Мои результаты',
            'name' => 'result',
        ), 
        array(
            'url' => '/student/materials',
            'title' => 'Материал',
            'name' => 'material',
            'tooltip' => 'Материал для подготовки'
        ),
        array(
            'url' => '/student/prepods',
            'title' => 'Преподаватели',
            'name' => 'prepod',
            'tooltip' => 'Список преподавателей'
        ),
    )
);
