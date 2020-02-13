<?php

namespace UTest\Kernel\Traits;

class FieldsValidateTraitHelper
{
    const _AVAILABLE = 'available';
    const _REQUIRED = 'required';
    const _NAME = 'name';
    const _ADD = 'add';
    const _EDIT = 'edit';
    const _VALIDATE = 'validate';
}

trait FieldsValidateTrait
{
    public function getGroupFields($fieldsMap = [], $arFields = [], $inGroups = null, $getRequired = null)
    {
        if (empty($inGroups) && is_null($getRequired)) {
            return array_keys($fieldsMap);
        }

        $inGroups = (array) $inGroups;
        $fields = array_filter($fieldsMap, function($item) use ($inGroups, $getRequired, $arFields) {
            if ($inGroups && array_diff($inGroups, (array) @$item[FieldsValidateTraitHelper::_AVAILABLE])) {
                return false;
            }
            if (!is_null($getRequired)) {
                if (is_callable(@$item[FieldsValidateTraitHelper::_REQUIRED])) {
                    return (bool) $item[FieldsValidateTraitHelper::_REQUIRED]($arFields, $inGroups);
                } else {
                    return (bool) @$item[FieldsValidateTraitHelper::_REQUIRED];
                }
            }
            return true;
        });

        return array_keys($fields);
    }

    protected function checkFields($fieldsMap = [], $arFields = [], $inGroups = null, &$errors = [])
    {
        $inGroups = (array) $inGroups;
        $arAvailableFields = $this->getGroupFields($fieldsMap, $arFields, $inGroups);
        $arRequiredFields = $this->getGroupFields($fieldsMap, $arFields, $inGroups, true);

        $arFields = array_filter($arFields, function($key) use ($arAvailableFields) {
            return in_array($key, $arAvailableFields);
        }, ARRAY_FILTER_USE_KEY);
        $arFields = array_map('trim', $arFields);

        if (empty($arFields)) {
            $errors[] = 'Входной массив параметров пуст';
            return false;
        }

        foreach ($arRequiredFields as $field) {
            if (in_array(FieldsValidateTraitHelper::_EDIT, $inGroups)) {
                if (isset($arFields[$field]) && strlen($arFields[$field]) == 0) {
                    $errors[] = "Заполните поле '{$fieldsMap[$field][FieldsValidateTraitHelper::_NAME]}'";
                }
            } else {
                if (!isset($arFields[$field]) || strlen($arFields[$field]) == 0) {
                    $errors[] = "Заполните поле '{$fieldsMap[$field][FieldsValidateTraitHelper::_NAME]}'";
                }
            }
            if (isset($fieldsMap[$field][FieldsValidateTraitHelper::_VALIDATE])) {
                // @todo реализовать предварительную валидацию поля
                // Предполагаемый массив параметров для указания валидации:
                // [
                //    'type' => 'integer',
                //    'limit' => 11,
                //    'link' => [TABLE_UNIVER_GROUP, 'id']
                // ]
            }
        }

        return $arFields;
    }
}