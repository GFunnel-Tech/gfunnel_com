<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */

class MsFansonlyAlertsResponse extends BxBaseModTextAlertsResponse
{
    
    public function __construct()
    {
        $this->MODULE = 'ms_fansonly';
        parent::__construct();
    }

	public function addlog($line){ 
        return;        //do nothing, remove this line for debugging only
		$ddl=date("dmy");
		$line = json_encode($line);
        $filename = BX_DIRECTORY_PATH_LOGS ."FansonlyModule-EnableModules.php";

        $fi = @fopen( $filename,"a+");
        if($fi){
           $array = explode("\n", fread($fi, filesize($filename)));
            if(!in_array($line, $array))
            {
        		fwrite($fi,$line. "\n");
            }

	    	fclose($fi);   	        

        }
    
	}
    
    public function isTypeProfileOrGroupModule($sModule){
        
        $oModule = BxDolModule::getInstance($sModule);
        
        if(!empty($oModule)){
                
                return $this->isProfileOrGroupModule($oModule);

        }
        
        return false;

    }


    public function isTimelineModule($oModule){
        if($oModule->_aModule["name"] == "bx_timeline"){
            return true;
        }
        return false;
    }
    public function isProfileOrGroupModule($oModule){
        
           if((method_exists($oModule, 'serviceActAsProfile') && $oModule->serviceActAsProfile() ) || (
               method_exists($oModule, 'serviceIsGroupProfile') && $oModule->serviceIsGroupProfile() )
            ){
                
                return true;
            }
            
            return false;
    }
    public function getUrlFromTimelineEvent($oAlert){
            $url = '';
                    if(!empty($oAlert->aExtras["event"]["content"]["url"])){
                        $url = $oAlert->aExtras["event"]["content"]["url"];
                    }else{
                        //lets build the url link by getting it from the CNF and Id
                        //$oAlert->aExtras["event"]["content"]["url"]
                        //print json_encode($oAlert->aExtras, true).'<br><br>';
                            $sModule = $oAlert->aExtras["event"]["type"];
  
                            $oModule = BxDolModule::getInstance($sModule);
                            if(!empty($oModule)){
                                    $CNF = &$oModule->_oConfig->CNF;
                                    if(!empty($CNF["URI_VIEW_ENTRY"])){
                                        $iContentId = $oAlert->aExtras["event"]["object_id"];
                                        $url = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $iContentId);
                                        return $url;
                                    }
                            }
                    }
                    return $url;
    }
    public function isAlbumMedia($oModule, $aParams){
//        return true;
        if($oModule->_aModule["name"] =="bx_albums" && !empty($aParams) && $aParams[0] == "photoswipe.html"){
            return true;
        }
        return false;
    }
    public function response($oAlert)
    {
            //            return;
      
            //print $oAlert->sUnit .' : '.$oAlert->sAction.' <br>';
            //print json_encode($oAlert->aExtras, true).'<br><br>';
            if(!$this->_oModule){ 
               //module was uninstalled but for some reason the  sys_alerts_handlers and sys_alerts wasn't cleared from the database
               //This fixes error 500               
               return;
            }
            
            if($oAlert->sUnit == "bx_timeline" && $oAlert->sAction=="get_post"){  
               
               $sType = $oAlert->aExtras["event"]["type"];

               if(!$this->isTypeProfileOrGroupModule($sType)){

                     $isAllowedView = $this->_oModule->isAllowedTimelineItemView($oAlert->aExtras["event"]);
                     if($isAllowedView){
                            //print "is allowed<br>";
                            return;
                     }

                    if($sType == "comments"){
                        return;
                    }

                    $oAlert->aExtras["event"]["title"] = "a photo";
                    $oAlert->aExtras["event"]["description"] = "Hannah added a photo";
                    
                    $url = $this->getUrlFromTimelineEvent($oAlert);

                    $image = $this->_oModule->_oTemplate->getPreviewImage();


                    $oAlert->aExtras["tmpl_vars"]["content"] = 
                '<a href="'.$url.'"><div class="bx-tl-item-text bx-def-margin-lefttopright">
                <div class="bx-tl-content"><p><div class="ms-fansonly-timeline-event" style="background-image:url('.$image.')">&nbsp;</div></p></div></div></a>';

            }
                    
            }else if($oAlert->sUnit == "module_template_method_call" && $oAlert->sAction=="entryRating"){
                        //$oAlert->aExtras["override_result"] = '';
            }else if($oAlert->sUnit == "module_template_method_call" && $oAlert->sAction=="entryAllActions"){
                        //$oAlert->aExtras["override_result"] = '';
            }else if($oAlert->sUnit == "module_template_method_call" && $oAlert->sAction=="getEntryPreview"){
                   $oModule = $oAlert->aExtras["module"];
                if(!$this->isProfileOrGroupModule($oModule) && !$this->isTimelineModule($oModule)){        

                         $aParams = $oAlert->aExtras["args"][0];

                         $iContentId = $aParams["id"];
                         $isAllowedView = $this->_oModule->isAllowedView($oModule, $iContentId, $aParams); //, $author);
                         if($isAllowedView){
                                return;
                         }

                        $oAlert->aExtras["override_result"] = $this->_oModule->_oTemplate->getEntryPreviewForum($aParams, $oModule);


                }                        

            }else if($oAlert->sUnit == "module_template_method_call" && $oAlert->sAction=="getTemplateFunctions"){


            }else if($oAlert->sUnit == "module_template_method_call" && $oAlert->sAction=="entryMediaView"){

                $oModule = $oAlert->aExtras["module"];

                if(!empty($oModule->_oConfig->CNF["URI_VIEW_MEDIA"])){
                       //$oAlert->aExtras["override_result"] = "Photo inside an album for fans only";
                        //Fix
                        //TODO, CHECK AND REPLACE FOR THE FANSONLY MESSAGE
                       //$mediaId = $oAlert->aExtras["args"][0];

                }
            }else if($oAlert->sUnit == "module_template_method_call" && $oAlert->sAction=="parseHtmlByName"){
                
                //      print '<br>parseHtmlByName:<br>'.json_encode($oAlert->aExtras, true).'<br><br>';        
                //                print json_encode([$_GET, $_POST], true).'<br>';
                $this->saveTimelineEdit();
                
                $oModule = $oAlert->aExtras["module"];

                if(!$this->isProfileOrGroupModule($oModule) && !$this->isTimelineModule($oModule)){        


                    if($this->isAlbumMedia($oModule, $oAlert->aExtras["args"]) )
                    {
                        
                    }else if(!empty($oAlert->aExtras["args"]) && !empty($oAlert->aExtras["args"][1]) && !empty($oAlert->aExtras["args"][1]["page_id"]) )
                    {
                        $CNF = &$oModule->_oConfig->CNF;

                        
                         $iContentId = (int) bx_get("id");
                         $aParams = $oModule->_oDb->getContentInfoById( $iContentId );
                                                                   
                         $isAllowedView = $this->_oModule->isAllowedView($oModule, $iContentId, $aParams); //, $author);
                         if($isAllowedView){
                                return;
                         }


                         if($aParams){
                             $oAlert->aExtras["override_result"] = $this->_oModule->_oTemplate->entryTextOverride($aParams, $oModule);
                             $oAlert->aExtras["override_result"] .= $this->_oModule->_oTemplate->getAuthorBlock($aParams["author"], $oModule);
                         }


                        }
                }

            }else if($oAlert->sUnit == "module_template_method_call" && $oAlert->sAction=="entryText"){

                   $oModule = $oAlert->aExtras["module"];
                   if(!$this->isProfileOrGroupModule($oModule)){

                         $aParams = $oAlert->aExtras["args"][0];

                         $iContentId = (int) bx_get("id");

                         $isAllowedView = $this->_oModule->isAllowedView($oModule, $iContentId, $aParams); //, $author);
                         if($isAllowedView){
                                return;
                         }

                       $oAlert->aExtras["override_result"] = $this->_oModule->_oTemplate->entryTextOverride($aParams, $oModule);
                    }


            }else if($oAlert->sUnit == "module_template_method_call" && $oAlert->sAction=="unit"){

                   $oModule = $oAlert->aExtras["module"];


                   $sTempl = $oAlert->aExtras["args"][2];
                   $aParams = $oAlert->aExtras["args"][0];
                   $aParams["summary"] = "<p>CONTENT FOR FANS ONLY</p>";
    
                   //TODO, get the profile icon
                   // print json_encode([$sTempl, $aParams], true).'<br><br>';

 
                   //TODO serviceIsUserProfile -- check if this method exists and if is group profile, then do nothing
                   //We don't need this, users can make groups for fans only
                   //Groups that are for fans only, cannot be watched and all content inside is marked for fans only automatically
                   //This must work with all group based modules
                   
                   if(!$this->isProfileOrGroupModule($oModule)){
                       //$oAlert->aExtras["override_result"] = $this->_oModule->_oTemplate->parseHtmlByName($sTempl, $aParams);
                        if($oModule->_aModule["name"]=="bx_albums" && empty($aParams["author"])){


                             $iMediaId = $aParams["id"];
                             $iContentId = $aParams["content_id"];                            
                             $aContentInfo = $oModule->_oDb->getContentInfoById( $iContentId );

                             
                             $isAllowedView = $this->_oModule->isAllowedView($oModule, $iContentId, $aContentInfo);
                             if($isAllowedView){
                                    return;
                             }


                             //iContentId is the album id, lets check if this is for fans only, then override

                            //individual photo file, do nothing
                            $oAlert->aExtras["override_result"] = $this->_oModule->_oTemplate->getUnitMedia($aParams, $aContentInfo, $oModule);

                         }else{

                                 $iContentId = $aParams["id"];
                                 $isAllowedView = $this->_oModule->isAllowedView($oModule, $iContentId, $aParams); //, $author);
                                 if($isAllowedView){
                                        return;
                                 }

                             $oAlert->aExtras["override_result"] = $this->_oModule->_oTemplate->getUnit($aParams, $aParams, $oModule);
                        }

                   }
                   
            }else if ('system' == $oAlert->sUnit && 'check_allowed_view' == $oAlert->sAction){
                //print json_encode($oAlert, true).'<br><br>';
                
            }else if ('system' == $oAlert->sUnit && 'save_setting' == $oAlert->sAction){
                    //print json_encode($oAlert, true).'<br>';
                    
                    if( $oAlert->aExtras["option"] == "ms_fansonly_enable_on" ){
                        
                        $all = explode(",",$oAlert->aExtras["value"]);
                        $this->_oModule->_oDb->resetSysObjectsHadlers($all);
                        
                        foreach($all as $val){
                            $this->_oModule->_oDb->setSysObjectsHandlers( $val );
                        }
                        
                    }

            } else if($oAlert->sUnit=="system" && $oAlert->sAction=="form_output"){

                //print json_encode($oAlert, true).'<br>';
                $this->addInput($oAlert->sUnit, $oAlert->sAction, $oAlert->iObject, $oAlert->iSender, $oAlert->aExtras);
            }else if($oAlert->sAction == "added"){
            }else if($oAlert->sAction == "edited"){
            }else if($oAlert->sAction == "deleted"){
            }else if($oAlert->sAction == "post_common"){ //timeline
            }else if($oAlert->sAction == "post_common_edited"){ //timeline
            }else if($oAlert->sAction == "delete"){ //timeline
                
                

            }
            
            if(
                   $oAlert->sAction == "added"
                || $oAlert->sAction == "edited"
                || $oAlert->sAction == "deleted"
                || $oAlert->sAction == "post_common" //timeline
                || $oAlert->sAction == "post_common_edited" //timeline edited
                || $oAlert->sAction == "delete" //timeline
                ){
                $this->addlog([$oAlert->sUnit, $oAlert->sAction, $oAlert->iObject, $oAlert->iSender, $oAlert->aExtras]);

                //TODO check if is owner OR ADMIN

                $bFansOnly = bx_get('for_fans_only');
                if($bFansOnly){
                    $this->_oModule->_oDb->markForFansOnly($oAlert->sUnit, $oAlert->iObject);
                    $this->addlog([ $oAlert->iObject .' on module '.$oAlert->sUnit.' was marked for fans only by '. $oAlert->iSender]);
                }else{
                    $this->_oModule->_oDb->unMarkForFansOnly($oAlert->sUnit, $oAlert->iObject);
                    $this->addlog([ $oAlert->iObject .' on module '.$oAlert->sUnit.' was UNMARKED for fans only by '. $oAlert->iSender]);
                }
                
            }


            //print $oAlert->sUnit .' : '.$oAlert->sAction.' <br>';

    }

    //Fix for timeline edit
    public function saveTimelineEdit(){
        if(!empty($_GET['r'])){
            $aRequest = explode('/', $_GET['r']);                    
            if(is_array($aRequest) && count($aRequest) == 3 && $aRequest[0] == 'timeline' && $aRequest[1] == 'edit'){

                $iObject = bx_process_input( $aRequest[2], BX_DATA_INT);

                if( $this->isEnabledOnThisForm('bx_timeline_post')){

                    $sender = bx_get_logged_profile_id();
                    //                print json_encode([$aRequest, $_POST["for_fans_only"]], true);
                    //TODO check if is owner OR ADMIN
                    $bFansOnly = bx_get('for_fans_only');
                    if($bFansOnly){
                        $this->_oModule->_oDb->markForFansOnly('bx_timeline', $iObject);
                        $this->addlog([ $iObject .' on module bx_timeline was marked for fans only by '.$sender]);
                    }else{
                        $this->_oModule->_oDb->unMarkForFansOnly('bx_timeline', $iObject);
                        $this->addlog([ $iObject .' on module bx_timeline was UNMARKED for fans only by '.$sender]);
                    }

                }
                                
            }
            //[["timeline","edit","14"],"on"][["timeline","edit","14"],"on"][["timeline","edit","14"],"on"]{"id":"14"}

        }
    }


                


    public function isEnabledOnThisForm( $id ){

            $aEnabledOn = getParam("ms_fansonly_enable_on");            
            $all = explode(",",$aEnabledOn);
            if(in_array("bx_photos_upload", $all)){ //This is the photo edit form
                $all[] = "bx_photos"; //We add the photo upload form too
            }
//            print json_encode($all, true).'<br>';
            foreach($all as $val){
                if($val == $id){
                    return true;
                }
            }
            return false;
    }

    public function addInput ($sUnit, $sAction, $iObjectId, $iSender, $aExtras) {

//            print json_encode([$sUnit, $sAction, $iObjectId, $iSender], true).'<br><br>';
//            print json_encode([$aExtras], true).'<br><br>';

//              print var_dump($aExtras).'<br><br><br><br>';
            $id = 0;
            if(isset($aExtras["object"])){
                
                $object = $aExtras["object"];

                //fix for timeline status //TODO, check if we don't have errors with other modules
                if(!empty($object->aFormAttrs['name'])){
                    $id = $object->aFormAttrs['name']; 
                }else{
                    $id = $object->aFormAttrs['id'];
                }
            }
        
            //            print ($id ).'<br>';   
            if(!empty($id)){
                if( !$this->isEnabledOnThisForm( $id ) ){
                    return false;
                }     
            }else{
                return false;
            }


            //          print json_encode([$sUnit, $sAction, $iObjectId, $iSender, $aExtras], true).'<br><br>';
            //          exit;
            
            $oEntry = $aExtras["object"];
            
            $checked = 0;
            if($id == 'bx_timeline_post'){
                        $sModule = 'bx_timeline';                
            }else{
                        $sModule = $oEntry->getId();
            }
            //$values = $oEntry->getKeyValuesPair ();
            //if(!empty($values[ $oEntry->aParams['db']['key'] ] )){
                 //$sModule = $oEntry->getName();
            //     $iContentId = $values[ $oEntry->aParams['db']['key'] ];

            //     if($this->isContentForFansOnly($sModule, $iContentId)){
            //        $checked = 1 ;
            //     }
           // }
            $oModule = BxDolModule::getInstance($sModule);
//            print json_encode([(int)$oEntry->aParams['view_mode']], true).'<br>';
            if((int)$oEntry->aParams['view_mode'] == 1){
                //It is not in view mode, so we do not add a default value
            }else{
                $checked = getParam("ms_fansonly_default_value") == 'on' ?  1 : 0 ;             
            }
            
            if(!empty($oModule)){                    
                 $iContentId = (int) bx_get("id");
                 if(!empty($iContentId)){
                     if($this->_oModule->isContentForFansOnly($sModule, $iContentId)){
                        $checked = 1 ;
                     }else{
                        $checked = 0 ;
                     }
                 }
            }

//print json_encode([' module '=>$oModule, 'checked'=>$checked], true).'<br>';

            if($this->isDeleteForm($oEntry)){
                return;
            }
//                         print json_encode(["oEntry", $iObjectId],true).'<br><br><br><br>';

//                         print json_encode(["oEntry", $oEntry],true).'<br><br><br><br>';

//                         print json_encode(["aInputs", $oEntry->aInputs],true).'<br><br><br><br>';
//                         print json_encode(["values", $values],true).'<br><br><br><br>';
 
//                         print json_encode(["aParams", $oEntry->aFormAttrs],true).'<br><br><br><br>';
//                         print json_encode(["aParams", $oEntry->aParams],true).'<br><br><br><br>';
            //             print json_encode(["allClassVars",get_object_vars($oEntry)],true).'<br><br><br><br>';
            //             print json_encode(["allClassMethods",get_class_methods($oEntry)],true).'<br><br><br><br>';



            $inputs = [];
            
//            $inputs["title2"] = [
            $inputs["for_fans_only"] = [
              "id"=> "1",
                    //              "type"=> "text",
                    //              "type"=> "checkbox",
              "type"=> "switcher",
              "name"=> "for_fans_only",
              "caption_system_src"=> "_ms_fansonly_checkbox_text_forfansonly",
              "caption_src"=> "_ms_fansonly_checkbox_text_forfansonly",
              "caption"=> _t("_ms_fansonly_checkbox_text_forfansonly"), //"This content is for fans only",
              "info_src"=> "",
              "info"=> "",
                //              "required"=> true,
              "unique"=> false,
              "collapsed"=> false,
              "privacy"=> false,
              "rateable"=> "",
              "html"=> "0",
              "attrs"=> [
                //            "onblur"=> "javascript:oMsHiremeForm.checkName('title2', 'name');"
              ],
              "tr_attrs"=> false,
              "attrs_wrapper"=> false,
              "visible_for_levels"=> "2147483647",
              "checked"=> $checked,
                //              "checked"=> false,
                /*              "checker"=> [
                                "func"=> "Avail",
                                "error"=> "Title is essential. Please, fill in this field"
                              ],
                */
              "db"=> [
                "pass"=> "Xss"
              ]
            ];

            if( !empty($oEntry->aInputs['do_submit']) ){
                $field = 'do_submit';
            }else{
                $field = 'do_publish';
            }		

            $oEntry->aInputs = $this->insertArrayBeforeDoSubmitOrDoPublish($oEntry->aInputs, $inputs );
            
        
            if( !empty($oEntry->aInputs['file']) ){
//                print 'input file exists<br>';
                $place = 3;
            }else{
//                print 'input file does not exists<br>';
                $place = 2;
            }
//print json_encode($oEntry->aInputs, true);

            $oEntry->aInputs = $this->insertArrayBeforeDoSubmitOrDoPublish($oEntry->aInputs, $inputs, $place );
            

    }
        
    public function insertArrayBeforeDoSubmitOrDoPublish( $array, $insert, $place = 2 ) {
        
        $position = count($array) - $place;
        
        return array_slice($array, 0, $position, TRUE) + $insert + array_slice($array, $position, NULL, TRUE);
        
    }
    
    public function isDeleteForm($oEntry){
        if(!empty($oEntry->aInputs["delete_confirm"])){
            //return true;
        }

        if(strpos($oEntry->aParams["display"], 'entry_delete') !== false && (int)$oEntry->aParams['view_mode'] == 0){
            return true;
        }

        return false;
    }
    
}

/** @} */
