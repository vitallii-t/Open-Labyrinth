<?php

/**
 * Open Labyrinth [ http://www.openlabyrinth.ca ]
 *
 * Open Labyrinth is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Open Labyrinth is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Open Labyrinth.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright 2012 Open Labyrinth. All Rights Reserved.
 *
 */
defined('SYSPATH') or die('No direct script access.');

class Controller_WebinarManager extends Controller_Base {

    public function before() {
        parent::before();

        Breadcrumbs::add(Breadcrumb::factory()->set_title(__('Scenario Management'))->set_url(URL::base().'webinarManager'));
    }

    public function action_index()
    {
        $user       = Auth::instance()->get_user();
        $userType   = $user->type->name;

        $this->templateData['webinars'] = ($userType == 'superuser' OR $userType == 'Director')
            ? DB_ORM::model('webinar')->getAllWebinars()
            : DB_ORM::model('webinar')->getAllWebinars($user->id);
        $this->templateData['center'] = View::factory('webinar/view')->set('templateData', $this->templateData);

        $this->template->set('templateData', $this->templateData);
    }

    public function action_my() {
        $this->templateData['webinars'] = DB_ORM::model('webinar')->getWebinarsForUser(Auth::instance()->get_user()->id);

        $this->templateData['center'] = View::factory('webinar/my');
        $this->templateData['center']->set('templateData', $this->templateData);

        unset($this->templateData['right']);
        $this->template->set('templateData', $this->templateData);
    }

    public function action_add()
    {
        Breadcrumbs::add(Breadcrumb::factory()->set_title(__('Create Scenario'))->set_url(URL::base() . 'webinarManager/add'));

        $this->templateData['users']    = DB_ORM::model('user')->getAllUsersAndAuth('ASC');
        $this->templateData['groups']   = DB_ORM::model('group')->getAllGroups();
        $this->templateData['maps']     = (Auth::instance()->get_user()->type->name == 'superuser')
            ? DB_ORM::model('map')->getAllEnabledMap()
            : DB_ORM::model('map')->getAllEnabledAndAuthoredMap(Auth::instance()->get_user()->id, 0, true);
        // ------ Add sections ------- //
        foreach ($this->templateData['maps'] as $map)
        {
            foreach (DB_ORM::select('Map_Node_Section')->where('map_id', '=', $map->id)->query()->as_array() as $section)
            {
                $section->name = $map->name.'. Section: '.$section->name;
                $this->templateData['maps'][] = $section;
            }
        }
        // ------ End add sections ------- //
        $this->templateData['forums']   = DB_ORM::model('dforum')->getAllForums(1,0); // Type of Sort, 1 = Name , 0 - ASC
        $this->templateData['center']   = View::factory('webinar/webinar')->set('templateData', $this->templateData);
        $this->template->set('templateData', $this->templateData);
    }

    public function action_edit()
    {
        Breadcrumbs::add(Breadcrumb::factory()->set_title(__('Edit Scenario'))->set_url(URL::base().'webinarManager/edit'));

        $webinarId = $this->request->param('id', null);

        $MapsObj = (Auth::instance()->get_user()->type->name == 'superuser')
            ? DB_ORM::model('map')->getAllEnabledMap()
            : DB_ORM::model('map')->getAllEnabledAndAuthoredMap(Auth::instance()->get_user()->id, 0, true);

        $this->templateData['maps'] = $MapsObj;

        // ------ Add sections ------- //
        foreach ($MapsObj as $map)
        {
            foreach (DB_ORM::select('Map_Node_Section')->where('map_id', '=', $map->id)->query()->as_array() as $section)
            {
                $this->templateData['sections'][$section->id] = $section->map_id;
                $section->name = $map->name;
                $this->templateData['maps'][] = $section;
            }
        }
        // ------ End add sections ------- //

        // ------ Add poll node, end nodes of map ------- //
        foreach (DB_ORM::select('Webinar_PollNode')->where('webinar_id', '=', $webinarId)->query()->as_array() as $obj)
        {
            $nodeObj = DB_ORM::model('Map_Node', array($obj->node_id));
            $mapId   = $nodeObj->map->id;
            $this->templateData[$mapId]['pollNodes'][$obj->node_id] = $obj->time;

            if (empty($this->templateData[$mapId]['mapNodes']))
            {
                foreach (DB_ORM::model('Map_Node')->getAllNode($mapId) as $nodeObj)
                {
                    $this->templateData[$mapId]['mapNodes'][$nodeObj->id] = $nodeObj->title;
                }
            }
        }
        // ------ End add poll node ------- //

        $this->templateData['webinar'] = DB_ORM::model('webinar', array($webinarId));

        $this->templateData['experts']  = array();
        foreach (DB_ORM::select('Webinar_User')->where('webinar_id', '=', $webinarId)->query()->as_array() as $wUserObj)
        {
            if ($wUserObj->expert == 1) $this->templateData['experts'][] = $wUserObj->user_id;
        }

        $existUsers = array();
        if(count($this->templateData['webinar']->users) > 0)
        {
            foreach($this->templateData['webinar']->users as $webinarUser) {
                $existUsers[] = $webinarUser->user_id;
            }
        }

        $existGroups = array();
        if($this->templateData['webinar'] != null && count($this->templateData['webinar']->groups) > 0) {
            foreach($this->templateData['webinar']->groups as $webinarGroup) {
                $existGroups[] = $webinarGroup->group_id;
            }
        }

        $this->templateData['users']  = DB_ORM::model('user')->getAllUsersAndAuth('ASC', $existUsers);
        $this->templateData['groups'] = DB_ORM::model('group')->getAllGroups('ASC', $existGroups);

        $allUsers = DB_ORM::model('user')->getAllUsersAndAuth('ASC');
        if($allUsers != null && count($allUsers) > 0) {
            foreach($allUsers as $user) {
                $this->templateData['usersMap'][$user['id']] = $user;
            }
        }
        $this->templateData['center'] = View::factory('webinar/webinar')->set('templateData', $this->templateData);
        $this->template->set('templateData', $this->templateData);
    }

    public function action_showStats()
    {
        $scenarioId = $this->request->param('id', null);
        $step       = $this->request->param('id2', null);
        $dateId     = $this->request->param('id3', null);

        if ($scenarioId == null || $dateId == null) Request::initial()->redirect(URL::base().'webinarmanager/index');

        $scenarioStepMap = array();
        $scenarioData    = array();
        $usersMap        = array();
        $scenario        = DB_ORM::model('webinar', array((int)$scenarioId));

        if (count($scenario->users) AND count($scenario->maps))
        {
            foreach ($scenario->users as $scenarioUser)
            {
                $userId = $scenarioUser->user_id;
                if ( ! isset($usersMap[$userId])) $usersMap[$userId] = $scenarioUser->user;

                foreach($scenario->maps as $scenarioMap)
                {
                    $scenarioStep     = $scenarioMap->step;
                    $scenarioWhichId  = $scenarioMap->reference_id;
                    $scenarioWhich    = $scenarioMap->which;

                    if ($scenarioWhich == 'labyrinth') $scenarioData[$userId][$scenarioStep][$scenarioWhichId]['map'] = DB_ORM::model('Map', array((int)$scenarioWhichId));
                    elseif ($scenarioWhich == 'section') $scenarioData[$userId][$scenarioStep][$scenarioWhichId]['section'] = DB_ORM::model('Map_Section', array((int)$scenarioWhichId));
                    $scenarioData[$userId][$scenarioStep][$scenarioWhichId]['user']   = $scenarioUser->user;
                    $scenarioData[$userId][$scenarioStep][$scenarioWhichId]['status'] = $scenarioStep <= $step
                        ? DB_ORM::model('statistics_user_session')->isUserFinishMap($scenarioWhichId, $userId, $scenarioWhich, $scenario->id, $step, $dateId)
                        : 0;
                }
            }

            if(count($scenario->steps))
            {
                foreach($scenario->steps as $scenarioStep)
                {
                    $scenarioStepMap[$scenarioStep->id] = $scenarioStep;
                }
            }
        }

        $this->templateData['webinar']        = $scenario;
        $this->templateData['webinarStepMap'] = $scenarioStepMap;

        foreach ($this->templateData['webinar']->users as $user)
        {
            DB_ORM::model('webinar_user')->updateInclude4R($user->id, 1);

            $this->templateData['includeUsersData'][$user->user_id] = $user->id;
            $this->templateData['includeUsers'][$user->user_id]     = $user->include_4R;
        }

        $this->templateData['usersMap']     = $usersMap;
        $this->templateData['webinarData']  = $scenarioData;
        $this->templateData['step']         = $step;
        $this->templateData['dateId']       = $dateId;

        foreach(DB_ORM::model('user')->getAllUsersAndAuth('ASC') as $user)
        {
            $this->templateData['usersAuthMap'][$user['id']] = $user;
        }

        $this->templateData['center'] = View::factory('webinar/showStats')->set('templateData', $this->templateData);
        $this->template->set('templateData', $this->templateData);

        $date  = date('Y-m-d H:i:s', DB_ORM::model('statistics_user_datesave', array($dateId))->date_save);
        Breadcrumbs::add(Breadcrumb::factory()->set_title('Statistics for '.$scenario->title)->set_url(URL::base().'webinarManager/statistics/'.$scenarioId));
        Breadcrumbs::add(Breadcrumb::factory()->set_title($date)->set_url(URL::base().'webinarManager/progress'));
    }

    public function action_progress()
    {
        $webinarId = $this->request->param('id', null);

        if ($webinarId == null) Request::initial()->redirect(URL::base().'webinarmanager/index');

        Breadcrumbs::add(Breadcrumb::factory()->set_title(__('Scenario Progress'))->set_url(URL::base().'webinarManager/progress'));

        $wData          = array();
        $usersMap       = array();
        $webinar        = DB_ORM::model('webinar', array((int)$webinarId));
        $webinarStepMap = array();

        if($webinar != null && count($webinar->users) && count($webinar->maps) > 0)
        {
            foreach($webinar->users as $wUser)
            {
                $wUserId = $wUser->user_id;
                $userType =DB_ORM::model('User', array($wUserId))->type_id;
                if( ! isset($usersMap[$wUserId])) $usersMap[$wUserId] = $wUser->user;

                $this->templateData['includeUsersData'][$wUserId] = $wUser->id;
                $this->templateData['includeUsers'][$wUserId] = $wUser->include_4R;
                if ($userType != 1) $this->templateData['experts'][$wUserId] = $wUser->expert;

                foreach ($webinar->maps as $wMapObj)
                {
                    $wStep          = $wMapObj->step;
                    $id             = $wMapObj->reference_id;
                    $wCurrentStep   = $webinar->current_step;
                    $prefix         = '';

                    if ($wMapObj->which == 'labyrinth') $wData[$wUserId][$wStep][$id]['map'] = DB_ORM::model('map', array((int)$id));
                    else
                    {
                        $prefix = 's';
                        $sectionObj = DB_ORM::model('Map_Node_Section', array($id));
                        $sectionObj->id     = $sectionObj->map_id;
                        $sectionObj->name   = 'Section: '.$sectionObj->name;
                        $wData[$wUserId][$wStep][$prefix.$id]['map'] = $sectionObj;
                    }

                    $wData[$wUserId][$wStep][$prefix.$id]['status'] = ($wStep <= $wCurrentStep)
                        ? DB_ORM::model('user_session')->isUserFinishMap($id, $wUserId, $wMapObj->which, $webinar->id, $wCurrentStep)
                        : 0;
                    $wData[$wUserId][$wStep][$prefix.$id]['user']   = $wUser->user;
                }
            }
            if(count($webinar->steps))
            {
                foreach($webinar->steps as $webinarStep)
                {
                    $webinarStepMap[$webinarStep->id] = $webinarStep;
                }
            }
        }

        $this->templateData['scenario']       = DB_ORM::select('Webinar')->query()->as_array();
        $this->templateData['webinarStepMap'] = $webinarStepMap;
        $this->templateData['webinar']        = $webinar;
        $this->templateData['usersMap']       = $usersMap;
        $this->templateData['webinarData']    = $wData;

        $allUsers = DB_ORM::model('user')->getAllUsersAndAuth('ASC');
        if(count($allUsers))
        {
            foreach($allUsers as $user)
            {
                $this->templateData['usersAuthMap'][$user['id']] = $user;
            }
        }

        $this->templateData['center'] = View::factory('webinar/statistic')->set('templateData', $this->templateData);
        $this->template->set('templateData', $this->templateData);
    }

    public function action_statistic() {
        Request::initial()->redirect(URL::base() . 'webinarmanager/progress/' . $this->request->param('id', null));
    }

    public function action_statistics() {
        $webinarId   = $this->request->param('id', null);

        if($webinarId == null) {
            Request::initial()->redirect(URL::base() . 'webinarmanager/index');
        } else {
            $this->templateData['history'] = DB_ORM::model('statistics_user_session')->getDateSaveByWebinarId($webinarId);

            $webinar = DB_ORM::model('webinar', array((int)$webinarId));

            $this->templateData['webinar']     = $webinar;
            Breadcrumbs::add(Breadcrumb::factory()->set_title('Statistics for ' . $webinar->title)->set_url(URL::base() . 'webinarManager/statistics'));

            $this->templateData['center'] = View::factory('webinar/all');
            $this->templateData['center']->set('templateData', $this->templateData);
            $this->template->set('templateData', $this->templateData);
        }
    }

    public function action_publishStep()
    {
        $webinarId   = $this->request->param('id', null);
        $webinarStep = $this->request->param('id2', null);
        $dateId      = $this->request->param('id3', null);
        $webinar     = DB_ORM::model('webinar', array((int)$webinarId));

        if($webinar != null && $webinarStep != null && $webinarStep > 0)
        {
            $jsonObject = ($webinar->publish == null) ? array() : json_decode($webinar->publish);

            if(!in_array($webinarId.'-'.$webinarStep, $jsonObject))
            {
                $jsonObject[] = $webinarId.'-'.$webinarStep;

                $webinar->publish = json_encode($jsonObject);
                $webinar->save();

                if ($webinar->forum_id > 0) DB_ORM::model('dforum_messages')->createMessage($webinar->forum_id, '<a href="' . URL::base() . 'webinarManager/stepReport4R/' . $webinarId . '/' . $webinarStep .'/'. $dateId. '">Step ' . $webinarStep . ' 4R Report</a>');
            }

            Request::initial()->redirect(URL::base() . 'webinarmanager/showStats/' . $webinarId . '/' . $webinarStep . '/' . $dateId);
        }
        else Request::initial()->redirect(URL::base() . 'webinarmanager/index');
    }

    public function action_render()
    {
        $webinarId = $this->request->param('id', null);
        $webinar   = DB_ORM::model('webinar', array((int)$webinarId));

        if(count($webinar->steps) > 0)
        {
            foreach($webinar->maps as $webinarMap)
            {
                $this->templateData['mapsMap'][$webinarMap->step][$webinarMap->reference_id] = ($webinarMap->step <= $webinar->current_step)
                    ? DB_ORM::model('user_session')->isUserFinishMap($webinarMap->reference_id, Auth::instance()->get_user()->id, $webinarMap->which, $webinarId, $webinar->current_step)
                    : 0;
            }
        }
        $this->templateData['webinar']  = $webinar;
        $this->templateData['center']   = View::factory('webinar/render')->set('templateData', $this->templateData);

        $this->template->set('templateData', $this->templateData);
    }

    public function action_delete() {
        DB_ORM::model('webinar')->deleteWebinar($this->request->param('id', null));

        Request::initial()->redirect(URL::base() . 'webinarmanager/index');
    }

    public function action_save()
    {
        DB_ORM::model('webinar')->saveWebinar($this->request->post());
        Request::initial()->redirect(URL::base().'webinarmanager/index');
    }

    public function action_changeStep() {
        $webinarId = $this->request->param('id' , null);
        $step      = $this->request->param('id2', null);
        $redirect  = $this->request->param('id3', null);

        DB_ORM::model('webinar')->changeWebinarStep($webinarId, $step);

        if ($redirect == null){
            Request::initial()->redirect(URL::base() . 'webinarmanager/index');
        } else {
            Request::initial()->redirect(URL::base() . 'webinarmanager/progress/'.$webinarId);
        }
    }

    public function action_stepReport4R()
    {
        $webinarId = $this->request->param('id', null);
        $stepKey   = $this->request->param('id2', null);
        $dateId    = $this->request->param('id3', null);

        if ($webinarId != null && $webinarId > 0 && $stepKey != null && $stepKey > 0) {
            $webinar = DB_ORM::model('webinar', array((int)$webinarId));
            $isExistAccess = false;

            if (Auth::instance()->get_user()->id == $webinar->author_id || Auth::instance()->get_user()->type->name == 'superuser') {
                $isExistAccess = true;
            }

            if ( ! $isExistAccess && $webinar->publish != null) {
                $jsonObject = json_decode($webinar->publish);

                $isExistAccess = in_array($webinarId . '-' . $stepKey, $jsonObject);
            }

            if ($isExistAccess) {
                $report  = new Report_4R(new Report_Impl_PHPExcel(), $webinar->title);
                $notIncludUsers = DB_ORM::model('webinar_user')->getNotIncludedUsers($webinar->id);
                if($webinar != null && count($webinar->maps) > 0) {
                    foreach($webinar->maps as $webinarMap) {
                        if($webinarMap->step == $stepKey) {
                            $mapId = ($webinarMap->which == 'labyrinth')
                                ? $webinarMap->reference_id
                                : DB_ORM::model('Map_Node_Section', array($webinarMap->reference_id))->map_id;
                            $report->add($mapId, $webinar->id, $stepKey, $notIncludUsers,$dateId);
                        }
                    }
                }
                $report->generate();

                $report->get();
            } else {
                Request::initial()->redirect(URL::base() . 'home/index');
            }
        } else {
            Request::initial()->redirect(URL::base() . 'webinarmanager/index');
        }
    }

    public function action_stepReportSCT()
    {
        $webinarId          = $this->request->param('id', null);
        $stepKey            = $this->request->param('id2', null);
        $expertWebinarId    = $this->request->param('id3', null);

        if ($webinarId == null AND $stepKey != null) Request::initial()->redirect(URL::base().'webinarmanager/index');

        $webinar = DB_ORM::model('webinar', array((int)$webinarId));
        $isExistAccess = false;

        if (Auth::instance()->get_user()->id == $webinar->author_id || Auth::instance()->get_user()->type->name == 'superuser') $isExistAccess = true;

        if ( ! $isExistAccess AND $webinar->publish != null)
        {
            $jsonObject = json_decode($webinar->publish);
            $isExistAccess = in_array($webinarId . '-' . $stepKey, $jsonObject);
        }

        if($isExistAccess)
        {
            $report  = new Report_SCT(new Report_Impl_PHPExcel(), $webinar->title);
            if($webinar != null && count($webinar->maps) > 0)
            {
                foreach($webinar->maps as $webinarMap)
                {
                    if($webinarMap->step == $stepKey)
                    {
                        // if labyrinth, else section
                        if ($webinarMap->which == 'labyrinth')
                        {
                            $mapId = $webinarMap->reference_id;
                            $sectionId = false;
                        }
                        else
                        {
                            $mapId = DB_ORM::model('Map_Node_Section', array($webinarMap->reference_id))->map_id;
                            $sectionId = $webinarMap->reference_id;
                        }
                        $report->add($mapId, $webinarId, $expertWebinarId, $sectionId);
                    }
                }
            }
            $report->generate();
            $report->get();
        }
        else Request::initial()->redirect(URL::base().'home/index');
    }

    public function action_stepReportPoll()
    {
        $webinarId          = $this->request->param('id', null);
        $stepKey            = $this->request->param('id2', null);

        if ($webinarId == null AND $stepKey != null) Request::initial()->redirect(URL::base().'webinarmanager/index');

        $webinar = DB_ORM::model('webinar', array((int)$webinarId));
        $isExistAccess = false;

        if (Auth::instance()->get_user()->id == $webinar->author_id OR Auth::instance()->get_user()->type->name == 'superuser') $isExistAccess = true;

        if ( ! $isExistAccess AND $webinar->publish != null)
        {
            $jsonObject = json_decode($webinar->publish);
            $isExistAccess = in_array($webinarId . '-' . $stepKey, $jsonObject);
        }

        if($isExistAccess)
        {
            $report         = new Report_Poll(new Report_Impl_PHPExcel(), $webinar->title);
            if(count($webinar->maps) > 0)
            {
                foreach($webinar->maps as $webinarMap)
                {
                    if($webinarMap->step == $stepKey)
                    {
                        $mapId = ($webinarMap->which == 'labyrinth')
                            ? $webinarMap->reference_id
                            : DB_ORM::model('Map_Node_Section', array($webinarMap->reference_id))->map_id;
                        $report->add($mapId, $webinarId);
                    }
                }
            }
            $report->generate();
            $report->get();
        }
        else Request::initial()->redirect(URL::base().'home/index');
    }

    public function action_mapReport4R()
    {
        $this->createReport('4R');
    }

    public function action_mapReportSCT()
    {
        $this->createReport('SCT');
    }

    public function action_mapReportPoll()
    {
        $this->createReport('Poll');
    }

    private function createReport($type)
    {
        $webinarId          = $this->request->param('id', null);
        $mapId              = $this->request->param('id2', null);
        $sectionId          = $this->request->param('id3', null);
        $expertWebinarId    = $this->request->param('id4', null);

        if ($webinarId == null AND $mapId == null) Request::initial()->redirect(URL::base().'webinarmanager/index');

        switch ($type)
        {
            case 'SCT':
                $report = new Report_SCT(new Report_Impl_PHPExcel(), 'SCT Report '.DB_ORM::model('map', array((int)$mapId))->name);
                $report->add($mapId, $webinarId, $expertWebinarId, $sectionId);
                $report->generate();
                $report->get();
            break;
            case 'Poll':
                $report = new Report_Poll(new Report_Impl_PHPExcel(), 'Poll '.DB_ORM::model('map', array((int)$mapId))->name);
                $report->add($mapId, $webinarId, '');
                $report->generate();
                $report->get();
            break;
            case '4R':
                $notIncludUsers = DB_ORM::model('webinar_user')->getNotIncludedUsers($webinarId);

                $report  = new Report_4R(new Report_Impl_PHPExcel(), DB_ORM::model('map', array((int)$mapId))->name);
                $report->add($mapId, $webinarId, '' , $notIncludUsers);
                $report->generate();
                $report->get();
            break;
            default: Request::initial()->redirect(URL::base().'webinarmanager/index');
        }
    }

    public function action_play()
    {
        $webinarId = $this->request->param('id', null);
        $step      = $this->request->param('id2', null);
        $id        = $this->request->param('id3', null);
        $type      = $this->request->param('id4', null);

        if ($webinarId > 0 AND $step > 0)
        {
            Session::instance()->set('webinarId', $webinarId);
            Session::instance()->set('step', $step);

            //------ redirect to webinar section ------//
            if ($type == 'section')
            {
                $section = DB_ORM::model('Map_Node_Section', array($id));
                $sectionNode = DB_ORM::select('Map_Node_Section_Node')->where('section_id', '=', $id)->where('node_type', '=', 'in')->query()->fetch(0);

                if ( ! $sectionNode) Request::initial()->redirect(URL::base());

                Session::instance()->set('webinarSection', $type);
                Session::instance()->set('webinarSectionId', $id);
                Request::initial()->redirect(URL::base().'renderLabyrinth/go/'.$section->map->id.'/'.$sectionNode->node_id);
            }
            //------ end redirect to webinar section ------//
        }

        Request::initial()->redirect(URL::base().'renderLabyrinth/index/'.$id);
    }

    public function action_reset() {
        $webId = $this->request->param('id', null);

        $dataStatisticsIds = DB_ORM::model('statistics_user_session')->getSessionByWebinarId($webId);
        list($data, $ids) = DB_ORM::model('user_session')->getSessionByWebinarId($webId,$dataStatisticsIds);

        if (count($data) > 0) {
            // Save Statistics
            DB_ORM::model('statistics_user_session')->saveWebInarSession( $data );
            DB_ORM::model('statistics_user_sessiontrace')->saveWebInarSessionTraces( $ids );
            DB_ORM::model('statistics_user_response')->saveScenarioResponse( $ids );
        }

        DB_ORM::model('webinar')->resetWebinar($webId);

        Request::initial()->redirect(URL::base() . 'webinarmanager/index');
    }

    public function action_updateInclude4R() {
        $id = $this->request->param('id', null);
        $include = $this->request->param('id2', null);

        DB_ORM::model('webinar_user')->updateInclude4R($id, $include);

        return true;

    }

    public function action_updateExpert()
    {
        $id     = $this->request->param('id', null);
        $expert = $this->request->param('id2', null);

        DB_ORM::model('Webinar_User')->updateExpert($id, $expert);
        return true;
    }

    public function action_getMapByWebinar()
    {
        $wId = $this->request->param('id', null);
        $mapsId = array();
        foreach (DB_ORM::select('Webinar_Map')->where('webinar_id', '=', $wId)->query()->as_array() as $wObj)
        {
            $mapsId[] = $wObj->reference_id;
        }
        echo(json_encode($mapsId));
        exit;
    }

    public function action_getSectionAJAX()
    {
        $response   = array();
        $mapId      = $this->request->param('id');
        $sections   = DB_ORM::model('Map_Node_Section')->getSectionsByMapId($mapId);

        foreach ($sections as $sectionObj)
        {
            $response[$sectionObj->name] = $sectionObj->id;
        }
        echo json_encode($response);
        exit;
    }

    public function action_getNodesAjax ()
    {
        $mapId  = $this->request->param('id');
        $result = array();

        foreach(DB_ORM::select('Map_Node')->where('map_id', '=', $mapId)->query()->as_array() as $nodeObj)
        {
            $result[$nodeObj->title] = $nodeObj->id;
        }
        ksort($result);
        exit(json_encode($result));
    }

    public function action_deleteNodeAjax ()
    {
        DB_ORM::model('Webinar_PollNode')->deleteNode($this->request->param('id'));
    }

    public function action_visualEditor ()
    {
        $scenarioId = $this->request->param('id');
        Breadcrumbs::add(Breadcrumb::factory()->set_title(__('Visual Editor')));

        $this->templateData['enabledMaps']  = DB_ORM::model('map')->getAllEnabledMap(0, 'name', 'ASC');
        $this->templateData['steps']        = DB_ORM::model('Webinar_Step')->getScenarioSteps($scenarioId);
        $this->templateData['scenario']     = DB_ORM::model('Webinar', array($scenarioId));
        $this->templateData['scenarioJSON'] = DB_ORM::model('Webinar')->generateJSON($scenarioId);
        $this->templateData['center']       = View::factory('webinar/canvas')->set('templateData', $this->templateData);
        $this->template->set('templateData', $this->templateData);
    }

    public function action_ajaxStepUpdate()
    {
        $scenarioId     = $this->request->post('scenarioId');
        $data           = $this->request->post('data');
        $data           = json_decode($data, true);
        $steps          = Arr::get($data, 'steps', array());
        $elements       = Arr::get($data, 'elements', array());

        $dbSteps = DB_ORM::model('Webinar_Step')->getOnlyId($scenarioId);

        foreach ($elements as $idStep => $elementsData){
            $labyrinths = Arr::get($elementsData, 'labyrinth', array());
            $section    = Arr::get($elementsData, 'section', array());

            if ( ! ($labyrinths OR $section)) continue;

            // ----- steps ----- //
            $newStepName = Arr::get($steps, $idStep, '');
            if (is_int($idStep)) {
                DB_ORM::model('Webinar_Step')->updateStep($idStep, $newStepName);
                unset($dbSteps[$idStep]);
            } else {
                $idStep = DB_ORM::model('Webinar_Step')->addStep($scenarioId, $newStepName);
            }
            // ----- end steps ----- //

            // ----- elements ----- //
            $dbElements = DB_ORM::model('Webinar_Map')->elementsForAjax($idStep);

            // update labyrinth
            foreach ($labyrinths as $idElement){
                if(isset($dbElements[$idElement])) unset($dbElements[$idElement]);
                else DB_ORM::model('Webinar_Map')->addMap($scenarioId, $idElement, $idStep, 'labyrinth');
            }

            // update section
            foreach ($section as $idElement){
                if(isset($dbElements[$idElement])) unset($dbElements[$idElement]);
                else DB_ORM::model('Webinar_Map')->addMap($scenarioId, $idElement, $idStep, 'section');
            }

            // delete remains
            foreach ($dbElements as $recordId){
                DB_ORM::delete('Webinar_Map')->where('id', '=', $recordId)->execute();
            }
            // ----- end elements ----- //
        }

        // ----- delete steps ----- //
        foreach ($dbSteps as $id=>$trash){
            DB_ORM::model('Webinar_Step')->removeStep($id);
        }

        exit(DB_ORM::model('Webinar')->generateJSON($scenarioId));
    }
}