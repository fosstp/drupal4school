<?php

namespace Drupal\tpedu\Plugin\Field\FieldWidget;

/**
 * Plugin implementation of the 'classes_default' widget.
 *
 * @FieldWidget(
 *   id = "students_default",
 *   label = "選擇學生",
 *   field_types = {
 *     "tpedu_students"
 *   }
 * )
 */
class StudentsDefaultWidget extends TpeduWidgetBase
{
    protected function getOptions()
    {
        $options = array();
        $students = array();
        if ($this->getFieldSetting('filter_by_class') && $this->getFieldSetting('class')) {
            $students = get_students_of_class($this->getFieldSetting('class'));
            usort($students, function ($a, $b) { return strcmp($a->seat, $b->seat); });
            foreach ($students as $r) {
                $options[$r->uuid] = $r->seat.' '.$r->realname;
            }
        }
        $account = User::load(\Drupal::currentUser()->id());
        if ($account->get('init')->value == 'tpedu') {
            $user = get_user($account->get('uuid')->value);
            if ($this->getFieldSetting('filter_by_current_user') && !empty($user->class)) {
                $students = get_students_of_class($user->class);
                usort($students, function ($a, $b) { return strcmp($a->seat, $b->seat); });
                foreach ($students as $r) {
                    $options[$r->uuid] = $r->seat.' '.$r->realname;
                }
            }
        }

        return $options;
    }
}
