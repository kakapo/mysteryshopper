<?php
class corporation{
	function __construct(){
		global $tpl;	
		$this->tpl = $tpl;
		$user = authenticate();	
		if(isset($user['user']) && $user['user_id']==1){
			$tpl->assign('user',$user);
		}else{
			redirect(BASE_URL);
		}
		
	}
    function view_defaults(){
		$cur_sort = !empty($_GET['sort'])?$_GET['sort']:'c_id';
		$c_name = !empty($_GET['c_name'])?$_GET['c_name']:'';
		$c_id = !empty($_GET['c_id'])?$_GET['c_id']:'';
		$c_contacter = !empty($_GET['c_contacter'])?$_GET['c_contacter']:'';
		$c_title = !empty($_GET['c_title'])?$_GET['c_title']:'';
		
		$con['order'] = $cur_sort;
		$con['c_name'] = $c_name;
		$con['c_id'] = $c_id;
		$con['c_contacter'] = $c_contacter;
		$con['c_title'] = $c_title;
		
		include_once("CorporationModel.class.php");
		$corporationModel = new CorporationModel();
				
		$corporations = $corporationModel->getItems($con,10);
		$this->tpl->assign('total',$corporations['page']->total);
		$this->tpl->assign('corporations',$corporations);
		
    }   

	function view_search(){
		$cur_sort = !empty($_GET['sort'])?$_GET['sort']:'c_id';
		$c_name = !empty($_GET['c_name'])?$_GET['c_name']:'';
		$c_id = !empty($_GET['c_id'])?$_GET['c_id']:'';
		$c_contacter = !empty($_GET['c_contacter'])?$_GET['c_contacter']:'';
		$c_title = !empty($_GET['c_title'])?$_GET['c_title']:'';
		
		$con['order'] = $cur_sort;
		$con['c_name'] = $c_name;
		$con['c_id'] = $c_id;
		$con['c_contacter'] = $c_contacter;
		$con['c_title'] = $c_title;
		$this->tpl->assign('con',$con);
	}
	function view_add(){
		
	}
    function op_addcorporation(){
    	$msg = '';
		
				
		$pattern = "/^[a-zA-Z][a-zA-Z0-9_]{1,13}[a-zA-Z0-9]$/i";
		
		$_POST ['c_name'] = trim ( $_POST ['c_name'] );
		$c_name_len = mb_strlen ( $_POST ['c_name'], "UTF-8");
		if (empty ( $_POST ['c_name'] ) || ! preg_match ( $pattern, $_POST ['c_name'] ) || $c_name_len < 2 || $c_name_len > 16 ) {
			//$msg = array('s'=> 400,'m'=>lang('cnamerule'),'d'=>'');				
			//exit(json_output($msg));
			show_message(lang('cnamerule'));
			goback();
		}
		
		if (strlen($_POST ['c_password']) <6) {
			show_message(lang('pwdrule'));
			goback();
			//$msg = array('s'=> 400,'m'=>lang('pwdrule'),'d'=>'');				
			//exit(json_output($msg));
		}
		
		include_once("CorporationModel.class.php");
		$corpmod = new CorporationModel();
	
		if($corpmod->checkName($_POST ['c_name'])){
			show_message(lang('cnameexist'));
			goback();
//			$msg = array('s'=> 400,'m'=>lang('cnameexist'),'d'=>'');				
//			exit(json_output($msg));
		}	

		$corporation['c_password'] = md5(md5($_POST ['c_password']));
		$corporation['c_name'] =  $_POST ['c_name'] ;
		$corporation['c_initial'] =  $_POST ['c_initial'] ;
		$corporation['c_title'] = empty($_POST ['c_title'])?"":addslashes($_POST ['c_title']);
		$corporation['c_contacter'] = empty($_POST ['c_contacter'])?"":addslashes($_POST ['c_contacter']);
		$corporation['c_phone'] = empty($_POST ['c_phone'])?"":addslashes($_POST ['c_phone']);
		$corporation['c_intro'] =empty($_POST ['c_intro'])?"":strip_tags($_POST ['c_intro']);
		if(isset($_FILES['c_logo']) && $_FILES['c_logo']['error']==0){
			$img_ext = substr($_FILES['c_logo']['name'],strrpos($_FILES['c_logo']['name'],'.'));
			$corporation['c_logo'] = "logo_".uniqid().$img_ext;
			move_uploaded_file($_FILES['c_logo']['tmp_name'],APP_DIR."/public/upload/logo/".$corporation['c_logo']);
    	}
    			
		// 1. create db corporation
		$row = $corpmod->createNewCorporation ( $corporation );
		if ($row !== false) {
			//$msg = array('s'=> 200,'m'=>'ok','d'=>$GLOBALS ['gSiteInfo'] ['www_site_url']."/admin.php/corporation/defaults");				
			//exit(json_output($msg));
			show_message("保存成功!");
			redirect("/admin.php/corporation/defaults");
								
		}
    }
 
    function op_delcorporation(){
    	$t = true;
    	if(isset($_POST['delete']) && is_array($_POST['delete'])){
    		include_once("CorporationModel.class.php");
    		$corporation = new CorporationModel();
    		foreach ($_POST['delete'] as $u){
    			
    			$t *= $corporation->deleteCorporation($u);
    			
    		}
    		
    		if($t) show_message_goback(lang('success'));
    	}
    	show_message(lang('selectone'));
    	goback();
    }
    function view_detail(){
    	$corporation = $_GET['corporation'];
    	$c_id = $_GET['c_id'];
    	
    	include_once("CorporationModel.class.php");
    	$corporation = new CorporationModel();
    	$corporationinfo = $corporation->getcorporationById($c_id,$corporation);
    	if($corporationinfo){
    		$corporationinfo['corporation_lastlogin_time'] = date("y-m-d H:i:s",$corporationinfo['corporation_lastlogin_time']);
    		if($corporationinfo['corporation_sex']==1) $corporationinfo['corporation_sex'] = lang('boy');
    		if($corporationinfo['corporation_sex']==2) $corporationinfo['corporation_sex'] = lang('girl');
    		$msg = array('s'=> 200,'m'=>'','d'=>$corporationinfo);				
			exit(json_output($msg));
    	}else{
    		$msg = array('s'=> 400,'m'=>lang('corporationnotexist'),'d'=>'');				
			exit(json_output($msg));
    	}
    }
    
    function view_edit(){
  
    	$c_id = $_GET['c_id'];
    	include_once("CorporationModel.class.php");
    	$corporation = new CorporationModel();
    	$info = $corporation->getCorporationById($c_id);
    	$this->tpl->assign('info',$info);
    }
    function op_updatecorporation(){
    	$msg = '';
		if(!empty($_POST ['c_password']) && $_POST ['c_password']!=$_POST ['c_password2'] ){
			show_message(lang('pwdnotsame'));
			goback();
			//$msg = array('s'=> 400,'m'=>lang('pwdnotsame'),'d'=>'');				
			//exit(json_output($msg));
		}else{
			if (!empty($_POST ['c_password']) && strlen($_POST ['c_password']) <6) {
				show_message(lang('pwdrule'));
				goback();
				//$msg = array('s'=> 400,'m'=>lang('pwdrule'),'d'=>'');				
				//exit(json_output($msg));
			}else if(!empty($_POST ['c_password'])){
				$updates['c_password'] = md5( $_POST ['c_password']);
			}
			
		}

		$c_id = $_POST['c_id'];

		include_once("CorporationModel.class.php");
		$corpmod = new CorporationModel();
	
		$updates['c_title'] = empty($_POST ['c_title'])?"":addslashes($_POST ['c_title']);
		$updates['c_initial'] = empty($_POST ['c_initial'])?"":addslashes($_POST ['c_initial']);
		$updates['c_contacter'] = empty($_POST ['c_contacter'])?"":addslashes($_POST ['c_contacter']);
		$updates['c_phone'] = empty($_POST ['c_phone'])?"":addslashes($_POST ['c_phone']);
		$updates['c_intro'] =empty($_POST ['c_intro'])?"":strip_tags($_POST ['c_intro']);
		//print_r($_FILES);die;
		if(isset($_FILES['c_logo']) && $_FILES['c_logo']['error']==0){
			$img_ext = substr($_FILES['c_logo']['name'],strrpos($_FILES['c_logo']['name'],'.'));
			$updates['c_logo'] = "logo_".uniqid().$img_ext;
			move_uploaded_file($_FILES['c_logo']['tmp_name'],APP_DIR."/public/upload/logo/".$updates['c_logo']);
    	}
    			
		
		// 1. update db corporation
		$row = $corpmod->updateCorporation( $updates, $c_id);
		if ($row !== false) {

//			$msg = array('s'=> 200,'m'=>lang('success'),'d'=>'');				
//			exit(json_output($msg));
			show_message("保存成功！");
			goback(1000);
								
		}else{
			show_message("保存失败！");
			goback();
			//$msg = array('s'=> 400,'m'=>lang('failed'),'d'=>'');				
			//exit(json_output($msg));
		}
    }
       
     
    function view_store(){
    	$c_id = $_GET['c_id'];
    	include_once("CorporationModel.class.php");
    	$corpmod = new CorporationModel();
		$stores  = $corpmod->getStoreByCid($c_id);
		$corp  = $corpmod->getCorporationById($c_id);
		$total = count($stores);
		$this->tpl->assign('stores',$stores);
		$this->tpl->assign('corp',$corp);
		$this->tpl->assign('total',$total);
    }
    function view_addstore(){
    	$corps = array();
    	include_once("CorporationModel.class.php");
		$corpmod = new CorporationModel();
		$corps  = $corpmod->getAllCorps();
		$this->tpl->assign('corps',$corps);
    }
    function op_savestore(){
    	$msg = '';
    	if (empty ( $_POST ['cs_name'] ) ) {
			$msg = array('s'=> 400,'m'=>lang('cs_name_rule'),'d'=>'');				
			exit(json_output($msg));
		}
		
		$store['cs_name'] =  addslashes($_POST ['cs_name']) ;
		$store['cs_abbr'] =  addslashes($_POST ['cs_abbr']) ;
		$store['c_id'] =  intval($_POST ['c_id']) ;
		$store['cs_address'] = empty($_POST ['cs_address'])?"":addslashes($_POST ['cs_address']);
		
		include_once("CorporationModel.class.php");
		$corpmod = new CorporationModel();
		// 1. create db corporation
		$row = $corpmod->createNewStore ( $store );
		if ($row !== false) {
			$msg = array('s'=> 200,'m'=>'ok','d'=>$GLOBALS ['gSiteInfo'] ['www_site_url']."/admin.php/corporation/store/c_id/".$store['c_id']);				
			exit(json_output($msg));
								
		}
    }
    function op_delstore(){
    	$t = true;
    	if(isset($_POST['delete']) && is_array($_POST['delete'])){
    		include_once("CorporationModel.class.php");
    		$corporation = new CorporationModel();
    		foreach ($_POST['delete'] as $u){
    			
    			$t *= $corporation->deleteStore($u);
    			
    		}
    		
    		if($t) show_message_goback(lang('success'));
    	}
    	show_message(lang('selectone'));
    	goback();
    }
    function view_editstore(){
    	$cs_id = $_GET['cs_id'];
    	$corps = array();
    	include_once("CorporationModel.class.php");
		$corpmod = new CorporationModel();
		$corps  = $corpmod->getAllCorps();
		$store  = $corpmod->getStoreById($cs_id);
		$this->tpl->assign('cs_id',$cs_id);
		$this->tpl->assign('store',$store);
		$this->tpl->assign('corps',$corps);
    }
    function op_updatestore(){
    	include_once("CorporationModel.class.php");
		$corpmod = new CorporationModel();
		$msg = '';
    	if (empty ( $_POST ['cs_name'] ) ) {
			$msg = array('s'=> 400,'m'=>lang('cs_name_rule'),'d'=>'');				
			exit(json_output($msg));
		}
		$cs_id = $_POST['cs_id'];
		$store['cs_name'] =  addslashes($_POST ['cs_name']) ;
		$store['cs_abbr'] =  addslashes($_POST ['cs_abbr']) ;
		$store['c_id'] =  intval($_POST ['c_id']) ;
		$store['cs_address'] = empty($_POST ['cs_address'])?"":addslashes($_POST ['cs_address']);
		// 1. update db corporation
		$row = $corpmod->updateStore( $store, $cs_id);
		if ($row !== false) {

			$msg = array('s'=> 200,'m'=>lang('success'),'d'=>$GLOBALS ['gSiteInfo'] ['www_site_url']."/admin.php/corporation/store/c_id/".$store['c_id']);				
			exit(json_output($msg));
								
		}else{
			$msg = array('s'=> 400,'m'=>lang('failed'),'d'=>'');				
			exit(json_output($msg));
		}
    }
    function view_ajaxstore(){
    	$c_id = $_GET['c_id'];
    	include_once("CorporationModel.class.php");
    	$corpmod = new CorporationModel();
		$stores  = $corpmod->getStoreByCid($c_id);
    	$msg = array('s'=> 200,'m'=>'ok','d'=>json_encode($stores));				
		exit(json_output($msg));
    }
}
?>