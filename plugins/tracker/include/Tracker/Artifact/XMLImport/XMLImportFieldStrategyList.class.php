<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Tracker_Artifact_XMLImport_XMLImportFieldStrategyList extends Tracker_Artifact_XMLImport_XMLImportFieldStrategyAlphanumeric {

    const BIND_STATIC  = 'static';
    const BIND_UGROUPS = 'ugroups';
    const FORMAT_ID    = 'id';

    /** @var Tracker_FormElement_Field_List_Bind_Static_ValueDao */
    private $static_value_dao;

    /** @var Tracker_Artifact_XMLImport_XMLImportHelper */
    private $xml_import_helper;

    /** @var TrackerXmlFieldsMapping */
    private $xml_fields_mapping;

    public function __construct(
        Tracker_FormElement_Field_List_Bind_Static_ValueDao $static_value_dao,
        Tracker_Artifact_XMLImport_XMLImportHelper $xml_import_helper,
        TrackerXmlFieldsMapping $xml_fields_mapping
    ) {
        $this->static_value_dao   = $static_value_dao;
        $this->xml_import_helper  = $xml_import_helper;
        $this->xml_fields_mapping = $xml_fields_mapping;
    }

    /**
     * Extract Field data from XML input
     *
     * @param Tracker_FormElement_Field $field
     * @param SimpleXMLElement $field_change
     *
     * @return array
     */
    public function getFieldData(Tracker_FormElement_Field $field, SimpleXMLElement $field_change) {
        $bind = (string) $field_change['bind'];
        $data = array();

        if ($bind === self::BIND_STATIC) {
            foreach ($field_change as $value) {
                $data[] = $this->getStaticListDataValue($field, $value);
            }
        } elseif ($bind === self::BIND_UGROUPS) {
            foreach ($field_change as $value) {
                $data[] = $this->getUgroupListDataValue($value);
            }
        } else {
            foreach ($field_change as $value) {
                $user   = $this->xml_import_helper->getUser($value);
                $data[] = $user->getId();
            }
        }

        return $data;
    }

    private function getStaticListDataValue(Tracker_FormElement_Field $field, $value) {
        if (isset($value['format']) && (string) $value['format'] === self::FORMAT_ID){
            return $this->xml_fields_mapping->getNewValueId((int) $value);
        }

        $result = $this->static_value_dao->searchValueByLabel($field->getId(), (string) $value);
        $row    = $result->getRow();
        return (int) $row['id'];

    }

    private function getUgroupListDataValue($value) {
        if (isset($value['format']) && (string) $value['format'] === self::FORMAT_ID){
            return $this->xml_fields_mapping->getNewValueId((int) $value);
        }
    }
}