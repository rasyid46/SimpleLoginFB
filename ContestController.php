<?php

class ContestController extends Zend_Controller_Action
{

    public function init()
    {
        $this->_helper->_acl->allow(null);
    }

    protected function _getAuthAdapter($formData)
    {
        $dbAdapter = Zend_Db_Table::getDefaultAdapter();
        $authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);
        $authAdapter->setTableName('members')
            ->setIdentityColumn('user_id')
            ->setCredentialColumn('password')
            ->setCredentialTreatment('SHA1(CONCAT(?, password_salt))');
        $authAdapter->setIdentity($formData->getValue('userId'));
        $authAdapter->setCredential($formData->getValue('password'));
        return $authAdapter;
    }

    public function indexAction()
    {
        $baseUrl = new Zend_View_Helper_BaseUrl();
        $request = $this->getRequest();
        $warning = '';

        $this->view->accordionActive = $request->getParam('formName') == 'registrationForm' ? 1 : 0;

        if ($request->getParam('contestId') == null) {
            $this->_redirect('contest/list');
        }

        //############################## preparing Styles ####################################################
        $this->view->headLink()
            ->appendStylesheet('http://fonts.googleapis.com/css?family=Coustard|EB+Garamond|Alike')
            ->appendStylesheet($request->getBaseUrl() . '/css/blitzer/jquery-ui-1.8.16.custom.css');

        //############################## preparing Scripts ####################################################               
        $this->view->headScript()
            ->appendFile($baseUrl->getBaseUrl() . '/js/jquery.watermark.js')
            ->appendFile($request->getBaseUrl() . '/js/jquery-ui-1.8.16.custom.min.js')
            ->appendFile($request->getBaseUrl() . '/js/register_form.js')
            ->prependScript('
                    $(function() {
		         $("#dateOfBirth").datepicker({
                            changeMonth: true,
                            changeYear: true,
                            yearRange: "-82:-10",
                            altFormat: "yy-mm-dd",
                            dateFormat: "yy-mm-dd"
                            //maxDate: "-10"
                         });
                         $("#dateOfBirth").datepicker("option", "showAnim", "clip");
                         $("#passwd").watermark("at least 6 characters long");
	            });
	            //$("#dateOfBirth").val("1/12/2002");
	            '
        )
            ->prependFile($request->getBaseUrl() . '/js/jquery-1.6.2.min.js');


        $loginForm = new Member_Form_Login();
        $this->view->login = $loginForm;
        $contestId = $request->getParam('contestId');

        $contestsMapper = new Admin_Model_ContestsMapper();
        $contest = new Admin_Model_Contest();

        $registrationForm = new Member_Form_Registration();
        $this->view->registration = $registrationForm;

        if ($this->getRequest()->isPost()) {
            $questionNumber = $request->getParam('questionNumber');
            $questionsCheck = true;
            for ($i = 1; $i <= $questionNumber; $i++) {
                $questionsCheck = $request->getParam('Answer' . $i) == null ? 0 : 1;
            }

            //################# The Questions is Not Empty ###################################
            if ($questionsCheck) {

                //###################### If The Post Type is Register #########################
                if (($registrationForm->isValid($request->getPost()) && ($request->getPost('formName') == 'registrationForm')) || $this->getRequest()->getParam('formName') == 'loginForm') {
                    $result = null;
                    if ($request->getPost('formName') == 'registrationForm') {
                        $memberMapper = new Admin_Model_MembersMapper();
                        $member = new Admin_Model_Member($registrationForm->getValues());
                        $memberMapper->save($member);
                        $authAdapter = $this->_getAuthAdapter($registrationForm);
                        $auth = Zend_Auth::getInstance();
                        $result = $auth->authenticate($authAdapter);
                    }

                    if ($this->getRequest()->getParam('formName') == 'loginForm') {
                        $loginForm->populate($request->getPost());
                        $authAdapter = $this->_getAuthAdapter($loginForm);
                        $auth = Zend_Auth::getInstance();
                        $result = $auth->authenticate($authAdapter);
                    }

//                    $mapper = new Admin_Model_MembersMapper();
//                    $memberId = $mapper->getIdbyUsername($member->getUserId());

//                    $authAdapter = $this->_getAuthAdapter($registrationForm);
//                    $auth = Zend_Auth::getInstance();
//                    $result = $auth->authenticate($authAdapter);

                    if ($result->isValid()) {
                        $data = $authAdapter->getResultRowObject(null, "password");
                        $data->role = 'member';
                        $auth->getStorage()->write($data);

                        $dbParticipant = new Admin_Model_DbTable_Participants();
                        $result = $dbParticipant->fetchAll('CURDATE() < date_created AND member_id = '.$data->id);

                        if (!$result->count() > 0) {

                            $participantMapper = new Admin_Model_ParticipantsMapper();
                            $participant = new Admin_Model_Participant();
                            $participant->setContestId($contestId);
                            $memberId = $data->id;
                            $participant->setMemberId($memberId);

                           // if (!$participantMapper->isHasJoinContest($participant->getMemberId(), $participant->getContestId())) {
                                $participantMapper->save($participant);
                                $questionNumber = $request->getParam('questionNumber');
                                $shortQuestionNumber = $request->getParam('shortQuestionNumber');
                                $answersMapper = new Admin_Model_AnswersMapper();
                                $shortAnswersMapper = new Admin_Model_ShortAnswersMapper();
                                for ($i = 1; $i <= $questionNumber; $i++) {
                                    $questionId = $request->getParam('Answer' . $i . 'QuestionId');
                                    $answer = $request->getParam('Answer' . $i);
                                    $answersMapper->save($questionId, $answer, $contestId, $memberId);
                                }
                                for ($i = 1; $i <= $shortQuestionNumber; $i++) {
                                    $questionId = $request->getParam('SQA' . $i . 'QuestionId');
                                    $answer = $request->getParam('SQA' . $i . 'Answer');
                                    $shortAnswersMapper->save($questionId, $answer, $contestId, $memberId);
                                }
                                $contest = new Admin_Model_Contest();
                                $contestMapper = new Admin_Model_ContestsMapper();
                                $contestMapper->find($contestId, $contest);
                                if ($participantMapper->getRightAnswered($contestId, $memberId) == $contest->getQuestionNumber()) {
                                    $participantMapper->setQualify($contestId, $memberId);
                                }
                            //}
                            $this->_redirect('member/profile/index/message/1');
                        }else{
                            $this->_redirect('member/profile/index/message/2');
                        }

                    } else {
                        $registrationForm->populate($request->getPost());
                    }
                } else {
                    $registrationForm->populate($request->getPost());
                }
            } else {
                $warning = "Choose your answers before registering or logged in";
                $registrationForm->populate($request->getPost());
            }
        }

        $contestsMapper->find($contestId, $contest);

        $this->view->warning = $warning;
        $this->view->contest = $contest;

        $questionsMapper = new Admin_Model_QuestionsMapper();

        $questions = array();
        for ($i = 1; $i <= $contest->getQuestionNumber(); $i++) {
            $question = new Admin_Model_Question();
            $questionsMapper->find($i, $contestId, $question);
            $questions[$i] = $question;
        }
        $this->view->questions = $questions;

        $qsMapper = new Admin_Model_ShortQuestionsMapper();

        $qs = array();
        for ($i = 1; $i <= $contest->getShortQuestionNumber(); $i++) {
            $tmp = new Admin_Model_ShortQuestion();
            $qsMapper->find($i, $contestId, $tmp);
            $qs[$i] = $tmp;
        }
        $this->view->shortQuestions = $qs;

    }

    public function downloadAction()
    {
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = $this->getRequest();
        $file = $request->getParam('file');
        $contestId = $request->getParam('contestId');

        $visitorClick = new Admin_Model_Visitor();
        $mapper = new Admin_Model_VisitorClickMapper();
        $visitorClick->setIpAddress($this->getRequest()->getServer('REMOTE_ADDR'));
        $visitorClick->setContestId($contestId);
        $mapper->save($visitorClick);

        $uploadPath = APPLICATION_PATH . '/uploads/contest/files/';
        $file_path = $uploadPath . $file;

        header('Content-Disposition: attachment; filename="' . $file . '"');
        readfile($uploadPath . $file);
    }

    public function gotoUrlAction()
    {
        $contest = new Admin_Model_Contest();
        $db = new Admin_Model_ContestsMapper();


        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = $this->getRequest();
        $url = $request->getParam('url');
        $contestId = $request->getParam('contestId');

        $db->find($contestId, $contest);

        $visitorClick = new Admin_Model_Visitor();
        $mapper = new Admin_Model_VisitorClickMapper();
        $visitorClick->setIpAddress($this->getRequest()->getServer('REMOTE_ADDR'));
        $visitorClick->setContestId($contestId);
        $mapper->save($visitorClick);

        if ($contest->getUrl() === '') {
            $url = '#';
        } else if (strpos($contest->getUrl(), 'http') !== false) {
            $url = $contest->getUrl();
        } else {
            $url = "http://" . $contest->getUrl();
        }

        header("location: $url");
    }

    public function imageAction()
    {
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = $this->getRequest();
        $file = $request->getParam('file');
        $contestId = $request->getParam('contestId');

        $visitor = new Admin_Model_Visitor();
        $mapper = new Admin_Model_VisitorMapper();
        $visitor->setIpAddress($this->getRequest()->getServer('REMOTE_ADDR'));
        $visitor->setContestId($contestId);
        $mapper->save($visitor);

        $uploadPath = APPLICATION_PATH . '/uploads/contest/images/';
        $file = $file == null ? 'no-image.png' : $file;
        $file_path = $uploadPath . $file;
        header('Content-Disposition: attachment; filename="' . $file . '"');
        readfile($uploadPath . $file);
    }

    public function listAction()
    {
        $contests = new Admin_Model_ContestsMapper();
        $this->view->contests = $contests->fetchContests($this->getRequest()->getParams());
    }

    public function enlargeImageAction()
    {
        $this->view->layout()->disableLayout();
        $request = $this->getRequest();
        $file = $request->getParam('file');
        $contestId = $request->getParam('contestId');
        $this->view->file = $file;
        $this->view->contestId = $contestId;
    }

}