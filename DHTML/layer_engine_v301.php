<?php
/* --------------------------- "Layer Engine" (Tabs) Version 3.01 ----------------------------------
2008-01-22
----------
switched over to use of className vs style.  declare layer_engine_v301.css before calling this file in the head coding.
TODO: be able to handle different tab schemes

2004-10-07
----------
added the recognition of tabs named "help".  This uses strictly the relatebase system, which is going to be pretty much public anyway.  

2004-06-09
----------
added the use of $cgrp which can be used to change the default tab on the fly, this updated from version 2.00 to 2.01.  This also allows all of the tabs to relax their code 

from this:
<div id="getemail_a_fileSrc" style="< ?php echo cg('ab','getemail','fileSrc',1);? >">
<div id="getemail_i_fileSrc" style="border-bottom:1px solid white;<? php echo cg('ib','getemail','fileSrc',1);?>" onClick="hl_1('getemail',getemail,'fileSrc');"> 
.. also also the main layer itself

so that THE 4TH "1" PARAMETER IS NO LONGER NEEDED, because it is read globally.

LAYERS ENGINE
2003-12-11
----------
OK, I have a pretty easy way to create layers and sublayers.  There are two ways to control the layers:
1. Static displays such as links or images with an onclick event.  These show no matter what the layers do, and are usually out of the layers
2. Active and inactive buttons; in this case, all buttons are set to inactive, and the clicked button is set to active.  The active layer matching the button is also displayed.  The active and inactive buttons can be anything inside teh div tag

STEPS TO CREATE CONTROL GROUPS:
1. Write out the array below for each instance, including the layers
2. DIV Layers to be shown:
	a. id should be = "controlgroupN_layernameN"
	b. style should include <php echo cg('l','controlgroupN','layernameN',1); >
	   (1 flag only on the default layer)

3. Buttons should be one of the following choices:
	a. STATIC LINKS:
		1. no need for a div tag
		2. onclick="hideLayers_controlgroupN('layernameN');"
		3. if a radio button, something like this for checked attribute:
			< ?php echo !isset($_POST[thisControl])|| $thisControl==1?checked:''? >
			(for the default button)
			< ?php echo $thisControl==2?checked:''? >
			
	b. ACTIVE AND INACTIVE BUTTONS:
		1. active button in a tag named controlgroupN_a_layernameN
		2. active button style should include:
			<php echo cg('ab','controlgroupN','layernameN',1); >
			(1 flag only on default layer)
		3. same thing for inactive button, but use _i_ for the div, 'ib' for the function
4. VERY IMPORTANT
	a. create a hidden form field for each control group as follows
		<input id="controlgroupN_status" type="hidden" name="nullcontrolgroupN_status" value="< ?php echo isset($_POST[nullcontrolgroupN_status])?$_POST[nullcontrolgroupN_status]:'beta';?>">
		1. note I named the field null.., this keeps it from interfering with other reserved names on the form.
		2. under value, 'beta' is the default layer to show if we don't have a form post
*****/
//cgrp (controlgroup) can be found in cookie, session, etc.
if(is_array($cgrp) && count($cgrp)){
	foreach($cg as $n=>$v){
		if(array_key_exists($cg[$n]['CGPrefix'],$cgrp)){
			$cg[$n]['defaultLayer']=$cgrp[$cg[$n]['CGPrefix']];
		}
	}
}else if($x=$cgrp){
	//can only set first layer with single string in cgrp
	$cg[1]['defaultLayer']=$x;
	unset($cgrp);
}
foreach($cg as $n=>$v){
	//get all other cg default layers that haven't been set so they can be read easily
	if(!$cgrp[$v['CGPrefix']]){
		$cgrp[$v['CGPrefix']]=$cg[$n]['defaultLayer'];
	}
}
//called initially to set display of layers
function cg($fn, $group, $layer, $default=0){
	global $cgrp;
	if($cgrp[$group]==$layer || $default==1){
		$def=1;
	}
	if($fn=='ab' || $fn=='l'){
		$a='block';$b='none';
	}elseif($fn=='ib'){
		$a='none';$b='block';
	}
	//if we have a control group, all other layers are hidden
	if(isset($cgrp[$group]) && $cgrp[$group]!==$layer){
		return "display:$b;";
	}
	if(
		$_POST['null'.$group.'_status']==$layer || 
		(!isset($_POST['null'.$group.'_status']) && $def)
		){
		return "display:$a;";
	}else{
		return "display:$b;";
	}
}
?>

<script language="javascript" type="text/javascript">
<?php
if($activeHelpSystem){
	echo "var activeHelpSystem='$activeHelpSystem';\n\n";
	echo "//initial condition\n";
	echo "var helpSet=false;\n";
}else{
	echo "var activeHelpSystem=false;\n";
}
//declare the array for each interface
foreach ($cg as $n=>$v){
	echo "\n";
	if($v['CGLayers']){
		echo "var {$v[CGPrefix]}= new Array();\n";
		$i=-1;
		foreach($v['CGLayers'] as $w){
			$i++;
			echo $v['CGPrefix'].'['.$i.'] = "'.$w.'";'."\n";
		}
	}
}
?>
var layerEngineVersion='3.01';
var loadHelpPath='/client/help/help_01_exe.php';
function hl_1(mygroup,mygroupArray,x){
	//--- version 3.0, using className (tHide, tShow) vs. declaring style
	var pass=0;
	for(y in mygroupArray){
		//inactivate all buttons
		try{
			g(mygroup+"_a_"+mygroupArray[y]).className='ab tHide';
			g(mygroup+"_i_"+mygroupArray[y]).className='ib tShow';
		}
		catch(e){ }
	}
	//show targeted button
	try{
		g(mygroup+'_a_'+x).className='ab tShow';
		g(mygroup+'_i_'+x).className='ib tHide';
		//set cookie for the tab by default
		if(x!=='help')sCookie('tabs'+mygroup,x);
	}
	catch(e){ }
	for(y in mygroupArray){
		//hide layers
		g(mygroup+"_"+mygroupArray[y]).className='aArea tHide';
	}
	//show targeted layer
	g(mygroup+'_'+x).className='aArea tShow';
	//declare hidden field for initial value
	g(mygroup+'_status').value=x;
	//load help module if applicable
	if(activeHelpSystem){
		if(x=='help' && helpSet==false){
			le_helpmodule(mygroup);
		}
	}
}
function le_helpmodule(mygroup){
	try{
		g('pageHelpStatus').innerHTML='Loading help resources..';
		w0.location=loadHelpPath+'?mode=loadhelp&ID='+mygroup+'&orientation=tab';
	}
	catch(e){ if(e.description)alert(e.description+': Help iframe w0 not installed on this page'); }
}
</script>
