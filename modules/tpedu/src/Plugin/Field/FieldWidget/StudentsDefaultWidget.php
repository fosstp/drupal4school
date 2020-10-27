<?php

namespace Drupal\tpedu\Plugin\Field\FieldWidget;

use Drupal\user\Entity\User;

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
        $options = [];
        $students = [];
        $class = $this->getFieldSetting('class');
        if (empty($class)) {
            $class = '101';
        }
        if ($this->getFieldSetting('filter_by_class') && !empty($class)) {
            $students = get_students_of_class($class);
            usort($students, function ($a, $b) { return ($a->seat - $b->seat) ? -1 : 1; });
            foreach ($students as $r) {
                $options[$r->uuid] = $r->seat.' '.$r->realname;
            }
        }
        if ($this->getFieldSetting('filter_by_current_user')) {
            $account = User::load(\Drupal::currentUser()->id());
            if ($account->get('init')->value == 'tpedu') {
                $user = get_user($account->get('uuid')->value);
                if (!empty($user->class)) {
                    $students = get_students_of_class($user->class);
                    usort($students, function ($a, $b) { return ($a->seat - $b->seat) ? -1 : 1; });
                    foreach ($students as $r) {
                        $options[$r->uuid] = $r->seat.' '.$r->realname;
                    }
                }
            }
        }

        return $options;
    }
}
