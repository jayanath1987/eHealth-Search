<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
--------------------------------------------------------------------------------
HHIMS - Hospital Health Information Management System
Copyright (c) 2011 Information and Communication Technology Agency of Sri Lanka
<http: www.hhims.org/>
----------------------------------------------------------------------------------
This program is free software: you can redistribute it and/or modify it under the
terms of the GNU Affero General Public License as published by the Free Software 
Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,but WITHOUT ANY 
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR 
A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License along 
with this program. If not, see <http://www.gnu.org/licenses/> 
---------------------------------------------------------------------------------- 
Date : June 2016
Author: Mr. Jayanath Liyanage   jayanathl@icta.lk

Programme Manager: Shriyananda Rathnayake
URL: http://www.govforge.icta.lk/gf/project/hhims/
__________________________________________________________________________________
SNOMED Modification :

Date : July 2015		ICT Agency of Sri Lanka (www.icta.lk), Colombo
Author : Laura Lucas
Programme Manager: Shriyananda Rathnayake
Supervisors : Jayanath Liyanage, Erandi Hettiarachchi
URL: http://www.govforge.icta.lk/gf/project/hhims/
----------------------------------------------------------------------------------
*/
class Search extends MX_Controller {
	 function __construct(){
		parent::__construct();
		$this->checkLogin();
		$this->load->library('session');
		$this->load->helper('text');
		if(isset($_GET["mid"])){
			$this->session->set_userdata('mid', $_GET["mid"]);
		}
	 }

	public function index()
	{	
		return;
	}

            private function loadMDSPager($fName) {
        $path='application/forms/' . $fName . '.php';
        require $path;
        $frm = $form;
        $columns = $frm["LIST"];
        $table = $frm["TABLE"];
        $sql = "SELECT ";

        foreach ($columns as $column) {
            $sql.=$column . ',';
        }
        $sql = substr($sql, 0, -1);
        $sql.=" FROM $table ";
        $this->load->model('mpager');
        $this->mpager->setSql($sql);
        $this->mpager->setDivId('snomed_search');
        $this->mpager->setSortorder('asc');
        //set colun headings
        $colNames = array();
        foreach ($frm["DISPLAY_LIST"] as $colName) {
            array_push($colNames, $colName);
        }
        $this->mpager->setColNames($colNames);

        //set captions
        $this->mpager->setCaption($frm["CAPTION"]);
        //set row id
        $this->mpager->setRowid($frm["ROW_ID"]);

        //set column models
        foreach ($frm["COLUMN_MODEL"] as $columnName => $model) {
            if (gettype($model) == "array") {
                $this->mpager->setColOption($columnName, $model);
            }
        }

        $conceptName='';
        $termName='';
        foreach($this->mpager->getColModelJSAR() as $key=>$value){
            if($key=='"CONCEPTID"'){
                $conceptName=$value->getName();
            }
            if($key=='"TERM"'){
                $termName=$value->getName();
            }


        }

        //set actions
        $action = $frm["ACTION"];
        $this->mpager->gridComplete_JS = "function() {
            var c = null;
            $('.jqgrow').mouseover(function(e) {
                var rowId = $(this).attr('id');
                c = $(this).css('background');
                $(this).css({'background':'yellow','cursor':'pointer'});
            }).mouseout(function(e){
                $(this).css('background',c);
            }).click(function(e){
                var rowId = $(this).attr('id');
                var rowData = {$this->mpager->getGrid()}.jqGrid('getRowData',rowId);
                var st= rowData['snomed_text'];
                var sc= rowData['snomed_code'];
                $('#SNOMED_Text').val(rowData['$termName']);
                $('#SNOMED_Code').val(rowData['$conceptName']);
                $('#snomedDiv').modal('toggle');
            });
            }";

        //report starts
        if(isset($frm["ORIENT"])){
            $this->mpager->setOrientation_EL($frm["ORIENT"]);
        }
        if(isset($frm["TITLE"])){
            $this->mpager->setTitle_EL($frm["TITLE"]);
        }
        $this->mpager->setWidth('540');
        $this->mpager->setColHeaders_EL(isset($frm["COL_HEADERS"])?$frm["COL_HEADERS"]:$frm["DISPLAY_LIST"]);
        //report endss

        return $this->mpager->render(false);
    }
	
	public function sample_order(){
      $qry = "SELECT 
	  lab_order.LAB_ORDER_ID,
	  lab_order.OrderDate,
	  patient.HIN as HIN, 
	  CONCAT(patient.Full_Name_Registered,' ', patient.Personal_Used_Name),
	  lab_order.TestGroupName,
	  lab_order.Priority,
	  lab_order.Collection_Status
	  
	  from lab_order 
	  LEFT JOIN `patient` ON patient.PID = lab_order.PID 
	  where (lab_order.Active =1)
	  
			";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
        $page->setRowid('LAB_ORDER_ID');
        $page->setCaption("Sample collection list");
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("","Date", "HIN", "Patient","Test","Priority","Status"));
        $page->setRowNum(25);
        $page->setColOption("LAB_ORDER_ID", array("search" => false, "hidden" => true));
        $page->setColOption("HIN", array("search" => true, "hidden" => false));
	$page->setColOption("OrderDate", array("search" => true, "hidden" => false ));
        $page->setColOption("OrderDate", $page->getDateSelector(date("Y-m-d")));
        //$page->setColOption("patient_name", array("search" => true, "hidden" => false));
        
         $page->setColOption(
            "Priority", array("stype"         => "select",
                                           "searchoptions" => array("value"        => ":All;Normal:Normal;Urgent:Urgent;UnKnown:UnKnown","defaultValue" => "Normal"))
        );
        
         $page->setColOption(
            "Collection_Status", array("stype"         => "select",
                                           "searchoptions" => array("value"        => ":All;Pending:Pending;Done:Done;UnKnown:UnKnown","defaultValue" => "Pending"))
        );
        //$page->setColOption("Collection_Status", array("search" => false, "hidden" => false));
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/form/edit/lab_order_update")."/'+rowId+'?CONTINUE=search/sample_order';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/sample_order');		
	}
        
        public function opd_sample_order(){
      $qry = "SELECT 
	  patient.HIN as HIN, 
	  lab_order.LAB_ORDER_ID,
	  lab_order.OrderDate,
	  patient.PID as PID, 
	  CONCAT(patient.Full_Name_Registered,' ', patient.Personal_Used_Name)  ,
	  lab_order.TestGroupName,
	  lab_order.Priority,
	  lab_order.Collection_Status
	  
	  from lab_order 
	  LEFT JOIN `patient` ON patient.PID = lab_order.PID 
	  where (lab_order.Active =1 AND lab_order.Dept = 'OPD')
	  
			";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
        $page->setRowid('LAB_ORDER_ID');
        $page->setCaption("OPD Sample Collection List");
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("HIN","HIN","Date", "OrderID", "Patient","Test","Priority","Status"));
        $page->setRowNum(25);
        $page->setColOption("LAB_ORDER_ID", array("search" => false, "hidden" => true));
        $page->setColOption("HIN", array("search" => true, "hidden" => false));
        $page->setColOption("PID", array("search" => true, "hidden" => false));
		
        //$page->setColOption("patient_name", array("search" => true, "hidden" => false));
		$page->setColOption("OrderDate", $page->getDateSelector(date("Y-m-d")));
        $page->setColOption("Collection_Status", array("stype" => "select", "searchoptions" => array("value" => ":All;Pending:Pending;Done:Done","defaultValue"=>"Pending")));
	
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/form/edit/lab_order_update")."/'+rowId+'?CONTINUE=search/sample_order';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/sample_order');		
	}


public function clinic_sample_order(){
      $qry = "SELECT 
	  patient.HIN as HIN, 
	  lab_order.LAB_ORDER_ID,
	  lab_order.OrderDate,
	  patient.PID as PID, 
	  CONCAT(patient.Full_Name_Registered,' ', patient.Personal_Used_Name) ,
	  lab_order.TestGroupName,
	  lab_order.Priority,
	  lab_order.Collection_Status
	  
	  from lab_order 
	  LEFT JOIN `patient` ON patient.PID = lab_order.PID 
	  where (lab_order.Active =1 AND lab_order.Dept = 'CLN')
	  
			";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
        $page->setRowid('LAB_ORDER_ID');
        $page->setCaption("Clinic Sample Collection List");
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("HIN","HIN","Date", "OrderID", "Patient","Test","Priority","Status"));
        $page->setRowNum(25);
        $page->setColOption("LAB_ORDER_ID", array("search" => false, "hidden" => true));
        $page->setColOption("HIN", array("search" => true, "hidden" => false));
        $page->setColOption("PID", array("search" => true, "hidden" => false));
		
       // $page->setColOption("patient_name", array("search" => true, "hidden" => false));
		$page->setColOption("OrderDate", $page->getDateSelector(date("")));
        $page->setColOption("Collection_Status", array("stype" => "select", "searchoptions" => array("value" => ":All;Pending:Pending;Done:Done","defaultValue"=>"Pending")));
	
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/form/edit/lab_order_update")."/'+rowId+'?CONTINUE=search/clinic_sample_order';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/sample_order');		
	}
        
                public function adm_sample_order($wid){
      $qry = "SELECT 
	  patient.HIN as HIN, 
	  lab_order.LAB_ORDER_ID,
	  lab_order.OrderDate,
	  patient.PID as PID, 
	  CONCAT(patient.Full_Name_Registered,' ', patient.Personal_Used_Name)  ,
	  lab_order.TestGroupName,
	  lab_order.Priority,
	  lab_order.Collection_Status
	  
	  from lab_order 
	  LEFT JOIN `patient` ON patient.PID = lab_order.PID
          LEFT JOIN `admission` ON admission.ADMID = lab_order.OBJID
	  where (lab_order.Active =1) AND (lab_order.Dept = 'ADM') AND (admission.Ward='$wid')
	  
			";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
        $page->setRowid('LAB_ORDER_ID');
        $page->setCaption("Sample Collection List");
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("HIN","HIN","Date", "OrderID", "Patient","Test","Priority","Status"));
        $page->setRowNum(25);
        $page->setColOption("LAB_ORDER_ID", array("search" => false, "hidden" => true));
        $page->setColOption("HIN", array("search" => true, "hidden" => false));
        $page->setColOption("PID", array("search" => true, "hidden" => false));
		
        //$page->setColOption("patient_name", array("search" => true, "hidden" => false));
		$page->setColOption("OrderDate", $page->getDateSelector(date("Y-m-d")));
        $page->setColOption("Collection_Status", array("stype" => "select", "searchoptions" => array("value" => ":All;Pending:Pending;Done:Done","defaultValue"=>"Pending")));
	
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/form/edit/lab_order_update")."/'+rowId+'?CONTINUE=search/adm_sample_order/$wid';
        });
        }";
        $page->setOrientation_EL("L");
        $this->load->model('mpersistent');
	$data["ward_info"] = $this->mpersistent->open_id($wid,"ward","WID");
	$data['pager'] = $page->render(false);
	$this->load->vars($data);
        
       $this->load->view('search/adm_sample_order');		
	}
        
	public function ward(){
      $qry = "SELECT 
	  WID,
	  Name,
	  Telephone,
	  BedCount, 
	  Remarks 	  
	  from ward 
	  where (Active =1)
	  
			";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("ward_list"); //important
        $page->setDivClass('');
        $page->setRowid('WID');
        $page->setCaption("Ward list");
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("WID","Name", "Telephone", "BedCount","Remarks"));
        $page->setRowNum(25);
        $page->setColOption("WID", array("search" => false, "hidden" => true));
        $page->setColOption("Telephone", array("search" => true, "hidden" => false));
		$page->setColOption("BedCount", array("search" => true, "hidden" => false ));
        $page->setColOption("Remarks", array("search" => true, "hidden" => false));
        $page->gridComplete_JS
            = "function() {
        $('#ward_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/ward/view")."/'+rowId+'?CONTINUE=search/ward';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/ward_list');		
	}
		
	public function lab_orders($dept=null){
		  $qry = "SELECT 
		  lab_order.LAB_ORDER_ID,
		  lab_order.OrderDate,
		  patient.HIN as HIN, 
		  CONCAT(patient.Full_Name_Registered,' ', patient.Personal_Used_Name),
		  lab_order.TestGroupName,
		  lab_order.Priority,
		  lab_order.Collection_Status,
		  lab_order.Status
		  
		  from lab_order 
		  LEFT JOIN `patient` ON patient.PID = lab_order.PID 
		  where (lab_order.Active =1)
		
			";
		if ($dept){
			$qry .= ' and (Dept = "'.$dept.'")';
		}
		else{
			$qry .= ' and (Dept = "OPD")';
		}
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
        $page->setRowid('LAB_ORDER_ID');
		if ($dept){
			$data["table_title"]="Lab order list";
			$page->setCaption($data["table_title"]);
		}
		else{
			$data["table_title"]="OPD lab order list";
			$page->setCaption($data["table_title"]);
		}
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("Lab ID","Date", "HIN", "Patient","Test","Priority","Collection Status","Result Status"));
        $page->setRowNum(25);
       // $page->setColOption("Dept", array("search" => false, "hidden" => false,"width"=>"30px"));
        $page->setColOption("LAB_ORDER_ID", array("search" => true, "hidden" => false,"width"=>"60px"));
        $page->setColOption("HIN", array("search" => true, "hidden" => false,"width"=>"100px"));
        $page->setColOption("OrderDate", array("search" => true, "hidden" => false ));
        $page->setColOption("OrderDate", $page->getDateSelector());
		//$page->setColOption("OrderDate", array("search" => true, "hidden" => false,"width"=>"100px" ));
       // $page->setColOption("patient_name", array("search" => true, "hidden" => false));
         $page->setColOption(
            "Collection_Status", array("stype"         => "select",
                                           "searchoptions" => array("value"        => ":All;Pending:Pending;Done:Done;UnKnown:UnKnown","defaultValue" => "All"))
        );
          $page->setColOption(
            "Priority", array("stype"         => "select",
                                           "searchoptions" => array("value"        => ":All;Normal:Normal;Urgent:Urgent;UnKnown:UnKnown","defaultValue" => "Normal"))
        );
            $page->setColOption(
            "Status", array("stype"         => "select",
                                           "searchoptions" => array("value"        => ":All;Pending:Pending;Available:Available;Received:Received;UnKnown:UnKnown","defaultValue" => "Pending"))
        );
        
       // $page->setColOption("Collection_Status", array("search" => false, "hidden" => false));
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("laboratory/process_result/")."/'+rowId+'?CONTINUE=search/lab_orders/".$dept."';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/lab_order');		
	}

        	public function adm_lab_orders(){
		  $qry = "SELECT 
		  lab_order.LAB_ORDER_ID,
                  admission.BHT,
                  ward.Name,
                  lab_order.OrderDate,
		  patient.HIN as HIN, 
		  CONCAT(patient.Full_Name_Registered,' ', patient.Personal_Used_Name),
		  lab_order.TestGroupName,
		  lab_order.Priority,
		  lab_order.Collection_Status,
		  lab_order.Status
		  from lab_order 
		  LEFT JOIN `patient` ON patient.PID = lab_order.PID 
                  LEFT JOIN `admission` ON admission.ADMID = lab_order.OBJID
                  LEFT JOIN `ward` ON admission.Ward = ward.WID
		  where (lab_order.Active =1) and (Dept = 'ADM') ";

        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
        $page->setRowid('LAB_ORDER_ID');

        $data["table_title"]="Admission lab order list";
	$page->setCaption($data["table_title"]);
		
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("Sample ID","BHT","Ward","Date", "HIN", "Patient","Test","Priority","Collection Status","Result Status"));
        $page->setRowNum(25);
       // $page->setColOption("Dept", array("search" => false, "hidden" => false,"width"=>"30px"));
        $page->setColOption("LAB_ORDER_ID", array("search" => true, "hidden" => false,"width"=>"60px"));
        $page->setColOption("HIN", array("search" => true, "hidden" => false,"width"=>"100px"));
        $page->setColOption("OrderDate", array("search" => true, "hidden" => false ));
        $page->setColOption("OrderDate", $page->getDateSelector());
		//$page->setColOption("OrderDate", array("search" => true, "hidden" => false,"width"=>"100px" ));
       // $page->setColOption("patient_name", array("search" => true, "hidden" => false));
         $page->setColOption(
            "Collection_Status", array("stype"         => "select",
                                           "searchoptions" => array("value"        => ":All;Pending:Pending;Done:Done;UnKnown:UnKnown","defaultValue" => "All"))
        );
          $page->setColOption(
            "Priority", array("stype"         => "select",
                                           "searchoptions" => array("value"        => ":All;Normal:Normal;Urgent:Urgent;UnKnown:UnKnown","defaultValue" => "Normal"))
        );
            $page->setColOption(
            "Status", array("stype"         => "select",
                                           "searchoptions" => array("value"        => ":All;Pending:Pending;Available:Available;Received:Received;UnKnown:UnKnown","defaultValue" => "Pending"))
        );
        
       // $page->setColOption("Collection_Status", array("search" => false, "hidden" => false));
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("laboratory/process_result/")."/'+rowId+'?CONTINUE=search/adm_lab_orders';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/lab_order');		
	}
	
		public function select_list($repid){
      $qry = "SELECT qu_select_id,select_text,select_value,help,	CreateUser,active
	  from qu_select 

	  where (qu_question_id	 ='$repid')
	  
			";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("select_list"); //important
        $page->setDivClass('');
        $page->setRowid('qu_select_id');
        $page->setCaption("Select option list");
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("","Name", "Value", "help","CreateUser","active"));
        $page->setRowNum(25);
        $page->setColOption("qu_select_id", array("search" => false, "hidden" => true));
        $page->setColOption("select_text", array("search" => true, "hidden" => false));
		$page->setColOption("select_value", array("search" => true, "hidden" => false));
		$page->setColOption("help", array("search" => true, "hidden" => false));
		$page->setColOption("CreateUser", array("search" => true, "hidden" => false));
		$page->setColOption("active", array("search" => true, "hidden" => false));
        $page->gridComplete_JS
            = "function() {
        $('#select_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/form/edit/qu_select")."/'+rowId+'?CONTINUE=search/select_list/".$repid."';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$data['repid'] = $repid;
		$this->load->vars($data);
        $this->load->view('search/select_list');	
	}	
	
	
	public function procedures_order(){
      $qry = "SELECT 
	  opd_treatment.OPDTREATMENTID,
	  opd_treatment.CreateDate,
	  patient.HIN as HIN, 
	  CONCAT(patient.Full_Name_Registered,' ', patient.Personal_Used_Name)  ,
	  treatment.Treatment,
	  opd_treatment.Status
	  from opd_treatment 
	  LEFT JOIN `opd_visits` ON opd_visits.OPDID = opd_treatment.OPDID 
	  LEFT JOIN `patient` ON patient.PID = opd_visits.PID
          LEFT JOIN `treatment` ON treatment.TREATMENTID = opd_treatment.TREATMENTID
	  where (opd_treatment.Active =1)
	  
			";
        $this->load->model('mpager',"page");
        
      
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
        $page->setRowid('OPDTREATMENTID');
        $page->setCaption("Procedure order list");
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("","Date", "HIN", "Patient","Treatment","Status"));
        $page->setRowNum(25);
        $page->setColOption("OPDTREATMENTID", array("search" => false, "hidden" => true));
        $page->setColOption("HIN", array("search" => true, "hidden" => false));
	$page->setColOption("CreateDate", array("search" => true, "hidden" => false ));
        $page->setColOption("CreateDate", $page->getDateSelector(date("Y-m-d")));
        //$page->setColOption("patient_name", array("search" => true, "hidden" => false));
        
        $page->setColOption(
            "Status", array("stype"         => "select",
                                           "searchoptions" => array("value"        => ":All;Pending:Pending;Done:Done;UnKnown:UnKnown","defaultValue" => "Pending"))
        );
        
        //$page->setColOption("Status", array("search" => false, "hidden" => false));
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/form/edit/opd_treatment_update")."/'+rowId+'?CONTINUE=search/procedures_order';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/procedures_order');	
	}	
	
	
        	public function clinic_procedure_order(){
      $qry = "
			  SELECT 
			  clinic_treatment.clinic_treatment_id,
			  clinic_treatment.CreateDate,
			  patient.PID as PID, 
			  CONCAT(patient.Full_Name_Registered,' ', patient.Personal_Used_Name)  ,
			  clinic_treatment.Treatment,
			  clinic_treatment.Status
			  from clinic_treatment 
			  LEFT JOIN `clinic_visits` ON clinic_visits.clinic_visits_id = clinic_treatment.clinic_visits_id 
			  LEFT JOIN `patient` ON patient.PID = clinic_visits.PID 
			  where (clinic_treatment.Active =1 AND clinic_treatment.Treatment != 'X-Ray' AND clinic_treatment.Treatment != 'ECG' )
		";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
        $page->setRowid('clinic_treatment_id');
        $page->setCaption("Clnic Procedure order list");
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("","Date", "ID", "Patient","Treatment","Status"));
        $page->setRowNum(25);
        $page->setColOption("clinic_treatment_id", array("search" => false, "hidden" => true));
        $page->setColOption("PID", array("search" => true, "hidden" => false));
		//$page->setColOption("patient_name", array("search" => true, "hidden" => false));
		 $page->setColOption("CreateDate", $page->getDateSelector(date("Y-m-d")));
        $page->setColOption("Status", array("stype" => "select", "searchoptions" => array("value" => ":All;Pending:Pending;Done:Done","defaultValue"=>"Pending")));
        
        $page->gridComplete_JS
            = "function() {
				$('#patient_list .jqgrow').mouseover(function(e) {
					var rowId = $(this).attr('id');
					$(this).css({'cursor':'pointer'});
				}).mouseout(function(e){
				}).click(function(e){
					var rowId = $(this).attr('id');
					window.location='".site_url("/form/edit/clinic_treatment_update")."/'+rowId+'?CONTINUE=search/clinic_procedure_order';
				});
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/procedures_order');	
	}


public function clinic_x_ray_order(){
      $qry = "
			  SELECT 
			  clinic_treatment.clinic_treatment_id,
			  clinic_treatment.CreateDate,
			  patient.PID as PID, 
			  CONCAT(patient.Full_Name_Registered,' ', patient.Personal_Used_Name) ,
			  clinic_treatment.Treatment,
			  clinic_treatment.Status
			  from clinic_treatment 
			  LEFT JOIN `clinic_visits` ON clinic_visits.clinic_visits_id = clinic_treatment.clinic_visits_id 
			  LEFT JOIN `patient` ON patient.PID = clinic_visits.PID 
			  where (clinic_treatment.Active =1 AND clinic_treatment.Treatment = 'X-Ray')
		";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
        $page->setRowid('clinic_treatment_id');
        $page->setCaption("Clnic X-Ray order list");
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("","Date", "ID", "Patient","Treatment","Status"));
        $page->setRowNum(25);
        $page->setColOption("clinic_treatment_id", array("search" => false, "hidden" => true));
        $page->setColOption("PID", array("search" => true, "hidden" => false));
		//$page->setColOption("patient_name", array("search" => true, "hidden" => false));
		 $page->setColOption("CreateDate", $page->getDateSelector(date("")));
        $page->setColOption("Status", array("stype" => "select", "searchoptions" => array("value" => ":All;Pending:Pending;Done:Done","defaultValue"=>"Pending")));
        
        $page->gridComplete_JS
            = "function() {
				$('#patient_list .jqgrow').mouseover(function(e) {
					var rowId = $(this).attr('id');
					$(this).css({'cursor':'pointer'});
				}).mouseout(function(e){
				}).click(function(e){
					var rowId = $(this).attr('id');
					window.location='".site_url("/form/edit/clinic_treatment_update")."/'+rowId+'?CONTINUE=search/clinic_x_ray_order';
				});
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/procedures_order');	
	}	
	

public function clinic_ecg_order(){
      $qry = "
			  SELECT 
			  clinic_treatment.clinic_treatment_id,
			  clinic_treatment.CreateDate,
			  patient.PID as PID, 
			  CONCAT(patient.Full_Name_Registered,' ', patient.Personal_Used_Name) ,
			  clinic_treatment.Treatment,
			  clinic_treatment.Status
			  from clinic_treatment 
			  LEFT JOIN `clinic_visits` ON clinic_visits.clinic_visits_id = clinic_treatment.clinic_visits_id 
			  LEFT JOIN `patient` ON patient.PID = clinic_visits.PID 
			  where (clinic_treatment.Active =1 AND clinic_treatment.Treatment = 'ECG')
		";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
        $page->setRowid('clinic_treatment_id');
        $page->setCaption("Clnic ECG order list");
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("","Date", "ID", "Patient","Treatment","Status"));
        $page->setRowNum(25);
        $page->setColOption("clinic_treatment_id", array("search" => false, "hidden" => true));
        $page->setColOption("PID", array("search" => true, "hidden" => false));
		//$page->setColOption("patient_name", array("search" => true, "hidden" => false));
		 $page->setColOption("CreateDate", $page->getDateSelector(date("")));
        $page->setColOption("Status", array("stype" => "select", "searchoptions" => array("value" => ":All;Pending:Pending;Done:Done","defaultValue"=>"Pending")));
        
        $page->gridComplete_JS
            = "function() {
				$('#patient_list .jqgrow').mouseover(function(e) {
					var rowId = $(this).attr('id');
					$(this).css({'cursor':'pointer'});
				}).mouseout(function(e){
				}).click(function(e){
					var rowId = $(this).attr('id');
					window.location='".site_url("/form/edit/clinic_treatment_update")."/'+rowId+'?CONTINUE=search/clinic_ecg_order';
				});
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/ecg_order');	
	}


public function opd_ecg_order(){
      $qry = "SELECT 
	  opd_ecg.OPDECGID,
	  opd_ecg.CreateDate,
	  patient.HIN as HIN, 
	  CONCAT(patient.Full_Name_Registered,' ', patient.Personal_Used_Name) ,
	  opd_ecg.Order_Remarks,
      opd_ecg.Result_Remarks,
	  opd_ecg.Status
	  
	  from opd_ecg 
	  LEFT JOIN `opd_visits` ON opd_visits.OPDID = opd_ecg.OPDID 
	  LEFT JOIN `patient` ON patient.PID = opd_visits.PID 
	  where (opd_ecg.Active =1 )
			";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
        $page->setRowid('OPDECGID');
        $page->setCaption("OPD ECG order list");
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("","Date", "HIN", "Patient","Order Remarks","Result Remarks","Status"));
        $page->setRowNum(25);
        $page->setColOption("OPDECGID", array("search" => false, "hidden" => true));
        $page->setColOption("HIN", array("search" => true, "hidden" => false));
		//$page->setColOption("patient_name", array("search" => true, "hidden" => false));
		 $page->setColOption("CreateDate", $page->getDateSelector(date("Y-m-d")));
        $page->setColOption("Status", array("stype" => "select", "searchoptions" => array("value" => ":All;Pending:Pending;Done:Done","defaultValue"=>"Pending")));
        
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/form/edit/opd_ecg_update")."/'+rowId+'?CONTINUE=search/opd_ecg_order';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/ecg_order');	
	}

	public function my_opd_patient(){
		$data['user_group']=$this->session->userdata('UserGroup');;
		$data['full_name']=$this->session->userdata('FullName');;
		$uid = $this->session->userdata('UID');;
      $qry = "SELECT 
	  opd_visits.OPDID as OPDID, 
	  opd_visits.DateTimeOfVisit as DateTimeOfVisit,
	  patient.HIN as HIN, 
	  CONCAT(patient.Full_Name_Registered,' ', patient.Personal_Used_Name)  ,
	  opd_visits.Complaint as Complaint 
	  from opd_visits
	  LEFT JOIN `patient` ON patient.PID = opd_visits.PID 
	  where opd_visits.Doctor = '$uid'
	";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
		 $page->setCaption("OPD patient list");
        $page->setRowid('OPDID');
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("","Date", "HIN", "Patient","Complaint"));
        $page->setRowNum(25);
        $page->setColOption("OPDID", array("search" => false, "hidden" => true));
        $page->setColOption("HIN", array("search" => true, "hidden" => false));
	$page->setColOption("DateTimeOfVisit", array("search" => true, "hidden" => false));
        $page->setColOption("DateTimeOfVisit", $page->getDateSelector(date("Y-m-d")));
        //$page->setColOption("patient_name", array("search" => true, "hidden" => false));
        $page->setColOption("Complaint", array("search" => true, "hidden" => false));
		
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/opd/view/")."/'+rowId+'?CONTINUE=doctor';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/my_opd_patient');	
	}
	
	
	public function my_clinic_patient(){
		$data['user_group']=$this->session->userdata('UserGroup');;
		$data['full_name']=$this->session->userdata('FullName');;
		$uid = $this->session->userdata('UID');;
      $qry = "SELECT 
	  clinic_visits.clinic_visits_id as clinic_visits_id, 
	  clinic_visits.DateTimeOfVisit as DateTimeOfVisit,
	  patient.HIN as HIN, 
	  CONCAT(patient.Full_Name_Registered,' ', patient.Personal_Used_Name) ,
	  clinic.name as clinic 
	  from clinic_visits
	  LEFT JOIN `patient` ON patient.PID = clinic_visits.PID 
	  LEFT JOIN `clinic` ON clinic.clinic_id = clinic_visits.clinic 
	  where clinic_visits.Doctor = '$uid'
	";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
		 $page->setCaption("Clinic patient list");
        $page->setRowid('clinic_visits_id');
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("","Date", "HIN", "Patient","Clinic"));
        $page->setRowNum(25);
        $page->setColOption("clinic_visits_id", array("search" => false, "hidden" => true));
        $page->setColOption("HIN", array("search" => true, "hidden" => false));
	$page->setColOption("DateTimeOfVisit", array("search" => true, "hidden" => false));
        $page->setColOption("DateTimeOfVisit", $page->getDateSelector(date("Y-m-d")));
        //$page->setColOption("patient_name", array("search" => true, "hidden" => false));
        $page->setColOption("clinic", array("search" => true, "hidden" => false));
		
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/clinic/visit_view/")."/'+rowId+'?CONTINUE=doctor';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/my_clinic_patient');	
	}

	public function my_lab_order($dept){
		$data['user_group']=$this->session->userdata('UserGroup');;
		$data['full_name']=$this->session->userdata('FullName');;
		$uid = $this->session->userdata('UID');;
      $qry = "SELECT 
	  lab_order.LAB_ORDER_ID as LAB_ORDER_ID, 
	  lab_order.OrderDate as OrderDate,
	  patient.HIN as HIN, 
	  CONCAT(patient.Full_Name_Registered,' ', patient.Personal_Used_Name) ,
	  lab_order.TestGroupName as lab ,
	  lab_order.Priority as Priority ,
	  lab_order.Status as Status 
	  from lab_order
	  LEFT JOIN `patient` ON patient.PID = lab_order.PID  ";
	  if ($dept == "OPD"){
		$qry .= "LEFT JOIN `opd_visits` ON opd_visits.OPDID = lab_order.OBJID 
		where ";
		$qry .= " opd_visits.Doctor = '$uid' ";
	  }
	  else if($dept == "ADM"){
	  $qry .= "LEFT JOIN `admission` ON admission.ADMID = lab_order.OBJID 
		where ";
		$qry .= " admission.Doctor = '$uid' ";
	  }
	  else if($dept == "CLN"){
		$qry .= "LEFT JOIN `clinic_visits` ON clinic_visits.clinic_visits_id = lab_order.OBJID 
		where ";
		$qry .= " clinic_visits.Doctor = '$uid' ";
	  }
	  $qry .= " and lab_order.Dept = '$dept'";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
		 $page->setCaption("OPD lab orders");
        $page->setRowid('LAB_ORDER_ID');
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("","Date", "HIN", "Patient","Test","Priority","Status"));
        $page->setRowNum(25);
        $page->setColOption("LAB_ORDER_ID", array("search" => false, "hidden" => true));
        $page->setColOption("HIN", array("search" => true, "hidden" => false));
	$page->setColOption("OrderDate", array("search" => true, "hidden" => false));
        $page->setColOption("OrderDate", $page->getDateSelector(date("Y-m-d")));
       // $page->setColOption("patient_name", array("search" => true, "hidden" => false));
        $page->setColOption("lab", array("search" => true, "hidden" => false));
        $page->setColOption("Priority", array("search" => true, "hidden" => false));
        $page->setColOption("Status", array("search" => true, "hidden" => false));
        $page->setColOption(
            "Status", array("stype"         => "select",
                                           "searchoptions" => array("value"        => ":All;Done:Done;Pending:Pending;UnKnown:UnKnown","defaultValue" => "Pending"))
        );
		
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/laboratory/order")."/'+rowId+'?CONTINUE=doctor';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/my_opd_lab');	
	}
		
	public function my_ward_patient(){
		$data['user_group']=$this->session->userdata('UserGroup');;
		$data['full_name']=$this->session->userdata('FullName');;
		$uid = $this->session->userdata('UID');;
      $qry = "SELECT 
	  admission.ADMID as ADMID, 
	  admission.AdmissionDate as AdmissionDate,
	  admission.BHT as BHT,
	  patient.HIN as HIN, 
	  CONCAT(patient.Full_Name_Registered,' ', patient.Personal_Used_Name) ,
	  admission.Complaint as Complaint ,
	  ward.name as Ward 
	  from admission
	  LEFT JOIN `patient` ON patient.PID = admission.PID 
	  LEFT JOIN `ward` ON ward.WID = admission.Ward 
	  where (admission.Doctor = '$uid')
          AND (admission.Status='Admitted')
	";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
		 $page->setCaption("Admission patient list");
        $page->setRowid('ADMID');
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("","Date", "BHT", "HIN","Patient","Complaint","Ward"));
        $page->setRowNum(25);
        $page->setColOption("ADMID", array("search" => false, "hidden" => true));
        $page->setColOption("BHT", array("search" => true, "hidden" => false));
	$page->setColOption("HIN", array("search" => true, "hidden" => false));
	$page->setColOption("AdmissionDate", array("search" => true, "hidden" => false));
        $page->setColOption("AdmissionDate", $page->getDateSelector(date("Y-m-d")));
        //$page->setColOption("patient_name", array("search" => true, "hidden" => false));
        $page->setColOption("Complaint", array("search" => true, "hidden" => false));
        $page->setColOption("Ward", array("search" => true, "hidden" => false));
		
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/admission/view/")."/'+rowId+'?CONTINUE=doctor/ward_patient';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/my_opd_patient');	
	}
        
	
	public function injection_order(){
      $qry = "SELECT 
	  patient_injection.patient_injection_id,
	  patient_injection.CreateDate,
	  patient.HIN as HIN, 
	  CONCAT(patient.Full_Name_Registered,' ', patient.Personal_Used_Name) ,
	  injection.name,
	  injection.dosage,
	  patient_injection.Status
	  from patient_injection 
	  LEFT JOIN `patient` ON patient.PID = patient_injection.PID 
	  LEFT JOIN `injection` ON injection.injection_id = patient_injection.injection_id 
	  
	  where (patient_injection.Active =1)
	  
			";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
        $page->setRowid('patient_injection_id');
        $page->setCaption("Injection order list");
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("","Date", "HIN", "Patient","Injection","Dose","Status"));
        $page->setRowNum(25);
        $page->setColOption("patient_injection_id", array("search" => false, "hidden" => true));
        $page->setColOption("HIN", array("search" => true, "hidden" => false));
	$page->setColOption("CreateDate", array("search" => true, "hidden" => false ));
        $page->setColOption("CreateDate", $page->getDateSelector(date("Y-m-d")));
        //$page->setColOption("patient_name", array("search" => true, "hidden" => false));
        
        $page->setColOption(
            "Status", array("stype"         => "select",
                                           "searchoptions" => array("value"        => ":All;Pending:Pending;Done:Done;UnKnown:UnKnown","defaultValue" => "Pending"))
        );
        
        //$page->setColOption("Status", array("search" => false, "hidden" => false));
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/form/edit/patient_injection_update")."/'+rowId+'?CONTINUE=search/injection_order';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/procedures_order');	
	}	
        
         private function loadICD($fName="icd10") {
        $path='application/forms/' . $fName . '.php';
        require $path;
        $frm = $form;
        $columns = $frm["LIST"];
        $table = $frm["TABLE"];
        $sql = "SELECT ";

        foreach ($columns as $column) {
            $sql.=$column . ',';
        }
        $sql = substr($sql, 0, -1);
        $sql.=" FROM $table ";
        $this->load->model('mpager');
        $this->mpager->setSql($sql);
        $this->mpager->setDivId('icd_search');
        $this->mpager->setSortorder('asc');
        //set colun headings
        $colNames = array();
        foreach ($frm["DISPLAY_LIST"] as $colName) {
            array_push($colNames, $colName);
        }
        $this->mpager->setColNames($colNames);

        //set captions
        $this->mpager->setCaption($frm["CAPTION"]);
        //set row id
        $this->mpager->setRowid($frm["ROW_ID"]);

        //set column models
        foreach ($frm["COLUMN_MODEL"] as $columnName => $model) {
            if (gettype($model) == "array") {
                $this->mpager->setColOption($columnName, $model);
            }
        }

        //set actions
        $action = $frm["ACTION"];
        $this->mpager->gridComplete_JS = "function() {
            var c = null;
            $('.jqgrow').mouseover(function(e) {
                var rowId = $(this).attr('id');
                c = $(this).css('background');
                $(this).css({'background':'yellow','cursor':'pointer'});
            }).mouseout(function(e){
                $(this).css('background',c);
            }).click(function(e){
                var rowId = $(this).attr('id');
                var rowData = {$this->mpager->getGrid()}.jqGrid('getRowData',rowId);
                var st= rowData['snomed_text'];
                var sc= rowData['snomed_code'];
                $('#ICD_Text').val(rowData['icd_text']);
                $('#ICD_Code').val(rowData['icd_code']);
                $('#icdDiv').modal('toggle');
            });
            }";

        //report starts
        if(isset($frm["ORIENT"])){
            $this->mpager->setOrientation_EL($frm["ORIENT"]);
        }
        if(isset($frm["TITLE"])){
            $this->mpager->setTitle_EL($frm["TITLE"]);
        }
        $this->mpager->setWidth('540');
        $this->mpager->setColHeaders_EL(isset($frm["COL_HEADERS"])?$frm["COL_HEADERS"]:$frm["DISPLAY_LIST"]);
        //report endss

        return $this->mpager->render(false);
    }
    
    public function immir_pending_list(){
      $qry = "SELECT 
	  a.ADMID,
	  a.DischargeDate as DischargeDate,
	  p.HIN as HIN, 
	  CONCAT(p.Full_Name_Registered,' ', p.Personal_Used_Name) ,
	  a.BHT,
          a.OutCome,
	  a.Ward
	  
	  from admission a 
	  LEFT JOIN patient p ON p.PID = a.PID 
	  where a.IMMR_UUID = '' 
			";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
        $page->setRowid('ADMID');
        $page->setCaption("IMMIR list");
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("","Discharge Date", "HIN", "Patient","BHT","OutCome","Ward"));
        $page->setRowNum(25);
        $page->setColOption("ADMID", array("search" => false, "hidden" => true));
        $page->setColOption("DischargeDate", $page->getDateSelector(date("Y-m-d")));
        $page->setColOption("HIN", array("search" => true, "hidden" => false));
		//$page->setColOption("patient_name", array("search" => true, "hidden" => false));
		 
        //$page->setColOption("Status", array("stype" => "select", "searchoptions" => array("value" => ":All;Pending:Pending;Done:Done","defaultValue"=>"Pending")));
        
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/form/edit/admission_discharge")."/'+rowId+'?CONTINUE=search/immir_pending_list';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/immir_pending_list');	
	}
        
    public function dicom_pending_list(){
      $qry = "SELECT 
	  d.did,	  
          d.CreateDate,
	  p.HIN as HIN, 
	  CONCAT(p.Full_Name_Registered,' ', p.Personal_Used_Name) ,
	  dc.dct_name,
          d.Status,
	  d.Active
	  
	  from dicom d 
	  LEFT JOIN patient p ON p.PID = d.PID 
          LEFT JOIN dicom_category dc ON dc.dctid = d.dctid 
	  ";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
        $page->setRowid('did');
        $page->setCaption("Dicom list");
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("","CreateDate", "HIN", "Patient","Type","Status","Active"));
        $page->setRowNum(25);
        $page->setColOption("did", array("search" => false, "hidden" => true));
        $page->setColOption("CreateDate", $page->getDateSelector(date("Y-m-d")));
        $page->setColOption("HIN", array("search" => true, "hidden" => false));
		//$page->setColOption("patient_name", array("search" => true, "hidden" => false));
		 
        $page->setColOption("dct_name", array("stype" => "select", "searchoptions" => array("value" => ":All;CT:CT;MRI:MRI;Ultrasound:Ultrasound;X-Ray:X-Ray;Fluoroscopy:Fluoroscopy;Angiography:Angiography;Mammography:Mammography;Breast Tomosynthesis:Breast Tomosynthesis;PET:PET;SPECT:SPECT;Endoscopy:Endoscopy;Microscopy:Microscopy;Whole Slide Imaging:Whole Slide Imaging;OCT:OCT","defaultValue"=>"All")));
        $page->setColOption("Status", array("stype" => "select", "searchoptions" => array("value" => ":All;Pending:Pending;Done:Done;Cancel:Cancel","defaultValue"=>"Pending")));
        
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/form/edit/dicom")."/'+rowId+'?CONTINUE=search/dicom_pending_list';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/dicom_pending_list');	
	}

public function dicom_exposure_pending_list(){
      $qry = "SELECT 
	  d.did,	  
          d.CreateDate,
	  p.HIN as HIN, 
	  CONCAT(p.Full_Name_Registered,' ', p.Personal_Used_Name) ,
	  dc.dct_name,
          d.Status,
	  d.Active
	  
	  from dicom d 
	  LEFT JOIN patient p ON p.PID = d.PID 
          LEFT JOIN dicom_category dc ON dc.dctid = d.dctid 
          where d.Status = 'Exposured'
	  ";
        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
        $page->setRowid('did');
        $page->setCaption("Dicom list");
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("","CreateDate", "HIN", "Patient","Type","Status","Active"));
        $page->setRowNum(25);
        $page->setColOption("did", array("search" => false, "hidden" => true));
        $page->setColOption("CreateDate", $page->getDateSelector(date("Y-m-d")));
        $page->setColOption("HIN", array("search" => true, "hidden" => false));
		//$page->setColOption("patient_name", array("search" => true, "hidden" => false));
		 
        $page->setColOption("dct_name", array("stype" => "select", "searchoptions" => array("value" => ":All;CT:CT;MRI:MRI;Ultrasound:Ultrasound;X-Ray:X-Ray;Fluoroscopy:Fluoroscopy;Angiography:Angiography;Mammography:Mammography;Breast Tomosynthesis:Breast Tomosynthesis;PET:PET;SPECT:SPECT;Endoscopy:Endoscopy;Microscopy:Microscopy;Whole Slide Imaging:Whole Slide Imaging;OCT:OCT","defaultValue"=>"All")));
        $page->setColOption("Status", array("stype" => "select", "searchoptions" => array("value" => ":All;Pending:Pending;Exposured:Exposured;Done:Done;Cancel:Cancel","defaultValue"=>"Exposured")));
        
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/form/edit/dicom")."/'+rowId+'?CONTINUE=search/dicom_pending_list';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('search/dicom_exposure_pending_list');	
	}	

} 


//////////////////////////////////////////

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
