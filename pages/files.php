<?php

/*
 * FileSender www.filesender.org
 * 
 * Copyright (c) 2009-2011, AARNet, HEAnet, SURFnet, UNINETT
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 
 * *	Redistributions of source code must retain the above copyright
 * 	notice, this list of conditions and the following disclaimer.
 * *	Redistributions in binary form must reproduce the above copyright
 * 	notice, this list of conditions and the following disclaimer in the
 * 	documentation and/or other materials provided with the distribution.
 * *	Neither the name of AARNet, HEAnet, SURFnet and UNINETT nor the
 * 	names of its contributors may be used to endorse or promote products
 * 	derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/* ---------------------------------
 * MyFiles Page
 * ---------------------------------
 * 
 */

if(isset($_REQUEST["a"]) && isset($_REQUEST["id"])) 
{
$myfileData = $functions->getVoucherData($_REQUEST['id']);
$myfileData = $myfileData[0];
if($_REQUEST["a"] == "del" )
{
if($functions->deleteFile($myfileData["fileid"]))
{
echo "<div id='message'>File Deleted</div>";
}
}

if($_REQUEST["a"] == "resend")
{
$sendmail->sendEmail($myfileData ,$config['fileuploadedemailbody']);
echo "<div id='message'>".lang("_FILE_RESENT")."</div>";
}

if($_REQUEST["a"] == "add")
{
$myfileData["filemessage"] = $_POST["filemessage"];
$myfileData["filesubject"] = $_POST["filesubject"];
$myfileData["fileexpirydate"] = date($config["postgresdateformat"],strtotime($_POST["fileexpirydate"]));

// loop emails in fileto
$emailto = str_replace(",",";",$_POST["fileto"]);
$emailArray = preg_split("/;/", $emailto);
foreach ($emailArray as $Email) { 
$myfileData["fileto"] = $Email;
$myfileData["filevoucheruid"] = getGUID();
$functions->inserFileHTML5($myfileData);
}
// display the add box
echo "<div id='message'>".lang("_EMAIL_SENT").".</div>";
}
}
$filedata = $functions->getUserFiles();
//$filedata = $filedata[0];
//echo $filedata;
$json_o=json_decode($filedata,true);

?>
<script type="text/javascript">
	
	var selectedFile = ""; // file uid selected when deleteting
	// set default maximum date for date datepicker
	var maximumDate= '<?php echo $config['default_daysvalid']?>';

	$(function() {
		// initialise datepicker
		$("#datepicker" ).datepicker({ minDate: 1, maxDate: "+"+maximumDate+"D",altField: "#fileexpirydate", altFormat: "d-m-yy" });
		$("#datepicker" ).datepicker( "option", "dateFormat", "dd-mm-yy" );
		$("#datepicker").datepicker('setDate', new Date()+maximumDate);
		
		// stripe every second row in the tables
		$("#myfiles tr:odd").not(":first").addClass("altcolor");
		
		// delete modal dialog box
		$("#dialog-delete").dialog({ autoOpen: false, height: 140, modal: true,
			buttons: {
				<?php echo lang("_CANCEL") ?>: function() {
				$( this ).dialog( "close" );
				},
				<?php echo lang("_DELETE") ?>: function() { 
				deletefile();
				$( this ).dialog( "close" );
				}
			}
		});
		
		// add new recipient modal dialog box
		$("#dialog-addrecipient").dialog({ autoOpen: false, height: 360,width:650, modal: true,
			buttons: {
				<?php echo lang("_CANCEL") ?>: function() {
					$( this ).dialog( "close" );
				},
				<?php echo lang("_SEND") ?>: function() { 
				// calidate form before sending
				if(validateForm())
				{
				// submit form to add new recipient/s
				$("#form1").submit();
				}
				}
			}
		});
		
	});
	
	// validate form beofre sending
	function validateForm()
	{
		// remove previouse vaildation messages
		$("#fileto_msg").hide();
		$("#expiry_msg").hide();
		var validate = true;
		if(!validate_fileto() ){validate = false;};		// validate emails
		if(!validate_expiry() ){validate = false;};		// check date
		return validate;
	}
	
	function deletefile()
		{
		// reload page to delete selected file
		// should add a tick box to delete multiple selected files	
		window.location.href="index.php?s=files&a=del&id=" + selectedFile;
		}
	
	function confirmdelete(vid)
		{
			// confirm deletion of selected file
			selectedFile = vid;
			$("#dialog-delete" ).dialog( "open" );
		}
		
	function openAddRecipient(vid,filename,filesize,from)
	{
		// populate form and open add-recipient modal form
		$("#form1").attr("action", "index.php?s=files&a=add&id=" + vid );
		$("#filevoucheruid").val(vid);
		$("#filefrom").html(from);
		$("#filename").html(filename);
		$("#filesize").html(readablizebytes(filesize));
		$("#dialog-addrecipient" ).dialog( "open" );
		
	}
	
	// display msg 
	function fileMsg(msg)
	{
		$("#file_msg").html(msg);
		$("#file_msg").show();
	}
	
	// display bytes in readable format
	function readablizebytes(bytes)
	  {
		if (bytes > 1024*1024*1024)
			bytesdisplay = (Math.round(bytes * 100/(1024*1024*1024))/100).toString() + " GB";
		else if (bytes > 1024*1024)
			bytesdisplay = (Math.round(bytes * 100/(1024*1024))/100).toString() + " MB";
		else if (bytes > 1024)
			bytesdisplay = (Math.round(bytes * 100/1024)/100).toString() + " KB";
		else
			bytesdisplay = (Math.round(bytes * 100)/100).toString() + " Bytes";
		return bytesdisplay;
	}	
	
	// Validate FILETO
function validate_fileto()
{
	$("#fileto_msg").hide();
	// remove white spaces 
	var email = $("#fileto").val();
	email = email.split(" ").join("");
	$("#fileto").val(email);
	email = email.split(/,|;/);
	for (var i = 0; i < email.length; i++) {
		if (!echeck(email[i], 1, 0)) {
		$("#fileto_msg").show();
		return false;
		}
		}
	return true;	
}

// Validate EXPIRY
function validate_expiry()
{
var validformat=/^\d{2}\-\d{2}\-\d{4}$/ //Basic check for format validity
	
	var returnval=false
	if (!validformat.test($("#datepicker").val())) 
	{
	$("#expiry_msg").show();
	return false;
	}
	var monthfield=$("#datepicker").val().split("-")[1]
	var dayfield=$("#datepicker").val().split("-")[0]
	var yearfield=$("#datepicker").val().split("-")[2]
	var dayobj = new Date(yearfield, monthfield-1, dayfield)
	if ((dayobj.getMonth()+1!=monthfield)||(dayobj.getDate()!=dayfield)||(dayobj.getFullYear()!=yearfield))
	{
	$("#expiry_msg").show();
	return false;
	}
	if($("#datepicker").datepicker("getDate") == null)
	{
		$("#expiry_msg").show();
		return false;
	}
	$("#expiry_msg").hide();
	return true;
}

//  validate single email	
function echeck(str) {

		var at="@"
		var dot="."
		var lat=str.indexOf(at)
		var lstr=str.length
		var ldot=str.indexOf(dot)
		if (str.indexOf(at)==-1){
		  // alert("Invalid E-mail")
		   return false
		}

		if (str.indexOf(at)==-1 || str.indexOf(at)==0 || str.indexOf(at)==lstr){
		   //alert("Invalid E-mail")
		   return false
		}

		if (str.indexOf(dot)==-1 || str.indexOf(dot)==0 || str.indexOf(dot)==lstr){
		   // alert("Invalid E-mail")
		    return false
		}

		 if (str.indexOf(at,(lat+1))!=-1){
		    //alert("Invalid E-mail")
		    return false
		 }

		 if (str.substring(lat-1,lat)==dot || str.substring(lat+1,lat+2)==dot){
		    //alert("Invalid E-mail")
		    return false
		 }

		 if (str.indexOf(dot,(lat+2))==-1){
		    //alert("Invalid E-mail")
		    return false
		 }
		
		 if (str.indexOf(" ")!=-1){
		    //alert("Invalid E-mail")
		    return false
		 }

	 return true					
}
</script>

<div id="box"> <?php echo '<div id="pageheading">'.lang("_MY_FILES").'</div>'; ?>
  <div id="tablediv">
    <table id="myfiles" width="750" border="0" cellspacing="1" style="table-layout:fixed;">
      <tr class="headerrow">
        <td width="18">&nbsp;</td>
        <td width="18">&nbsp;</td>
        <td><strong><?php echo lang("_TO"); ?></strong></td>
        <td><strong><?php echo lang("_FROM"); ?></strong></td>
        <td><strong><?php echo lang("_FILE_NAME"); ?></strong></td>
        <td width="60"><strong><?php echo lang("_SIZE"); ?></strong></td>
        <td><strong><?php echo lang("_SUBJECT") ; ?></strong></td>
        <td width="16"><strong></strong></td>
        <td width="80"><strong><?php echo lang("_CREATED"); ?></strong></td>
        <td width="80"><strong><?php echo lang("_EXPIRY"); ?></strong></td>
        <td width="18">&nbsp;</td>
      </tr>
      <?php 
if(sizeof($json_o) > 0)
{
foreach($json_o as $item) {
   echo '<tr><td valign="top"> <a href="index.php?s=files&a=resend&id=' .$item['filevoucheruid'] . '"><img src="images/email_go.png" title="Re-send Email"></a></td><td valign="top"><img src="images/email_add.png" title="Add New Recipient" onclick="openAddRecipient('."'".$item['filevoucheruid']."','".$item['fileoriginalname'] ."','".$item['filesize'] ."','".$item['filefrom']."'" .');"  style="cursor:pointer;"></td>';
   if($item['fileto'] == $attributes["email"])
   {
   echo "<td class='HardBreak' valign='top'>".lang("_ME")."</td>";
   } else {
   echo "<td class='HardBreak'>" .$item['fileto'] . "</td>";
   }
    if($item['filefrom'] == $attributes["email"])
   {
   echo "<td class='HardBreak'>".lang("_ME")."</td>";
   } else {
   echo "<td class='HardBreak'>" .$item['filefrom'] . "</td>";
   }
   echo "<td class='HardBreak'><a href='download.php?vid=". $item["filevoucheruid"]."' target='_blank'>" .$item['fileoriginalname']. "</a></td><td>" .formatBytes($item['filesize']). "</td><td>".$item['filesubject']. "</td><td>";
   if($item['filemessage'] != "")
   {
   echo "<img src='images/page_white_text_width.png' border='0' title='".$item['filemessage']. "'>";
   }
   echo "</td><td>" .date("d-m-Y",strtotime($item['filecreateddate'])) . "</td><td>" .date("d-m-Y",strtotime($item['fileexpirydate'])) . "</td><td  valign='top'  width='22'><div style='cursor:pointer;'><img onclick='confirmdelete(".'"' .$item['filevoucheruid'] . '")'. "' src='images/shape_square_delete.png' title='Delete' ></div></td></tr>"; //etc
   }
} else {
	echo "<tr><td colspan='7'>There are currently no files available</td></tr>";
}
?>
    </table>
  </div>
</div>
<div id="dialog-delete" title="Delete File">
<p><?php echo lang("_CONFIRM_DELETE_FILE");?></p>
</div>
<div id="dialog-addrecipient" title="Add Recipient">
  <form id="form1" name="form1" enctype="multipart/form-data" method="POST" action="">
    <table  width="600" border="0">
      <tr>
        <td width="100" class="formfieldheading mandatory"><?php echo  lang("_TO"); ?>:</td>
        <td width="400" valign="middle"><input name="fileto" title="<?php echo  lang("_EMAIL_SEPARATOR_MSG"); ?>" type="text" id="fileto" size="60" onchange="validate_fileto()" />
          <div id="fileto_msg" style="display: none" class="validation_msg"><?php echo lang("_INVALID_MISSING_EMAIL"); ?></div></td>
      </tr>
      <tr>
        <td class="formfieldheading mandatory"><?php echo lang("_FROM"); ?>:</td>
        <td><div id="filefrom" name="filefrom"></div></td>
      </tr>
      <tr>
        <td class="formfieldheading"><?php echo lang("_SUBJECT"); ?>: (<?php echo lang("_OPTIONAL"); ?>)</td>
        <td><input name="filesubject" type="text" id="filesubject" size="60" /></td>
      </tr>
      <tr>
        <td class="formfieldheading"><?php echo lang("_MESSAGE"); ?>: (<?php echo lang("_OPTIONAL"); ?>)</td>
        <td><textarea name="filemessage" cols="57" rows="4" id="filemessage"></textarea></td>
      </tr>
      <tr>
        <td class="formfieldheading mandatory"><?php echo lang("_EXPIRY_DATE"); ?>:
          <input type="hidden" id="fileexpirydate" name="fileexpirydate" value="<?php echo date("d-m-Y",strtotime("+".$config['default_daysvalid']." day"));?>"/></td>
        <td><input id="datepicker" name="datepicker" onchange="validate_expiry()">
          </input>
          <div id="expiry_msg" class="validation_msg" style="display: none"><?php echo lang("_INVALID_EXPIRY_DATE"); ?></div><div class="">(dd-mm-yyyy)</div></td>
      </tr>
      <tr>
        <td class="formfieldheading mandatory"><?php echo lang("_FILE_TO_BE_RESENT"); ?>:</td>
        <td><div id="filename" name="filename"></div></td>
      </tr>
      <tr>
        <td class="formfieldheading mandatory"><?php echo lang("_SIZE"); ?>:</td>
        <td><div id="filesize" name="filesize"></div></td>
      </tr>
      <tr>
        <td class="formfieldheading mandatory"></td>
        <td><div id="file_msg" class="validation_msg" style="display: none"><?php echo lang("_INVALID_FILE"); ?></div></td>
      </tr>
    </table>
    <input name="filevoucheruid" type="hidden" id="filevoucheruid"/>
  </form>
</div>