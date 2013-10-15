<?php

class SurveyQuestionManager extends SurveyLibrary
{
    public $surveyId;
    public $questions;
    
    function __construct($id = null) 
    {
        $this->surveyId = $id;
        
        if (!empty($this->surveyId)) {
            $this->getQuestions();
        }
    }
    
    function getQuestions()
    {
        global $objDatabase;
        
        $query= "SELECT 
                    * 
                 FROM 
                    `".DBPREFIX."module_survey_surveyQuestions`
                 WHERE 
                    `survey_id`='{$this->surveyId}' ORDER BY pos,id DESC";
        $objResult = $objDatabase->Execute($query);
        
        if ($objResult) {
            while (!$objResult->EOF) {
                $this->questions[$objResult->fields['id']] = array(
                    'id'            => $objResult->fields['id'],
                    'survey_id'     => $objResult->fields['survey_id'],
                    'created'       => $objResult->fields['created'],
                    'isActive'      => $objResult->fields['isActive'],
                    'isCommentable' => $objResult->fields['isCommentable'],
                    'questionType'  => $objResult->fields['QuestionType'],
                    'question'      => $objResult->fields['Question'],
                    'pos'           => $objResult->fields['pos'],
                    'votes'         => $objResult->fields['votes'],
                    'skipped'       => $objResult->fields['skipped'],
                    'column_choice' => $objResult->fields['column_choice']                    
                );
                
                $objResult->MoveNext();
            }
        }
    }
    
    function showQuestions()
    {        
        global $objInit;
        
        $objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH."/{$this->moduleName}/template");
        $objTpl->loadTemplateFile("module_{$this->moduleName}_questions.html");
        
        $_ARRAYLANG = $objInit->loadLanguageData('survey');
        
        if (empty($this->questions)) {
            $objTpl->setVariable('TXT_SERVEY_NO_QUESTIONS', $_ARRAYLANG['TXT_SERVEY_NO_QUESTIONS']);
            $objTpl->parse('noSurveyQuestions');
        } else {
            $objTpl->hideBlock('noSurveyQuestions');
        }
        
        foreach ($this->questions as $questionId => $question) {
            
            $comment   = $question['isCommentable'] ? $_ARRAYLANG['TXT_YES'] : $_ARRAYLANG['TXT_NO'];
            $InputType = $question['questionType'];
            if(!empty($InputType)) {
                switch($InputType) {
                    case "1":
                        $Radio = $_ARRAYLANG['TXT_MULTIPLE_CHOICE_ONE_ANSWER'];
                        break;
                    case "2":
                        $Radio = $_ARRAYLANG['TXT_MULTIPLE_CHOICE_MULTIPLE_ANSWER'];
                        break;
                    case "3":
                        $Radio = $_ARRAYLANG['TXT_MATRIX_CHOICE_ONE_ANSWER_PER_ROW'];
                        break;
                    case "4":
                        $Radio = $_ARRAYLANG['TXT_MATRIX_CHOICE_MULTIPLE_ANSWER_PER_ROW'];
                        break;
                    case "5":
                        $Radio = $_ARRAYLANG['TXT_SINGLE_TEXTBOX'];
                        break;
                    case "6":
                        $Radio = $_ARRAYLANG['TXT_MULTIPLE_TEXTBOX'];
                        break;
                    case "7":
                        $Radio = $_ARRAYLANG['TXT_TEXT_ROW'];
                        break;
                }
            }

            // for question Title with tool tip
            $surveynameVar = contrexx_raw2xhtml($question['question']);
            $surveyTemp    = '';
            if($surveynameVar != "") {
                $surveyShot    = substr($surveynameVar, 0, 20);
                if(strlen($surveynameVar) > 20) {
                    $surveyTemp = $surveyShot.'..<a href="#" title="'.$surveynameVar.'" class="tooltip"><img border="0" src="'.ASCMS_MODULE_IMAGE_WEB_PATH.'/survey/comment.gif"><a>';
                } else {
                    $surveyTemp = $surveyShot;
                }
            }
            
            $objTpl->setVariable(array(
                    'SURVEY_ID'                       => contrexx_raw2xhtml($questionId),
                    'TXT_SURVEY_POS'	              => contrexx_raw2xhtml($question['pos']),
                    'SURVEY_QUESTION'                 => $surveyTemp,
                    'SURVEY_QUESTION_CREATED_AT'      => contrexx_raw2xhtml($question['created']),
                    'SURVEY_QUESTION_TYPE'            => contrexx_raw2xhtml($Radio),
                    'SURVEY_QUESTION_COMMENTABLE'     => contrexx_raw2xhtml($comment),                    
                    'SURVEY_COUNTER'                  => contrexx_raw2xhtml($question['votes'])." votes",                    
                    'ENTRY_ROWCLASS'                  => $row = ($row == 'row1') ? 'row2' : 'row1',
                    'TXT_ANALYSE_QUESTION_PREVIEW'    => $_ARRAYLANG['TXT_ANALYSE_QUESTION_PREVIEW'],
                    'TXT_SURVEY_EDIT_TXT'	      => $_ARRAYLANG['TXT_SURVEY_EDIT_TXT'],
                    'TXT_SURVEY_DELETE_TXT'	      => $_ARRAYLANG['TXT_SURVEY_DELETE_TXT'],
            ));
            $objTpl->parse('ShowQuestions');    
        }
        
        return $objTpl->get();
    }
}
