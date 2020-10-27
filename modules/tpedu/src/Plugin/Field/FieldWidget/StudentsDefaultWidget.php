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
    protected function getOptions(FormStateInterface $form_state)
    {
        $options = [];
        $students = [];
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
                if (empty($current)) {
                    $current = '101';
                }
            }
            $students = get_students_of_class($current_class);
        }
        if ($this->getFieldSetting('filter_by_current_user')) {
            $account = User::load(\Drupal::currentUser()->id());
            if ($account->get('init')->value == 'tpedu') {
                $user = get_user($account->get('uuid')->value);
                if (!empty($user->class)) {
                    $students = get_students_of_class($user->class);
                }
            }
        }
        if (!empty($students)) {
            usort($students, function ($a, $b) { return intval($a->seat) < intval($b->seat) ? -1 : 1; });
            foreach ($students as $r) {
                $options[$r->uuid] = $r->seat.' '.$r->realname;
            }
        }

        return $options;
    }
}
