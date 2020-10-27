<?php

namespace Drupal\tpedu\Plugin\Field\FieldWidget;

/**
 * Plugin implementation of the 'classes_default' widget.
 *
 * @FieldWidget(
 *   id = "teachers_default",
 *   label = "選擇教師",
 *   field_types = {
 *     "tpedu_teachers"
 *   }
 * )
 */
class TeachersDefaultWidget extends TpeduWidgetBase
{
    protected function getOptions(FormStateInterface $form_state)
    {
        $options = [];
        $teachers = [];
        if ($this->getFieldSetting('filter_by_unit')) {
            $current = '';
            $fields = $form_state->getStorage()['field_storage']['#parents']['#fields'];
            foreach ($fields as $field_name => $my_field) {
                if (isset($my_field['field_type']) && $my_field['field_type'] == 'tpedu_units') {
                    $current = $form_state->getValue($field_name);
                }
            }
            if (empty($current)) {
                $current = $this->getFieldSetting('unit');
            }
            if (!empty($current)) {
                $teachers = get_teachers_of_unit($current);
            }
        }
        if ($this->getFieldSetting('filter_by_role')) {
            $current = '';
            $fields = $form_state->getStorage()['field_storage']['#parents']['#fields'];
            foreach ($fields as $field_name => $my_field) {
                if (isset($my_field['field_type']) && $my_field['field_type'] == 'tpedu_roles') {
                    $current = $form_state->getValue($field_name);
                }
            }
            if (empty($current)) {
                $current = $this->getFieldSetting('role');
            }
            if (!empty($current)) {
                $teachers = get_teachers_of_role($current);
            }
        }
        if ($this->getFieldSetting('filter_by_domain')) {
            $current = '';
            $fields = $form_state->getStorage()['field_storage']['#parents']['#fields'];
            foreach ($fields as $field_name => $my_field) {
                if (isset($my_field['field_type']) && $my_field['field_type'] == 'tpedu_domain') {
                    $current = $form_state->getValue($field_name);
                }
            }
            if (empty($current)) {
                $current = $this->getFieldSetting('domain');
            }
            if (!empty($current)) {
                $teachers = get_teachers_of_domain($current);
            }
        }
        if ($this->getFieldSetting('filter_by_subject')) {
            $current = '';
            $fields = $form_state->getStorage()['field_storage']['#parents']['#fields'];
            foreach ($fields as $field_name => $my_field) {
                if (isset($my_field['field_type']) && $my_field['field_type'] == 'tpedu_subjects') {
                    $current = $form_state->getValue($field_name);
                }
            }
            if (empty($current)) {
                $current = $this->getFieldSetting('subject');
            }
            if (!empty($current)) {
                $teachers = get_teachers_of_subject($current);
            }
        }
        if ($this->getFieldSetting('filter_by_grade')) {
            $current = '';
            $fields = $form_state->getStorage()['field_storage']['#parents']['#fields'];
            foreach ($fields as $field_name => $my_field) {
                if (isset($my_field['field_type']) && $my_field['field_type'] == 'tpedu_grade') {
                    $current = $form_state->getValue($field_name);
                }
            }
            if (empty($current)) {
                $current = $this->getFieldSetting('grade');
            }
            if (!empty($current)) {
                $teachers = get_teachers_of_grade($current);
            }
        }
        if ($this->getFieldSetting('filter_by_class')) {
            $current = '';
            $fields = $form_state->getStorage()['field_storage']['#parents']['#fields'];
            foreach ($fields as $field_name => $my_field) {
                if (isset($my_field['field_type']) && $my_field['field_type'] == 'tpedu_classes') {
                    $current = $form_state->getValue($field_name);
                }
            }
            if (empty($current)) {
                $current = $this->getFieldSetting('class');
            }
            if (!empty($current)) {
                $teachers = get_teachers_of_class($current);
            }
        }
        if (empty($teachers)) {
            $teachers = all_teachers();
        }
        usort($teachers, function ($a, $b) { return strcmp($a->realname, $b->realname); });
        foreach ($teachers as $t) {
            $options[$t->uuid] = $t->role_name.' '.$t->realname;
        }

        return $options;
    }
}
