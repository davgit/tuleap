<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Account_ConfirmationPresenter {

    public $title;
    public $content;
    public $thanks;
    public $isThanks;
    public $redirect_url;
    public $redirect_content;

    public function __construct($title, $content, $thanks, $isThanks, $redirect_url, $redirect_content) {
        $this->title            = $title;
        $this->content          = $content;
        $this->thanks           = $thanks;
        $this->isThanks         = $isThanks;
        $this->redirect_url     = $redirect_url;
        $this->redirect_content = $redirect_content;
    }
}