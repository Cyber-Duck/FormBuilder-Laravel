<?php

namespace Nomensa\FormBuilder;

use App\User;
use App\EntryFormInstance;
use App\EntryFormSubmission;
use Illuminate\Support\MessageBag;

class FormBuilder
{
    use MarkerUpper;

    /** @var array of Instances of FormBuilder\Component */
    public $components = [];

    /** @var Group of rules for how fields are displayed */
    public $ruleGroups;

    /** @var App\EntryFormInstance  */
    public $formInstance;

    /**  A key in the 'access' array in the schema that describes how a field is rendered */
    public $state_id;

    /** @var array - Any additional variables that need to be made available */
    public $viewData;

    /** @var User  */
    public $owner;

    /** @var Any class that implements CSSClassProvider  */
    public $cssClassProvider;

    public $entryFormSubmission;

    /** @var MessageBag */
    public $errors;

    /** whether we render this form */
    public $render;


    public function __construct(array $form_schema, $options, EntryFormInstance $formInstance, $state_id, array $viewData, User $owner, MessageBag $errors, EntryFormSubmission $entryFormSubmission = null)
    {

        $this->components = $form_schema;

        foreach ($this->components as &$component) {

            $component = new Component($component);
        }

        $this->ruleGroups = (array)$options->rules;

        $this->formInstance = $formInstance;

        $this->state_id = $state_id;

        $this->viewData = $viewData;

        $this->owner = $owner;

        $this->entryFormSubmission = $entryFormSubmission;

        $this->errors = $errors;
    }

    /**
     * @return string HTML markup
     */
    public function markup()
    {
        $html = '';
        foreach ($this->components as $component) {
            $html .= $component->markup($this, $this->state_id);
        }
        return $html;
    }


    /**
     * TODO Unit test this
     *
     * @param $fieldName
     * @param string $needle Rule keyword to look for eg 'nullable'
     *
     * @return boolean
     */
    public function ruleExists($fieldName, $needle)
    {
        $rule = $this->getRule($fieldName);

        return strpos($rule, $needle) !== false;
    }


    private function getRuleGroupKey()
    {
        $ruleGroupKey = 'default';

        switch ($this->formInstance->entryForm->code) {

            case 'RCOA_005':

                $ruleGroupKey = ($this->state_id == 2) ? 'signoff' : 'default';

                break;

            default:

                $ruleGroupKey = ($this->state_id == 2 && $this->formInstance->workflow->name == 'assessor-approval') ? 'signoff' : $ruleGroupKey;

                $ruleGroupKey = ($this->state_id == 1 && $this->formInstance->workflow->name == 'learner-approval') ? 'signoff-learner-approval' : $ruleGroupKey;

                break;
        }

        return $ruleGroupKey;
    }

    /**
     * TODO Unit Test for this
     *
     * @param string $fieldName
     *
     * @return string A HTML Form style validation string
     */
    public function getRule($fieldName)
    {
        $ruleGroupKey = $this->getRuleGroupKey();

        $ruleGroup = $this->getRuleGroup($ruleGroupKey);
        if (isSet($ruleGroup[$fieldName])) {
            return $ruleGroup[$fieldName];
        }
    }


    /**
     * TODO Unit Test for this
     *
     * @param string $key
     *
     * @return array
     */
    public function getRuleGroup($key)
    {
        if (isSet($this->ruleGroups[$key])) {
            return (array)$this->ruleGroups[$key];
        }
        return [];
    }


    /**
     * Indicates if a submission of the form exists in the database
     * @return boolean
     */
    public function hasSubmission()
    {
        return empty($this->entryFormSubmission) == false;
    }



    /**
     *
     * @param string $key
     */
    public function getFieldValue($row_id, $field_type_name)
    {

        if (!$this->hasSubmission()) {
            return null;
        }
        $submissionRows = $this->entryFormSubmission->entryFormSubmissionRows;

        $row = $submissionRows->where('rid', $row_id)->where('field_type_name', $field_type_name)->first();
        if (empty($row)) {
            return null;
        }
        return $row->value;
    }


    public function getErrorAnchor($fieldName)
    {
        if (!empty($this->errors->get($fieldName))) {
            return MarkerUpper::wrapInTag('', 'a', ['name'=> MarkerUpper::makeErrorAnchorName($fieldName), 'class'=>'error-anchor' ]);
        }
        return '';
    }

    public function getInlineFieldError($fieldName)
    {
        return MarkerUpper::inlineFieldError($this->errors, $fieldName);
    }

}
