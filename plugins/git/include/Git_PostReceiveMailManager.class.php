<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Git_PostReceiveMailDao.class.php');

class Git_PostReceiveMailManager {

    var $dao;

    /*
     * Constructor of the class
     *
     * @return void
     */
    function __construct() {
        $this->dao = $this->_getDao();
    }

    /*
     * Add a mail address to a repository to be notified
     */
    function addMail($repositoryId, $mail) {
        try {
            $this->dao->createNotification($repositoryId, $mail);
        } catch (GitDaoException $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','dao_error_create_notification'));
            return false;
        }
        return true;
    }

    /*
     * Remove a notified mail address from a repository
     */
    function removeMailByRepository($repositoryId, $mail) {
        try {
            $this->dao->removeNotification($repositoryId, $mail);
        } catch (GitDaoException $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git','dao_error_remove_notification'));
            return false;
        }
        return true;
    }

    /*
     * Remove a notified mail address from all repositories of a project
     */
    function removeMailByProject($groupId, $mail) {
        $dao = new GitDao();
        $repositoryList = $dao->getProjectRepositoryList($groupId);

        if($repositoryList && !$repositoryList->isError()) {

            foreach ($repositoryList as $row){
                try {
                    $this->dao->removeNotification($row['repository_id'], $mail);
                } catch (GitDaoException $e) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_git','dao_error_remove_notification'));
                }
            }
        }
    }

    /**
     * Remove a notified mail address from all private repositories of a project
     *
     * @param Integer $groupId
     * @param User $user
     *
     * @return void
     */
    function removeMailByProjectPrivateRepository($groupId, $user) {

        if (!$user->isMember($groupId)) {
            $gitDao = $this->_getGitDao();
            $repositoryList = $gitDao->getProjectRepositoryList($groupId);

            if($repositoryList && !$repositoryList->isError()) {

                foreach ($repositoryList as $row){
                    $repository   = new GitRepository();
                    $repository->setId( $row['repository_id'] );
                    try {
                        $repository->load();
                        if ($repository->isPrivate()) {
                            $this->dao->removeNotification($row['repository_id'], $user->getEmail());
                        }
                    } catch (GitDaoException $e) {
                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_git','dao_error_remove_notification'));
                    }
                }
            }
        }
    }

    /**
     * Returns the list of notified mails for post commit
     *
     * @param Integer $repositoryId
     *
     * @return array
     */
    public function getNotificationMailsByRepositoryId($repositoryId) {
        $dar = $this->dao->searchByRepositoryId($repositoryId);

        $mailList = array();
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            foreach ($dar as $row ) {
                $mailList [] = $row['recipient_mail'];
            }
        }
        return $mailList;
    }

    /**
     * Obtain an instance of Git_PostReceiveMailDao
     *
     * @return Git_PostReceiveMailDao
     */
    function _getDao() {
        if (!$this->dao) {
            $this->dao = new Git_PostReceiveMailDao(CodendiDataAccess::instance());
        }
        return  $this->dao;
    }

    function _getGitDao() {
        return new GitDao();
    }

}

?>