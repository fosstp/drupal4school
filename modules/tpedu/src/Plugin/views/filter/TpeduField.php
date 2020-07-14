<?php

namespace Drupal\tpedu\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;

/**
 * Filter handler which uses tpedu-fields as options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("tpedu_field")
 */
class TpeduField extends ManyToOne
{
    use FieldAPIHandlerTrait;

    /**
     * {@inheritdoc}
     */
    public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = null)
    {
        parent::init($view, $display, $options);
        $field = $this->getFieldStorageDefinition();
        if ($field->getType() == 'tpedu_grade') {
            $this->valueOptions = $this->getGradeOptions();
        }
        if ($field->getType() == 'tpedu_units') {
            $this->valueOptions = $this->getUnitsOptions();
        }
        if ($field->getType() == 'tpedu_roles') {
            $this->valueOptions = $this->getRolesOptions();
        }
        if ($field->getType() == 'tpedu_domain') {
            $this->valueOptions = $this->getDomainOptions();
        }
        if ($field->getType() == 'tpedu_subjects') {
            $this->valueOptions = $this->getSubjectsOptions();
        }
        if ($field->getType() == 'tpedu_classes') {
            $this->valueOptions = $this->getClassesOptions();
        }
    }

    protected function getGradeOptions()
    {
        $options = array();
        $grades = all_grade();
        usort($grades, function ($a, $b) { return strcmp($a->grade, $b->grade); });
        foreach ($grades as $g) {
            $options[$g->grade] = $g->grade.'年級';
        }

        return $options;
    }

    protected function getUnitsOptions()
    {
        $options = array();
        $units = all_units();
        usort($units, function ($a, $b) { return strcmp($a->id, $b->id); });
        foreach ($units as $o) {
            $options[$o->id] = $o->name;
        }

        return $options;
    }

    protected function getRolesOptions()
    {
        $options = array();
        $roles = all_roles();
        usort($roles, function ($a, $b) { return strcmp($a->id, $b->id); });
        foreach ($roles as $r) {
            $options[$r->id] = $r->name;
        }

        return $options;
    }

    protected function getDomainOptions()
    {
        $options = array();
        $domains = all_domains();
        usort($domains, function ($a, $b) { return strcmp($a->domain, $b->domain); });
        foreach ($domains as $g) {
            $options[$g->domain] = $g->domain.'領域';
        }

        return $options;
    }

    protected function getSubjectsOptions()
    {
        $options = array();
        $subjects = all_subjects();
        usort($subjects, function ($a, $b) { return strcmp($a->id, $b->id); });
        foreach ($subjects as $r) {
            $options[$r->id] = $r->name;
        }

        return $options;
    }

    protected function getClassesOptions()
    {
        $options = array();
        $classes = all_classes();
        usort($classes, function ($a, $b) { return strcmp($a->id, $b->id); });
        foreach ($classes as $c) {
            $options[$c->id] = $c->name;
        }

        return $options;
    }
}
