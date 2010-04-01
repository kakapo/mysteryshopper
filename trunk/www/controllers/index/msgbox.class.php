<?php
class msgbox{
	public $login_user;
	public $tpl;
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
		include_once("MsgBoxModel.class.php");
		$this->msgModel = new MsgBoxModel();
	}
    function view_lists(){
		$con['order'] = "m_id";
		$con['m_pid'] = "0";
		$con['user_id'] = $this->login_user['user_id'];
		$data = $this->msgModel->getMsgLists($con,10);
		$this->tpl->assign("data",$data);
    }
	function view_read(){
		$m_id = isset($_GET['read'])?intval($_GET['read']):0;
		$msg = $this->msgModel->getMsgById($m_id);
		if($msg){
			$this->msgModel->updateMsg($m_id);
		}
		$reply_data = $this->msgModel->getMsgReplies($m_id);
		$this->tpl->assign("reply_data",$reply_data);
		$this->tpl->assign("msg",$msg);
	}
	function view_write(){
		
	}
	function op_post(){
		$msg = '';
		$field['m_pid'] = 0;
		$field['m_title'] = strip_tags($_POST['title']);
		$field['m_content'] = strip_tags($_POST['content']);
		$field['user_id'] = $this->login_user['user_id'];
		$field['user_nickname'] = $this->login_user['user_nickname'];
		$field['m_date'] ="MY_F:NOW()";
		
		$rs = $this->msgModel->saveMsg($field,'msg_box');
		if($rs){
			$msg = array('s'=> 200,'m'=>'ok','d'=>'/index.php/msgbox/lists');				
			exit(json_output($msg));
		}else{
			$msg = array('s'=> 400,'m'=>lang('failed'),'d'=>'');				
			exit(json_output($msg));
			
		}
	}
	
	function op_reply(){
		$msg = '';
		$msg_id = intval($_POST['msg_id']);
		$field['m_pid'] = $msg_id;
		$field['m_content'] = strip_tags($_POST['reply']);
		$field['user_id'] = $this->login_user['user_id'];
		$field['user_nickname'] = $this->login_user['user_nickname'];
		$field['m_date'] ="MY_F:NOW()";
		
		$rs = $this->msgModel->saveMsg($field,'msg_box');
		if($rs){
			$msg = array('s'=> 200,'m'=>'ok','d'=>'/index.php/msgbox/read/'.$msg_id);				
			exit(json_output($msg));
		}else{
			$msg = array('s'=> 400,'m'=>lang('failed'),'d'=>'');				
			exit(json_output($msg));
			
		}
	}
	
	function op_delete(){
		$t = true;
    	if(isset($_POST['id']) && is_array($_POST['id'])){
    		$t =  $this->msgModel->deleteMsg($_POST['id']);
    		if($t) show_message_goback(lang('success'));
    	}
    	show_message(lang('selectone'));
    	goback();
	}
}
?>