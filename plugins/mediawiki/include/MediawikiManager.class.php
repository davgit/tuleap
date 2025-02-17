<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class MediawikiManager {

    const READ_ACCESS  = 'PLUGIN_MEDIAWIKI_READ';
    const WRITE_ACCESS = 'PLUGIN_MEDIAWIKI_WRITE';

    /** @var MediawikiDao */
    private $dao;

    public function __construct(MediawikiDao $dao) {
        $this->dao = $dao;
    }

    public function getOptions(Project $project) {
        $project_id = $project->getID();

        $options = $this->dao->getAdminOptions($project_id);

        if (! $options) {
            return $this->getDefaultOptions();
        }

        return $options;
    }

    public function getDefaultOptions() {
        return array(
            'enable_compatibility_view' => false,
        );
    }

    public function saveOptions(Project $project, array $options) {
        $project_id                = $project->getID();
        $enable_compatibility_view = (bool) isset($options['enable_compatibility_view']) ? $options['enable_compatibility_view'] : 0;

        return $this->dao->updateAdminOptions($project_id, $enable_compatibility_view);
    }

    /**
     * @return int[]
     */
    public function getReadAccessControl(Project $project) {
        $ugroup_ids = $this->getAccessControl($project, self::READ_ACCESS);

        if (! $ugroup_ids) {
            return $this->getDefaultReadAccessControl($project);
        }

        return $ugroup_ids;
    }

    /**
     * @return int[]
     */
    private function getDefaultReadAccessControl(Project $project) {
        if ($project->isPublic()) {
            return array(ProjectUGroup::REGISTERED);
        }

        return array(ProjectUGroup::PROJECT_MEMBERS);
    }

    /**
     * @return int[]
     */
    public function getWriteAccessControl(Project $project) {
        $ugroup_ids =  $this->getAccessControl($project, self::WRITE_ACCESS);

        if (! $ugroup_ids) {
            return $this->getDefaultWriteAccessControl();
        }

        return $ugroup_ids;
    }

    /**
     * @return int[]
     */
    private function getDefaultWriteAccessControl() {
        return array(ProjectUGroup::PROJECT_MEMBERS);
    }

    /**
     * @return array
     */
    private function getAccessControl(Project $project, $access) {
        $result     = $this->dao->getAccessControl($project->getID(), $access);
        $ugroup_ids = array();

        foreach ($result as $row) {
            $ugroup_ids[] = $row['ugroup_id'];
        }

        return $ugroup_ids;
    }


    public function saveReadAccessControl(Project $project, array $ugroup_ids) {
        return $this->saveAccessControl($project, self::READ_ACCESS, $ugroup_ids);
    }

    public function saveWriteAccessControl(Project $project, array $ugroup_ids) {
        return $this->saveAccessControl($project, self::WRITE_ACCESS, $ugroup_ids);
    }

    private function saveAccessControl(Project $project, $access, array $ugroup_ids) {
        return $this->dao->saveAccessControl($project->getID(), $access, $ugroup_ids);
    }

    public function updateAccessControlInProjectChangeContext(
        Project $project,
        $old_access,
        $new_access
    ) {
        if ($new_access == Project::ACCESS_PRIVATE) {
            return $this->dao->disableAnonymousRegisteredAuthenticated($project->getID());
        }
        if ($new_access == Project::ACCESS_PUBLIC && $old_access == Project::ACCESS_PUBLIC_UNRESTRICTED) {
            return $this->dao->disableAuthenticated($project->getID());
        }
    }

    public function updateSiteAccess($old_value) {
        if ($old_value == ForgeAccess::ANONYMOUS) {
            $this->dao->updateAllAnonymousToRegistered();
        }
        if ($old_value == ForgeAccess::RESTRICTED) {
            $this->dao->updateAllAuthenticatedToRegistered();
        }
    }

    /**
     * @return bool
     */
    public function isCompatibilityViewEnabled(Project $project) {
        $plugin_has_view_enabled = (bool) forge_get_config('enable_compatibility_view', 'mediawiki');
        $project_options         = $this->getOptions($project);

        return ($plugin_has_view_enabled && $project_options['enable_compatibility_view']);
    }

    public function instanceUsesProjectID(Project $project) {
        return is_dir(forge_get_config('projects_path', 'mediawiki') . "/". $project->getID());
    }

    private function restrictedUserCanRead(PFUser $user, Project $project) {
        return in_array(ProjectUGroup::AUTHENTICATED, $this->getReadAccessControl($project));
    }

    private function restrictedUserCanWrite(PFUser $user, Project $project) {
        return in_array(ProjectUGroup::AUTHENTICATED, $this->getWriteAccessControl($project));
    }

    private function getUpgroupsPermissionsManager() {
        return new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );
    }

    private function hasDelegatedAccess(PFUser $user) {
        return $this->getUpgroupsPermissionsManager()->doesUserHavePermission(
            $user,
            new User_ForgeUserGroupPermission_MediawikiAdminAllProjects()
        );
    }
    /**
     * @param PFUser $user
     * @param Project $project
     * @return bool true if user can read
     */
    public function userCanRead(PFUser $user, Project $project) {
        if ($this->hasDelegatedAccess($user)) {
            return true;
        }

        if ($this->userIsRestrictedAndNotProjectMember($user, $project)) {
            return $this->restrictedUserCanRead($user, $project);
        }

        $common_ugroups_ids = array_intersect(
            $this->getReadAccessControl($project),
            $user->getUgroups($project->getID(), array())
        );

        return !empty($common_ugroups_ids);
    }

    /**
     * @param PFUser $user
     * @param Project $project
     * @return bool true if user can write
     */
    public function userCanWrite(PFUser $user, Project $project) {
        if ($this->hasDelegatedAccess($user)) {
            return true;
        }

        if ($this->userIsRestrictedAndNotProjectMember($user, $project)) {
            return $this->restrictedUserCanWrite($user, $project);
        }

        $common_ugroups_ids = array_intersect(
            $this->getWriteAccessControl($project),
            $user->getUgroups($project->getID(), array())
        );

        return !empty($common_ugroups_ids);
    }

    private function userIsRestrictedAndNotProjectMember(PFUser $user, Project $project) {
        return $project->allowsRestricted() && $user->isRestricted() && ! $user->isMember($project->getID());
    }
}