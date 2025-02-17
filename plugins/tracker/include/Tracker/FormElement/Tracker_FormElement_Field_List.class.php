<?php
/**
 * Copyright (c) Enalean, 2014 - 2015. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */


abstract class Tracker_FormElement_Field_List extends Tracker_FormElement_Field implements Tracker_FormElement_Field_Shareable {

    const NONE_VALUE = 100;

    protected $bind;

    /**
     * @return array
     */
    public function getFormElementDataForCreation($parent_id) {
        $form_element_data = parent::getFormElementDataForCreation($parent_id);

        if ($this->getBind()) {
            $form_element_data['bind-type'] = $this->getBind()->getType();
        }

        return $form_element_data;
    }

    /**
     * Return true if submitted value is None
     */
    abstract public function isNone($value);

    /**
     * @return Tracker_FormElement_Field_List_Bind
     */
    public function getBind() {
        if (!$this->bind) {
            $this->bind = null;
            //retrieve the type of the bind first...
            $dao = new Tracker_FormElement_Field_ListDao();
            if ($row = $dao->searchByFieldId($this->id)->getRow()) {
                //...and build the bind
                $bf = new Tracker_FormElement_Field_List_BindFactory();
                $this->bind = $bf->getBind($this, $row['bind_type']);
            }
        }
        return $this->bind;
    }

    /**
     * @return array of Tracker_FormElement_Field_List_BindDecorator
     */
    public function getDecorators() {
        return $this->getBind()->getDecorators();
    }

    public function setBind($bind) {
        $this->bind = $bind;
    }

    /**
     * Duplicate a field. If the field has custom properties,
     * they should be propagated to the new one
     * @param int $from_field_id
     * @return array the mapping between old values and new ones
     */
    public function duplicate($from_field_id) {
        $dao = new Tracker_FormElement_Field_ListDao();
        if ($dao->duplicate($from_field_id, $this->id)) {
            $bf = new Tracker_FormElement_Field_List_BindFactory();
            return $bf->duplicate($from_field_id, $this->id);
        }
        return array();
    }

    /**
     * @return boolean
     */
    public function isMultiple() {
        return false;
    }

    /**
     * Get the "from" statement to allow search with this field
     * You can join on 'c' which is a pseudo table used to retrieve
     * the last changeset of all artifacts.
     *
     * @param Tracker_ReportCriteria $criteria
     *
     * @return string
     */
    public function getCriteriaFrom($criteria) {
        //Only filter query if field is used
        if($this->isUsed()) {
            return $this->getBind()->getCriteriaFrom($this->getCriteriaValue($criteria));
        }
    }

    /**
     * Get the "where" statement to allow search with this field
     *
     * @see getCriteriaFrom
     *
     * @param Tracker_ReportCriteria $criteria
     *
     * @return string
     */
    public function getCriteriaWhere($criteria) {
        return $this->getBind()->getCriteriaWhere($this->getCriteriaValue($criteria));
    }

    /**
     * Get the "select" statement to retrieve field values
     *
     * @see getQueryFrom
     *
     * @return string
     */
    public function getQuerySelect() {
        return $this->getBind()->getQuerySelect();
    }

    /**
     * Get the "select" statement to retrieve field values with the RGB values of their decorator
     * Has no sense for fields other than lists
     * @return string
     * @see getQueryFrom
     */
    public function getQuerySelectWithDecorator() {
        return $this->getBind()->getQuerySelectWithDecorator();
    }

    /**
     * Get the "from" statement to retrieve field values
     * You can join on artifact AS a, tracker_changeset AS c
     * which tables used to retrieve the last changeset of matching artifacts.
     * @return string
     */
    public function getQueryFrom() {
        return $this->getBind()->getQueryFrom();
    }

	/**
     * Get the "from" statement to retrieve field values
     * You can join on artifact AS a, tracker_changeset AS c
     * which tables used to retrieve the last changeset of matching artifacts.
     * @return string
     */
    public function getQueryFromWithDecorator() {
        return $this->getBind()->getQueryFromWithDecorator();
    }

    /**
     * Get the "order by" statement to retrieve field values
     */
    public function getQueryOrderby() {
        return $this->getBind()->getQueryOrderby();
    }

    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby() {
        return $this->getBind()->getQueryGroupby();
    }

    /**
     * Fetch sql snippets needed to compute aggregate functions on this field.
     *
     * @param array $functions The needed function. @see getAggregateFunctions
     *
     * @return array of the form array('same_query' => string(sql snippets), 'separate' => array(sql snippets))
     *               example:
     *               array(
     *                   'same_query'       => "AVG(R2_1234.value) AS velocity_AVG, STD(R2_1234.value) AS velocity_AVG",
     *                   'separate_queries' => array(
     *                       array(
     *                           'function' => 'COUNT_GRBY',
     *                           'select'   => "R2_1234.value AS label, count(*) AS value",
     *                           'group_by' => "R2_1234.value",
     *                       ),
     *                       //...
     *                   )
     *              )
     *
     *              Same query handle all queries that can be run concurrently in one query. Example:
     *               - numeric: avg, count, min, max, std, sum
     *               - selectbox: count
     *              Separate queries handle all queries that must be run spearately on their own. Example:
     *               - numeric: count group by
     *               - selectbox: count group by
     *               - multiselectbox: all (else it breaks other computations)
     */
    public function getQuerySelectAggregate($functions) {
        return $this->getBind()->getQuerySelectAggregate($functions);
    }

    /**
     * @return array the available aggreagate functions for this field. empty array if none or irrelevant.
     */
    public function getAggregateFunctions() {
        return array('COUNT', 'COUNT_GRBY');
    }

    /**
     * Return the dao of the criteria value used with this field.
     * @return Tracker_Report_Criteria_List_ValueDao
     */
    protected function getCriteriaDao() {
        return new Tracker_Report_Criteria_List_ValueDao();
    }

    /**
     * Display the field as a Changeset value.
     * Used in report table
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $report=null, $from_aid = null) {

        //We have to fetch all values of the changeset as we are a list of value
        //This is the case only if we are multiple but an old changeset may
        //contain multiple values
        $values = array();
        foreach($this->getBind()->getChangesetValues($changeset_id) as $v) {
            $val = $this->getBind()->formatChangesetValue($v);
            if ($val != '') {
                $values[] = $val;
            }
        }
        return implode(', ', $values);
    }

    /**
     * Display the field as a Changeset value.
     * Used in CSV data export.
     *
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     *
     * @return string
     */
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value, $report) {
        $values = array();
        foreach($this->getBind()->getChangesetValues($changeset_id) as $v) {
            $values[] = $this->getBind()->formatChangesetValueForCSV($v);
        }
        return implode(',', $values);
    }

    /**
     * Search in the db the criteria value used to search against this field.
     * @param Tracker_ReportCriteria $criteria
     * @return mixed
     */
    public function getCriteriaValue($criteria) {
        if (empty($this->criteria_value) || empty($this->criteria_value[$criteria->report->id])) {
            $this->criteria_value = array();

            if (empty($this->criteria_value[$criteria->report->id])) {
                $this->criteria_value[$criteria->report->id] = array();

                if ($criteria->id > 0) {
                    foreach($this->getCriteriaDao()->searchByCriteriaId($criteria->id) as $row) {
                        $this->criteria_value[$criteria->report->id][] = $row['value'];
                    }
                }
            }

        } else if (in_array('', $this->criteria_value[$criteria->report->id])) {
            return '';
        }

        return $this->criteria_value[$criteria->report->id];
    }

    public function setCriteriaValueFromSOAP(Tracker_Report_Criteria $criteria, StdClass $soap_criteria_value) {
        $soap_criteria_values   = explode(',', $soap_criteria_value->value);
        $available_field_values = $this->getAllValues();
        $values                 = array();
        $criterias              = array();

        foreach ($available_field_values as $field_value_id => $field_value) {
            $values[$field_value->getLabel()] = $field_value_id;
        }

        foreach ($soap_criteria_values as $soap_criteria_value) {
            // Check if the SOAP string only contains digits
            if (ctype_digit($soap_criteria_value)) {
                $criterias[] = $soap_criteria_value;
            } else {
                $field_value_id = $values[$soap_criteria_value];
                if ($field_value_id) {
                    $criterias[] = $field_value_id;
                }
            }
        }
        $this->setCriteriaValue($criterias, $criteria->report->id);
    }

    /**
     * @throws Tracker_Report_InvalidRESTCriterionException
     */
    public function setCriteriaValueFromREST(Tracker_Report_Criteria $criteria, array $rest_criteria_value) {
        $searched_field_values = $rest_criteria_value[Tracker_Report_REST::VALUE_PROPERTY_NAME];
        $operator              = $rest_criteria_value[Tracker_Report_REST::OPERATOR_PROPERTY_NAME];

        if ($operator !== Tracker_Report_REST::OPERATOR_CONTAINS) {
            throw new Tracker_Report_InvalidRESTCriterionException("Unallowed operator for criterion field '$this->name' ($this->id). Allowed operators: [" . Tracker_Report_REST::OPERATOR_CONTAINS . "]");
        }

        if (is_numeric($searched_field_values)) {
            $values_to_match = array((int) $searched_field_values);
        } elseif(is_array($searched_field_values)) {
            $values_to_match = $searched_field_values;
        } else {
            throw new Tracker_Report_InvalidRESTCriterionException("Invalid format for criterion field '$this->name' ($this->id)");
        }

        $available_field_values = $this->getAllValues();
        $criterias              = array();

        foreach ($values_to_match as $value_to_match) {
            if (! is_numeric($value_to_match)) {
                throw new Tracker_Report_InvalidRESTCriterionException("Invalid format for criterion field '$this->name' ($this->id)");
            }

            if ($value_to_match == self::NONE_VALUE) {
                continue;
            }

            if (! isset($available_field_values[$value_to_match])) {
                continue;
            }

            $criterias[] = $value_to_match;
        }

        $this->setCriteriaValue($criterias, $criteria->report->id);

        return count($criterias) > 0;
    }

    /**
     * Format the criteria value submitted by the user for storage purpose (dao or session)
     *
     * @param mixed $value The criteria value submitted by the user
     *
     * @return mixed
     */
    public function getFormattedCriteriaValue($value) {
        if ( empty($value['values']) ) {
            $value['values'] = array('');
        }
        return $value['values'];
    }

    /**
     * Display the field value as a criteria
     * @param Tracker_ReportCriteria $criteria
     * @return string
     * @see fetchCriteria
     */
    public function fetchCriteriaValue($criteria) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $criteria_value = $this->getCriteriaValue($criteria);
        if ( ! is_array($criteria_value)) {
            $criteria_value = array($criteria_value);
        }

        $multiple = ' ';
        $size     = ' ';
        $prefix_name = "criteria[$this->id][values]";
        $name        = $prefix_name . '[]';

        if ($criteria->is_advanced) {
            $multiple = ' multiple="multiple" ';
            $size     = ' size="'. min(7, count($this->getBind()->getAllValues()) + 2) .'" ';
        }

        $html .= '<input type="hidden" name="'. $prefix_name .'" />';
        $html .= '<select id="tracker_report_criteria_'. ($criteria->is_advanced ? 'adv_' : '') . $this->id .'"
                          name="'. $name .'" '.
                          $size .
                          $multiple .'>';
        //Any value
        $selected = count($criteria_value) && !in_array('', $criteria_value) ? '' : 'selected="selected"';
        $html .= '<option value="" '. $selected .' title="'. $GLOBALS['Language']->getText('global','any') .'">'. $GLOBALS['Language']->getText('global','any') .'</option>';
        //None value
        $selected = in_array(Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID, $criteria_value) ? 'selected="selected"' : '';
        $html .= '<option value="'.Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID.'" '. $selected .' title="'. $GLOBALS['Language']->getText('global','none') .'">'. $GLOBALS['Language']->getText('global','none') .'</option>';
        //Field values
        foreach($this->getBind()->getAllValues() as $id => $value) {
            if (!$value->isHidden()) {
                $selected = in_array($id, $criteria_value) ? 'selected="selected"' : '';
                $style = $this->getBind()->getSelectOptionInlineStyle($id);
                $html .= '<option value="'. $id .'"  title="'. $this->getBind()->formatCriteriaValue($id) .'" '. $selected .' style="'. $style .'">';
                $html .= $this->getBind()->formatCriteriaValue($id);
                $html .= '</option>';
            }
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * Add some additionnal information beside the criteria.
     * This is up to the field. It can be html or inline javascript
     * to enhance the user experience
     * @return string
     */
    public function fetchCriteriaAdditionnalInfo() {
        return ''; //$this->getBind()->fetchDecoratorsAsJavascript();
    }
    /**
     * Add some additionnal information beside the field in the artifact form.
     * This is up to the field. It can be html or inline javascript
     * to enhance the user experience
     * @return string
     */
    public function fetchArtifactAdditionnalInfo($value, $submitted_values = null) {
        return ''; //$this->getBind()->fetchDecoratorsAsJavascript();
    }

     /**
     * Add some additionnal information beside the field in the submit new artifact form.
     * This is up to the field. It can be html or inline javascript
     * to enhance the user experience
     * @return string
     */
    public function fetchSubmitAdditionnalInfo($submitted_values) {
        return '';
    }

    /**
     * @return bool
     */
    protected function criteriaCanBeAdvanced() {
        return true;
    }

    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchRawValue($value) {
        return $this->getBind()->fetchRawValue($value);
    }

    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset) {
        return $this->getBind()->fetchRawValueFromChangeset($changeset);
    }

    /**
     * @return Tracker_FormElement_Field_Value_ListDao
     */
    protected function getValueDao() {
        return new Tracker_FormElement_Field_Value_ListDao();
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     *
     * @return string html
     */
    protected function fetchSubmitValue($submitted_values = array()) {
        $selected_values = isset($submitted_values[$this->id]) ? $submitted_values[$this->id] : array();
        $default_values  = $this->getSubmitDefaultValues();

        return $this->_fetchField(
            'tracker_field_'. $this->id,
            'artifact['. $this->id .']',
            $default_values,
            $selected_values
        );
    }

    private function getSubmitDefaultValues() {
        if ($this->fieldHasEnableWorkflow()) {
            return array();
        }

        return $this->getBind()->getDefaultValues();
    }

     /**
     * Fetch the html code to display the field value in masschange submission form
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange() {
        return $this->_fetchFieldMasschange('tracker_field_'. $this->id, 'artifact['. $this->id .']', $this->getBind()->getDefaultValues());
    }
    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param array                           $submitted_values The value already submitted by the user
     *
     * @return string
     */
    protected function fetchArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $submitted_values = array()) {
        $values = array();
        if (! empty($submitted_values) && isset($submitted_values[0][$this->id])) {
            $values = $submitted_values[0][$this->id];
        }
        $selected_values  = $value ? $value->getListValues() : array();
        return $this->_fetchField('tracker_field_'. $this->id,
                'artifact['. $this->id .']',
                $selected_values, $values);
    }

     /**
     * Fetch the field value in artifact to be displayed in mail
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param PFUser                          $user             The user who will receive the email
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param string                          $format           mail format
     *
     * @return string
     */
    public function fetchMailArtifactValue(Tracker_Artifact $artifact, PFUser $user, Tracker_Artifact_ChangesetValue $value = null, $format='text') {
        $output = '';
        switch($format) {
            case 'html':
                if ( empty($value) ||  !$value->getListValues()) {
                    return '-';
                }
                $output = $this->fetchArtifactValueReadOnly($artifact, $value);
                break;
            default:
                $tablo = array();
                $selected_values = !empty($value) ? $value->getListValues() : array();
                foreach ($selected_values as $value) {
                    $tablo[] = $this->getBind()->formatMailArtifactValue($value->getId());
                }
                $output = implode(', ', $tablo);
                break;
        }
        return $output;
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Tracker_Artifact                $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        $html = '';
        $selected_values = $value ? $value->getListValues() : array();
        $tablo = array();

        if (empty($selected_values)) {
            return $this->getNoValueLabel();
        }

        foreach ($selected_values as $id => $selected) {
            $tablo[] = $this->getBind()->formatArtifactValue($id);
        }
        $html .= implode(', ', $tablo);
        return $html;
    }

    public function fetchArtifactValueWithEditionFormIfEditable(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $submitted_values = array()) {
        return $this->fetchArtifactValueReadOnly($artifact, $value) . $this->getHiddenArtifactValueForEdition($artifact, $value, $submitted_values);
    }

    /**
     * Indicate if a workflow is defined and enabled on a field_id.
     * @param $id the field_id
     * @return boolean, true if a workflow is defined and enabled on the field_id
     */
    public function fieldHasEnableWorkflow(){
        $workflow = $this->getWorkflow();
        if(!empty($workflow) && $workflow->is_used){
            return $workflow->field_id===$this->id;
        }
        return false;
    }

     /**
     * Indicate if a workflow is defined on a field_id.
     * @param $id the field_id
     * @return boolean, true if a workflow is defined on the field_id
     */
    public function fieldHasDefineWorkflow(){
        $workflow = $this->getWorkflow();
        if(!empty($workflow)){
            return $workflow->field_id===$this->id;
        }
        return false;
    }

    /**
     * Get the workflow of the tracker.
     * @return Workflow Object
     */
    public function getWorkflow(){
        return WorkflowFactory::instance()->getWorkflowByTrackerId($this->tracker_id);
    }

    /**
     * Validate a value
     * @param Tracker_Artifact $artifact
     * @param mixed $value data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Tracker_Artifact $artifact, $value) {
        $valid          = true;
        $field_value_to = null;

        if ($this->fieldHasEnableWorkflow()) {

            $last_changeset = $artifact->getLastChangeset();

            try {
                $field_value_to = $this->getBind()->getValue($value);
                if (!$last_changeset) {
                    if (!$this->isTransitionValid(null, $field_value_to)) {
                           $this->has_errors = true;
                           $valid = false;
                    }
                } else {
                if ($last_changeset->getValue($this)!=null) {
                    foreach ($last_changeset->getValue($this)->getListValues() as $id => $value) {
                        if ($value != $field_value_to) {
                            if (!$this->isTransitionValid($value, $field_value_to)) {
                                $this->has_errors = true;
                                $valid = false;
                            }
                        }
                    }
                } else {
                    if (!$this->isTransitionValid(null, $field_value_to)) {
                        $this->has_errors = true;
                        $valid = false;
                    }
                }
            }
            } catch (Tracker_FormElement_InvalidFieldValueException $exexption) {
                $valid = false;
            }

            if ($valid) {
                //Check permissions on transition
                if (!$last_changeset || $last_changeset->getValue($this) == null) {
                    $from = null;
                    $to = $value;
                } else {
                    list(, $from) = each ($last_changeset->getValue($this)->getListValues());
                    if (!is_string($value)) {
                        $to = $value->getId();
                    }else {
                        $to = $value;
                    }
                }
                $transition_id = $this->getTransitionId($from, $to);
                if (!$this->userCanMakeTransition($transition_id)) {
                        $valid = false;
                 }
            }
        }

        if ($valid) {
            return true;
        } else {
            if ($field_value_to !== null) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'transition_not_valid', array($field_value_to->getLabel())));
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'transition_to_none'));
            }
            return false;
        }
    }


    protected function isTransitionValid($field_value_from, $field_value_to){
        if (!$this->fieldHasEnableWorkflow()) {
            return true;
        }else {
            $workflow = $this->getWorkflow();
            if ($workflow->isTransitionExist($field_value_from, $field_value_to)) {
                return true;
            }else return false;
        }
    }

    protected function getSelectedValue($selected_values) {
        if ($this->getBind()) {
            foreach($this->getBind()->getAllValues() as $id => $value) {
                    if(isset($selected_values[$id])) {
                        $from = $value;
                        return $from;
                    }
            }
            return null;
        }
    }

    /**
     * @return array of BindValues
     */
    public function getAllValues() {
        return $this->getBind()->getAllValues();
    }

    /**
     * @return array of BindValues that are not hidden + none value if any
     */
    public function getVisibleValuesPlusNoneIfAny() {
        $values = $this->getAllValues();
        foreach ($values as $key => $value) {
            if ($value->isHidden()) {
                unset($values[$key]);
            }
        }
        if ($values) {
            if (! $this->isRequired()) {
                $none = new Tracker_FormElement_Field_List_Bind_StaticValue_None();
                $values = array_merge(array($none), $values);
            }
        }
        return $values;
    }

    /**
     * @return Tracker_FormElement_Field_List_Value or null if not found
     */
    public function getListValueById($value_id) {
        foreach ($this->getVisibleValuesPlusNoneIfAny() as $value) {
            if ($value->getId() == $value_id) {
                return $value;
            }
        }
    }

    /**
     *
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    public function getFirstValueFor(Tracker_Artifact_Changeset $changeset) {
        if ($this->userCanRead()) {
            $value = $changeset->getValue($this);
            if ($value && ($last_values = $value->getListValues())) {
                // let's assume there is no more that one status
                if ($label = array_shift($last_values)->getLabel()) {
                    return $label;
                }
            }
        }
    }

    protected function _fetchField($id, $name, $selected_values, $submitted_values = array()) {
        $html     = '';
        $purifier = Codendi_HTMLPurifier::instance();

        if ($name) {
            if ($this->isMultiple()) {
                $name .= '[]';
            }
            $name = 'name="'. $purifier->purify($name) .'"';
        }

        if ($id) {
            $id = 'id="'. $id .'"';
        }

        $html .= $this->fetchFieldContainerStart($id, $name);

        $from = $this->getSelectedValue($selected_values);
        if ($from == null && !isset($submitted_values)) {
            $none_is_selected = isset($selected_values[Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID]);
        } else {
            $none_is_selected = ($submitted_values==Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID);
        }

        if (!$this->fieldHasEnableWorkflow()) {
            $none_value = new Tracker_FormElement_Field_List_Bind_StaticValue_None();
            $html .= $this->fetchFieldValue($none_value, $name, $none_is_selected);
        }

        if (($submitted_values) && !is_array($submitted_values)) {
            $submitted_values_array[] = $submitted_values;
            $submitted_values = $submitted_values_array;
        }

        foreach($this->getBind()->getAllValues() as $id => $value) {
            $transition_id = null;
            if ($this->isTransitionValid($from, $value)) {
                $transition_id = $this->getTransitionId($from, $value->getId());
                if (!empty($submitted_values)) {
                    $is_selected = in_array($id, array_values($submitted_values));
                } else {
                    $is_selected = isset($selected_values[$id]);
                }
                if ($this->userCanMakeTransition($transition_id)) {
                    if (!$value->isHidden()) {
                        $html .= $this->fetchFieldValue($value, $name, $is_selected);
                    }
                }
            }
        }

        $html .= $this->fetchFieldContainerEnd();
        return $html;
    }

    protected function fetchFieldContainerStart($id, $name) {
        $html     = '';
        $multiple = '';
        $size     = '';
        if ($this->isMultiple()) {
            $multiple = 'multiple="multiple"';
            $size     = 'size="'. min($this->getMaxSize(), count($this->getBind()->getAllValues()) + 2) .'"';
        }
        $html .= "<select $id $name $multiple $size>";
        return $html;
    }

    protected function fetchFieldValue(Tracker_FormElement_Field_List_Value $value, $name, $is_selected) {
        $id = $value->getId();
        if ($id == Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID) {
            $label = $value->getLabel();
        } else {
            $label = $this->getBind()->formatArtifactValue($id);
        }
        $style    = $this->getBind()->getSelectOptionInlineStyle($id);
        $selected = $is_selected ? 'selected="selected"' : '';
        return '<option value="'. $id .'" '. $selected .' title="'. $label .'" style="'. $style .'">'. $label .'</option>';
    }

    protected function fetchFieldContainerEnd() {
        return '</select>';
    }


    protected function _fetchFieldMasschange($id, $name, $selected_values) {
        $html = '';
        $multiple = ' ';
        $size     = ' ';
        if ($this->isMultiple()) {
            $multiple = ' multiple="multiple" ';
            $size     = ' size="'. min($this->getMaxSize(), count($this->getBind()->getAllValues()) + 2) .'" ';
            if ($name) {
                $name .= '[]';
            }
        }
        $html .= '<select ';
        if ($id) {
            $html .= 'id="'. $id .'" ';
        }
        if ($name) {
            $html .= 'name="'. $name .'" ';
        }
        $html .= $size . $multiple .'>';

        //if ( $this->fieldHasEnableWorkflow() ) {
        $html .= '<option value="'.$GLOBALS['Language']->getText('global','unchanged').'" selected="selected">'. $GLOBALS['Language']->getText('global','unchanged') .'</option>';
        $html .= '<option value="'.Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID.'">'. $GLOBALS['Language']->getText('global','none') .'</option>';
        //}

        foreach($this->getBind()->getAllValues() as $id => $value) {
                    if (!$value->isHidden()) {
                        $style = $this->getBind()->getSelectOptionInlineStyle($id);
                        $html .= '<option value="'. $id .'" title="'. $this->getBind()->formatArtifactValue($id) .'" style="'. $style .'">';
                        $html .= $this->getBind()->formatArtifactValue($id);
                        $html .= '</option>';
                    }
        }

        $html .= '</select>';
        return $html;
    }


    protected function getMaxSize() {
        return 7;
    }

    /**
     * Fetch the changes that has been made to this field in a followup
     * @param Tracker_ $artifact
     * @param array $from the value(s) *before*
     * @param array $to   the value(s) *after*
     */
    public function fetchFollowUp($artifact, $from, $to) {
        $html = '';
        $values = array();
        if ($from && isset($from['changeset_id'])) {
            foreach($this->getBind()->getChangesetValues($from['changeset_id']) as $v) {
                if ($v['id'] != Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID) {
                    $values[] = $this->getBind()->formatChangesetValue($v);
                }
            }
            $from_value = implode(', ', $values);
        }

        if (!$from_value) {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_artifact','set_to').' ';
        } else {
            $html .= ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','changed_from').' '. $from_value .'  '.$GLOBALS['Language']->getText('plugin_tracker_artifact','to').' ';
        }

        $values = array();
        foreach($this->getBind()->getfChangesetValues($to['changeset_id']) as $v) {
            $values[] = $this->getBind()->formatChangesetValue($v);
        }
        $html .= implode(', ', $values);
        return $html;
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement() {
        $html = '';
        $html .= $this->_fetchField('', '', $this->getBind()->getDefaultValues());
        return $html;
    }

    /**
     * Fetch the html code to display the field value in tooltip
     * @param Tracker_Artifact $artifact
     * @param Tracker_Artifact_ChangesetValue_List $value The changeset value of this field
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        $html = '';
        if ($value) {
            $html .= $this->fetchChangesetValue($artifact->id, $artifact->getLastChangeset()->id, $value);
        }
        return $html;
    }

    /**
     * @see Tracker_FormElement_Field::fetchCardValue()
     */
    public function fetchCardValue(Tracker_Artifact $artifact, Tracker_CardDisplayPreferences $display_preferences) {
        $html = '';
        //We have to fetch all values of the changeset as we are a list of value
        //This is the case only if we are multiple but an old changeset may
        //contain multiple values
        $values = array();
        foreach($this->getBind()->getChangesetValues($artifact->getLastChangeset()->id) as $v) {
            $val = $this->getBind()->formatCardValue($v, $display_preferences);
            if ($val != '') {
                $values[] = $val;
            }
        }
        $html .= implode(' ', $values);
        return $html;
    }

    /**
     * Update the form element.
     * Override the parent function to handle binds
     *
     * @return void
     */
    protected function processUpdate(TrackerManager $tracker_manager, $request, $current_user) {
        $redirect = false;
        if ($request->exist('bind')) {
            $redirect = $this->getBind()->process($request->get('bind'), $no_redirect = true);
        }
        parent::processUpdate($tracker_manager, $request, $current_user, $redirect);
    }

    /**
     * Hook called after a creation of a field
     *
     * @param array $data The data used to create the field
     *
     * @return void
     */
    public function afterCreate($formElement_data) {
        parent::afterCreate();
        $type      = isset($formElement_data['bind-type']) ? $formElement_data['bind-type'] : '';
        $bind_data = isset($formElement_data['bind'])      ? $formElement_data['bind']      : array();

        $bf = new Tracker_FormElement_Field_List_BindFactory();
        if ($this->bind = $bf->createBind($this, $type, $bind_data)) {
            $dao = new Tracker_FormElement_Field_ListDao();
            $dao->save($this->getId(), $bf->getType($this->bind));
        }
    }

    /**
     * Transforms FormElement_List into a SimpleXMLElement
     *
     * @param SimpleXMLElement $root        The node to which the FormElement is attached (passed by reference)
     * @param array            &$xmlMapping The correpondance between real ids and xml IDs
     */
    public function exportToXml(SimpleXMLElement $root, &$xmlMapping) {
        parent::exportToXML($root, $xmlMapping);
        if ($this->getBind() && $this->shouldBeBindXML()) {
            $child = $root->addChild('bind');
            $bf = new Tracker_FormElement_Field_List_BindFactory();
            $child->addAttribute('type', $bf->getType($this->getBind()));
            $this->getBind()->exportToXML($child, $xmlMapping);
        }
    }

    /**
     * Say if we export the bind in the XML
     *
     * @return bool
     */
    public function shouldBeBindXML() {
        return true;
    }

    /**
     * Continue the initialisation from an xml (FormElementFactory is not smart enough to do all stuff.
     * Polymorphism rulez!!!
     *
     * @param SimpleXMLElement $xml         containing the structure of the imported Tracker_FormElement
     * @param array            &$xmlMapping where the newly created formElements indexed by their XML IDs are stored (and values)
     *
     * @return void
     */
    public function continueGetInstanceFromXML($xml, &$xmlMapping) {
        parent::continueGetInstanceFromXML($xml, $xmlMapping);
        // if field is a list add bind
        if ($xml->bind) {
            $bind = $this->getBindFactory()->getInstanceFromXML($xml->bind, $this, $xmlMapping);
            $this->setBind($bind);
        }
    }

    /**
     * Callback called after factory::saveObject. Use this to do post-save actions
     *
     * @param Tracker $tracker The tracker
     *
     * @return void
     */
    public function afterSaveObject(Tracker $tracker) {
        $bind = $this->getBind();
        $this->getListDao()->save($this->getId(), $this->getBindFactory()->getType($bind));
        $bind->saveObject();
    }

    /**
     * Get an instance of Tracker_FormElement_Field_ListDao
     *
     * @return Tracker_FormElement_Field_ListDao
     */
    public function getListDao() {
        return new Tracker_FormElement_Field_ListDao();
    }

    /**
     * Get an instance of Tracker_FormElement_Field_List_BindFactory
     *
     * @return Tracker_FormElement_Field_List_BindFactory
     */
    function getBindFactory() {
        return new Tracker_FormElement_Field_List_BindFactory();
    }

    /**
     * Save the value and return the id
     *
     * @param Tracker_Artifact                $artifact                The artifact
     * @param int                             $changeset_value_id      The id of the changeset_value
     * @param mixed                           $value                   The value submitted by the user
     * @param Tracker_Artifact_ChangesetValue $previous_changesetvalue The data previously stored in the db
     *
     * @return boolean
     */
    protected function saveValue($artifact, $changeset_value_id, $value, Tracker_Artifact_ChangesetValue $previous_changesetvalue = null) {
        return $this->getValueDao()->create($changeset_value_id, $value);
    }

    /**
     * Get the value of this field
     *
     * @param Tracker_Artifact_Changeset $changeset   The changeset (needed in only few cases like 'lud' field)
     * @param int                        $value_id    The id of the value
     * @param boolean                    $has_changed If the changeset value has changed from the rpevious one
     *
     * @return Tracker_Artifact_ChangesetValue or null if not found
     */
    public function getChangesetValue($changeset, $value_id, $has_changed) {
        $changeset_value = null;
        $value_ids = $this->getValueDao()->searchById($value_id, $this->id);
        $bindvalue_ids = array();
        foreach($value_ids as $v) {
            $bindvalue_ids[] = $v['bindvalue_id'];
        }
        $bind_values = array();
        if (count($bindvalue_ids)) {
            $bind_values = $this->getBind()->getBindValues($bindvalue_ids);
        }
        $changeset_value = new Tracker_Artifact_ChangesetValue_List($value_id, $this, $has_changed, $bind_values);
        return $changeset_value;
    }

    /**
     * Get available values of this field for SOAP usage
     * Fields like int, float, date, string don't have available values
     *
     * @return mixed The values or null if there are no specific available values
     */
     public function getSoapAvailableValues() {
         $values = null;
         $bind = $this->getBind();
         if ($bind != null) {
             $values = $bind->getSoapAvailableValues();
         }
         return $values;
     }

     public function getSoapBindingProperties() {
         $bind = $this->getBind();
         return $bind->getSoapBindingProperties();
     }

     public function getFieldDataFromRESTValue(array $value, Tracker_Artifact $artifact = null) {
         if (array_key_exists('bind_value_ids', $value) && is_array($value['bind_value_ids'])) {
             return array_map('intval', $value['bind_value_ids']);
         }
         throw new Tracker_FormElement_InvalidFieldValueException('List fields values must be passed as an array of ids (integer) in \'bind_value_ids\''
            . ' Example: {"field_id": 1548, "bind_value_ids": [457]}');
     }

    public function getFieldDataFromRESTValueByField($value, Tracker_Artifact $artifact = null) {
        throw new Tracker_FormElement_RESTValueByField_NotImplementedException();
    }

     public function getFieldDataFromSoapValue(stdClass $soap_value, Tracker_Artifact $artifact = null) {
         if (isset($soap_value->field_value->bind_value)) {
             if ($this->isMultiple()) {
                 $values = array();
                 foreach ($soap_value->field_value->bind_value as $bind_value) {
                    $values[] = $bind_value->bind_value_id;
                 }
                 return $values;
             } else {
                 return $soap_value->field_value->bind_value[0]->bind_value_id;
             }
         } else {
             return $this->getFieldData($soap_value->field_value->value);
         }
     }

    /**
     * Get the field data for artifact submission
     *
     * @param string the soap field value
     *
     * @return mixed the field data corresponding to the soap_value for artifact submision
     */
    public function getFieldData($soap_value) {
        if ($soap_value === $GLOBALS['Language']->getText('global','none')) {
            return Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID;
        }

        $bind = $this->getBind();
        if ($bind != null) {
            $soap_value = $bind->getFieldData($soap_value, $this->isMultiple());
            if ($soap_value != null) {
                return $soap_value;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

     /**
     * Check if there are changes between old and new value for this field
     *
     * @param Tracker_Artifact_ChangesetValue $previous_changesetvalue The data stored in the db
     * @param mixed                           $new_value               May be string or array
     *
     * @return bool true if there are differences
     */
    public function hasChanges($previous_changesetvalue, $new_value) {
        if (!is_array($new_value)) {
            $new_value = array($new_value);
        }
        if (empty($new_value)) {
            $new_value = array(Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID);
        }
        if ($previous_changesetvalue) {
            $old_value = $previous_changesetvalue->getValue();
        }
        if (empty($old_value)) {
            $old_value = array(Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID);
        }
        sort($old_value);
        sort($new_value);
        return $old_value != $new_value;
    }

    /**
     * Say if this fields suport notifications
     *
     * @return bool
     */
    public function isNotificationsSupported() {
        if ($b = $this->getBind()) {
            return $b->isNotificationsSupported();
        }
        return false;
    }

    protected function permission_is_authorized($type, $transition_id, $user_id, $group_id) {
        include_once 'www/project/admin/permissions.php';

        return permission_is_authorized($type, $transition_id, $user_id, $group_id);
    }

    /**
     * Check if the user can make the transition
     *
     * @param int  $transition_id The id of the transition
     * @param PFUser $user          The user. If null, take the current user
     *
     *@return boolean true if user has permission on this field
     */
    public function userCanMakeTransition($transition_id, PFUser $user = null) {
        if ($transition_id) {
            $group_id = $this->getTracker()->getGroupId();

            if (!$user) {
                $user = $this->getCurrentUser();
            }
            return $this->permission_is_authorized('PLUGIN_TRACKER_WORKFLOW_TRANSITION', $transition_id, $user->getId(), $group_id);
        }
        return true;
    }

    /**
     * Get a recipients list for notifications. This is filled by users fields for example.
     *
     * @param Tracker_Artifact_ChangesetValue $changeset_value The changeset
     *
     * @return array
     */
    public function getRecipients(Tracker_Artifact_ChangesetValue $changeset_value) {
        return $this->getBind()->getRecipients($changeset_value);
    }

    protected function getTransitionId($from, $to) {
        return TransitionFactory::instance()->getTransitionId($from, $to);
    }

    public function getDefaultValue() {
        $default_array = $this->getBind()->getDefaultValues();
        if (! $default_array) {
            return array(Tracker_FormElement_Field_List_Bind::NONE_VALUE);
        }
        return array_keys($default_array);
    }

    /**
     * Say if the value is valid. If not valid set the internal has_error to true.
     *
     * @param Tracker_Artifact $artifact The artifact
     * @param mixed            $value    data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    public function isValid(Tracker_Artifact $artifact, $value) {
        $this->has_errors = !$this->validate($artifact, $value);

        return !$this->has_errors;
    }

    /**
     * Validate a required field
     *
     * @param Tracker_Artifact                $artifact             The artifact to check
     * @param mixed                           $value      The submitted value
     *
     * @return boolean true on success or false on failure
     */
    public function isValidRegardingRequiredProperty(Tracker_Artifact $artifact, $value) {
        $this->has_errors = false;

        if ($this->isEmpty($value, $artifact) && $this->isRequired()) {
            $this->addRequiredError();
        }

        return ! $this->has_errors;
    }

    public function isEmpty($value, Tracker_Artifact $artifact) {
        return $this->isNone($value);
    }

    /**
     * @see Tracker_FormElement_Field_Shareable
     */
    public function fixOriginalValueIds(array $value_mapping) {
        $this->getBind()->fixOriginalValueIds($value_mapping);
    }

    /**
     * @see Tracker_FormElement::process()
     */
    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        parent::process($layout, $request, $current_user);
        if ($request->get('func') == 'get-values') {
            $GLOBALS['Response']->sendJSON($this->getBind()->fetchFormattedForJson());
        }
    }

    public function fetchFormattedForJson() {
        $json = parent::fetchFormattedForJson();
        $json['values'] = $this->getBind()->fetchFormattedForJson();
        return $json;
    }

    public function getRESTAvailableValues() {
        $values = null;
        $bind = $this->getBind();
        if ($bind != null) {
            $values = $bind->getRESTAvailableValues();
        }
        return $values;
    }
}
?>
