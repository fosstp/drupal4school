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
    protected function getOptions()
    {
        $options = [];
        $teachers = [];
        if ($this->getFieldSetting('filter_by_unit') && $this->getFieldSetting('unit')) {
            $teachers = get_teachers_of_unit($this->getFieldSetting('unit'));
        }
        if ($this->getFieldSetting('filter_by_role') && $this->getFieldSetting('role')) {
            $teachers = get_teachers_of_role($this->getFieldSetting('role'));
        }
        if ($this->getFieldSetting('filter_by_domain') && $this->getFieldSetting('domain')) {
            $teachers = get_teachers_of_domain($this->getFieldSetting('domain'));
        }
        if ($this->getFieldSetting('filter_by_subject') && $this->getFieldSetting('subject')) {
            $teachers = get_teachers_of_subject($this->getFieldSetting('subject'));
        }
        if ($this->getFieldSetting('filter_by_grade') && $this->getFieldSetting('grade')) {
            $teachers = get_teachers_of_grade($this->getFieldSetting('grade'));
        }
        if ($this->getFieldSetting('filter_by_class') && $this->getFieldSetting('class')) {
            $teachers = get_teachers_of_class($this->getFieldSetting('class'));
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
