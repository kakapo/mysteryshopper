<?php
class home{
	public $login_user;
	public $tpl;
	private $assignmentModel;
	function __construct(){
		global $tpl;
		$this->tpl = $tpl;
		$this->login_user = authenticate();	
		if(!$this->login_user){
			show_message("您还未登录!");
			redirect("/index.php/passport/login");
		}
		$this->tpl->assign('user',$this->login_user);
		$view = isset($_GET['view'])?$_GET['view']:"defaults";
		$this->tpl->assign('view',$view);
	}
    function view_defaults(){
		
		include_once("AssignmentModel.class.php");
		$assignmentModel = new AssignmentModel();
		$myassignment = $assignmentModel->getMyAssignments($this->login_user['user_id']);
		$lastestassignment = $assignmentModel->getLastestAssignments();
		$this->tpl->assign("lastestassignment",$lastestassignment);
		$this->tpl->assign("myassignment",$myassignment);
    }


    function view_msgbox(){
    	
    }
    function view_history(){
    	include_once("AssignmentModel.class.php");
		$assignmentModel = new AssignmentModel();
		$myassignment = $assignmentModel->getMyHistoryAssignments($this->login_user['user_id']);
		$this->tpl->assign("myassignment",$myassignment);
    }
    function view_allassignment(){
    	$cur_sort = !empty($_GET['sort'])?$_GET['sort']:'a_order';

		$a_title = !empty($_GET['a_title'])?$_GET['a_title']:'';
		$c_id = !empty($_GET['c_id'])?$_GET['c_id']:'';
		$cs_id= !empty($_GET['cs_id'])?$_GET['cs_id']:'';
		$a_sdate= !empty($_GET['a_sdate'])?$_GET['a_sdate']:'';
		$a_edate= !empty($_GET['a_edate'])?$_GET['a_edate']:'';
		
		$con['order'] = $cur_sort;
		$con['a_title'] = $a_title;
		$con['c_id'] = $c_id;
		$con['cs_id'] = $cs_id;
		$con['a_sdate'] = $a_sdate;
		$con['a_edate'] = $a_edate;
		$con['notselected'] = 'notselected';
		
		include_once("AssignmentModel.class.php");
		$assignmentModel = new AssignmentModel();
				
		$assignments = $assignmentModel->getItems($con,16);
		$this->tpl->assign('assignments',$assignments);
		$this->tpl->assign('total',$assignments['page']->total);
    }    
    function get_assignment($a_id){	
    	include_once("AssignmentModel.class.php");
    	$this->assignmentModel = new AssignmentModel();
    	$assignmentinfo = $this->assignmentModel->getAssignmentById($a_id);
    	if(!$assignmentinfo){
    		show_message("参数无效！");
    		goback(10000);
    	}
    	$this->tpl->assign('a_id',$a_id);
    	$this->tpl->assign('assignmentinfo',$assignmentinfo);
    	$do_quiz = '';
		if(isset($_SESSION['a_'.$a_id]['pass']) && $_SESSION['a_'.$a_id]['pass']==1){
			$do_quiz = 'pass';
		}
		if(isset($_SESSION['a_'.$a_id]['pass']) && $_SESSION['a_'.$a_id]['pass']==0){
			$do_quiz = 'fail';
		}
		$this->tpl->assign("do_quiz",$do_quiz);
    	return $assignmentinfo;
    }
    function view_assignment(){
    	$a_id = isset($_GET['a_id'])?intval($_GET['a_id']):'0';
    	$this->get_assignment($a_id);

    }
	function view_reportpreview(){
		$a_id = isset($_GET['a_id'])?intval($_GET['a_id']):'0';
		$assignment = $this->get_assignment($a_id);
		$re_id = $assignment['re_id'];
		include_once("ReportModel.class.php");
		$ReportModel = new ReportModel();
		$report_questions  = $ReportModel->getQuestionsByReId($re_id);
		$this->tpl->assign("report_questions",$report_questions);
	}
	function view_quiz(){
		$a_id = isset($_GET['a_id'])?intval($_GET['a_id']):'0';
		
		
		$assignment = $this->get_assignment($a_id);
		$quiz = $this->assignmentModel->generateQuiz($assignment['a_quiz']);
		$this->tpl->assign("quiz",$quiz);

		
	}
	function op_doquiz(){
		$a_id = isset($_POST['a_id'])?intval($_POST['a_id']):'0';
		$this->get_assignment($a_id);
		$count = isset($_POST['num'])?intval($_POST['num']):0;
		$a_id = isset($_POST['a_id'])?intval($_POST['a_id']):0;
		$pass = 0;
		if($count>0){
			for ($i=0;$i<$count;$i++){
				if(isset($_POST['option_'.$i]) && isset($_POST['hida_'.$i]) && $_POST['option_'.$i]==$_POST['hida_'.$i]){
					$pass++;
				}
			}
			if($pass==$count){
				$_SESSION['a_'.$a_id]['pass'] = 1;
				show_message("恭喜您，测试通过！您可以报名了！");
				redirect("/index.php/home/assignment/a_id/$a_id");
			}else{
				$_SESSION['a_'.$a_id]['pass'] = 0;
			}
		}
		goback(0);
	}
	function view_report(){
		$a_id = isset($_GET['a_id'])?intval($_GET['a_id']):'0';
		$assignment = $this->get_assignment($a_id);
		$re_id = $assignment['re_id'];
		$a_id =isset($_GET['a_id'])?$_GET['a_id']:0;
		
		if($assignment['user_id']==$this->login_user['user_id']){
		
			include_once("ReportModel.class.php");
			$ReportModel = new ReportModel();
			$report_questions  = $ReportModel->getQuestionsByReId($re_id);
			if($report_questions){
				foreach ($report_questions as $k=>$v){
					$v['answer'] = $ReportModel->getAnswerByAid($a_id,$v['rq_id'],$v['rq_type']);
					$report_questions[$k] = $v;
				}
				
			}
			$this->tpl->assign("report_questions",$report_questions);
		}
	}
	function op_savereport(){
		include_once("ReportModel.class.php");
		$reportModel = new ReportModel();
		$u_id= $this->login_user['user_id'];
		$a_id= $_POST['a_id'];
		$r = true;
		if($_POST){
			foreach ($_POST as $k=>$v){
				if(substr($k,0,7)=='rq_ans_'){
					list(,,$rq_type,$rq_id) = split("_",$k);
					$r *=$reportModel->saveAnswer($rq_id,$u_id,$a_id,$rq_type,addslashes($v));
				}
			}
		}
		
		if($r){
			show_message_goback(lang('success'));
		}else{
			show_message_goback(lang('failed'));
		
		}
	}
	
	function op_apply(){
		$rs = false;
		$a_id = isset($_POST['a_id'])?intval($_POST['a_id']):'0';
		$this->get_assignment($a_id);
		$count = $this->assignmentModel->getAssignmentApplyCountById($a_id);
		if($count<3){
			$rs=$this->assignmentModel->apply($a_id,$this->login_user['user_id']);
		}
		if($rs){
			show_message("恭喜您，报名成功！如果您从被选中，我们会联系您！");
			unset($_SESSION['a_'.$a_id]['pass']);
			redirect("/index.php/home/allassignment/a_id/$a_id",2);
		}else{
			show_message("对不起，名额已满！请选择其他任务！");
			redirect("/index.php/home/allassignment");
		}
	}
	function view_upload(){
		$a_id = isset($_GET['a_id'])?intval($_GET['a_id']):'0';
		$this->get_assignment($a_id);
	}
    
    function view_mydetail(){
    	$type = !empty($_GET['mydetail'])?$_GET['mydetail']:"contact";
    	include_once("PassportModel.class.php");
    	$passmod = new PassportModel();
		$userinfo  = $passmod->getUserInfoById($this->login_user['user_id']);
    	list($userinfo['year'],$userinfo['month'],$userinfo['day']) = explode("-",$userinfo['birthdate']);
    	$this->tpl->assign("type",$type);
    	$this->tpl->assign("userinfo",$userinfo);
    
    	//$rs = $passmod->saveUserExt ( $user ,$this->login_user['user_id']);
    }
    function op_updateuserdetail(){
    	include_once("PassportModel.class.php");
    	$type = isset($_POST['type'])?$_POST['type']:"";
    	switch ($type){
    		
    		case "contact":
    			
				$user['mobile'] = !empty($_POST['mobile'])?$_POST['mobile']:'';
				$user['phone'] = !empty($_POST['phone'])?$_POST['phone']:'';
				$user['qq'] = !empty($_POST['qq'])?$_POST['qq']:'';
				$user['msn'] = !empty($_POST['msn'])?$_POST['msn']:'';
				$user['city'] = !empty($_POST['city'])?$_POST['city']:'';
				$user['address'] = !empty($_POST['address'])?addslashes($_POST['address']):'';
    		break;
    		case "extinfo":
    			$user['realname'] = !empty($_POST['realname'])?$_POST['realname']:'';
    			$birthdateyear = !empty($_POST['birthdateyear'])?$_POST['birthdateyear']:'';
				$birthdatemonth = !empty($_POST['birthdatemonth'])?$_POST['birthdatemonth']:'';
				$birthdateday = !empty($_POST['birthdateday'])?$_POST['birthdateday']:'';
				$user['birthdate']  =$birthdateyear.'-'. sprintf('%02d',$birthdatemonth).'-'.sprintf('%02d',$birthdateday);
				$user['marital'] = !empty($_POST['maritalstatus'])?$_POST['maritalstatus']:'0';
				$user['nationality'] = !empty($_POST['nationality'])?$_POST['nationality']:'';
				$user['occupation'] = !empty($_POST['occupation'])?$_POST['occupation']:'';
				$user['householdincome'] = !empty($_POST['householdincome'])?$_POST['householdincome']:'0';
				$user['education'] = !empty($_POST['education'])?$_POST['education']:'0';
				$user['havecar'] = !empty($_POST['havecar'])?$_POST['havecar']:'0';
				$user['speak_english'] = !empty($_POST['speakenglish'])?$_POST['speakenglish']:'0';
    		break;    		
    		case "other":
    			$user['eatingoutcount'] = !empty($_POST['eatingoutcount'])?intval($_POST['eatingoutcount']):'';
				$user['avgbill'] = !empty($_POST['avgbill'])?$_POST['avgbill']:'';
				$user['apparelspending'] = !empty($_POST['apparelspending'])?$_POST['apparelspending']:'';
				$user['hearabout'] = !empty($_POST['hearabout'])?$_POST['hearabout']:'0';
				$user['newletters'] = !empty($_POST['newletters'])?$_POST['newletters']:'0';
				$user['interests'] = !empty($_POST['interests'])?$_POST['interests']:'';
		    		break;	
    		case "payment":
    			$user['alipay'] = !empty($_POST['alipay'])?$_POST['alipay']:"";
    			$user['bank_name'] = !empty($_POST['bank_name'])?$_POST['bank_name']:"";
    			$user['bank_realname'] = !empty($_POST['bank_realname'])?$_POST['bank_realname']:"";
    			$user['bank_account'] = !empty($_POST['bank_account'])?$_POST['bank_account']:"";
    			
    		break;    		
    		
    		case "upload":
    		//	print_r($_FILES);die;
    			if(isset($_FILES['face']) && $_FILES['face']['error']==0){
    				$img_ext = substr($_FILES['face']['name'],strrpos($_FILES['face']['name'],'.'));
    				$user['face_img'] = "face_".$this->login_user['user_id'].$img_ext;
    				move_uploaded_file($_FILES['face']['tmp_name'],APP_DIR."/public/upload/faceimg/".$user['face_img']);
    			}
    			
    		break;
    		
    		default:
    			
    	}
    	
    	$passmod = new PassportModel();
		$rs = $passmod->saveUserExt ( $user ,$this->login_user['user_id']);
		if(!$rs){
				$msg = array('s'=> 400,'m'=>'fail','d'=>"");				
				if($type!="upload") exit(json_output($msg));
		}else{
				$msg = array('s'=> 200,'m'=>'已经保存！','d'=>$GLOBALS ['gSiteInfo'] ['www_site_url']."/index.php/home/mydetail/$type");				
				if($type!="upload") exit(json_output($msg));
				
		}
		show_message("保存成功！");
		redirect("/index.php/home/mydetail/$type");
		
    }
}
?>