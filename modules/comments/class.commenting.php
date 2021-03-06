<?php
/* ========================================================================
 * Open eClass 3.0
* E-learning and Course Management System
* ========================================================================
* Copyright 2003-2014  Greek Universities Network - GUnet
* A full copyright notice can be read in "/info/copyright.txt".
* For a full list of contributors, see "credits.txt".
*
* Open eClass is an open platform distributed in the hope that it will
* be useful (without any warranty), under the terms of the GNU (General
		* Public License) as published by the Free Software Foundation.
* The full license can be read in "/info/license/license_gpl.txt".
*
* Contact address: GUnet Asynchronous eLearning Group,
*                  Network Operations Center, University of Athens,
*                  Panepistimiopolis Ilissia, 15784, Athens, Greece
*                  e-mail: info@openeclass.org
* ======================================================================== */

/**
 * This class represents a commenting system
*/
Class Commenting {
    
    private $rtype = '';
    private $rid = 0;
    
    /**
     * Constructor
     * @param course_id the id of the course in case of a course blog
     * @param user_id the id of the user in case of a user blog
     */
    public function __construct($rtype, $rid) {
    	$this->rtype = $rtype;
    	$this->rid = $rid;
    }
    
    
    /**
     * Get number of comments for a resource
     * @return int
     */
    public function getCommentsNum() {
        $sql = "SELECT COUNT(`id`) as c FROM `comments` WHERE `rtype` = ?s AND `rid` = ?d";
        $res = Database::get()->querySingle($sql, $this->rtype, $this->rid);
        return $res->c;
    }
    
    /**
     * Get comments from DB
     * @return array with Comment objects
     */
    public function getCommentsDB() {
    	$sql = 'SELECT * FROM `comments` WHERE '
    	      .'`rtype` = ?s AND '
    	      .'`rid` = ?d '
    	      .'ORDER BY `time` ASC';
    	$result = Database::get()->queryArray($sql, $this->rtype, $this->rid);
    	$ret = array();
    	if (is_array($result)) {
    		$ret = Comment::loadFromPDOArr($result);
    	}
    	return $ret;
    }
    
    /**
     * Injects all commenting module code in other subsystems
     * @param courseCode the course code
     * @param $isEditor
     * @param $uid the user id
     * @return string
     */
    public function put($courseCode, $isEditor, $uid) {
        global $langComments, $langBlogPostUser, $langSubmit, $themeimg, $langModify, $langDelete,
        $langCommentsDelConfirm, $langCommentsSaveConfirm, $urlServer;
        
        $commentsNum = $this->getCommentsNum();
        
        //the array is declared in commenting.js
        $out = '<script type="text/javascript">showCommentArea['.$this->rid.'] = false;</script>';
        
        $out .= '<div class="commenting">';
        $out .= '<a href="javascript:void(0)" onclick="showComments('.$this->rid.')">'.$langComments.' (<span id="commentsNum-'.$this->rid.'">'.$commentsNum.'</span>)</a><br/>';
        $out .= '<div class="commentArea" id="commentArea-'.$this->rid.'">';
        $out .= '<div id="comments-'.$this->rid.'">';
        
        if ($commentsNum != 0) {
            //retrieve comments
            $comments = $this->getCommentsDB();
            foreach ($comments as $comment) {
                $out .= '<div class="comment" id="comment-'.$comment->getId().'">';
                $out .= '<div class="smaller">'.nice_format($comment->getTime(), true).$langBlogPostUser.q(uid_to_name($comment->getAuthor())).':</div>';
                $out .= '<div id="comment_content-'.$comment->getId().'">'.q($comment->getContent()).'</div>';
                
                if ($comment->permEdit($isEditor, $uid)) {
                    $out .= '<div class="comment_actions">';
                    $out .= '<a href="javascript:void(0)" onclick="xmlhttpPost(\''.$urlServer.'modules/comments/comments.php?course='.$courseCode.'\', \'editLoad\', '.$this->rid.', \''.$this->rtype.'\', \'\', '.$comment->getId().')">';
                    $out .= '<img src="'.$themeimg.'/edit.png" alt="'.$langModify.'" title="'.$langModify.'"/></a>';
                    $out .= '<a href="javascript:void(0)" onclick="xmlhttpPost(\''.$urlServer.'modules/comments/comments.php?course='.$courseCode.'\', \'delete\', '.$this->rid.', \''.$this->rtype.'\', \''.$langCommentsDelConfirm.'\', '.$comment->getId().')">';
                    $out .= '<img src="'.$themeimg.'/delete.png" alt="'.$langDelete.'" title="'.$langDelete.'"/></a>';
                    $out .='</div>';
                }
                
                $out .= '</div>';
            }
        }
        $out .= "</div>";
        
        if (Commenting::permCreate($isEditor, $uid, course_code_to_id($courseCode))) {
            $out .= '<form action="" onsubmit="xmlhttpPost(\''.$urlServer.'modules/comments/comments.php?course='.$courseCode.'\', \'new\','.$this->rid.', \''.$this->rtype.'\', \''.$langCommentsSaveConfirm.'\'); return false;">';
            $out .= '<textarea name="textarea" id="textarea-'.$this->rid.'" cols="40" rows="5"></textarea><br/>';
            $out .= '<input name="send_button" type="submit" value="'.$langSubmit.'" />';
            $out .= '</form>';
        }
        
        $out .= '</div>';
        $out .= '</div>';
        
        return $out;
    }
    
    /**
     * Check if a user has permission to create comments
     * @param isEditor boolean showing if user is teacher
     * @param uid the user id
     * @param courseId the course id
     * @return boolean
     */
    public static function permCreate($isEditor, $uid, $courseId) {
        if ($isEditor) {//teacher is always allowed to create
            return true;
        } else {
            //students allowed to create
            $sql = "SELECT COUNT(`user_id`) as c FROM `course_user` WHERE `course_id` = ?d AND `user_id` = ?d";
            $result = Database::get()->querySingle($sql, $courseId, $uid);
            if ($result->c > 0) {//user is course member
                return true;
    	    } else {//user is not course member
                return false;
            }
        } 
    }
    
    /**
     * Delete all comments of a resource
     * @param rtype the resource type
     * @param rid the resource id
     * @return boolean
     */
    public static function deleteComments($rtype, $rid) {
        Database::get()->query("DELETE FROM `comments` WHERE `rtype`=?s AND `rid`=?d", $rtype, $rid);
    }
    
}

/**
 * Add necessary javascript to head section of an html document
 */
function commenting_add_js() {
    global $head_content, $urlServer;
    $head_content .= '<script src="'.$urlServer.'modules/comments/commenting.js" type="text/javascript"></script>';
}
